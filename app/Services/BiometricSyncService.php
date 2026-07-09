<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class BiometricSyncService
{
    /**
     * Resolve a usable Python executable for the current server.
     */
    public function resolvePythonExecutable(): ?string
    {
        $configured = trim((string) config('biometric.python_path'));

        $candidates = array_values(array_unique(array_filter(array_merge(
            [$configured],
            $this->discoverWindowsPythonPaths(),
            ['python', 'py', 'python3']
        ))));

        foreach ($candidates as $candidate) {
            if ($this->isExecutablePython($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function discoverWindowsPythonPaths(): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return [];
        }

        $paths = [];
        $roots = array_filter([
            getenv('LOCALAPPDATA') ? getenv('LOCALAPPDATA') . '\\Programs\\Python' : null,
            getenv('LOCALAPPDATA') ? getenv('LOCALAPPDATA') . '\\Python' : null,
            'C:\\Python314',
            'C:\\Python313',
            'C:\\Python312',
            'C:\\Program Files\\Python314',
            'C:\\Program Files\\Python313',
            'C:\\Program Files\\Python312',
        ]);

        foreach ($roots as $root) {
            if (!is_dir($root)) {
                continue;
            }

            $pattern = rtrim($root, '\\') . '\\**\\python.exe';
            foreach (glob($pattern, GLOB_BRACE) ?: [] as $pythonExe) {
                $paths[] = $pythonExe;
            }

            $direct = rtrim($root, '\\') . '\\python.exe';
            if (is_file($direct)) {
                $paths[] = $direct;
            }
        }

        return $paths;
    }

    /**
     * @return array{success: bool, output?: string, message?: string}
     */
    public function runAttendanceScript(): array
    {
        if (!$this->canRunProcesses()) {
            return [
                'success' => false,
                'message' => 'PHP cannot run external commands. Enable proc_open (or shell_exec) in php.ini disable_functions.',
            ];
        }

        $pythonScript = trim((string) config('biometric.script_path'));

        if ($pythonScript === '') {
            return [
                'success' => false,
                'message' => 'Biometric script path is not configured. Set BIOMETRIC_SCRIPT_PATH in .env',
            ];
        }

        if (!is_file($pythonScript)) {
            return [
                'success' => false,
                'message' => 'Biometric script not found at: ' . $pythonScript,
            ];
        }

        $python = $this->resolvePythonExecutable();

        if (!$python) {
            $configured = trim((string) config('biometric.python_path'));

            return [
                'success' => false,
                'message' => $configured !== ''
                    ? 'Python was not found at: ' . $configured . '. Install Python or set BIOMETRIC_PYTHON_PATH in .env to a valid python.exe path.'
                    : 'Python was not found on this server. Install Python and set BIOMETRIC_PYTHON_PATH in .env.',
            ];
        }

        $timeout = max(30, (int) config('biometric.timeout', 120));
        $environment = $this->buildWindowsCompatibleEnvironment();
        $process = new Process([$python, $pythonScript], dirname($pythonScript), $environment);
        $process->setTimeout($timeout);
        $process->run();

        $output = trim($process->getOutput());
        $errorOutput = trim($process->getErrorOutput());

        if (!$process->isSuccessful()) {
            $details = $errorOutput !== '' ? $errorOutput : $output;

            if ($this->isWindowsSocketProviderError($details)) {
                $fallback = $this->runAttendanceScriptViaShell($python, $pythonScript, $environment, $timeout);
                if ($fallback['success']) {
                    return $fallback;
                }
            }

            return [
                'success' => false,
                'message' => $this->summarizeProcessError($details),
                'output' => $details,
            ];
        }

        if ($output === '' && $errorOutput !== '') {
            $output = $errorOutput;
        }

        return [
            'success' => true,
            'output' => $output,
        ];
    }

    /**
     * @return array{success: bool, output?: string, message?: string}
     */
    private function runAttendanceScriptViaShell(
        string $python,
        string $pythonScript,
        array $environment,
        int $timeout
    ): array {
        $command = escapeshellarg($python) . ' ' . escapeshellarg($pythonScript);
        $process = Process::fromShellCommandline($command, dirname($pythonScript), $environment);
        $process->setTimeout($timeout);
        $process->run();

        $output = trim($process->getOutput());
        $errorOutput = trim($process->getErrorOutput());

        if (!$process->isSuccessful()) {
            return [
                'success' => false,
                'message' => $this->summarizeProcessError($errorOutput !== '' ? $errorOutput : $output),
                'output' => $errorOutput !== '' ? $errorOutput : $output,
            ];
        }

        return [
            'success' => true,
            'output' => $output !== '' ? $output : $errorOutput,
        ];
    }

    /**
     * Build an environment block that allows Python sockets under Apache/XAMPP on Windows.
     *
     * @return array<string, string>
     */
    private function buildWindowsCompatibleEnvironment(): array
    {
        $env = [];

        foreach ($_ENV as $key => $value) {
            if (is_string($value)) {
                $env[$key] = $value;
            }
        }

        foreach (['SystemRoot', 'WINDIR', 'PATH', 'TEMP', 'TMP', 'USERPROFILE', 'LOCALAPPDATA', 'ProgramFiles', 'ComSpec'] as $key) {
            $value = getenv($key);
            if ($value !== false && $value !== '') {
                $env[$key] = $value;
            }
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            return $env;
        }

        $systemRoot = $env['SystemRoot'] ?? $env['WINDIR'] ?? 'C:\\Windows';
        $system32 = $systemRoot . '\\System32';
        $wbem = $system32 . '\\Wbem';
        $pathParts = array_values(array_filter(explode(';', $env['PATH'] ?? '')));

        foreach ([$system32, $systemRoot, $wbem] as $requiredPath) {
            if (!in_array($requiredPath, $pathParts, true)) {
                array_unshift($pathParts, $requiredPath);
            }
        }

        $env['PATH'] = implode(';', $pathParts);
        $env['SystemRoot'] = $systemRoot;
        $env['ComSpec'] = $env['ComSpec'] ?? $system32 . '\\cmd.exe';

        return $env;
    }

    private function isWindowsSocketProviderError(string $details): bool
    {
        return str_contains($details, 'WinError 10106')
            || str_contains($details, 'service provider could not be loaded');
    }

    private function summarizeProcessError(string $details): string
    {
        $details = trim($details);

        if ($details === '') {
            return 'Biometric script failed. Check Python packages (pyzk) and device connectivity.';
        }

        if ($this->isWindowsSocketProviderError($details)) {
            return 'Biometric device sync cannot open network sockets from the web server (Windows WinError 10106). '
                . 'Run "php artisan attendance:sync" on the server, or use "Rebuild from Logs" if punches are already stored.';
        }

        if (preg_match('/Connection refused|timed out|No route to host/i', $details)) {
            return 'Could not reach the biometric device. Check device IP/power and network connectivity.';
        }

        if (preg_match('/ModuleNotFoundError: No module named \'([^\']+)\'/', $details, $matches)) {
            return 'Missing Python package: ' . $matches[1] . '. Install it with pip on the server.';
        }

        $lines = preg_split('/\R/', $details) ?: [];
        $lastLine = trim((string) end($lines));

        return $lastLine !== ''
            ? 'Biometric script failed: ' . $lastLine
            : 'Biometric script failed.';
    }

    private function canRunProcesses(): bool
    {
        if (!function_exists('proc_open')) {
            return function_exists('shell_exec') && $this->isFunctionEnabled('shell_exec');
        }

        return $this->isFunctionEnabled('proc_open');
    }

    private function isExecutablePython(string $candidate): bool
    {
        $candidate = trim($candidate);

        if ($candidate === '') {
            return false;
        }

        $looksLikePath = str_contains($candidate, '\\') || str_contains($candidate, '/');

        if ($looksLikePath) {
            if (!is_file($candidate)) {
                return false;
            }

            $process = new Process([$candidate, '--version']);
            $process->setTimeout(15);
            $process->run();

            return $process->isSuccessful();
        }

        $process = Process::fromShellCommandline(
            escapeshellarg($candidate) . ' --version'
        );
        $process->setTimeout(15);
        $process->run();

        return $process->isSuccessful();
    }

    private function isFunctionEnabled(string $function): bool
    {
        if (!function_exists($function)) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return !in_array($function, $disabled, true);
    }
}
