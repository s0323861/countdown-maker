<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';

$id = (string) ($_GET['id'] ?? '');
$event = loadEvent($id);
if ($event === null) {
    http_response_code(404);
    exit('イベントが見つかりません。');
}

$events = [];
foreach ($event['milestones'] as $days) {
    $events[] = icsDateEvent($event, (int) $days);
}

$calendar = implode("\r\n", [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//Akira Mukai//Countdown Maker//JA',
    'CALSCALE:GREGORIAN',
    'METHOD:PUBLISH',
    implode("\r\n", $events),
    'END:VCALENDAR',
    '',
]);

$safeFilename = preg_replace('/[^A-Za-z0-9_-]+/', '-', $event['event_name']);
if ($safeFilename === '' || $safeFilename === null) {
    $safeFilename = 'countdown';
}

header('Content-Type: text/calendar; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $safeFilename . '.ics"');
header('Content-Length: ' . strlen($calendar));
echo $calendar;

