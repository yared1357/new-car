<?php
require_once __DIR__ . '/session.php';

// === HANDLE DELETE ===
if (isset($_GET['delete'])) {
  $id = (int) $_GET['delete'];
  $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
  $stmt->execute([$id]);
  header("Location: messages.php?deleted=1");
  exit;
}

// === MARK AS READ ===
if (isset($_GET['read'])) {
  $id = (int) $_GET['read'];
  $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
  $stmt->execute([$id]);
}

// === FETCH MESSAGES ===
$stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY submitted_at DESC");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Messages | <?= htmlspecialchars($siteSettings['site_name'], ENT_QUOTES, 'UTF-8') ?> Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .message-card {
      transition: all 0.3s;
      cursor: pointer;
    }

    .message-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
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

  <?php $activeSidebar = 'messages'; include __DIR__ . '/sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <main id="main-content" class="flex-1 transition-all duration-300 bg-gray-50 w-full pt-16 px-4">
    <div class="max-w-7xl mx-auto py-12">
      <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-center text-gray-800 mb-10 whitespace-nowrap">Customer Messages</h1>

      <!-- SEARCH BAR -->
      <div class="max-w-2xl mx-auto mb-8">
        <input type="text" id="search-input" placeholder="Search by name, email, or message..."
          class="w-full px-5 py-4 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
      </div>

      <!-- MESSAGES GRID -->
      <div id="messages-container" class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($messages as $msg):
          $shortMessage = mb_substr($msg['message'], 0, 80);
          $needsToggle = mb_strlen($msg['message']) > 80;
          $isNew = $msg['is_read'] == 0;
          ?>
          <div
            class="message-card bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 hover:shadow-2xl transition relative <?= $needsToggle ? '' : 'cursor-pointer' ?>"
            data-id="<?= $msg['id'] ?>" data-name="<?= htmlspecialchars(strtolower($msg['name'])) ?>"
            data-email="<?= htmlspecialchars(strtolower($msg['email'])) ?>"
            data-phone="<?= htmlspecialchars(strtolower($msg['phone'] ?? '')) ?>"
            data-message="<?= htmlspecialchars(strtolower($msg['message'])) ?>" <?= !$needsToggle && $isNew ? "onclick=\"location.href='?read={$msg['id']}'\"" : '' ?>>
            <?php if ($isNew): ?>
              <span
                class="absolute -top-3 -right-3 bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg animate-pulse">
                NEW
              </span>
            <?php endif; ?>

            <div class="p-6">
              <div class="flex justify-between items-start mb-4">
                <h3 class="font-bold text-lg text-gray-800 truncate max-w-[70%]"><?= htmlspecialchars($msg['name']) ?>
                </h3>
                <span class="text-xs text-gray-500"><?= date('M j, Y', strtotime($msg['submitted_at'])) ?></span>
              </div>

              <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                <strong>Email:</strong>
                <?= htmlspecialchars($msg['email']) ?: '<span class="text-gray-400">(not provided)</span>' ?>
                <?php if (!empty($msg['phone'])): ?>
                  <br><strong>Phone:</strong> <?= htmlspecialchars($msg['phone']) ?>
                <?php endif; ?>
              </p>

              <p class="text-gray-700 dark:text-gray-200 text-base leading-relaxed message-preview">
                <span class="short-text"><?= htmlspecialchars($shortMessage) ?><?= $needsToggle ? '...' : '' ?></span>
                <span class="full-text hidden"><?= nl2br(htmlspecialchars($msg['message'])) ?></span>
              </p>

              <?php if ($needsToggle): ?>
                <div class="mt-5">
                  <button class="read-more-btn text-primary font-semibold hover:underline text-sm">
                    Read More
                  </button>
                </div>
              <?php endif; ?>

              <div class="mt-4 flex justify-end">
                <span class="seen-status text-green-600 font-bold text-sm <?= $msg['is_read'] ? '' : 'hidden' ?>">
                  Seen
                </span>
              </div>

              <div class="mt-5 pt-4 border-t border-gray-200">
                <button type="button"
                  class="delete-btn w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-xl font-medium transition"
                  data-id="<?= $msg['id'] ?>">
                  Delete Message
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

        <?php if (empty($messages)): ?>
          <p class="col-span-full text-center text-gray-500 text-xl mt-20">No messages yet.</p>
        <?php endif; ?>
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
    applyLayout();

    // Profile dropdown
    document.getElementById('profile-btn').addEventListener('click', (e) => {
      e.stopPropagation();
      document.getElementById('profile-menu').classList.toggle('hidden');
    });
    document.addEventListener('click', () => document.getElementById('profile-menu').classList.add('hidden'));

    // Success on delete
    const params = new URLSearchParams(window.location.search);
    if (params.get('deleted') === '1') {
      Swal.fire({ icon: 'success', title: 'Deleted!', text: 'Message deleted.', timer: 3000, showConfirmButton: false });
    }

    // Read More / Less + Mark as Read for long messages
    document.querySelectorAll('.read-more-btn').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        const card = this.closest('.message-card');
        const short = card.querySelector('.short-text');
        const full = card.querySelector('.full-text');
        const seen = card.querySelector('.seen-status');
        const id = card.dataset.id;

        short.classList.toggle('hidden');
        full.classList.toggle('hidden');
        this.textContent = full.classList.contains('hidden') ? 'Read More' : 'Read Less';

        if (seen.classList.contains('hidden')) {
          fetch(`?read=${id}`)
            .then(() => {
              seen.classList.remove('hidden');
              const badge = card.querySelector('.bg-green-500');
              if (badge) badge.remove();
            });
        }
      });
    });

    // Delete Confirmation
    document.querySelectorAll('.delete-btn').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        const id = this.dataset.id;
        Swal.fire({
          title: 'Delete Message?',
          text: "This action cannot be undone.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          confirmButtonText: 'Yes, delete'
        }).then(result => {
          if (result.isConfirmed) {
            window.location.href = `?delete=${id}`;
          }
        });
      });
    });

    // Live Search
    const searchInput = document.getElementById('search-input');
    const container = document.getElementById('messages-container');
    searchInput.addEventListener('input', function () {
      const query = this.value.toLowerCase().trim();
      let visible = 0;
      document.querySelectorAll('.message-card').forEach(card => {
        const text = (card.dataset.name + ' ' + card.dataset.email + ' ' + card.dataset.phone + ' ' + card.dataset.message).toLowerCase();
        if (text.includes(query)) {
          card.style.display = '';
          visible++;
        } else {
          card.style.display = 'none';
        }
      });
      let noResult = container.querySelector('.no-result');
      if (visible === 0 && query !== '') {
        if (!noResult) {
          noResult = document.createElement('p');
          noResult.className = 'no-result col-span-full text-center text-gray-500 text-lg mt-10';
          noResult.textContent = 'No messages found.';
          container.appendChild(noResult);
        }
      } else if (noResult) {
        noResult.remove();
      }
    });
  </script>
</body>

</html>