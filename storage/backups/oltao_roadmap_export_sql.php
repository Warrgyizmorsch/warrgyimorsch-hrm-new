<?php
// Builds oltao_roadmap_server.sql from the LOCAL database: a phpMyAdmin-safe file that finds
// rows by project slug, phase sort_order and original task title (no hard-coded ids).
// Usage: php storage/backups/oltao_roadmap_export_sql.php

$pdo = new PDO('mysql:host=127.0.0.1;dbname=wm_hrms;charset=utf8mb4', 'root', '');
$q = fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v);
$titles = require __DIR__ . '/oltao_roadmap_titles.php';
$pid = $pdo->query("select id from projects where slug='oltao-erp'")->fetchColumn();
$phaseSort = $pdo->query("select id, sort_order from project_phases where project_id=$pid")->fetchAll(PDO::FETCH_KEY_PAIR);

$sql = "-- Oltao ERP roadmap (OLT tickets). Generated " . date('Y-m-d H:i') . " from local project $pid.\n"
     . "-- Safe to run twice. Back up first. Only titles/descriptions/new tickets change; statuses are left alone.\n"
     . "SET NAMES utf8mb4;\nSTART TRANSACTION;\n"
     . "SET @p := (SELECT id FROM projects WHERE slug='oltao-erp');\n"
     . "SET @emp := (SELECT id FROM employees WHERE name='Prakash Sharma' LIMIT 1);\n"
     . "SET @usr := (SELECT id FROM users WHERE employee_id=@emp LIMIT 1);\n"
     . "SELECT @p AS project_id, @emp AS employee_id, @usr AS user_id;  -- all three must be non-NULL\n\n";

$rows = $pdo->query("select * from daily_tasks where project_id=$pid order by id")->fetchAll(PDO::FETCH_ASSOC);
$sql .= "-- 1. existing tasks: add key + ticket body (matched by original title, skipped if already keyed)\n";
foreach ($rows as $r) {
    if (!isset($titles[$r['id']])) { continue; }
    $sql .= 'UPDATE daily_tasks SET task_title=' . $q($r['task_title']) . ', description=' . $q($r['description'])
          . ', updated_at=NOW() WHERE project_id=@p AND task_title=' . $q($titles[$r['id']]) . ";\n";
}
$sql .= "\n-- 2. new tickets (inserted only if not present)\n";
foreach ($rows as $r) {
    if (isset($titles[$r['id']])) { continue; }
    $sort = (int) $phaseSort[$r['phase_id']];
    $sql .= 'INSERT INTO daily_tasks (project_id, phase_id, module, task_title, start_date, end_date, priority, status, employee_id, assigned_by, description, created_at, updated_at) '
          . "SELECT @p, (SELECT id FROM project_phases WHERE project_id=@p AND sort_order=$sort LIMIT 1), "
          . $q($r['module']) . ', ' . $q($r['task_title']) . ', ' . $q($r['start_date']) . ', ' . $q($r['end_date']) . ', '
          . $q($r['priority']) . ", 'Pending', @emp, @usr, " . $q($r['description']) . ', NOW(), NOW() FROM DUAL '
          . 'WHERE NOT EXISTS (SELECT 1 FROM daily_tasks x WHERE x.project_id=@p AND x.task_title=' . $q($r['task_title']) . ");\n";
}
$sql .= "\n-- 3. sprint plan on each phase (replaces the phase description with the local one)\n";
foreach ($pdo->query("select sort_order, description from project_phases where project_id=$pid order by sort_order") as $ph) {
    $sql .= 'UPDATE project_phases SET description=' . $q($ph['description']) . ", updated_at=NOW() WHERE project_id=@p AND sort_order={$ph['sort_order']};\n";
}
$end = $pdo->query("select end_date from projects where id=$pid")->fetchColumn();
$sql .= "\n-- 4. project end date\nUPDATE projects SET end_date=" . $q($end) . ", updated_at=NOW() WHERE id=@p;\n\n"
      . "SELECT COUNT(*) AS total_tickets, SUM(task_title LIKE 'OLT-%') AS keyed FROM daily_tasks WHERE project_id=@p;  -- expect 132 / 132\n"
      . "COMMIT;\n";

file_put_contents(__DIR__ . '/oltao_roadmap_server.sql', $sql);
echo 'Wrote oltao_roadmap_server.sql (' . round(strlen($sql) / 1024) . " KB)\n";
