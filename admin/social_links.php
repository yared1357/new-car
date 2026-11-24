<?php
require_once __DIR__ . '/session.php';

if ($admin['role'] !== 'super_admin') {
    http_response_code(403);
    echo 'Only the super admin can change social media links.';
    exit;
}

if (!isset($_SESSION['social_links_csrf_token'])) {
    $_SESSION['social_links_csrf_token'] = bin2hex(random_bytes(32));
}

$platformNames = [
    'facebook' => 'Facebook',
    'telegram' => 'Telegram',
    'tiktok' => 'TikTok',
    'youtube' => 'YouTube',
    'instagram' => 'Instagram',
    'whatsapp' => 'WhatsApp',
];

$error = '';
$formMode = 'add';
$formPlatform = '';
$formUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['social_links_csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        $error = 'Your form session expired. Reload the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'delete') {
            $platform = strtolower(trim((string) ($_POST['platform'] ?? '')));
            if ($platform !== '') {
                $stmt = $pdo->prepare('DELETE FROM social_links WHERE platform = ?');
                $stmt->execute([$platform]);
                header('Location: social_links.php?saved=1');
                exit;
            }
            $error = 'Could not determine which social link to delete.';
        } else {
            $platform = strtolower(trim((string) ($_POST['platform'] ?? '')));
            $url = trim((string) ($_POST['url'] ?? ''));
            $oldPlatform = strtolower(trim((string) ($_POST['old_platform'] ?? '')));
            $formMode = $action === 'edit' ? 'edit' : 'add';
            $formPlatform = $platform;
            $formUrl = $url;

            if (!array_key_exists($platform, $platformNames)) {
                $error = 'Select a valid social media platform.';
            } elseif (strlen($url) > 500 || !is_valid_social_url($url)) {
                $error = 'Enter a complete valid URL for the selected social profile.';
            } elseif ($action === 'edit' && $oldPlatform === '') {
                $error = 'Could not determine which social link to edit.';
            }

            if ($error === '') {
                if ($action === 'edit' && $oldPlatform !== $platform) {
                    $delete = $pdo->prepare('DELETE FROM social_links WHERE platform = ?');
                    $delete->execute([$oldPlatform]);
                }
                $stmt = $pdo->prepare(
                    'INSERT INTO social_links (platform, url) VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE url = VALUES(url)'
                );
                $stmt->execute([$platform, $url]);
                header('Location: social_links.php?saved=1');
                exit;
            }
        }
    }
}

$socialRows = $pdo->query(
    'SELECT platform, url FROM social_links
     WHERE platform IN ("facebook", "telegram", "tiktok") OR url <> ""
     ORDER BY platform ASC'
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Social Media | <?= htmlspecialchars($siteSettings['site_name'], ENT_QUOTES, 'UTF-8') ?> Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] }, colors: { primary: '#1E40AF', accent: '#F59E0B' } } },
    };
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col">
  <?php include __DIR__ . '/header.php'; ?>
  <header class="fixed top-0 left-0 right-0 z-50 hidden bg-white shadow-lg border-b-2 border-gray-300">
    <div class="w-full flex items-center justify-between h-16 lg:h-20 px-2">
      <div id="header-left" class="flex items-center transition-all duration-300">
        <button id="menu-btn" class="h-full px-4 lg:px-5 hover:bg-gray-100 transition rounded-lg" aria-label="Toggle navigation">
          <svg class="w-7 h-7 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
        <a href="dashboard.php" class="ml-2 flex items-center space-x-3">
          <img src="<?= htmlspecialchars($adminLogoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="h-10 w-10 object-contain">
          <span class="text-primary font-extrabold uppercase"><?= htmlspecialchars($siteSettings['site_name'], ENT_QUOTES, 'UTF-8') ?></span>
        </a>
      </div>
      <div class="pr-4 relative">
        <button id="profile-btn" class="p-2 rounded-full hover:bg-gray-100" aria-label="Open profile menu">
          <span class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-white font-bold"><?= htmlspecialchars(strtoupper(substr($displayName, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
        </button>
        <div id="profile-menu" class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl border hidden z-50 p-4">
          <p class="font-bold"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></p>
          <p class="text-sm text-gray-600"><?= htmlspecialchars($admin['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
          <a href="change_password.php" class="mt-3 block rounded px-3 py-2 hover:bg-gray-100">Change password</a>
          <a href="settings.php" class="block rounded px-3 py-2 hover:bg-gray-100">Site settings</a>
          <a href="social_links.php" class="block rounded px-3 py-2 font-semibold text-blue-800 hover:bg-gray-100">Social media</a>
          <form action="logout.php" method="POST"><button class="mt-1 w-full rounded px-3 py-2 text-left text-red-600 hover:bg-red-50">Logout</button></form>
        </div>
      </div>
    </div>
  </header>
  <div id="overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden"></div>
  <?php $activeSidebar = 'social'; include __DIR__ . '/sidebar.php'; ?>
  <main id="main-content" class="flex-1 w-full pt-16 px-4 transition-all duration-300">
    <div class="mx-auto max-w-5xl py-8">
      <?php if (isset($_GET['saved'])): ?><p class="mb-4 rounded-lg bg-green-100 p-4 text-green-800">Social media links saved.</p><?php endif; ?>
      <?php if ($error !== ''): ?><p class="mb-4 rounded-lg bg-red-100 p-4 text-red-800"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
      
      <div class="mb-5 flex items-center justify-between gap-3">
        <h1 class="text-xl sm:text-2xl font-bold">Social media links</h1>
        <button type="button" id="toggle-social-form" class="shrink-0 rounded-lg bg-blue-700 px-3 py-2.5 text-sm sm:px-5 sm:py-3 sm:text-base font-semibold text-white hover:bg-blue-800">
          <span id="toggle-social-label">+ Add social media</span>
        </button>
      </div>

      <form method="post" id="social-form" class="mb-6 hidden space-y-5 rounded-xl bg-white p-6 shadow">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['social_links_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="action" id="social-action" value="<?= htmlspecialchars($formMode === 'edit' ? 'edit' : 'add', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="old_platform" id="old-platform" value="">
        <div class="grid gap-5 md:grid-cols-2">
          <label class="block">Platform
            <select name="platform" id="platform-select" required class="mt-1 w-full rounded border bg-white p-3">
              <option value="">Select platform</option>
              <?php foreach ($platformNames as $platform => $name): ?>
                <option value="<?= htmlspecialchars($platform, ENT_QUOTES, 'UTF-8') ?>" <?= $formPlatform === $platform ? 'selected' : '' ?>><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="block">Display name
            <input type="text" id="display-name" readonly value="" placeholder="Selected automatically" class="mt-1 w-full rounded border bg-gray-100 p-3 text-gray-700">
          </label>
        </div>
        <label class="block">Profile URL
          <input type="url" name="url" id="social-url" required maxlength="500" value="<?= htmlspecialchars($formUrl, ENT_QUOTES, 'UTF-8') ?>" placeholder="https://facebook.com/yourpage" class="mt-1 w-full rounded border p-3">
        </label>
        <div class="flex flex-wrap gap-3">
          <button type="submit" id="save-social-button" class="rounded-lg bg-blue-700 px-6 py-3 font-semibold text-white hover:bg-blue-800">Save social link</button>
          <button type="button" id="cancel-social-form" class="rounded-lg border px-6 py-3 font-semibold">Cancel</button>
        </div>
      </form>

      <div class="overflow-hidden rounded-xl bg-white shadow">
        <div class="border-b px-5 py-4 font-semibold">Created social media</div>
        <?php if ($socialRows): ?>
          <div class="divide-y">
            <?php foreach ($socialRows as $row): ?>
              <?php
                $platform = $row['platform'];
                $displayName = $platformNames[$platform] ?? ucfirst(str_replace('_', ' ', $platform));
              ?>
              <div class="flex items-center gap-3 px-4 py-4 sm:px-5">
                <div class="min-w-0">
                  <p class="truncate font-semibold"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></p>
                  <a href="<?= htmlspecialchars($row['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="block max-w-[60vw] truncate text-sm text-blue-700 sm:max-w-xl"><?= htmlspecialchars($row['url'], ENT_QUOTES, 'UTF-8') ?></a>
                </div>
                <div class="ml-auto flex shrink-0 items-center gap-2">
                  <button type="button" class="edit-social flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 text-blue-700 transition hover:bg-blue-50"
                    aria-label="Edit <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                    title="Edit"
                    data-platform="<?= htmlspecialchars($platform, ENT_QUOTES, 'UTF-8') ?>"
                    data-url="<?= htmlspecialchars($row['url'], ENT_QUOTES, 'UTF-8') ?>"
                    data-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 3.5a2.121 2.121 0 013 3L12 16l-4 1 1-4 9.5-9.5z" />
                    </svg>
                  </button>
                  <form method="post" onsubmit="return confirm('Delete this social media link?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['social_links_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="platform" value="<?= htmlspecialchars($platform, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 text-red-600 transition hover:bg-red-50"
                      aria-label="Delete <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                      title="Delete">
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M8 6V4h8v2m-9 0l1 14h8l1-14M10 10v6m4-6v6" />
                      </svg>
                    </button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="px-5 py-8 text-center text-gray-500">No social media links have been added yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </main>
  <footer class="bg-blue-900 py-8 text-center text-sm text-white">&copy; <?= date('Y') ?> <?= htmlspecialchars(strtoupper($siteSettings['site_name']), ENT_QUOTES, 'UTF-8') ?>. All rights reserved.</footer>
  <script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const main = document.getElementById('main-content');
    const headerLeft = document.getElementById('header-left');
    let open = window.innerWidth >= 1024 && localStorage.getItem('adminSidebarClosed') !== '1';
    function layout() {
      const desktop = window.innerWidth >= 1024;
      sidebar.classList.toggle('-translate-x-full', !open);
      overlay.classList.toggle('hidden', desktop || !open);
      main.style.marginLeft = desktop && open ? '256px' : '0';
      main.style.width = desktop && open ? 'calc(100% - 256px)' : '100%';
      headerLeft.style.marginLeft = desktop && open ? '258px' : '0';
    }
    function toggle(value) { open = value; layout(); }
    document.getElementById('menu-btn').addEventListener('click', () => toggle(!open));
    document.getElementById('sidebar-close-btn').addEventListener('click', () => toggle(false));
    overlay.addEventListener('click', () => toggle(false));
    window.addEventListener('resize', layout);
    layout();
    document.getElementById('profile-btn').addEventListener('click', event => {
      event.stopPropagation();
      document.getElementById('profile-menu').classList.toggle('hidden');
    });
    document.addEventListener('click', () => document.getElementById('profile-menu').classList.add('hidden'));

    const socialForm = document.getElementById('social-form');
    const toggleSocialForm = document.getElementById('toggle-social-form');
    const toggleSocialLabel = document.getElementById('toggle-social-label');
    const platformSelect = document.getElementById('platform-select');
    const displayName = document.getElementById('display-name');
    const socialUrl = document.getElementById('social-url');
    const socialAction = document.getElementById('social-action');
    const oldPlatform = document.getElementById('old-platform');
    const saveButton = document.getElementById('save-social-button');

    const platformNames = {
      facebook: 'Facebook',
      telegram: 'Telegram',
      tiktok: 'TikTok',
      youtube: 'YouTube',
      instagram: 'Instagram',
      whatsapp: 'WhatsApp'
    };

    function showSocialForm(mode = 'add', platform = '', url = '') {
      socialForm.classList.remove('hidden');
      toggleSocialLabel.textContent = 'Hide social media form';
      socialAction.value = mode;
      platformSelect.value = platform;
      displayName.value = platformNames[platform] || '';
      socialUrl.value = url;
      oldPlatform.value = mode === 'edit' ? platform : '';
      saveButton.textContent = mode === 'edit' ? 'Update social link' : 'Save social link';
      if (mode === 'add') {
        socialUrl.placeholder = 'https://example.com/yourprofile';
      }
      platformSelect.focus();
    }

    function hideSocialForm() {
      socialForm.classList.add('hidden');
      toggleSocialLabel.textContent = '+ Add social media';
      socialAction.value = 'add';
      platformSelect.value = '';
      displayName.value = '';
      socialUrl.value = '';
      oldPlatform.value = '';
      saveButton.textContent = 'Save social link';
    }

    toggleSocialForm.addEventListener('click', () => {
      if (socialForm.classList.contains('hidden')) {
        showSocialForm();
      } else {
        hideSocialForm();
      }
    });

    document.getElementById('cancel-social-form').addEventListener('click', hideSocialForm);

    platformSelect.addEventListener('change', () => {
      displayName.value = platformNames[platformSelect.value] || '';
    });

    document.querySelectorAll('.edit-social').forEach(button => {
      button.addEventListener('click', () => {
        showSocialForm('edit', button.dataset.platform, button.dataset.url);
        socialForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    });

    <?php if ($error !== '' || $formMode === 'edit'): ?>
    showSocialForm('<?= $formMode === 'edit' ? 'edit' : 'add' ?>', <?= json_encode($formPlatform, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= json_encode($formUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
    <?php endif; ?>
  </script>
</body>
</html>
