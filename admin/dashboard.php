<?php
require_once __DIR__ . '/session.php';

// === STATISTICS ===
$total_cars = $pdo->query("SELECT COUNT(*) FROM cars")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$total_super_admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'super_admin'")->fetchColumn();
$total_messages = $pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
$unread_messages = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();
$total_slides = $pdo->query("SELECT COUNT(*) FROM hero_images")->fetchColumn();
$recent_messages = $pdo->query("SELECT * FROM contact_messages ORDER BY submitted_at DESC LIMIT 2")->fetchAll();

// === ALL 12 MONTHS (Current Year) ===
$monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$monthlyData = array_fill(0, 12, 0);

$stmt = $pdo->query("
    SELECT MONTH(created_at) AS month_num, COUNT(*) AS count 
    FROM cars 
    WHERE YEAR(created_at) = YEAR(CURDATE())
    GROUP BY MONTH(created_at)
");
foreach ($stmt->fetchAll() as $row) {
  $monthlyData[$row['month_num'] - 1] = (int) $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard | <?= htmlspecialchars($siteSettings['site_name'], ENT_QUOTES, 'UTF-8') ?> Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    .stat-card {
      transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }

    .stat-card:hover {
      transform: translateY(-2px);
      border-color: #cbd5e1;
      box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    }

    /* Responsive Chart Height */
    #salesChart {
      height: 220px !important;
    }

    @media (min-width: 1024px) {
      #salesChart {
        height: 320px !important;
      }
    }

    /* Scrollbars */
    .scrollbar-thin::-webkit-scrollbar {
      height: 8px;
      width: 8px;
    }

    .scrollbar-thin::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    .scrollbar-thin::-webkit-scrollbar-thumb {
      background: #c1c1c1;
      border-radius: 10px;
    }

    .scrollbar-thin::-webkit-scrollbar-thumb:hover {
      background: #a1a1a1;
    }

    * {
      scrollbar-width: thin;
      scrollbar-color: #c1c1c1 #f1f1f1;
    }
  </style>
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
            class="text-primary font-extrabold text-sm sm:text-base lg:text-2xl xl:text-3xl whitespace-nowrap tracking-tight uppercase"><?= htmlspecialchars($siteSettings['site_name'], ENT_QUOTES, 'UTF-8') ?></span>
        </a>
      </div>
      <div class="pr-2 text-gray-900">
        <div class="relative">
          <button id="profile-btn" class="p-2 rounded-full hover:bg-gray-100 transition">
            <div
              class="w-10 h-10 lg:w-12 lg:h-12 rounded-full bg-primary flex items-center justify-center text-white font-bold text-lg lg:text-xl shadow-lg">
              <?php echo strtoupper(substr($displayName, 0, 1)); ?>
            </div>
          </button>
          <div id="profile-menu"
            class="absolute right-0 z-50 mt-3 hidden overflow-hidden rounded-2xl border border-slate-200 bg-white text-gray-900 shadow-2xl"
            style="width: min(20rem, calc(100vw - 1rem)); max-width: calc(100vw - 1rem);">
            <div class="bg-slate-50 px-6 py-7 text-center">
              <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary text-2xl font-bold text-white ring-4 ring-blue-100">
                <?php echo htmlspecialchars(strtoupper(substr($displayName, 0, 1))); ?>
              </div>
              <p class="mt-3 break-words text-lg font-bold text-slate-900">
                <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>
              </p>
              <p class="mt-1 break-all text-sm text-slate-500">
                <?= htmlspecialchars($admin['email'] ?: 'No email address', ENT_QUOTES, 'UTF-8') ?>
              </p>
              <?php if (!empty($admin['phone'])): ?>
                <p class="mt-1 text-sm text-slate-500">Phone: <?= htmlspecialchars($admin['phone'], ENT_QUOTES, 'UTF-8') ?></p>
              <?php endif; ?>
              <span class="mt-3 inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-amber-800">
                <?= $admin['role'] === 'super_admin' ? 'Super admin' : 'Admin' ?>
              </span>
            </div>
            <div class="space-y-2 p-4">
              <a href="change_password.php"
                class="flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-800">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Change Password
              </a>
              <?php if ($admin['role'] === 'super_admin'): ?>
                <a href="settings.php"
                  class="flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-800">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15.5A3.5 3.5 0 1012 8a3.5 3.5 0 000 7.5zm0-13v2m0 15v2m10-9.5h-2m-16 0H2m17-7l-1.5 1.5M6.5 17.5L5 19m14 0l-1.5-1.5M6.5 6.5L5 5"/>
                  </svg>
                  Site settings
                </a>
              <?php endif; ?>
              <form action="logout.php" method="POST" data-confirm-title="Log out?" data-confirm-message="Are you sure you want to log out of the admin panel?" data-confirm-button="Log out">
                <button
                  class="flex w-full items-center justify-center gap-2 rounded-xl border border-red-100 px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
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

  <?php $activeSidebar = 'dashboard'; include __DIR__ . '/sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <main id="main-content" class="flex-1 transition-all duration-300 bg-white w-full pt-16 px-4">
    <div class="max-w-7xl mx-auto py-8">

      <div class="mb-6 flex items-end justify-between lg:mb-8">
        <div>
          <p class="mb-1 text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Overview</p>
          <h1 class="text-2xl font-extrabold text-slate-900 sm:text-3xl lg:text-4xl">Admin Dashboard</h1>
        </div>
        <p class="hidden rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-500 sm:block">
          <?= date('l, F j, Y') ?>
        </p>
      </div>

      <!-- Dashboard statistics -->
      <div class="mb-8 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5 md:mb-12 md:gap-5">
        <a href="cars.php" class="stat-card rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
          <p class="text-sm font-semibold text-slate-500">Total Cars</p>
          <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900 md:text-4xl"><?= (int) $total_cars ?></p>
          <p class="mt-2 text-xs text-slate-400">View inventory</p>
        </a>
        <a href="hero_images.php" class="stat-card rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
          <p class="text-sm font-semibold text-slate-500">Hero Slides</p>
          <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900 md:text-4xl"><?= (int) $total_slides ?></p>
          <p class="mt-2 text-xs text-slate-400">Homepage banners</p>
        </a>
        <a href="messages.php" class="stat-card rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
          <p class="text-sm font-semibold text-slate-500">Messages</p>
          <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900 md:text-4xl"><?= (int) $total_messages ?></p>
          <p class="mt-2 text-xs text-slate-400"><?= (int) $unread_messages ?> unread</p>
        </a>
        <div class="stat-card rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
          <p class="text-sm font-semibold text-slate-500">Admin Accounts</p>
          <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900 md:text-4xl"><?= (int) $total_admins ?></p>
          <p class="mt-2 text-xs text-slate-400">Administrator users</p>
        </div>
        <div class="stat-card rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
          <p class="text-sm font-semibold text-slate-500">Super Admin Accounts</p>
          <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900 md:text-4xl"><?= (int) $total_super_admins ?></p>
          <p class="mt-2 text-xs text-slate-400">Super administrator users</p>
        </div>
      </div>

      <!-- CHART + MESSAGES -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        <!-- Monthly Chart -->
        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-lg shadow-slate-200/50">
          <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
            <div>
              <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Performance</p>
              <h3 class="mt-1 text-lg font-bold text-slate-800">Cars Added This Year</h3>
            </div>
            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700"><?= date('Y') ?></span>
          </div>
          <div class="p-5 sm:p-6">
            <canvas id="salesChart"></canvas>
          </div>
        </div>

        <!-- Recent Messages -->
        <div class="flex flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-lg shadow-slate-200/50">
          <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
            <div>
              <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Inbox</p>
              <h3 class="mt-1 text-lg font-bold text-slate-800">Recent Messages</h3>
            </div>
            <?php if ($unread_messages > 0): ?>
              <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-600"><?= $unread_messages ?> unread</span>
            <?php endif; ?>
          </div>
          <div class="flex-1 overflow-y-auto scrollbar-thin p-6 space-y-4">
            <?php foreach ($recent_messages as $msg): ?>
              <div
                class="flex items-start space-x-4 rounded-xl border p-4 <?= !$msg['is_read'] ? 'border-emerald-100 bg-emerald-50/70' : 'border-slate-100 bg-slate-50/70' ?>">
                <div
                  class="bg-gradient-to-br from-primary to-blue-700 text-white w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg shadow-md">
                  <?= strtoupper(substr($msg['name'], 0, 2)) ?>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="font-bold text-gray-800 truncate"><?= htmlspecialchars($msg['name']) ?></p>
                  <p class="text-sm text-gray-600 truncate"><?= htmlspecialchars(mb_substr($msg['message'], 0, 80)) ?>...
                  </p>
                  <p class="text-xs text-gray-500 mt-1"><?= date('M j, Y • g:i A', strtotime($msg['submitted_at'])) ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="p-4 border-t border-gray-200">
            <a href="messages.php"
              class="block w-full rounded-xl bg-slate-900 py-3.5 text-center font-semibold text-white shadow-md transition hover:bg-blue-700">
              View All Messages <?= $total_messages > 2 ? "($total_messages)" : '' ?>
            </a>
          </div>
        </div>
      </div>

    </div>
  </main>

  <!-- FOOTER -->
  <footer class="bg-[#0f2a3d] text-white py-8 text-center text-sm">
    &copy; <?= date('Y') ?> <?= htmlspecialchars(strtoupper($siteSettings['site_name']), ENT_QUOTES, 'UTF-8') ?>. All rights reserved.
  </footer>


  <!-- SCRIPTS -->
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

    // Profile dropdown
    document.getElementById('profile-btn').addEventListener('click', (e) => {
      e.stopPropagation();
      document.getElementById('profile-menu').classList.toggle('hidden');
    });

    document.addEventListener('click', () => document.getElementById('profile-menu').classList.add('hidden'));

    // Chart - Fixed Height
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($monthNames) ?>,
        datasets: [{
          label: 'Cars Added',
          data: <?= json_encode($monthlyData) ?>,
          backgroundColor: 'rgba(37, 99, 235, 0.88)',
          hoverBackgroundColor: '#1D4ED8',
          borderRadius: 8,
          borderWidth: 0,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#0F172A',
            padding: 12,
            cornerRadius: 10
          }
        },
        scales: {
          x: { grid: { display: false } },
          y: {
            beginAtZero: true,
            ticks: { stepSize: 1, precision: 0 },
            grid: { color: 'rgba(148, 163, 184, 0.18)' },
            border: { display: false }
          }
        }
      }
    });
  </script>
</body>

</html>