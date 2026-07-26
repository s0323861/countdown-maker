<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';

$id = (string) ($_GET['id'] ?? '');
$event = loadEvent($id);
if ($event === null) {
    http_response_code(404);
    $pageTitle = 'イベントが見つかりません';
} else {
    $pageTitle = $event['event_name'];
    $eventDate = dateOnly($event['event_date']);
    $startDate = dateOnly($event['start_date']);
    $baseDate = today();
    $progressValue = progress($startDate, $eventDate, $baseDate);
    $eventUrl = publicEventUrl($event['id']);
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= $event ? h(countdownMessage($event['event_name'], $eventDate, $baseDate)) : 'イベントが見つかりません。' ?>">
  <title><?= h($pageTitle) ?>｜カウントダウンメーカー</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
  <style>
    :root { --brand: #5746d9; --brand-dark: #34268f; --soft: #f2f0ff; }
    body { background: linear-gradient(145deg, var(--soft), #fff 60%); color: #25223a; min-height: 100vh; }
    .navbar { background: rgba(35, 28, 75, .96); }
    .countdown-card { border: 0; border-radius: 1.5rem; box-shadow: 0 1.25rem 3rem rgba(52, 38, 143, .14); overflow: hidden; }
    .countdown-hero { background: linear-gradient(135deg, var(--brand-dark), var(--brand)); color: #fff; }
    .countdown-message { font-size: clamp(1.8rem, 5vw, 3.6rem); font-weight: 800; letter-spacing: -.04em; }
    .progress { height: 1.1rem; background: #e7e3ff; }
    .progress-bar { background: linear-gradient(90deg, #7a69ed, var(--brand)); }
    .table .past { color: #777; }
    .btn-brand { background: var(--brand); border-color: var(--brand); color: #fff; }
    .btn-brand:hover { background: var(--brand-dark); border-color: var(--brand-dark); color: #fff; }
  </style>
</head>
<body>
<nav class="navbar navbar-dark">
  <div class="container py-1">
    <a class="navbar-brand fw-bold" href="./"><i class="fa-regular fa-hourglass-half me-2"></i>カウントダウンメーカー</a>
  </div>
</nav>

<main class="container py-5" style="max-width: 980px;">
<?php if ($event === null): ?>
  <div class="alert alert-danger">
    <h1 class="h3">イベントが見つかりません</h1>
    <p>URLが正しいかご確認ください。</p>
    <a href="./" class="btn btn-outline-danger">新しいカウントダウンを作る</a>
  </div>
<?php else: ?>
  <?php if (isset($_GET['created'])): ?>
    <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i>カウントダウンページを作成しました！</div>
  <?php endif; ?>

  <article class="card countdown-card mb-4">
    <div class="countdown-hero text-center p-4 p-md-5">
      <p class="text-white-50 mb-2">COUNTDOWN TO</p>
      <h1 class="h3 mb-4"><?= h($event['event_name']) ?></h1>
      <p class="countdown-message mb-0"><?= h(countdownMessage($event['event_name'], $eventDate, $baseDate)) ?></p>
    </div>
    <div class="card-body p-4 p-md-5">
      <dl class="row mb-4">
        <dt class="col-sm-3">開催日</dt><dd class="col-sm-9"><?= h(japaneseDate($eventDate)) ?></dd>
        <?php if ($event['location'] !== ''): ?><dt class="col-sm-3">会場</dt><dd class="col-sm-9"><?= h($event['location']) ?></dd><?php endif; ?>
        <?php if ($event['memo'] !== ''): ?><dt class="col-sm-3">メモ</dt><dd class="col-sm-9"><?= nl2br(h($event['memo'])) ?></dd><?php endif; ?>
      </dl>

      <div class="d-flex justify-content-between align-items-end mb-2">
        <div>
          <h2 class="h5 mb-1">カウントダウン期間</h2>
          <span class="text-secondary"><?= h(japaneseDate($startDate)) ?>から開始</span>
        </div>
        <strong class="fs-4"><?= $progressValue ?>%</strong>
      </div>
      <div class="progress mb-2" role="progressbar" aria-label="カウントダウン期間の経過率" aria-valuenow="<?= $progressValue ?>" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar" style="width: <?= $progressValue ?>%"></div>
      </div>
      <p class="small text-secondary">設定した期間の<?= $progressValue ?>％が経過しました。準備の進捗率ではなく、時間の経過率を表しています。</p>
    </div>
  </article>

  <section class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h4 mb-0"><i class="fa-solid fa-flag-checkered text-primary me-2"></i>節目の日付</h2>
        <a href="generate.php?id=<?= h($event['id']) ?>" class="btn btn-brand"><i class="fa-regular fa-calendar-days me-2"></i>ICSをまとめて保存</a>
      </div>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead><tr><th>節目</th><th>日付</th><th>状態</th><th>カレンダー</th></tr></thead>
          <tbody>
          <?php foreach ($event['milestones'] as $days):
              $date = milestoneDate($eventDate, (int) $days);
              $isPast = $date < $baseDate;
          ?>
            <tr class="<?= $isPast ? 'past' : '' ?>">
              <td class="fw-semibold"><?= h(milestoneLabel((int) $days)) ?></td>
              <td><?= h(japaneseDate($date)) ?></td>
              <td><?= $isPast ? '<i class="fa-solid fa-check text-success"></i> 経過' : '<i class="fa-regular fa-circle text-primary"></i> これから' ?></td>
              <td><a class="btn btn-sm btn-outline-danger" target="_blank" rel="noopener noreferrer" href="<?= h(googleCalendarUrl($event, (int) $days)) ?>"><i class="fa-brands fa-google me-1"></i>追加</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <section class="card border-0 shadow-sm">
    <div class="card-body p-4">
      <h2 class="h5">専用URL</h2>
      <div class="input-group">
        <input type="text" class="form-control" id="event-url" value="<?= h($eventUrl) ?>" readonly aria-label="専用URL">
        <button class="btn btn-outline-primary" type="button" id="copy-url"><i class="fa-regular fa-copy me-1"></i>URLをコピー</button>
      </div>
      <div class="form-text" id="copy-status" aria-live="polite">このURLをブックマークしたり、家族や友人と共有したりできます。</div>
    </div>
  </section>
<?php endif; ?>
</main>

<?php if ($event !== null): ?>
<script>
document.getElementById('copy-url').addEventListener('click', async () => {
  const field = document.getElementById('event-url');
  const status = document.getElementById('copy-status');
  try {
    await navigator.clipboard.writeText(field.value);
    status.textContent = '専用URLをコピーしました。';
  } catch (error) {
    field.select();
    document.execCommand('copy');
    status.textContent = '専用URLをコピーしました。';
  }
});
</script>
<?php endif; ?>
</body>
</html>

