<?php
require_once __DIR__ . '/session.php';

// === STATISTICS ===
$total_cars = $pdo->query("SELECT COUNT(*) FROM cars")->fetchColumn();
$featured_cars = $pdo->query("SELECT COUNT(*) FROM cars WHERE featured = 1")->fetchColumn();
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
      position: relative;
      overflow: hidden;
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .stat-card:hover {
      transform: translateY(-8px);
    }

    .stat-card::after {
      content: '';
      position: absolute;
      width: 9rem;
      height: 9rem;
      right: -4.5rem;
      bottom: -5.5rem;
      border-radius: 9999px;
      background: rgba(255, 255, 255, 0.12);
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

      <!-- STAT CARDS - TWO IN ONE ROW ON MOBILE, FOUR ON LARGE -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6 mb-8 md:mb-12">
        <!-- Total Cars - Clickable -->
        <a href="cars.php"
          class="stat-card block rounded-2xl border border-blue-400/20 bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-700 p-4 text-white shadow-lg shadow-blue-900/10 transition md:p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium text-blue-100 md:text-sm">Total Cars</p>
              <p class="mt-2 text-3xl font-extrabold tracking-tight md:text-4xl"><?= $total_cars ?></p>
              <p class="mt-2 hidden text-xs text-blue-100 md:block">View inventory</p>
            </div>
            <svg class="w-10 h-10 md:w-16 md:h-16 text-blue-200 opacity-50" fill="none" stroke="currentColor"
              viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M3 13.5C3 12.12 4.12 11 5.5 11H18.5C19.88 11 21 12.12 21 13.5V15c0 .55-.45 1-1 1h-1.1a2 2 0 01-3.8 0H9.9a2 2 0 01-3.8 0H5c-.55 0-1-.45-1-1v-1.5zM5.5 11l1.5-3.5A2 2 0 009 6h6a2 2 0 012 1.5L19 11" />
              <circle cx="7.5" cy="17" r="1" fill="currentColor" />
              <circle cx="16.5" cy="17" r="1" fill="currentColor" />
            </svg>
          </div>
        </a>

        <!-- Featured Cars -->
        <div
          class="stat-card rounded-2xl border border-emerald-400/20 bg-gradient-to-br from-emerald-400 via-emerald-500 to-teal-700 p-4 text-white shadow-lg shadow-emerald-900/10 md:p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium text-emerald-50 md:text-sm">Featured Cars</p>
              <p class="mt-2 text-3xl font-extrabold tracking-tight md:text-4xl"><?= $featured_cars ?></p>
              <p class="mt-2 hidden text-xs text-emerald-50 md:block">Spotlight vehicles</p>
            </div>
            <svg class="w-10 h-10 md:w-16 md:h-16 text-green-200 opacity-50" fill="none" stroke="currentColor"
              viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
            </svg>
          </div>
        </div>

        <!-- Slides (Hero Images) - Clickable -->
        <a href="hero_images.php"
          class="stat-card block rounded-2xl border border-amber-300/30 bg-gradient-to-br from-amber-300 via-amber-400 to-orange-500 p-4 text-slate-950 shadow-lg shadow-amber-900/10 md:p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium text-amber-950/70 md:text-sm">Hero Slides</p>
              <p class="mt-2 text-3xl font-extrabold tracking-tight md:text-4xl"><?= $total_slides ?></p>
              <p class="mt-2 hidden text-xs text-amber-950/70 md:block">Homepage banners</p>
            </div>
            <svg class="w-10 h-10 md:w-16 md:h-16 text-yellow-200 opacity-60" fill="none" stroke="currentColor"
              viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16v12H4zM10 20h4" />
            </svg>
          </div>
        </a>

        <!-- Messages - Clickable -->
        <a href="messages.php"
          class="stat-card block rounded-2xl border border-violet-400/20 bg-gradient-to-br from-violet-500 via-purple-600 to-fuchsia-700 p-4 text-white shadow-lg shadow-violet-900/10 md:p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium text-violet-100 md:text-sm">Messages</p>
              <p class="mt-2 text-3xl font-extrabold tracking-tight md:text-4xl"><?= $total_messages ?></p>
              <?php if ($unread_messages > 0): ?>
                <p
                  class="text-[10px] md:text-sm mt-1 md:mt-2 bg-red-500 px-2 md:px-4 py-0.5 md:py-1 rounded-full inline-block font-bold">
                  <?= $unread_messages ?>
                  Unread
                </p>
              <?php endif; ?>
            </div>
            <svg class="w-10 h-10 md:w-16 md:h-16 text-purple-200 opacity-50" fill="none" stroke="currentColor"
              viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </div>
        </a>
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
  <footer class="bg-blue-900 text-white py-8 text-center text-sm">
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