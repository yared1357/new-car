<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('../service-worker.js').catch(() => {});
    });
  }

  (() => {
    const closed = localStorage.getItem('adminSidebarClosed') === '1';
    document.documentElement.classList.toggle('admin-sidebar-closed', closed);

    window.addEventListener('DOMContentLoaded', () => {
      requestAnimationFrame(() => document.documentElement.classList.add('admin-layout-ready'));

      const menuButton = document.getElementById('menu-btn');
      const closeButton = document.getElementById('sidebar-close-btn');
      const overlay = document.getElementById('overlay');

      const setClosed = (value) => {
        document.documentElement.classList.toggle('admin-sidebar-closed', value);
        if (window.innerWidth >= 1024) {
          localStorage.setItem('adminSidebarClosed', value ? '1' : '0');
        }
      };

      menuButton?.addEventListener('click', () => {
        if (window.innerWidth >= 1024) {
          setClosed(!document.documentElement.classList.contains('admin-sidebar-closed'));
        }
      }, true);
      closeButton?.addEventListener('click', () => {
        if (window.innerWidth >= 1024) setClosed(true);
        else document.documentElement.classList.remove('admin-sidebar-closed');
      }, true);
      overlay?.addEventListener('click', () => {
        if (window.innerWidth >= 1024) setClosed(true);
        else document.documentElement.classList.remove('admin-sidebar-closed');
      }, true);
    });
  })();
</script>
<style>
  @media (min-width: 1024px) {
    html:not(.admin-sidebar-closed) #sidebar {
      transform: translateX(0) !important;
    }
  }
  @media (min-width: 1024px) {
    html.admin-sidebar-closed #sidebar {
      transform: translateX(-100%) !important;
    }
  }
  html:not(.admin-layout-ready) #sidebar,
  html:not(.admin-layout-ready) #main-content,
  html:not(.admin-layout-ready) #header-left {
    transition: none !important;
  }
</style>
<header class="fixed left-0 right-0 top-0 z-50 border-b border-slate-200 bg-white shadow-sm">
  <div class="flex h-16 w-full items-center justify-between px-2 lg:h-16">
    <div id="header-left" class="flex items-center transition-all duration-300">
      <button id="menu-btn" class="flex h-12 w-12 items-center justify-center rounded-lg text-slate-600 transition" aria-label="Toggle navigation">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
      <a href="dashboard.php" class="ml-1 flex items-center gap-3">
        <img src="<?= htmlspecialchars($adminLogoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Logo"
          class="h-10 w-10 object-contain">
        <span class="max-w-[42vw] truncate text-base font-bold uppercase tracking-tight text-primary lg:text-lg">
          <?= htmlspecialchars($siteSettings['site_name'], ENT_QUOTES, 'UTF-8') ?>
        </span>
      </a>
    </div>

    <div class="pr-3 text-slate-900">
      <div class="relative">
        <button id="profile-btn" class="rounded-full p-1 transition" aria-label="Open profile menu">
          <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-sm font-bold text-white shadow-md">
            <?= htmlspecialchars(strtoupper(substr($displayName, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
          </span>
        </button>
        <div id="profile-menu" class="absolute right-0 mt-2 hidden w-72 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl z-50">
          <div class="bg-gradient-to-br from-primary to-blue-700 p-5 text-white">
            <p class="font-bold text-base"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></p>
            <?php if (!empty($admin['email'])): ?>
              <p class="text-xs opacity-90"><?= htmlspecialchars($admin['email'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <?php if (!empty($admin['phone'])): ?>
              <p class="mt-1 text-xs opacity-90">Phone: <?= htmlspecialchars($admin['phone'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <p class="mt-1 text-xs font-semibold uppercase tracking-wide"><?= $admin['role'] === 'super_admin' ? 'Super admin' : 'Admin' ?></p>
          </div>
          <div class="p-2">
            <a href="change_password.php" class="block rounded-lg px-4 py-3 text-sm transition hover:bg-slate-100">Change Password</a>
            <?php if ($admin['role'] === 'super_admin'): ?>
              <a href="settings.php" class="block rounded-lg px-4 py-3 text-sm transition hover:bg-slate-100">Site settings</a>
            <?php endif; ?>
            <form action="logout.php" method="POST" class="mt-1">
              <button type="submit" class="w-full rounded-lg px-4 py-3 text-left text-sm text-red-600 transition hover:bg-red-50">Logout</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>
