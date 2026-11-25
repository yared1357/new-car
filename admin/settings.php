<?php
require_once __DIR__ . '/session.php';

if ($admin['role'] !== 'super_admin') {
    http_response_code(403);
    echo 'Only the super admin can change site settings.';
    exit;
}

if (!isset($_SESSION['settings_csrf_token'])) {
    $_SESSION['settings_csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$fields = [
    'site_name',
    'tagline',
    'contact_phone',
    'contact_email',
    'address',
    'primary_color',
    'accent_color',
    'header_color',
    'footer_color',
];
$values = $siteSettings;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['settings_csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        $error = 'Your form session expired. Reload the page and try again.';
    } else {
        foreach ($fields as $field) {
            $values[$field] = trim($_POST[$field] ?? '');
        }

        if ($values['site_name'] === '' || strlen($values['site_name']) > 100) {
            $error = 'Enter a site name of up to 100 characters.';
        } elseif (strlen($values['tagline']) > 160) {
            $error = 'The tagline must be 160 characters or fewer.';
        } elseif (strlen($values['contact_phone']) > 30 || ($values['contact_phone'] !== '' && !preg_match('/^[0-9+()\-\s]{6,30}$/', $values['contact_phone']))) {
            $error = 'Enter a valid contact phone number.';
        } elseif ($values['contact_email'] !== '' && !filter_var($values['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid contact email address.';
        } elseif (strlen($values['address']) > 255) {
            $error = 'Address must be 255 characters or fewer.';
        } elseif (
            !preg_match('/^#[0-9a-fA-F]{6}$/', $values['primary_color'])
            || !preg_match('/^#[0-9a-fA-F]{6}$/', $values['accent_color'])
            || !preg_match('/^#[0-9a-fA-F]{6}$/', $values['header_color'])
            || !preg_match('/^#[0-9a-fA-F]{6}$/', $values['footer_color'])
        ) {
            $error = 'Choose valid six-digit hex colors for the theme, header, and footer.';
        }

        if ($error === '' && isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK || $_FILES['logo']['size'] > 2 * 1024 * 1024) {
                $error = 'The logo upload failed or is larger than 2 MB.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['logo']['tmp_name']);
                $extensions = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'image/gif' => 'gif',
                ];

                if (!isset($extensions[$mime])) {
                    $error = 'Upload a PNG, JPG, WEBP, or GIF logo.';
                } else {
                    $logoDirectory = __DIR__ . '/../uploads';
                    if (!is_dir($logoDirectory) && !mkdir($logoDirectory, 0755, true) && !is_dir($logoDirectory)) {
                        $error = 'Could not create the logo upload directory.';
                    } else {
                        $logoPath = 'uploads/site_logo_' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
                        $destination = __DIR__ . '/../' . $logoPath;
                        if (!move_uploaded_file($_FILES['logo']['tmp_name'], $destination)) {
                            error_log('Could not move uploaded logo to ' . $destination);
                            $error = 'XAMPP could not move the uploaded logo into the uploads folder. Check the Apache error log and confirm the folder is writable.';
                        } else {
                            $values['logo_path'] = $logoPath;
                        }
                    }
                }
            }
        }

        if ($error === '') {
            $stmt = $pdo->prepare(
                'INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
            );
            foreach ($fields as $field) {
                $stmt->execute([$field, $values[$field]]);
            }
            if (isset($logoPath)) {
                $stmt->execute(['logo_path', $logoPath]);
            }

            header('Location: settings.php?saved=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Site Settings | <?= htmlspecialchars($siteSettings['site_name'], ENT_QUOTES, 'UTF-8') ?> Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: { sans: ['Inter', 'sans-serif'] },
          colors: { primary: '#1E40AF', accent: '#F59E0B' },
        },
      },
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
          <svg class="w-7 h-7 lg:w-8 lg:h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <a href="dashboard.php" class="ml-2 lg:ml-3 flex items-center space-x-3 lg:space-x-4">
          <img src="<?= htmlspecialchars($adminLogoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Logo"
            class="w-9 h-9 sm:w-10 sm:h-10 lg:w-12 lg:h-12 xl:w-14 xl:h-14 object-contain">
          <span class="text-primary font-extrabold text-sm sm:text-base lg:text-2xl xl:text-3xl whitespace-nowrap tracking-tight uppercase"><?= htmlspecialchars($siteSettings['site_name'], ENT_QUOTES, 'UTF-8') ?></span>
        </a>
      </div>
      <div class="pr-4 text-gray-900">
        <div class="relative">
          <button id="profile-btn" class="p-2 rounded-full hover:bg-gray-100 transition" aria-label="Open profile menu">
            <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-full bg-primary flex items-center justify-center text-white font-bold text-lg lg:text-xl shadow-lg">
              <?= htmlspecialchars(strtoupper(substr($displayName, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
            </div>
          </button>
          <div id="profile-menu" class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-2xl border border-gray-200 hidden z-50 overflow-hidden">
            <div class="p-5 bg-gradient-to-br from-primary to-blue-700 text-white">
              <p class="font-bold text-lg"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></p>
              <p class="text-sm opacity-90"><?= htmlspecialchars($admin['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
              <p class="text-sm opacity-90 mt-1">Phone: <?= htmlspecialchars($admin['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
              <p class="mt-1 text-xs font-semibold uppercase tracking-wide">Super admin</p>
            </div>
            <div class="p-2">
              <a href="change_password.php" class="block rounded-lg px-4 py-3 transition hover:bg-gray-100">Change Password</a>
              <a href="settings.php" class="block rounded-lg bg-primary px-4 py-3 font-semibold text-white">Site settings</a>
              <form action="logout.php" method="POST" class="mt-1">
                <button class="w-full rounded-lg px-4 py-3 text-left text-red-600 transition hover:bg-red-50">Logout</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <div id="overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden"></div>
  <?php $activeSidebar = 'settings'; include __DIR__ . '/sidebar.php'; ?>

  <main id="main-content" class="flex-1 transition-all duration-300 bg-gray-100 dark:bg-gray-900 w-full pt-16 px-4">
    <div class="mx-auto max-w-5xl py-8">
    <h1 class="mb-2 text-2xl sm:text-3xl font-bold">Site settings</h1>
    <p class="mb-6 text-gray-600 dark:text-gray-300">Update the public site identity, logo, contact details, and theme colors.</p>

    <?php if (isset($_GET['saved'])): ?>
      <p class="mb-4 rounded-lg bg-green-100 p-4 text-green-800">Settings saved.</p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
      <p class="mb-4 rounded-lg bg-red-100 p-4 text-red-800"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="space-y-6 rounded-xl bg-white p-6 shadow">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['settings_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
      <div class="grid gap-5 md:grid-cols-2">
        <label class="block">Site name
          <input name="site_name" maxlength="100" required value="<?= htmlspecialchars($values['site_name'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded border p-3">
        </label>
        <label class="block">Tagline
          <input name="tagline" maxlength="160" value="<?= htmlspecialchars($values['tagline'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded border p-3">
        </label>
        <label class="block">Contact phone
          <input name="contact_phone" maxlength="30" value="<?= htmlspecialchars($values['contact_phone'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded border p-3">
        </label>
        <label class="block">Contact email
          <input type="email" name="contact_email" maxlength="100" value="<?= htmlspecialchars($values['contact_email'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded border p-3">
        </label>
        <label class="block md:col-span-2">Address
          <input name="address" maxlength="255" value="<?= htmlspecialchars($values['address'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded border p-3">
        </label>
        <label class="block">Primary accent color
          <input type="color" name="primary_color" value="<?= htmlspecialchars($values['primary_color'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 h-12 w-full rounded border p-1">
        </label>
        <label class="block">Secondary accent color
          <input type="color" name="accent_color" value="<?= htmlspecialchars($values['accent_color'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 h-12 w-full rounded border p-1">
        </label>
        <label class="block">Header background color
          <input type="color" name="header_color" value="<?= htmlspecialchars($values['header_color'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 h-12 w-full rounded border p-1">
        </label>
        <label class="block">Footer background color
          <input type="color" name="footer_color" value="<?= htmlspecialchars($values['footer_color'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 h-12 w-full rounded border p-1">
        </label>
        <label class="block md:col-span-2">Logo image (PNG, JPG, WEBP, or GIF; max 2 MB)
          <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/gif" class="mt-1 block w-full rounded border p-3">
          <span class="mt-2 block text-sm text-gray-500">Current logo: <?= htmlspecialchars($values['logo_path'], ENT_QUOTES, 'UTF-8') ?></span>
        </label>
      </div>
      <div class="flex flex-wrap gap-3">
        <button type="submit" class="rounded-lg bg-blue-700 px-6 py-3 font-semibold text-white hover:bg-blue-800">Save settings</button>
        <a href="social_links.php" class="rounded-lg border px-6 py-3 font-semibold">Configure Social Media</a>
         </div>
    </form>
    </div>
  </main>
  <footer class="bg-blue-900 py-8 text-center text-sm text-white">
    &copy; <?= date('Y') ?> <?= htmlspecialchars(strtoupper($siteSettings['site_name']), ENT_QUOTES, 'UTF-8') ?>. All rights reserved.
  </footer>
  <script>
    const menuBtn = document.getElementById('menu-btn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const mainContent = document.getElementById('main-content');
    const headerLeft = document.getElementById('header-left');
    const sidebarCloseBtn = document.getElementById('sidebar-close-btn');
    let isSidebarOpen = window.innerWidth >= 1024 && localStorage.getItem('adminSidebarClosed') !== '1';

    function applyLayout() {
      if (window.innerWidth >= 1024) {
        overlay.classList.add('hidden');
        if (isSidebarOpen) {
          mainContent.style.marginLeft = '256px';
          mainContent.style.width = 'calc(100% - 256px)';
          sidebar.classList.remove('-translate-x-full');
          headerLeft.style.marginLeft = '258px';
        } else {
          mainContent.style.marginLeft = '0';
          mainContent.style.width = '100%';
          sidebar.classList.add('-translate-x-full');
          headerLeft.style.marginLeft = '0';
        }
      } else {
        mainContent.style.marginLeft = '';
        mainContent.style.width = '';
        headerLeft.style.marginLeft = '0';
        sidebar.classList.toggle('-translate-x-full', !isSidebarOpen);
      }
    }

    function setSidebarOpen(open) {
      isSidebarOpen = open;
      if (window.innerWidth < 1024) overlay.classList.toggle('hidden', !open);
      applyLayout();
    }

    menuBtn.addEventListener('click', () => setSidebarOpen(!isSidebarOpen));
    sidebarCloseBtn.addEventListener('click', () => setSidebarOpen(false));
    overlay.addEventListener('click', () => setSidebarOpen(false));
    window.addEventListener('resize', applyLayout);
    applyLayout();
    document.getElementById('profile-btn').addEventListener('click', (event) => {
      event.stopPropagation();
      document.getElementById('profile-menu').classList.toggle('hidden');
    });
    document.addEventListener('click', () => document.getElementById('profile-menu').classList.add('hidden'));
  </script>
</body>
</html>
