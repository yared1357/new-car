<?php
require_once __DIR__ . '/session.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm) {
        $error = "New passwords do not match.";
    } elseif (strlen($new) < 4) {
        $error = "New password must be at least 4 characters.";
    } else {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $user = $stmt->fetch();

        if ($user && password_verify($current, $user['password'])) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ?, force_password_change = 0 WHERE id = ?");
            $update->execute([$hashed, $_SESSION['admin_id']]);
            $message = "Password changed successfully!";
            if (isset($_GET['forced'])) {
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Change Password | <?= htmlspecialchars($siteSettings['site_name'], ENT_QUOTES, 'UTF-8') ?> Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { primary: '#1E40AF', accent: '#F59E0B' },
                    maxWidth: { '80vw': '80vw', '98vw': '98vw' },
                },
            },
        };
    </script>
</head>

<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col">
  <?php include __DIR__ . '/header.php'; ?>
  <header class="fixed top-0 left-0 right-0 z-50 hidden bg-white shadow-lg border-b-2 border-gray-300">
    <div class="w-full flex items-center justify-between h-16 lg:h-20 px-2">
      <div id="header-left" class="flex items-center transition-all duration-300">
        <button id="menu-btn" class="h-full px-4 lg:px-5 hover:bg-gray-100 transition rounded-lg">
          <svg class="w-7 h-7 lg:w-8 lg:h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <a href="dashboard.php" class="ml-2 lg:ml-3 flex items-center space-x-3 lg:space-x-4">
          <img src="<?= htmlspecialchars($adminLogoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Logo"
            class="w-9 h-9 sm:w-10 sm:h-10 lg:w-12 lg:h-12 xl:w-14 xl:h-14 object-contain">
          <span
            class="text-primary font-extrabold text-sm sm:text-base lg:text-2xl xl:text-3xl whitespace-nowrap tracking-tight uppercase">Smart
            Car Selling</span>
        </a>
      </div>
      <div class="pr-4 text-gray-900">
        <div class="relative">
          <button id="profile-btn" class="p-2 rounded-full hover:bg-gray-100 transition">
            <div
              class="w-10 h-10 lg:w-12 lg:h-12 rounded-full bg-primary flex items-center justify-center text-white font-bold text-lg lg:text-xl shadow-lg">
              <?php echo strtoupper(substr($displayName, 0, 1)); ?>
            </div>
          </button>
          <div id="profile-menu"
            class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-2xl border border-gray-200 hidden z-50 overflow-hidden">
            <div class="p-5 bg-gradient-to-br from-primary to-blue-700 text-white">
              <div class="flex items-center gap-4">
                <div
                  class="w-16 h-16 rounded-full bg-white/20 backdrop-blur flex items-center justify-center text-3xl font-bold">
                  <?php echo strtoupper(substr($displayName, 0, 1)); ?>
                </div>
                <div>
                  <p class="font-bold text-lg">
                    <?php echo htmlspecialchars($displayName); ?>
                  </p>
                  <p class="text-sm opacity-90">
                    <?php echo htmlspecialchars($admin['email']); ?>
                  </p>
                  <?php if (!empty($admin['phone'])): ?>
                    <p class="text-sm opacity-90 mt-1">Phone:
                      <?php echo htmlspecialchars($admin['phone']); ?>
                    </p>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <div class="p-2">
              <a href="change_password.php"
                class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Change Password
              </a>
              <?php if ($admin['role'] === 'super_admin'): ?>
                <a href="settings.php" class="block rounded-lg px-4 py-3 transition hover:bg-gray-100">Site settings</a>
              <?php endif; ?>
              <form action="logout.php" method="POST" class="mt-1">
                <button
                  class="w-full flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg transition border-none bg-transparent cursor-pointer">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                  </svg>
                  Logout
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
    
    <!-- OVERLAY -->
    <div id="overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden"></div>

    <!-- SIDEBAR - MATCH cars.php (no Change Password or Logout here) -->
    <?php $activeSidebar = 'password'; include __DIR__ . '/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main id="main-content" class="flex-1 transition-all duration-300 bg-gray-50 w-full pt-16">
        <div class="max-w-4xl mx-auto py-12 px-6">
            <div class="bg-white rounded-2xl shadow-xl p-8 border">
                <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-center text-gray-800 mb-8 whitespace-nowrap">Change Password</h1>

                <?php if ($message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 text-center">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 text-center">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div class="relative">
                        <label class="block text-sm font-medium mb-2">Current Password</label>
                        <input type="password" name="current_password" required
                            class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-primary pr-12">
                        <button type="button" class="toggle-pass absolute right-3 top-10 text-gray-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <div class="relative">
                        <label class="block text-sm font-medium mb-2">New Password</label>
                        <input type="password" name="new_password" required minlength="6"
                            class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-primary pr-12">
                        <button type="button" class="toggle-pass absolute right-3 top-10 text-gray-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <div class="relative">
                        <label class="block text-sm font-medium mb-2">Confirm New Password</label>
                        <input type="password" name="confirm_password" required minlength="6"
                            class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-primary pr-12">
                        <button type="button" class="toggle-pass absolute right-3 top-10 text-gray-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <button type="submit"
                        class="w-full bg-primary hover:bg-blue-700 text-white font-bold py-4 rounded-xl transition transform hover:scale-105 shadow-lg">
                        Change Password
                    </button>
                </form>
            </div>
        </div>

    </main>

    <!-- FOOTER -->
    <footer class="bg-blue-900 text-white py-8 text-center text-sm">
        &copy; <?= date('Y') ?> <?= htmlspecialchars(strtoupper($siteSettings['site_name']), ENT_QUOTES, 'UTF-8') ?>. All rights reserved.
    </footer>

    <script>
        const menuBtn = document.getElementById('menu-btn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const mainContent = document.getElementById('main-content');
        const headerLeft = document.getElementById('header-left');
        const sidebarCloseBtn = document.getElementById('sidebar-close-btn');
        const SIDEBAR_WIDTH = 256; // 16rem
        let isSidebarOpen = window.innerWidth >= 1024 && localStorage.getItem('adminSidebarClosed') !== '1';

        if (isSidebarOpen) {
            sidebar.classList.remove('-translate-x-full');
        } else {
            sidebar.classList.add('-translate-x-full');
        }

        function applyLayout() {
            if (window.innerWidth >= 1024) {
                overlay.classList.add('hidden');
                if (isSidebarOpen) {
                    mainContent.style.marginLeft = `${SIDEBAR_WIDTH}px`;
                    mainContent.style.width = `calc(100% - ${SIDEBAR_WIDTH}px)`;
                    sidebar.classList.remove('-translate-x-full');
                    if (headerLeft) headerLeft.style.marginLeft = `${SIDEBAR_WIDTH + 2}px`;
                } else {
                    mainContent.style.marginLeft = '0';
                    mainContent.style.width = '100%';
                    sidebar.classList.add('-translate-x-full');
                    if (headerLeft) headerLeft.style.marginLeft = '0';
                }
            } else {
                mainContent.style.marginLeft = '';
                mainContent.style.width = '';
                if (!isSidebarOpen) {
                    sidebar.classList.add('-translate-x-full');
                }
                // On small screens keep header content near the burger
                if (headerLeft) headerLeft.style.marginLeft = '0';
            }
        }

        function openSidebar() {
            isSidebarOpen = true;
            sidebar.classList.remove('-translate-x-full');
            if (window.innerWidth < 1024) {
                overlay.classList.remove('hidden');
            }
            applyLayout();
        }

        function closeSidebar() {
            isSidebarOpen = false;
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            applyLayout();
        }

        menuBtn.addEventListener('click', () => {
            sidebar.classList.contains('-translate-x-full') ? openSidebar() : closeSidebar();
        });

        // Sidebar open/close via burger, overlay, and small-screen close button
        sidebarCloseBtn?.addEventListener('click', closeSidebar);
        overlay.addEventListener('click', closeSidebar);

        function handleResize() {
            if (window.innerWidth >= 1024 && isSidebarOpen) {
                sidebar.classList.remove('-translate-x-full');
            }
            if (window.innerWidth < 1024 && !isSidebarOpen) {
                sidebar.classList.add('-translate-x-full');
            }
            overlay.classList.add('hidden');
            applyLayout();
        }

        window.addEventListener('resize', handleResize);
        applyLayout(); // Initial layout

        document.getElementById('profile-btn').addEventListener('click', (e) => {
            e.stopPropagation();
            document.getElementById('profile-menu').classList.toggle('hidden');
        });

        document.addEventListener('click', () => document.getElementById('profile-menu').classList.add('hidden'));

        document.querySelectorAll('.toggle-pass').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = btn.previousElementSibling;
                input.type = input.type === 'password' ? 'text' : 'password';
            });
        });

        <?php if ($message): ?>
            Swal.fire('Success!', '<?= addslashes($message) ?>', 'success');
        <?php endif; ?>
    </script>
</body>

</html>