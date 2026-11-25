<?php
require_once __DIR__ . '/session.php';
if ($admin['role'] !== 'super_admin') {
    http_response_code(403);
    echo 'Only the super admin can reset administrator passwords.';
    exit;
}

if (!isset($_SESSION['admin_password_reset_csrf'])) {
    $_SESSION['admin_password_reset_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$resetPassword = $_SESSION['admin_password_reset_flash'] ?? null;
unset($_SESSION['admin_password_reset_flash']);
$success = isset($_GET['reset']) ? 'Temporary password set. The administrator must change it after signing in.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['admin_password_reset_csrf'], $_POST['csrf_token'] ?? '')) {
        $error = 'Your form session expired. Reload the page and try again.';
    } else {
        $accountId = (int) ($_POST['account_id'] ?? 0);
        $password = (string) ($_POST['temporary_password'] ?? '');

        if ($accountId <= 0) {
            $error = 'Select a valid administrator account.';
        } elseif (mb_strlen($password) < 4) {
            $error = 'Temporary password must be at least 4 characters.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET password = ?, force_password_change = 1 WHERE id = ? AND role = 'admin'");
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $accountId]);
            if ($stmt->rowCount() > 0) {
                $_SESSION['admin_password_reset_flash'] = $password;
                header('Location: admin_password_reset.php?reset=1');
                exit;
            }
            $error = 'Administrator account not found.';
        }
    }
}

$accounts = $pdo->query("SELECT id, name, email, phone FROM users WHERE role = 'admin' ORDER BY name, phone")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Admin Passwords | <?= htmlspecialchars($siteSettings['site_name'], ENT_QUOTES, 'UTF-8') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: { colors: { primary: '#1E40AF' } } } };
  </script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<?php include __DIR__ . '/header.php'; ?>
<div id="overlay" class="fixed inset-0 z-40 hidden bg-black/50 lg:hidden"></div>
<?php $activeSidebar = 'password_reset'; include __DIR__ . '/sidebar.php'; ?>
<main id="main-content" class="min-h-screen w-full pt-16 transition-all duration-300">
  <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
    <div class="mb-6">
      <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Super admin</p>
      <h1 class="mt-1 text-2xl font-bold sm:text-3xl">Reset Admin Passwords</h1>
      <p class="mt-2 text-sm text-slate-600">Set a temporary password for an administrator.</p>
    </div>
    <?php if ($success): ?><p class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= htmlspecialchars($success) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if ($resetPassword !== null): ?>
      <div class="mb-5 rounded-lg border border-blue-200 bg-blue-50 p-4 text-blue-900">
        <p class="text-sm font-bold">Temporary password to share</p>
        <div class="mt-2 flex flex-wrap items-center gap-2">
          <code id="reset-password-value" class="rounded bg-white px-3 py-2 font-mono text-lg"><?= htmlspecialchars($resetPassword, ENT_QUOTES, 'UTF-8') ?></code>
          <button type="button" id="copy-reset-password" class="rounded-lg border border-blue-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-blue-100">Copy password</button>
        </div>
        <p class="mt-2 text-xs">This password is shown only once here. The administrator must change it after signing in.</p>
      </div>
    <?php endif; ?>

    <?php if ($accounts): ?>
      <label class="mb-4 block text-sm font-semibold">Search administrators
        <input type="search" id="admin-search" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5" placeholder="Search by name, phone, or email">
      </label>
    <?php endif; ?>
    <div id="admin-account-list" class="space-y-4">
      <?php foreach ($accounts as $account): ?>
        <section class="admin-account rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-search="<?= htmlspecialchars(mb_strtolower(implode(' ', array_filter([$account['name'], $account['phone'], $account['email']]))), ENT_QUOTES, 'UTF-8') ?>">
          <div class="mb-4">
            <h2 class="font-bold"><?= htmlspecialchars($account['name'] ?: $account['phone']) ?></h2>
            <p class="mt-1 text-sm text-slate-500"><?= htmlspecialchars($account['phone'] ?: '-') ?><?= $account['email'] ? ' | ' . htmlspecialchars($account['email']) : '' ?></p>
          </div>
          <form method="post" class="flex flex-col gap-3 sm:flex-row sm:items-end" data-confirm-title="Reset this password?" data-confirm-message="This will immediately replace the current password. The administrator will need to use the temporary password and change it after signing in." data-confirm-button="Reset password" data-confirm-style="primary">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_password_reset_csrf']) ?>">
            <input type="hidden" name="account_id" value="<?= (int) $account['id'] ?>">
            <label class="flex-1 text-sm font-semibold">Temporary password
              <input type="text" name="temporary_password" required minlength="4" autocomplete="off" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5" placeholder="At least 4 characters">
            </label>
            <button type="submit" class="rounded-lg bg-primary px-5 py-2.5 font-bold text-white">Reset password</button>
          </form>
        </section>
      <?php endforeach; ?>
      <?php if (!$accounts): ?>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 text-center text-slate-500">There are no administrator accounts to reset.</div>
      <?php endif; ?>
    </div>
    <?php if ($accounts): ?>
      <p id="no-admin-search-results" class="mt-4 hidden rounded-2xl border border-slate-200 bg-white p-6 text-center text-slate-500">No administrators match your search.</p>
    <?php endif; ?>
  </div>
</main>
<script>
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');
  const main = document.getElementById('main-content');
  const headerLeft = document.getElementById('header-left');
  let open = window.innerWidth >= 1024 && localStorage.getItem('adminSidebarClosed') !== '1';

  function applyLayout() {
    const desktop = window.innerWidth >= 1024;
    sidebar.classList.toggle('-translate-x-full', !open);
    overlay.classList.toggle('hidden', desktop || !open);
    main.style.marginLeft = desktop && open ? '256px' : '0';
    main.style.width = desktop && open ? 'calc(100% - 256px)' : '100%';
    if (headerLeft) headerLeft.style.marginLeft = desktop && open ? '258px' : '0';
  }

  document.getElementById('menu-btn').addEventListener('click', () => {
    open = !open;
    if (window.innerWidth >= 1024) localStorage.setItem('adminSidebarClosed', open ? '0' : '1');
    applyLayout();
  });
  document.getElementById('sidebar-close-btn')?.addEventListener('click', () => { open = false; applyLayout(); });
  overlay.addEventListener('click', () => { open = false; applyLayout(); });
  window.addEventListener('resize', applyLayout);
  applyLayout();

  const searchInput = document.getElementById('admin-search');
  const adminCards = [...document.querySelectorAll('.admin-account')];
  const noSearchResults = document.getElementById('no-admin-search-results');
  searchInput?.addEventListener('input', () => {
    const query = searchInput.value.trim().toLocaleLowerCase();
    let visibleCount = 0;
    adminCards.forEach(card => {
      const matches = card.dataset.search.includes(query);
      card.classList.toggle('hidden', !matches);
      if (matches) visibleCount++;
    });
    noSearchResults?.classList.toggle('hidden', visibleCount > 0);
  });

  document.getElementById('copy-reset-password')?.addEventListener('click', async event => {
    const button = event.currentTarget;
    try {
      await navigator.clipboard.writeText(document.getElementById('reset-password-value').textContent);
      button.textContent = 'Copied';
    } catch (error) {
      button.textContent = 'Copy failed';
    }
  });
</script>
</body>
</html>