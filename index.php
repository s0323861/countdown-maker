<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';

$defaultEventDate = today()->modify('+100 days')->format('Y-m-d');
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="大切な日までの残り日数と節目の日付を計算し、専用ページを作成します。">
  <title>カウントダウンメーカー｜大切な日まで、あと何日？</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
  <style>
    :root { --brand: #5746d9; --brand-dark: #34268f; --soft: #f2f0ff; }
    body { background: linear-gradient(180deg, var(--soft), #fff 420px); color: #25223a; }
    .navbar { background: rgba(35, 28, 75, .96); }
    .hero { padding: 5rem 0 2.5rem; }
    .eyebrow { color: var(--brand); font-weight: 700; letter-spacing: .08em; }
    .hero h1 { font-weight: 800; letter-spacing: -.03em; }
    .form-card { border: 0; border-radius: 1.25rem; box-shadow: 0 1.25rem 3rem rgba(52, 38, 143, .13); }
    .section-number { display: inline-grid; place-items: center; width: 2rem; height: 2rem; border-radius: 50%; background: var(--brand); color: #fff; }
    .milestone-check:checked + label { color: var(--brand-dark); border-color: var(--brand); background: var(--soft); }
    .milestone-label { cursor: pointer; min-width: 6.6rem; }
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

<main>
  <section class="hero text-center">
    <div class="container">
      <p class="eyebrow mb-2">COUNTDOWN MAKER</p>
      <h1 class="display-4">大切な日まで、あと何日？</h1>
      <p class="lead text-secondary mx-auto" style="max-width: 720px;">試験、結婚式、旅行、ライブなどの節目を計算し、毎日確認できる専用ページを作ります。</p>
    </div>
  </section>

  <section class="container pb-5" style="max-width: 920px;">
    <div class="card form-card">
      <div class="card-body p-4 p-md-5">
        <form action="create.php" method="post" class="needs-validation" novalidate>
          <h2 class="h4 mb-4"><span class="section-number me-2">1</span>イベントを入力</h2>

          <div class="mb-3">
            <label for="event_name" class="form-label fw-semibold">イベント名 <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-lg" id="event_name" name="event_name" maxlength="100" placeholder="例：2027年度大学入学共通テスト" required>
            <div class="invalid-feedback">イベント名を入力してください。</div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label for="event_date" class="form-label fw-semibold">イベント日 <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="event_date" name="event_date" min="<?= h(today()->format('Y-m-d')) ?>" value="<?= h($defaultEventDate) ?>" required>
              <div class="invalid-feedback">今日以降の日付を入力してください。</div>
            </div>
            <div class="col-md-6">
              <label for="start_date" class="form-label fw-semibold">カウントダウン開始日 <span class="text-secondary fw-normal">（任意）</span></label>
              <input type="date" class="form-control" id="start_date" name="start_date" max="<?= h($defaultEventDate) ?>">
              <div class="form-text">空欄の場合は、作成日を開始日とします。</div>
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label for="location" class="form-label fw-semibold">会場 <span class="text-secondary fw-normal">（任意）</span></label>
              <input type="text" class="form-control" id="location" name="location" maxlength="150" placeholder="例：○○大学">
            </div>
            <div class="col-md-6">
              <label for="memo" class="form-label fw-semibold">メモ <span class="text-secondary fw-normal">（任意）</span></label>
              <textarea class="form-control" id="memo" name="memo" maxlength="500" rows="2" placeholder="持ち物や準備事項など"></textarea>
            </div>
          </div>

          <h2 class="h4 mb-3"><span class="section-number me-2">2</span>登録する節目を選択</h2>
          <div class="d-flex gap-2 mb-3">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="select-all">すべて選択</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-all">すべて解除</button>
          </div>
          <div class="d-flex flex-wrap gap-2 mb-4">
            <?php foreach (AVAILABLE_MILESTONES as $days): ?>
              <div>
                <input class="btn-check milestone-check" type="checkbox" name="milestones[]" value="<?= $days ?>" id="m<?= $days ?>" <?= in_array($days, [100, 50, 30, 14, 7, 1, 0], true) ? 'checked' : '' ?>>
                <label class="btn btn-outline-secondary milestone-label" for="m<?= $days ?>"><?= h(milestoneLabel($days)) ?></label>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="alert alert-warning small" role="note">
            <i class="fa-solid fa-shield-halved me-1"></i>
            専用URLを知っている人は、イベント名・会場・メモを閲覧できます。住所や電話番号などの個人情報は入力しないでください。
          </div>

          <button type="submit" class="btn btn-brand btn-lg w-100 py-3 fw-bold">
            <i class="fa-solid fa-wand-magic-sparkles me-2"></i>カウントダウンを作成する
          </button>
        </form>
      </div>
    </div>
  </section>
</main>

<footer class="border-top py-4 text-center text-secondary small">
  Copyright &copy; <?= date('Y') ?>
  <a href="https://s0323861.github.io" target="_blank" rel="noopener noreferrer" class="text-secondary text-decoration-none">Akira Mukai</a>
  <span aria-hidden="true"> | </span>
  <a href="https://github.com/s0323861/countdown-maker" target="_blank" rel="noopener noreferrer" class="text-secondary text-decoration-none">
    <i class="fa-brands fa-github me-1" aria-hidden="true"></i>GitHub
  </a>
</footer>

<script>
const form = document.querySelector('.needs-validation');
const eventDate = document.getElementById('event_date');
const startDate = document.getElementById('start_date');
const milestones = [...document.querySelectorAll('.milestone-check')];

eventDate.addEventListener('change', () => {
  startDate.max = eventDate.value;
  if (startDate.value && startDate.value > eventDate.value) startDate.value = '';
});
document.getElementById('select-all').addEventListener('click', () => milestones.forEach(item => item.checked = true));
document.getElementById('clear-all').addEventListener('click', () => milestones.forEach(item => item.checked = false));
form.addEventListener('submit', event => {
  if (!form.checkValidity() || !milestones.some(item => item.checked)) {
    event.preventDefault();
    event.stopPropagation();
    if (!milestones.some(item => item.checked)) alert('登録する節目を1つ以上選択してください。');
  }
  form.classList.add('was-validated');
});
</script>
</body>
</html>
