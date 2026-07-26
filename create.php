<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$eventName = trim((string) ($_POST['event_name'] ?? ''));
$eventDateInput = trim((string) ($_POST['event_date'] ?? ''));
$startDateInput = trim((string) ($_POST['start_date'] ?? ''));
$location = trim((string) ($_POST['location'] ?? ''));
$memo = trim((string) ($_POST['memo'] ?? ''));
$requestedMilestones = array_map('intval', (array) ($_POST['milestones'] ?? []));
$milestones = array_values(array_intersect(AVAILABLE_MILESTONES, $requestedMilestones));
sort($milestones, SORT_NUMERIC);
$milestones = array_reverse($milestones);
$errors = [];

if ($eventName === '' || mb_strlen($eventName) > 100) {
    $errors[] = 'イベント名を100文字以内で入力してください。';
}
if (mb_strlen($location) > 150) {
    $errors[] = '会場は150文字以内で入力してください。';
}
if (mb_strlen($memo) > 500) {
    $errors[] = 'メモは500文字以内で入力してください。';
}
if ($milestones === []) {
    $errors[] = '登録する節目を1つ以上選択してください。';
}

try {
    $eventDate = dateOnly($eventDateInput);
    $startDate = $startDateInput === '' ? today() : dateOnly($startDateInput);
    if ($eventDate < today()) {
        $errors[] = 'イベント日は今日以降の日付を指定してください。';
    }
    if ($startDate > $eventDate) {
        $errors[] = '開始日はイベント日以前の日付を指定してください。';
    }
} catch (InvalidArgumentException $exception) {
    $errors[] = '日付を正しく入力してください。';
}

if ($errors !== []) {
    http_response_code(422);
    ?>
    <!doctype html><html lang="ja"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>入力内容をご確認ください</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
    <body class="bg-light"><main class="container py-5" style="max-width:720px"><div class="card shadow-sm"><div class="card-body p-4">
    <h1 class="h3">入力内容をご確認ください</h1><ul class="text-danger"><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul>
    <button class="btn btn-primary" onclick="history.back()">入力画面に戻る</button></div></div></main></body></html>
    <?php
    exit;
}

$id = bin2hex(random_bytes(8));
$event = [
    'id' => $id,
    'event_name' => $eventName,
    'event_date' => $eventDate->format('Y-m-d'),
    'start_date' => $startDate->format('Y-m-d'),
    'location' => $location,
    'memo' => $memo,
    'milestones' => $milestones,
    'created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
];

$directory = dataDirectory();
if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
    throw new RuntimeException('保存用フォルダを作成できませんでした。');
}

$json = json_encode($event, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
$tempFile = tempnam($directory, 'event-');
if ($tempFile === false || file_put_contents($tempFile, $json, LOCK_EX) === false || !rename($tempFile, eventFile($id))) {
    if ($tempFile && is_file($tempFile)) {
        unlink($tempFile);
    }
    throw new RuntimeException('イベントを保存できませんでした。');
}

header('Location: countdown.php?id=' . rawurlencode($id) . '&created=1');
exit;

