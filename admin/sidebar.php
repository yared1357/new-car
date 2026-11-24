<?php
$unreadMessages = 0;
try {
    $unreadMessages = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();
} catch (Throwable $e) {
    $unreadMessages = 0;
}
$activeSidebar = $activeSidebar ?? '';

$nameParts = preg_split('/\s+/', trim((string) $displayName)) ?: [];
$firstName = $nameParts[0] ?? '';
$firstName = $firstName !== '' ? mb_strtoupper(mb_substr($firstName, 0, 1)) . mb_substr($firstName, 1) : 'Admin';
$shortName = $firstName;
if (isset($nameParts[1]) && trim($nameParts[1]) !== '') {
    $shortName .= ' ' . mb_strtoupper(mb_substr(trim($nameParts[1]), 0, 1)) . '.';
}

$navItems = [
    'dashboard' => ['Dashboard', 'dashboard.php', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h6v6H4zm10 0h6v6h-6zM4 14h6v6H4zm10 0h6v6h-6z"/>'],
    'cars' => ['Manage Cars', 'cars.php', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 12h14M5 8h14M5 16h14M3 12l2 2m14-2l2-2"/>'],
    'slides' => ['Manage Slides', 'hero_images.php', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h16v14H4zm4 10l3-3 2 2 3-4 4 5"/>'],
    'messages' => ['Messages', 'messages.php', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h16v11H8l-4 4V5z"/>'],
];
?>
<aside id="sidebar"
  class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-[#0f2a3d] text-white shadow-2xl transition-transform duration-300 ease-in-out">
  <div class="mx-5 mt-5 flex items-center justify-between rounded-2xl border border-white/10 bg-white/10 px-4 py-5">
    <a href="dashboard.php" class="flex min-w-0 items-center gap-3 overflow-hidden">
      <img src="<?= htmlspecialchars($adminLogoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="h-12 w-12 flex-shrink-0 object-contain">
      <span class="min-w-0">
        <span class="block truncate text-base font-bold"><?= htmlspecialchars($shortName, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="mt-1 block text-xs font-bold tracking-widest text-amber-300"><?= $admin['role'] === 'super_admin' ? 'SUPER ADMIN' : 'ADMIN' ?></span>
      </span>
    </a>
    <button id="sidebar-close-btn" class="ml-1 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white shadow-md lg:hidden" aria-label="Close sidebar">
      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
  </div>

  <nav class="mt-5 flex-1 overflow-y-auto px-5 pb-4">
    <ul class="space-y-2">
      <?php foreach ($navItems as $key => [$label, $href, $icon]): ?>
        <li>
          <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
            class="<?= $activeSidebar === $key
              ? 'flex items-center gap-4 rounded-2xl bg-[#FFD84D] px-5 py-3.5 text-base font-bold text-slate-900 shadow-lg'
              : 'flex items-center gap-4 rounded-xl px-5 py-3.5 text-base font-semibold text-slate-200 transition hover:bg-white/10 hover:text-white' ?>">
            <svg class="h-6 w-6 <?= $activeSidebar === $key ? '' : 'text-slate-300' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $icon ?></svg>
            <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
            <?php if ($key === 'messages' && $unreadMessages > 0): ?>
              <span class="ml-auto flex h-7 w-7 items-center justify-center rounded-full bg-orange-500 text-sm text-white"><?= $unreadMessages ?></span>
            <?php endif; ?>
          </a>
        </li>
      <?php endforeach; ?>

      <?php if ($admin['role'] === 'super_admin'): ?>
        <li>
          <a href="settings.php" class="<?= $activeSidebar === 'settings'
            ? 'flex items-center gap-4 rounded-2xl bg-[#FFD84D] px-5 py-3.5 text-base font-bold text-slate-900 shadow-lg'
            : 'flex items-center gap-4 rounded-xl px-5 py-3.5 text-base font-semibold text-slate-200 transition hover:bg-white/10 hover:text-white' ?>">
            <svg class="h-6 w-6 <?= $activeSidebar === 'settings' ? '' : 'text-slate-300' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15.5A3.5 3.5 0 1012 8a3.5 3.5 0 000 7.5zm0-13v2m0 15v2m10-9.5h-2m-16 0H2m17-7l-1.5 1.5M6.5 17.5L5 19m14 0l-1.5-1.5M6.5 6.5L5 5"/></svg>
            Site settings
          </a>
        </li>
        <li>
          <a href="social_links.php" class="<?= $activeSidebar === 'social'
            ? 'flex items-center gap-4 rounded-2xl bg-[#FFD84D] px-5 py-3.5 text-base font-bold text-slate-900 shadow-lg'
            : 'flex items-center gap-4 rounded-xl px-5 py-3.5 text-base font-semibold text-slate-200 transition hover:bg-white/10 hover:text-white' ?>">
            <svg class="h-6 w-6 <?= $activeSidebar === 'social' ? '' : 'text-slate-300' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.6 13.5l6.8-4M9 15l6.5 4M8 12a3 3 0 100-6 3 3 0 000 6zm9-3a3 3 0 100-6 3 3 0 000 6zm-1 10a3 3 0 100-6 3 3 0 000 6z"/></svg>
            Social Links
          </a>
        </li>
      <?php endif; ?>

      <li>
        <a href="change_password.php" class="<?= $activeSidebar === 'password'
          ? 'flex items-center gap-4 rounded-2xl bg-[#FFD84D] px-5 py-3.5 text-base font-bold text-slate-900 shadow-lg'
          : 'flex items-center gap-4 rounded-xl px-5 py-3.5 text-base font-semibold text-slate-200 transition hover:bg-white/10 hover:text-white' ?>">
          <svg class="h-6 w-6 <?= $activeSidebar === 'password' ? '' : 'text-slate-300' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 11V8a5 5 0 0110 0v3m-11 0h12v10H6V11z"/></svg>
          Password
        </a>
      </li>
    </ul>
  </nav>

  <div class="space-y-3 px-5 pb-5">
    <a href="../index.php" target="_blank" rel="noopener noreferrer"
      class="flex items-center justify-center gap-3 rounded-2xl border border-white/15 bg-white/10 py-3.5 text-base font-bold text-slate-100 transition hover:bg-white/15">
      <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6zm9.5 3a3 3 0 100-6 3 3 0 000 6z"/></svg>
      View Site
    </a>
    <form action="logout.php" method="POST">
      <button type="submit"
        class="flex w-full items-center justify-center gap-3 rounded-2xl border border-white/15 bg-white/10 py-3.5 text-base font-bold text-slate-100 transition hover:border-red-400/40 hover:bg-red-500/10">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 17l5-5-5-5m5 5H3m18 7V5"/></svg>
        Logout
      </button>
    </form>
  </div>
</aside>
