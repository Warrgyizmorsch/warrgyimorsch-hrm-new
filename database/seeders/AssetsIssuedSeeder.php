<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Employee;
use App\Models\User;
use App\Services\AssetService;
use Illuminate\Database\Seeder;
use Illuminate\Validation\ValidationException;

/**
 * Imports the "Assets Issued" sheet (Assets_Issued.xlsx).
 *
 *   php artisan db:seed --class=AssetsIssuedSeeder
 *
 * Step 1: every device / peripheral goes into the asset master (status Available).
 * Step 2: items with a holder are allocated to that employee through AssetService,
 *         so the normal rules apply (one open allocation per asset, active users only).
 *
 * Safe to re-run: an asset is looked up by serial number (or by name when it has none)
 * and is never created twice; an asset that already has a holder is left alone.
 * Holders that can't be matched to exactly one user are reported, and their assets
 * stay Available so they can be allocated from the Assets page.
 */
class AssetsIssuedSeeder extends Seeder
{
    private const REASON = 'Opening stock - imported from Assets_Issued.xlsx';

    /**
     * Sheet name => names it may appear as in the HRMS (users.name / employees.name).
     * Add an email here (e.g. 'Imam' => ['nazreimam.warrgyizmorsch@gmail.com']) to pin
     * a holder exactly.
     */
    private const HOLDER_ALIASES = [
        'Imam' => ['Nazre Imam'],
        'Nisha' => ['Nisha Mewara'],
        'kapil menaria' => ['Kapil Menaria', 'Kapil'],
    ];

    public function run(AssetService $service): void
    {
        $created = $existing = $allocated = $alreadyHeld = 0;
        $unmatched = [];
        $failed = [];

        foreach ($this->assets() as $row) {
            $asset = $this->findOrCreateAsset($row, $wasCreated);
            $wasCreated ? $created++ : $existing++;

            $holder = $row['holder'] ?? null;
            if (!$holder) {
                continue;
            }

            if ($asset->activeAllocation()->exists()) {
                $alreadyHeld++;
                continue;
            }

            $userId = $this->resolveUserId($holder);
            if (!$userId) {
                $unmatched[$holder][] = $asset->name;
                continue;
            }

            if ($asset->status !== 'Available') {
                $failed[] = "{$asset->name}: status is {$asset->status}, not allocated";
                continue;
            }

            try {
                // Opening stock: these people already have the devices, so no receipt confirmation is asked for.
                $service->allocate($asset, $userId, self::REASON, null, ['require_ack' => false]);
                $allocated++;
            } catch (ValidationException $e) {
                $failed[] = "{$asset->name} -> {$holder}: " . collect($e->errors())->flatten()->implode(' ');
            }
        }

        $this->command?->info("Assets created: {$created}, already in master: {$existing}");
        $this->command?->info("Allocated now: {$allocated}, already allocated: {$alreadyHeld}");

        foreach ($unmatched as $name => $assetNames) {
            $this->command?->warn("No unique user found for \"{$name}\" - left Available: " . implode(', ', $assetNames));
        }
        foreach ($failed as $msg) {
            $this->command?->warn($msg);
        }
        if ($unmatched) {
            $this->command?->line('Fix the name in HOLDER_ALIASES (or allocate from the Assets page) and re-run.');
        }
    }

    private function findOrCreateAsset(array $row, ?bool &$wasCreated): Asset
    {
        $query = Asset::withTrashed();
        $asset = !empty($row['serial_number'])
            ? $query->where('serial_number', $row['serial_number'])->first()
            : $query->where('name', $row['name'])->where('type', $row['type'])->first();

        if ($asset) {
            if ($asset->trashed()) {
                $asset->restore();
            }
            $wasCreated = false;

            return $asset;
        }

        $wasCreated = true;

        // asset_tag (WM-PC-0001 ...) is generated automatically when the asset is created.
        return Asset::create([
            'name' => $row['name'],
            'type' => $row['type'],
            'serial_number' => $row['serial_number'] ?? null,
            'system_configuration' => $row['config'] ?? null,
            'status' => $row['status'] ?? 'Available',
        ]);
    }

    /** Returns the user id when exactly one active user matches the sheet name. */
    private function resolveUserId(string $sheetName): ?int
    {
        $candidates = array_merge([$sheetName], self::HOLDER_ALIASES[$sheetName] ?? []);

        foreach ($candidates as $candidate) {
            $ids = $this->userIdsFor($candidate);
            if (count($ids) === 1) {
                return $ids[0];
            }
            if (count($ids) > 1) {
                return null; // ambiguous - don't guess
            }
        }

        return null;
    }

    private function userIdsFor(string $nameOrEmail): array
    {
        $value = strtolower(preg_replace('/\s+/', ' ', trim($nameOrEmail)));
        $field = str_contains($value, '@') ? 'email' : 'name';
        $normalized = "LOWER(TRIM(REPLACE(REPLACE({$field}, '  ', ' '), '  ', ' ')))";

        $userIds = User::whereRaw("{$normalized} = ?", [$value])->pluck('id', 'id');

        $employeeIds = Employee::whereRaw("{$normalized} = ?", [$value])->pluck('id');
        if ($employeeIds->isNotEmpty()) {
            $userIds = $userIds->union(User::whereIn('employee_id', $employeeIds)->pluck('id', 'id'));
        }

        $users = User::whereIn('id', $userIds->keys())->get(['id', 'account_status']);
        $active = $users->filter(fn ($u) => $u->account_status === null || $u->account_status === 'active');

        return ($active->isNotEmpty() ? $active : $users)->pluck('id')->values()->all();
    }

    /** Cleaned-up rows from Assets_Issued.xlsx. */
    private function assets(): array
    {
        $rows = [
            // ---- Ranjit Singh Shaktawat ----
            [
                'name' => 'HP Laptop - DESKTOP-VR5PNHD', 'type' => 'Laptop', 'holder' => 'Ranjit Singh Shaktawat',
                'serial_number' => '662E4A4F-20D3-4467-A14B-59FDD2C00C26',
                'config' => "Device Name: DESKTOP-VR5PNHD\nProcessor: Intel(R) Core(TM) i5-6300U CPU @ 2.40GHz 2.50 GHz\nInstalled RAM: 16.0 GB (15.9 GB usable)\nStorage: 477 GB SSD ADATA SU660\nGraphics Card: Intel(R) HD Graphics 520 (128 MB)\nDevice ID: 662E4A4F-20D3-4467-A14B-59FDD2C00C26\nProduct ID: 00330-50520-59512-AAOEM\nSystem Type: 64-bit operating system, x64-based processor",
            ],

            // ---- Mayank Purohit ----
            [
                'name' => 'PC-39', 'type' => 'PC', 'holder' => 'Mayank Purohit',
                'serial_number' => '2ADAD08C-DB09-409C-9AB7-53449A4BB199',
                'config' => "PC [ 39 I5- 8G 16-250SSD ]\nDevice Name: AlexMark\nProcessor: Intel(R) Core(TM) i5-8400T CPU @ 1.70GHz (1.70 GHz)\nInstalled RAM: 16.0 GB (15.8 GB usable)\nGraphics Card: Intel(R) UHD Graphics 630 (128 MB)\nStorage: 238 GB\nDevice ID: 2ADAD08C-DB09-409C-9AB7-53449A4BB199\nProduct ID: 00330-53042-57013-AAOEM\nSystem Type: 64-bit operating system, x64-based processor",
            ],

            // ---- love kumar todawat (uses a personal laptop; company mouse only) ----
            [
                'name' => 'Dell Mouse', 'type' => 'Mouse', 'holder' => 'love kumar todawat',
                'serial_number' => 'CN-05NT8R-PRC00-3CR-0C3X',
                'config' => 'Dell mouse. Employee uses a personal laptop.',
            ],

            // ---- Gourav Mehta ----
            [
                'name' => 'Workstation - DESKTOP-71L7EAD', 'type' => 'PC', 'holder' => 'Gourav Mehta',
                'serial_number' => 'FC860C3B-6D1B-4759-B442-33D696E48DEB',
                'config' => "Device Name: DESKTOP-71L7EAD\nProcessor: Intel(R) Xeon(R) W-2145 CPU @ 3.70GHz (3.70 GHz)\nInstalled RAM: 64.0 GB (63.7 GB usable)\nGraphics Card: NVIDIA GeForce RTX 3060 (12 GB)\nStorage: 954 GB\nDevice ID: FC860C3B-6D1B-4759-B442-33D696E48DEB\nProduct ID: 00330-80000-00000-AA950\nSystem Type: 64-bit operating system, x64-based processor",
            ],
            ['name' => 'Workstation (DESKTOP-71L7EAD) Keyboard', 'type' => 'Keyboard', 'holder' => 'Gourav Mehta'],
            ['name' => 'Workstation (DESKTOP-71L7EAD) Mouse', 'type' => 'Mouse', 'holder' => 'Gourav Mehta'],
            ['name' => 'Workstation (DESKTOP-71L7EAD) Earphone', 'type' => 'Earphone', 'holder' => 'Gourav Mehta'],

            // ---- Ojaswini Singh ----
            [
                'name' => 'PC-35', 'type' => 'PC', 'holder' => 'Ojaswini Singh',
                'serial_number' => '67580887-CCE2-4321-8B2E-3F115591BD4D',
                'config' => "Device Name: DESKTOP-TCML531\nProcessor: Intel(R) Core(TM) i3-10105 CPU @ 3.70GHz (3.70 GHz)\nInstalled RAM: 16.0 GB (15.8 GB usable)\nDevice ID: 67580887-CCE2-4321-8B2E-3F115591BD4D\nProduct ID: 00329-00000-00003-AA704\nSystem Type: 64-bit operating system, x64-based processor",
            ],

            // ---- Fardeen Hussain (peripherals only) ----
            ['name' => 'Mouse - Fardeen Hussain', 'type' => 'Mouse', 'holder' => 'Fardeen Hussain'],
            ['name' => 'Keyboard - Fardeen Hussain', 'type' => 'Keyboard', 'holder' => 'Fardeen Hussain'],

            // ---- Gajendra Teli ----
            [
                'name' => 'PC-40', 'type' => 'PC', 'holder' => 'Gajendra Teli',
                'config' => "CPU: Intel Core i5-8500T @ 6x 3.5GHz\nRAM: 16GB\nOS: Linux Mint 22.3 Zena",
            ],
            ['name' => 'PC-40 Keyboard (Dell)', 'type' => 'Keyboard', 'holder' => 'Gajendra Teli'],
            ['name' => 'PC-40 Mouse (D-Select)', 'type' => 'Mouse', 'holder' => 'Gajendra Teli'],
            ['name' => 'PC-40 Monitor (1920x1080)', 'type' => 'Monitor', 'holder' => 'Gajendra Teli'],

            // ---- kapil menaria ----
            // The sheet repeats the Device Name / Device ID of Ranjit's HP laptop for this PC,
            // so no serial is stored - check the machine and update it.
            [
                'name' => 'PC-33', 'type' => 'PC', 'holder' => 'kapil menaria',
                'config' => "Processor: Intel(R) Core(TM) i5-6500T CPU @ 2.40GHz 2.50 GHz\nInstalled RAM: 16.0 GB (15.9 GB usable)\nStorage: 240 GB SSD ADATA SU660\nNote: Device Name / Device ID in the sheet were copied from another machine (DESKTOP-VR5PNHD) - verify.",
            ],

            // ---- Jay Sahu ----
            [
                'name' => 'Dell Laptop - i7-8850H (239 GB SSD)', 'type' => 'Laptop', 'holder' => 'Jay Sahu',
                'config' => 'Intel Core i7-8850H, 8th Generation, 8 GB RAM, 239 GB SSD',
            ],
            ['name' => 'Dell Adapter - i7-8850H Laptop (239 GB SSD)', 'type' => 'Charger', 'holder' => 'Jay Sahu'],

            // ---- Nisha ----
            [
                'name' => 'Laptop - DESKTOP-3UIF7Q4', 'type' => 'Laptop', 'holder' => 'Nisha',
                'serial_number' => 'CC94FF6B-7FA6-45B9-96FF-6DDDC94C1F5B',
                'config' => "Device Name: DESKTOP-3UIF7Q4\nProcessor: Intel(R) Core(TM) i7-8850H CPU @ 2.60GHz (2.59 GHz)\nInstalled RAM: 8.00 GB (7.80 GB usable)\nGraphics Card: Intel(R) UHD Graphics 630 (128 MB)\nStorage: 238 GB\nDevice ID: CC94FF6B-7FA6-45B9-96FF-6DDDC94C1F5B\nProduct ID: 00330-51741-77898-AAOEM\nSystem Type: 64-bit operating system, x64-based processor",
            ],
            ['name' => 'Laptop (DESKTOP-3UIF7Q4) Charger', 'type' => 'Charger', 'holder' => 'Nisha'],
            ['name' => 'Laptop (DESKTOP-3UIF7Q4) Mouse', 'type' => 'Mouse', 'holder' => 'Nisha'],

            // ---- Imam ----
            [
                'name' => 'PC-07', 'type' => 'PC', 'holder' => 'Imam',
                'serial_number' => '12976ACB-85CC-49B3-A69E-3D2790BEB26D',
                'config' => "Device Name: DESKTOP-74927MI\nProcessor: Intel(R) Core(TM) i5-6500T CPU @ 2.50GHz 2.50 GHz\nInstalled RAM: 16.0 GB (15.9 GB usable)\nStorage: 477 GB SSD EVM25/512GB\nGraphics Card: Intel(R) HD Graphics 530 (128 MB)\nDevice ID: 12976ACB-85CC-49B3-A69E-3D2790BEB26D\nProduct ID: 00342-50476-23628-AAOEM\nSystem Type: 64-bit operating system, x64-based processor",
            ],
            ['name' => 'PC-07 Mouse (Logitech)', 'type' => 'Mouse', 'holder' => 'Imam'],
            ['name' => 'PC-07 Keyboard (Fingers)', 'type' => 'Keyboard', 'holder' => 'Imam'],

            // ---- Anuj Bharadia ----
            [
                'name' => 'PC-02', 'type' => 'PC', 'holder' => 'Anuj Bharadia',
                'serial_number' => 'F4714B3A-9C31-4394-B4F3-715EAE8C6D90',
                'config' => "Device Name: DESKTOP-DL4E1IV\nProcessor: Intel(R) Core(TM) i5-7500T CPU @ 2.70GHz 2.71 GHz\nInstalled RAM: 16.0 GB (15.9 GB usable)\nStorage: 477 GB SSD ESSENCORE SATA SSD\nGraphics Card: Intel(R) HD Graphics 630 (128 MB)\nDevice ID: F4714B3A-9C31-4394-B4F3-715EAE8C6D90\nProduct ID: 00330-50969-86872-AAOEM\nSystem Type: 64-bit operating system, x64-based processor",
            ],
            ['name' => 'PC-02 Mouse (HP)', 'type' => 'Mouse', 'holder' => 'Anuj Bharadia'],
            ['name' => 'PC-02 Keyboard (iBall)', 'type' => 'Keyboard', 'holder' => 'Anuj Bharadia'],
            // Marked "Not Working" in the sheet: recorded as Faulty (a faulty asset can't be allocated).
            ['name' => 'PC-02 Earphone', 'type' => 'Earphone', 'status' => 'Faulty', 'config' => 'Not working (per Assets_Issued sheet). Was with Anuj Bharadia.'],

            // ---- Arfat Sheikh ----
            [
                'name' => 'PC-34 (Dell)', 'type' => 'PC', 'holder' => 'Arfat Sheikh',
                'serial_number' => 'A12297EF-7048-4B57-807A-D49ED492A4A3',
                'config' => "Make: Dell\nDevice Name: DESKTOP-BPP6UEM\nProcessor: Intel(R) Core(TM) i5-6500 CPU @ 3.20GHz 3.19 GHz\nInstalled RAM: 8.00 GB (7.89 GB usable)\nStorage: 238 GB SSD 256GB EVM SSD\nGraphics Card: Intel(R) HD Graphics 530 (128 MB)\nDevice ID: A12297EF-7048-4B57-807A-D49ED492A4A3\nProduct ID: 00330-51040-88824-AAOEM\nSystem Type: 64-bit operating system, x64-based processor",
            ],
            ['name' => 'PC-34 Keyboard (Logitech)', 'type' => 'Keyboard', 'holder' => 'Arfat Sheikh'],
            ['name' => 'PC-34 Monitor (Samsung)', 'type' => 'Monitor', 'holder' => 'Arfat Sheikh'],
            ['name' => 'PC-34 Mouse (HP)', 'type' => 'Mouse', 'holder' => 'Arfat Sheikh'],

            // ---- Not assigned to anyone ----
            ['name' => 'PC-23 (Server)', 'type' => 'PC', 'config' => "i5 7th Gen, 240GB SSD, 16GB RAM\nIn use as the office server."],
            ['name' => 'PC-06', 'type' => 'PC', 'config' => 'i5 6th Gen, 240GB SSD, 8GB RAM'],
            ['name' => 'PC-31', 'type' => 'PC', 'config' => 'i3 10th Gen, 500GB SSD, 8GB RAM'],
            ['name' => 'PC-28', 'type' => 'PC', 'config' => 'i5 6th Gen, 240GB SSD, 8GB RAM'],
            ['name' => 'PC-36', 'type' => 'PC', 'config' => 'i3 10th Gen, 1TB, 8GB RAM'],
            ['name' => 'PC-38', 'type' => 'PC', 'config' => 'i3 10th Gen, 250GB SSD, 8GB RAM'],
            ['name' => 'PC-16', 'type' => 'PC', 'config' => 'i3 8th Gen, 256GB SSD, 16GB RAM'],
        ];

        // "3 Extra Screen and 2 Mouse and 4 Keyboard also Free"
        foreach (['Monitor' => 3, 'Mouse' => 2, 'Keyboard' => 4] as $type => $count) {
            for ($i = 1; $i <= $count; $i++) {
                $rows[] = ['name' => "Spare {$type} {$i}", 'type' => $type];
            }
        }

        return $rows;
    }
}
