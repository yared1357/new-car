<?php
require_once __DIR__ . '/session.php';
if ($admin['role'] !== 'super_admin') {
    http_response_code(403);
    echo 'Only the super admin can manage administrator accounts.';
    exit;
}
if (!isset($_SESSION['admin_accounts_csrf'])) $_SESSION['admin_accounts_csrf'] = bin2hex(random_bytes(32));

$error = '';
$success = isset($_GET['saved']) ? 'Account saved successfully.' : '';
$editAccount = null;
$form = ['name' => '', 'phone' => '', 'email' => '', 'role' => 'admin'];
$createOpen = isset($_GET['create']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['admin_accounts_csrf'], $_POST['csrf_token'] ?? '')) {
        $error = 'Your form session expired. Reload the page and try again.';
    } else {
        $action = $_POST['action'] ?? 'create';
        if ($action === 'delete') {
            $accountId = (int) ($_POST['account_id'] ?? 0);
            if ($accountId === (int) $admin['id']) $error = 'You cannot delete your own account.';
            else if ($accountId > 0) {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id=? AND role IN ('admin','super_admin')");
                $stmt->execute([$accountId]);
                header('Location: admin_accounts.php?saved=1');
                exit;
            } else $error = 'Invalid account.';
        } else {
            $accountId = (int) ($_POST['account_id'] ?? 0);
            $form = [
                'name' => trim((string) ($_POST['name'] ?? '')),
                'phone' => trim((string) ($_POST['phone'] ?? '')),
                'email' => trim((string) ($_POST['email'] ?? '')),
                'role' => ($_POST['role'] ?? 'admin') === 'super_admin' ? 'super_admin' : 'admin',
            ];
            $passwordValue = (string) ($_POST['password'] ?? '');
            $createOpen = true;

            if ($form['name'] === '' || mb_strlen($form['name']) > 100) $error = 'Enter a valid full name.';
            else if ($form['phone'] === '' || !preg_match('/^[0-9+()\-\\s]{6,20}$/', $form['phone'])) $error = 'Enter a valid phone number.';
            else if ($form['email'] !== '' && (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($form['email']) > 100)) $error = 'Enter a valid email address.';
            else if ($action === 'create' && mb_strlen($passwordValue) < 4) $error = 'Password must be at least 4 characters.';
            else {
                $checkSql = 'SELECT id FROM users WHERE phone=?';
                $checkParams = [$form['phone']];
                if ($action === 'edit') { $checkSql .= ' AND id<>?'; $checkParams[] = $accountId; }
                $check = $pdo->prepare($checkSql);
                $check->execute($checkParams);
                if ($check->fetch()) $error = 'This phone number is already registered.';
                else if ($action === 'edit') {
                    if ($accountId <= 0) $error = 'Invalid account.';
                    else {
                        $stmt = $pdo->prepare("UPDATE users SET username=?, role=?, email=?, name=?, phone=? WHERE id=?");
                        $stmt->execute([$form['phone'], $form['role'], $form['email'], $form['name'], $form['phone'], $accountId]);
                        header('Location: admin_accounts.php?saved=1'); exit;
                    }
                } else {
                    $stmt = $pdo->prepare("INSERT INTO users (username,password,role,email,name,phone,force_password_change) VALUES (?,?,?,?,?,?,1)");
                    $stmt->execute([$form['phone'], password_hash($passwordValue, PASSWORD_DEFAULT), $form['role'], $form['email'], $form['name'], $form['phone']]);
                    header('Location: admin_accounts.php?saved=1'); exit;
                }
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $pdo->prepare("SELECT id,role,email,name,phone FROM users WHERE id=?");
    $stmt->execute([$editId]);
    $editAccount = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editAccount) {
        $form = ['name' => $editAccount['name'], 'phone' => $editAccount['phone'] ?? '', 'email' => $editAccount['email'], 'role' => $editAccount['role']];
        $createOpen = true;
    }
}
$accounts = $pdo->query("SELECT id,role,email,name,phone,created_at FROM users WHERE role IN ('admin','super_admin') ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Admins | <?= htmlspecialchars($siteSettings['site_name'], ENT_QUOTES, 'UTF-8') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: { colors: { primary: '#1E40AF' } } } };
  </script>
  <style>
    @media (max-width: 639px) {
      .admin-table thead { display: none; }
      .admin-table, .admin-table tbody, .admin-table tr, .admin-table td { display: block; width: 100%; }
      .admin-table tr { border-bottom: 1px solid #e2e8f0; padding: 0.75rem 0.25rem; }
      .admin-table td { border: 0; padding: 0.35rem 0.75rem; }
      .admin-table td.actions-cell { display: flex; justify-content: flex-end; padding-top: 0.5rem; }
    }
  </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<?php include __DIR__ . '/header.php'; ?>
<div id="overlay" class="fixed inset-0 z-40 hidden bg-black/50 lg:hidden"></div>
<?php $activeSidebar='admins'; include __DIR__.'/sidebar.php'; ?>
<main id="main-content" class="min-h-screen w-full pt-16 transition-all duration-300">
  <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
    <div class="mb-6 flex items-center justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Super admin</p><h1 class="mt-1 text-2xl font-bold sm:text-3xl">Manage Administrators</h1></div><button type="button" id="toggle-account-form" class="shrink-0 rounded-lg bg-primary px-4 py-2.5 text-sm font-bold text-white"><?= $createOpen ? 'Close form' : '+ Create admin' ?></button></div>
    <?php if ($success): ?><p class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= htmlspecialchars($success) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post" id="account-form" class="<?= $createOpen ? '' : 'hidden' ?> mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_accounts_csrf']); ?>">
      <input type="hidden" name="action" value="<?php echo $editAccount ? 'edit' : 'create'; ?>">
      <input type="hidden" name="account_id" value="<?php echo (int) ($editAccount['id'] ?? 0); ?>">
      <div class="grid gap-4 sm:grid-cols-2">
        <label class="text-sm font-semibold">Full name<input name="name" required maxlength="100" value="<?= htmlspecialchars($form['name']) ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5" placeholder="Administrator name"></label>
        <label class="text-sm font-semibold">Phone number<input type="tel" name="phone" required maxlength="20" value="<?= htmlspecialchars($form['phone']) ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5" placeholder="+251..."></label>
        <label class="text-sm font-semibold">Email <span class="font-normal text-slate-400">(optional)</span><input type="email" name="email" maxlength="100" value="<?= htmlspecialchars($form['email']) ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5" placeholder="admin@example.com"></label>
        <label class="text-sm font-semibold">Role<select name="role" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"><option value="admin" <?= $form['role']==='admin'?'selected':'' ?>>Admin</option><option value="super_admin" <?= $form['role']==='super_admin'?'selected':'' ?>>Super admin</option></select></label>
        <?php if (!$editAccount): ?>
          <label class="text-sm font-semibold sm:col-span-2">Password<input type="password" name="password" minlength="4" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5" placeholder="At least 4 characters"></label>
        <?php endif; ?>
      </div>
      <div class="mt-5 flex gap-3"><button type="submit" class="rounded-lg bg-primary px-5 py-2.5 font-bold text-white"><?= $editAccount?'Update account':'Create account' ?></button><a href="admin_accounts.php" id="cancel-account-form" class="rounded-lg border border-slate-300 px-5 py-2.5 font-semibold">Cancel</a></div>
    </form>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="admin-table w-full text-left text-sm">
          <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
            <tr><th class="px-4 py-3">Administrator</th><th class="px-4 py-3">Phone</th><th class="px-4 py-3">Role</th><th class="px-4 py-3 text-right">Actions</th></tr>
          </thead>
          <tbody class="divide-y divide-slate-100 sm:divide-y">
            <?php foreach ($accounts as $account): ?>
              <tr>
                <td class="px-4 py-4"><p class="font-bold"><?= htmlspecialchars($account['name'] ?: $account['phone']) ?></p><p class="mt-0.5 text-xs text-slate-500"><?= htmlspecialchars($account['email'] ?: 'No email') ?></p></td>
                <td class="px-4 py-4 text-slate-700"><?= htmlspecialchars($account['phone'] ?: '-') ?></td>
                <td class="px-4 py-4"><span class="inline-flex rounded-full <?= $account['role']==='super_admin'?'bg-amber-100 text-amber-800':'bg-blue-100 text-blue-800' ?> px-3 py-1 text-xs font-bold"><?= $account['role']==='super_admin'?'Super admin':'Admin' ?></span></td>
                <td class="actions-cell px-4 py-4">
                  <div class="flex justify-end gap-2">
                    <a href="?edit=<?= (int)$account['id'] ?>" class="flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 text-blue-700 transition hover:bg-blue-50" title="Edit" aria-label="Edit account"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 3.5a2.121 2.121 0 013 3L12 16l-4 1 1-4 9.5-9.5z"/></svg></a>
                    <?php if ((int)$account['id'] !== (int)$admin['id']): ?>
                      <form method="post" onsubmit="return confirm('Delete this administrator account?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_accounts_csrf']) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="account_id" value="<?= (int)$account['id'] ?>">
                        <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 text-red-600 transition hover:bg-red-50" title="Delete" aria-label="Delete account"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M8 6V4h8v2m-9 0l1 14h8l1-14M10 10v6m4-6v6"/></svg></button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>
<script>
  const toggleButton = document.getElementById('toggle-account-form');
  const accountForm = document.getElementById('account-form');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');
  const main = document.getElementById('main-content');
  const headerLeft = document.getElementById('header-left');
  let open = window.innerWidth >= 1024 && localStorage.getItem('adminSidebarClosed') !== '1';

  function resetAccountFormToCreateMode() {
    accountForm.reset();
    accountForm.querySelector('[name="action"]').value = 'create';
    accountForm.querySelector('[name="account_id"]').value = '0';

    accountForm.querySelector('[name="name"]').value = '';
    accountForm.querySelector('[name="phone"]').value = '';
    accountForm.querySelector('[name="email"]').value = '';
    accountForm.querySelector('[name="role"]').value = 'admin';
    const submitButton = accountForm.querySelector('button[type="submit"]');
    submitButton.textContent = 'Create account';

    const url = new URL(window.location.href);
    url.searchParams.delete('edit');
    window.history.replaceState({}, '', url.pathname + url.search);
  }

  function openCreateMode() {
    resetAccountFormToCreateMode();
    accountForm.classList.remove('hidden');
    toggleButton.textContent = 'Close form';
  }

  toggleButton.addEventListener('click', () => {
    if (accountForm.classList.contains('hidden')) {
      openCreateMode();
    } else {
      accountForm.classList.add('hidden');
      resetAccountFormToCreateMode();
      toggleButton.textContent = '+ Create admin';
    }
  });

  document.getElementById('cancel-account-form')?.addEventListener('click', event => {
    event.preventDefault();
    resetAccountFormToCreateMode();
    accountForm.classList.add('hidden');
    toggleButton.textContent = '+ Create admin';
  });

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
    if (window.innerWidth >= 1024) {
      localStorage.setItem('adminSidebarClosed', open ? '0' : '1');
    }
    applyLayout();
  });
  document.getElementById('sidebar-close-btn')?.addEventListener('click', () => { open = false; applyLayout(); });
  overlay.addEventListener('click', () => { open = false; applyLayout(); });
  window.addEventListener('resize', applyLayout);
  applyLayout();
</script>
</body>
</html>
