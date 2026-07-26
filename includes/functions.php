<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Tokyo');

const AVAILABLE_MILESTONES = [100, 50, 30, 14, 7, 3, 1, 0];

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function dataDirectory(): string
{
    return dirname(__DIR__) . '/data';
}

function eventFile(string $id): string
{
    return dataDirectory() . '/' . $id . '.json';
}

function isValidId(string $id): bool
{
    return preg_match('/\A[a-f0-9]{16}\z/', $id) === 1;
}

function loadEvent(string $id): ?array
{
    if (!isValidId($id)) {
        return null;
    }

    $file = eventFile($id);
    if (!is_file($file)) {
        return null;
    }

    $json = file_get_contents($file);
    if ($json === false) {
        return null;
    }

    $event = json_decode($json, true);
    return is_array($event) ? $event : null;
}

function japaneseDate(DateTimeImmutable $date): string
{
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
    return $date->format('Y年n月j日') . '（' . $weekdays[(int) $date->format('w')] . '）';
}

function milestoneLabel(int $days): string
{
    if ($days === 0) {
        return '当日';
    }
    if ($days === 1) {
        return '前日';
    }
    return $days . '日前';
}

function dateOnly(string $date): DateTimeImmutable
{
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$parsed || $parsed->format('Y-m-d') !== $date) {
        throw new InvalidArgumentException('日付の形式が正しくありません。');
    }
    return $parsed;
}

function today(): DateTimeImmutable
{
    return new DateTimeImmutable('today');
}

function dayDifference(DateTimeImmutable $from, DateTimeImmutable $to): int
{
    return (int) $from->diff($to)->format('%r%a');
}

function countdownMessage(string $eventName, DateTimeImmutable $eventDate, DateTimeImmutable $baseDate): string
{
    $days = dayDifference($baseDate, $eventDate);
    if ($days > 0) {
        return $eventName . 'まで、あと' . $days . '日です。';
    }
    if ($days === 0) {
        return '今日は' . $eventName . '当日です！';
    }
    return $eventName . 'から、' . abs($days) . '日が経過しました。';
}

function progress(DateTimeImmutable $startDate, DateTimeImmutable $eventDate, DateTimeImmutable $baseDate): int
{
    $total = dayDifference($startDate, $eventDate);
    if ($total <= 0) {
        return $baseDate >= $eventDate ? 100 : 0;
    }

    $elapsed = dayDifference($startDate, $baseDate);
    return max(0, min(100, (int) round(($elapsed / $total) * 100)));
}

function milestoneDate(DateTimeImmutable $eventDate, int $days): DateTimeImmutable
{
    return $eventDate->modify('-' . $days . ' days');
}

function googleCalendarUrl(array $event, int $days): string
{
    $eventDate = dateOnly($event['event_date']);
    $date = milestoneDate($eventDate, $days);
    $nextDate = $date->modify('+1 day');
    $label = milestoneLabel($days);
    $title = $days === 0
        ? $event['event_name']
        : $event['event_name'] . '（' . $label . '）';
    $details = $event['memo'] !== ''
        ? $event['memo']
        : $event['event_name'] . 'の' . $label . 'です。';

    return 'https://calendar.google.com/calendar/render?' . http_build_query([
        'action' => 'TEMPLATE',
        'text' => $title,
        'details' => $details,
        'location' => $event['location'],
        'dates' => $date->format('Ymd') . '/' . $nextDate->format('Ymd'),
    ], '', '&', PHP_QUERY_RFC3986);
}

function baseUrl(): string
{
    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $directory = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $scheme . '://' . $host . ($directory === '' ? '' : $directory);
}

function publicEventUrl(string $id): string
{
    return baseUrl() . '/countdown.php?id=' . rawurlencode($id);
}

function icsEscape(string $value): string
{
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\;', '\,', '\n'], $value);
}

function icsDateEvent(array $event, int $days): string
{
    $eventDate = dateOnly($event['event_date']);
    $date = milestoneDate($eventDate, $days);
    $nextDate = $date->modify('+1 day');
    $label = milestoneLabel($days);
    $title = $days === 0
        ? $event['event_name']
        : $event['event_name'] . '（' . $label . '）';
    $description = $event['memo'] !== ''
        ? $event['memo']
        : $event['event_name'] . 'の' . $label . 'です。';

    return implode("\r\n", [
        'BEGIN:VEVENT',
        'UID:' . $event['id'] . '-' . $days . '@countdown-maker',
        'DTSTAMP:' . gmdate('Ymd\THis\Z'),
        'DTSTART;VALUE=DATE:' . $date->format('Ymd'),
        'DTEND;VALUE=DATE:' . $nextDate->format('Ymd'),
        'SUMMARY:' . icsEscape($title),
        'DESCRIPTION:' . icsEscape($description),
        'LOCATION:' . icsEscape($event['location']),
        'URL:' . icsEscape(publicEventUrl($event['id'])),
        'END:VEVENT',
    ]);
}

