<?php
/**
 * Export Completed Tasks to CSV
 */

require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/classes/Task.php';

requireAuth();

$userId = getCurrentUserId();
$taskModel = new Task();
$completedTasks = $taskModel->getCompletedForExport($userId);

$filename = 'orbit_completed_tasks_' . date('Y-m-d_His') . '.csv';

// Output HTTP headers to force browser download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Header Row
fputcsv($output, ['Task ID', 'Title', 'Description', 'Category', 'Priority', 'Due Date', 'Completed Date', 'Created Date']);

// CSV Data Rows
foreach ($completedTasks as $row) {
    fputcsv($output, [
        $row['id'],
        $row['title'],
        $row['description'] ?? '',
        $row['category'] ?? 'Uncategorized',
        ucfirst($row['priority']),
        $row['due_date'] ?? 'No Due Date',
        $row['completed_at'] ?? '',
        $row['created_at'] ?? ''
    ]);
}

fclose($output);
exit;

