<?php
// Usage: php storage/backups/oltao_roadmap_apply.php [--dry]
// Idempotent: tasks already carrying an OLT- key are skipped; new tickets are matched by title.

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Portable: tasks are found by their original title and phases by sort order, so database ids may differ.
const KICKOFF = '2026-11-10';
const OWNER_NAME = 'Prakash Sharma';
$dry = in_array('--dry', $argv, true);

$project = DB::table('projects')->where('slug', 'oltao-erp')->first();
if (!$project) { exit("ABORT: project with slug oltao-erp not found\n"); }
define('PROJECT_ID', $project->id);
echo "Project: {$project->name} (id " . PROJECT_ID . ")\n";

$emp = DB::table('employees')->where('name', OWNER_NAME)->first();
$usr = $emp ? DB::table('users')->where('employee_id', $emp->id)->first() : null;
if (!$emp || !$usr) { exit("ABORT: employee/user '" . OWNER_NAME . "' not found\n"); }
define('EMPLOYEE', $emp->id);
define('ASSIGNER', $usr->id);
echo 'Assignee: ' . OWNER_NAME . " (employee {$emp->id}, user {$usr->id})\n";

$data = require __DIR__ . '/oltao_roadmap_tickets.php';
$titles = require __DIR__ . '/oltao_roadmap_titles.php';

// phases: local phase ids 1..11 = sort order 1..11
$phases = DB::table('project_phases')->where('project_id', PROJECT_ID)->orderBy('sort_order')->orderBy('id')->get()->keyBy('id');
if ($phases->count() !== 11) { exit('ABORT: expected 11 phases, found ' . $phases->count() . "\n"); }
$phaseByLocal = [];
foreach ($phases->values() as $i => $p) { $phaseByLocal[$i + 1] = $p->id; }
$blockStart = [];
foreach ($phases->values() as $i => $p) {
    $blockStart[$p->id] = $i === 0 ? 1 : $i * 100 + 1; // P0: 001.., 1.1: 101.., ... AMC: 1001..
}
foreach ($data['new'] as $tag => $n) { $data['new'][$tag][0] = $phaseByLocal[$n[0]]; }

// tasks: local id => server row, matched on original title (with or without an OLT- prefix)
$serverTasks = DB::table('daily_tasks')->where('project_id', PROJECT_ID)->get();
$byTitle = [];
foreach ($serverTasks as $t) { $byTitle[preg_replace('/^OLT-\d+ \[\w+\] /', '', $t->task_title)][] = $t; }
$missing = $dupes = [];
$tasks = collect();
foreach ($titles as $localId => $title) {
    $hit = $byTitle[$title] ?? [];
    if (count($hit) === 0) { $missing[] = "$localId: $title"; continue; }
    if (count($hit) > 1) { $dupes[] = "$localId: $title"; continue; }
    $row = clone $hit[0];
    $row->local_id = $localId;
    $tasks->push($row);
}
if ($missing || $dupes) {
    echo "ABORT: could not match every task.\n";
    foreach ($missing as $m) { echo "  missing  $m\n"; }
    foreach ($dupes as $d) { echo "  duplicate $d\n"; }
    exit(1);
}
echo 'Matched ' . $tasks->count() . " existing tasks.\n";

$weekOf = fn ($d) => intdiv(Carbon::parse(KICKOFF)->diffInDays(Carbon::parse($d), false) >= 0
    ? (int) Carbon::parse(KICKOFF)->diffInDays(Carbon::parse($d)) : -1, 7) + 1;
$sprintLabel = function ($start, $end) use ($weekOf) {
    $ws = Carbon::parse($start)->lt(Carbon::parse(KICKOFF)) ? 0 : $weekOf($start);
    $we = Carbon::parse($end)->lt(Carbon::parse(KICKOFF)) ? 0 : $weekOf($end);
    $ss = $ws === 0 ? 0 : (int) ceil($ws / 2);
    $se = $we === 0 ? 0 : (int) ceil($we / 2);
    $s = $ss === $se ? "S$ss" : "S$ss-S$se";
    $w = $ws === 0 && $we === 0 ? 'pre-kickoff' : ($ws === 0 ? "pre-kickoff to wk $we" : ($ws === $we ? "wk $ws" : "wk $ws-$we"));
    return [$s, $w, $ss];
};

// ---- assign keys: existing tasks in id order, then new tickets in listed order, per phase block
// keyed by LOCAL task id so keys and "depends on" match the local copy exactly
$tasks = $tasks->sortBy('local_id')->values();
$keyOf = [];
$counter = [];
foreach ($phases as $pid => $p) { $counter[$pid] = $blockStart[$pid]; }
$nextKey = function ($pid) use (&$counter) { return sprintf('OLT-%03d', $counter[$pid]++); };
foreach ($tasks as $t) { $keyOf[$t->local_id] = $nextKey($t->phase_id); }
foreach ($data['new'] as $tag => $n) { $keyOf[$tag] = $nextKey($n[0]); }

$e = fn ($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$body = function ($type, $pts, $deps, $story, $ac, $start, $end, $phase, $done, $notes) use ($keyOf, $sprintLabel, $e) {
    [$s, $w] = $sprintLabel($start, $end);
    $depKeys = array_map(fn ($d) => $keyOf[$d] ?? '?', $deps);
    $box = $done ? '&#9745;' : '&#9744;';
    $h = '<p><strong>Type:</strong> ' . $e($type) . ' &middot; <strong>Estimate:</strong> ' . $pts . ' pts'
       . ' &middot; <strong>Sprint:</strong> ' . $s . ' (' . $w . ')'
       . ' &middot; <strong>Epic:</strong> ' . $e($phase) . '</p>';
    $h .= '<p><strong>Depends on:</strong> ' . ($depKeys ? implode(', ', $depKeys) : 'none') . '</p>';
    $h .= '<h4>User story</h4><p>' . $e($story) . '</p>';
    $h .= '<h4>Acceptance criteria</h4><ul>' . implode('', array_map(fn ($a) => "<li>$box " . $e($a) . '</li>', $ac)) . '</ul>';
    $h .= '<h4>Definition of done</h4><p>Code reviewed, tests green, documented, deployed to staging with masked data (Cl. 13.4), committed to the client repository (Cl. 11.5). New scope only via signed Change Request (Cl. 10.2).</p>';
    if ($notes) { $h .= '<h4>Notes</h4>' . $notes; }
    return $h;
};

$updated = $inserted = 0;
DB::beginTransaction();
try {
    // 1. existing tasks
    foreach ($tasks as $t) {
        if (str_starts_with($t->task_title, 'OLT-')) { continue; }
        $d = $data['existing'][$t->local_id] ?? null;
        if (!$d) { echo "WARN no data for task {$t->local_id}\n"; continue; }
        [$type, $pts, $deps, $story, $ac] = $d;
        $done = in_array(strtolower($t->status), ['completed', 'done'], true);
        $notes = $t->description ? $t->description : null;
        DB::table('daily_tasks')->where('id', $t->id)->update([
            'task_title' => $keyOf[$t->local_id] . " [$type] " . $t->task_title,
            'description' => $body($type, $pts, $deps, $story, $ac, $t->start_date, $t->end_date, $phases[$t->phase_id]->name, $done, $notes),
            'updated_at' => now(),
        ]);
        $updated++;
    }
    // 2. new tickets
    foreach ($data['new'] as $tag => $n) {
        [$pid, $title, $start, $end, $prio, $type, $pts, $deps, $story, $ac] = $n;
        $full = $keyOf[$tag] . " [$type] $title";
        if (DB::table('daily_tasks')->where('project_id', PROJECT_ID)->where('task_title', 'like', "% [$type] $title")->exists()) { continue; }
        DB::table('daily_tasks')->insert([
            'project_id' => PROJECT_ID, 'phase_id' => $pid, 'module' => $tasks->firstWhere('phase_id', $pid)->module,
            'task_title' => $full, 'start_date' => $start, 'end_date' => $end, 'priority' => $prio, 'status' => 'Pending',
            'employee_id' => EMPLOYEE, 'assigned_by' => ASSIGNER,
            'description' => $body($type, $pts, $deps, $story, $ac, $start, $end, $phases[$pid]->name, false, null),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $inserted++;
    }
    // 3. sprint calendar + ticket index appended to each phase description
    $all = DB::table('daily_tasks')->where('project_id', PROJECT_ID)->orderBy('start_date')->orderBy('task_title')->get();
    foreach ($phases as $pid => $p) {
        $desc = preg_replace('#<h4>Sprint plan</h4>.*$#s', '', (string) $p->description);
        $bySprint = [];
        $pts = 0;
        foreach ($all->where('phase_id', $pid) as $t) {
            [, , $ss] = $sprintLabel($t->start_date, $t->end_date);
            preg_match('/^(OLT-\d+)/', $t->task_title, $m);
            $bySprint[$ss][] = $m[1] ?? '?';
            if (preg_match('/Estimate:<\/strong> (\d+) pts/', (string) $t->description, $pm)) { $pts += (int) $pm[1]; }
        }
        ksort($bySprint);
        $rows = '';
        foreach ($bySprint as $ss => $keys) {
            if ($ss === 0) { $dates = 'Pre-kickoff'; }
            else {
                $a = Carbon::parse(KICKOFF)->addWeeks(($ss - 1) * 2);
                $dates = $a->format('d M') . ' - ' . $a->copy()->addDays(13)->format('d M Y') . ' (wk ' . (2 * $ss - 1) . '-' . (2 * $ss) . ')';
            }
            sort($keys);
            $rows .= '<tr><td>S' . $ss . '</td><td>' . $dates . '</td><td>' . implode(', ', $keys) . '</td></tr>';
        }
        $desc .= '<h4>Sprint plan</h4><p>Two-week sprints from kickoff ' . Carbon::parse(KICKOFF)->format('d M Y') . '. Tickets are listed by the sprint they start in. Total estimate: <strong>' . $pts . ' pts</strong>.</p>'
               . '<table><thead><tr><th>Sprint</th><th>Dates</th><th>Tickets starting</th></tr></thead><tbody>' . $rows . '</tbody></table>';
        DB::table('project_phases')->where('id', $pid)->update(['description' => $desc, 'updated_at' => now()]);
    }
    // 4. project end date covers the warranty phase
    DB::table('projects')->where('id', PROJECT_ID)->update(['end_date' => $phases->max('due_date'), 'updated_at' => now()]);

    if ($dry) { DB::rollBack(); echo "DRY RUN (rolled back). "; } else { DB::commit(); }
    echo "updated=$updated inserted=$inserted\n";
} catch (Throwable $ex) {
    DB::rollBack();
    echo 'ERROR: ' . $ex->getMessage() . "\n";
    exit(1);
}
