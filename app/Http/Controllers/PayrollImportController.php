<?php

namespace App\Http\Controllers;

use App\Exports\PayrollImportTemplateExport;
use App\Imports\PayrollImport;
use App\Models\Employee;
use App\Services\PayrollImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Bulk payroll import: upload -> preview -> confirm. Nothing is saved before the confirm step.
 * The parsed rows wait in the session and are re-checked on confirm, since payroll or
 * employees may have changed since the preview.
 */
class PayrollImportController extends Controller
{
    private const SESSION_KEY = 'payroll_import';

    public function __construct(private PayrollImportService $service)
    {
    }

    public function template()
    {
        $employees = Employee::active()->orderBy('employee_code')->get(['id', 'employee_code', 'name']);

        return Excel::download(new PayrollImportTemplateExport($employees), 'payroll_import_template.xlsx');
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'month' => 'required|date_format:Y-m',
        ]);

        try {
            $file = $request->file('import_file');
            $sheet = Excel::toArray(new PayrollImport(), $file)[0] ?? [];
            $rows = $this->service->parse($sheet);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('payroll.index')->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Payroll import read failed: ' . $e->getMessage());

            return redirect()->route('payroll.index')->with('error', 'This file could not be read. Please check that it is a valid Excel or CSV file.');
        }

        session([self::SESSION_KEY => [
            'token' => (string) Str::uuid(),
            'month' => $validated['month'],
            'file_name' => $file->getClientOriginalName(),
            'rows' => $rows,
        ]]);

        return redirect()->route('payroll.import.preview');
    }

    public function preview()
    {
        $import = session(self::SESSION_KEY);
        if (!$import) {
            return redirect()->route('payroll.index')->with('error', 'No payroll import is waiting. Please upload the file again.');
        }

        return view('payroll.import_preview', [
            'import' => $import,
            'review' => $this->service->review($import['rows'], $import['month']),
        ]);
    }

    public function confirm(Request $request)
    {
        $import = session(self::SESSION_KEY);
        if (!$import || !hash_equals($import['token'], (string) $request->input('token'))) {
            return redirect()->route('payroll.index')->with('error', 'This import preview has expired or was replaced. Please upload the file again.');
        }

        $review = $this->service->review($import['rows'], $import['month']);
        if ($review['error_count'] > 0) {
            return redirect()->route('payroll.import.preview')->with('error', 'Nothing was saved. Please fix the errors below and upload the file again.');
        }

        try {
            $saved = $this->service->commit($review, $import['month'], $import['file_name']);
        } catch (\Throwable $e) {
            Log::error('Payroll import save failed: ' . $e->getMessage());

            return redirect()->route('payroll.import.preview')->with('error', 'Saving failed, so no payroll records were changed. Please try again.');
        }

        session()->forget(self::SESSION_KEY);

        return redirect()->route('payroll.index')->with('success', "{$saved} payroll record(s) saved for {$import['month']}.");
    }

    public function cancel()
    {
        session()->forget(self::SESSION_KEY);

        return redirect()->route('payroll.index')->with('success', 'Payroll import cancelled. Nothing was saved.');
    }
}
