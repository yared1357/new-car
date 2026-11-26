<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/site_settings.php';
$siteSettings = get_site_settings($pdo);
$socialLinks = get_social_links($pdo);
$pageTitle = $siteSettings['site_name'] . ($siteSettings['tagline'] !== '' ? ' – ' . $siteSettings['tagline'] : '');
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="description" content="<?= htmlspecialchars($siteSettings['tagline'], ENT_QUOTES, 'UTF-8') ?>">
  <meta name="theme-color" content="<?= htmlspecialchars($siteSettings['primary_color'], ENT_QUOTES, 'UTF-8') ?>">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($siteSettings['site_name'], ENT_QUOTES, 'UTF-8') ?>">
  <link rel="manifest" href="manifest.php">
  <link rel="icon" type="image/png" sizes="192x192" href="icons/icon-192.png">
  <link rel="apple-touch-icon" href="icons/icon-192.png">

  <!-- Tailwind CSS (via CDN) -->
  <script src="https://cdn.tailwindcss.com"></script>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Inter', 'sans-serif'] },
          colors: {
            primary: <?= json_encode($siteSettings['primary_color'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            accent: <?= json_encode($siteSettings['accent_color'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
          },
          maxWidth: { '80vw': '80vw', '96vw': '96vw', '98vw': '98vw' },
          height: { '60vh': '60vh', '70vh': '70vh' }, // For mobile height
        },
      },
      corePlugins: { container: false },
    };
  </script>


  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .animate-fade-in {
      animation: fadeIn 0.8s ease-out forwards;
    }

    .delay-200 {
      animation-delay: 0.2s;
    }

    .delay-400 {
      animation-delay: 0.4s;
    }

    @keyframes phone-ring {
      0%, 100% { transform: rotate(0); }
      10%, 30% { transform: rotate(-8deg); }
      20%, 40% { transform: rotate(8deg); }
      50% { transform: rotate(0); }
    }

    .header-phone-icon {
      animation: phone-ring 2.2s ease-in-out infinite;
      transform-origin: 50% 50%;
    }

    @keyframes refresh-spin {
      to { transform: rotate(360deg); }
    }

    #header-refresh-button.refreshing svg {
      animation: refresh-spin 0.7s linear infinite;
    }

    @keyframes contact-glow {
      0%, 100% {
        box-shadow: 0 10px 30px rgba(30, 64, 175, 0.28), 0 0 0 0 rgba(59, 130, 246, 0.5);
      }
      50% {
        box-shadow: 0 14px 36px rgba(30, 64, 175, 0.38), 0 0 0 12px rgba(59, 130, 246, 0);
      }
    }

    @keyframes contact-bounce {
      0%, 100% { transform: translateY(0) scale(1); }
      50% { transform: translateY(-3px) scale(1.04); }
    }

    #floating-contact-button:not([aria-expanded="true"]) {
      animation: contact-glow 2.2s ease-out infinite;
    }

    #floating-contact-button:not([aria-expanded="true"]) svg {
      animation: contact-bounce 1.8s ease-in-out infinite;
    }

    #floating-contact-panel {
      transform: translateY(24px) scale(.96);
      opacity: 0;
      pointer-events: none;
      transition: opacity .25s ease, transform .25s ease;
    }

    #floating-contact-panel.open {
      transform: translateY(0) scale(1);
      opacity: 1;
      pointer-events: auto;
    }

    #floating-contact-button {
      box-shadow: 0 14px 35px rgba(30, 64, 175, .35);
    }

    .hero-slide {
      flex: 0 0 100%;
      min-width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      background: #111827;
    }

    .hero-slide img {
      display: block;
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center;
    }

    #home {
      height: 44vh;
      min-height: 220px;
      max-height: 320px;
    }

    @media (min-width: 768px) {
      #home {
        height: 100vh;
        min-height: 0;
        max-height: none;
      }
    }

    /* Keep the read-more/read-less toggle on the same line as the content */
    .read-toggle {
      white-space: nowrap;
    }

    /* PERFECT IMAGE CAROUSEL */
    .vehicle-image-frame {
      width: 100%;
      aspect-ratio: 16 / 10;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      background: #f8fafc;
    }

    .vehicle-image-frame img {
      width: 100%;
      height: 100%;
      display: block;
      object-fit: contain;
      object-position: center;
    }

    .image-carousel {
      position: relative;
      overflow: hidden;
      background: #0f172a;
      height: 12rem;
    }

    @media (min-width: 640px) {
      .image-carousel {
        height: 14rem;
      }
    }

    @media (min-width: 768px) {
      .image-carousel {
        height: 16rem;
      }
    }

    @media (min-width: 1024px) {
      .image-carousel {
        height: 20rem;
      }
    }

    .image-carousel .slides {
      display: flex;
      width: 100%;
      height: 100%;
      transition: transform 0.4s ease;
    }

    .image-carousel .slide {
      min-width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .image-carousel img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
    }

    .image-carousel .nav-btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(255, 255, 255, 0.95);
      color: #1e293b;
      width: 44px;
      height: 44px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 22px;
      cursor: pointer;
      z-index: 10;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
      transition: all 0.2s;
    }

    .image-carousel .nav-btn:hover {
      background: white;
      transform: translateY(-50%) scale(1.1);
    }

    .image-carousel .prev {
      left: 12px;
    }

    .image-carousel .next {
      right: 12px;
    }

    .image-carousel .dots {
      position: absolute;
      bottom: 12px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 8px;
    }

    .image-carousel .dot {
      width: 10px;
      height: 10px;
      background: rgba(255, 255, 255, 0.5);
      border-radius: 50%;
      cursor: pointer;
      transition: all 0.3s;
    }

    .image-carousel .dot.active {
      background: white;
      transform: scale(1.4);
    }

    @media (max-width: 768px) {
      .image-carousel .nav-btn {
        display: none;
      }
    }

    /* MODAL CAROUSEL */
    .image-carousel-modal {
      position: relative;
      overflow: hidden;
      background: #000;
      width: 100%;
    }

    .image-carousel-modal .slides-modal {
      display: flex;
      width: 100%;
      height: 100%;
      transition: transform 0.4s ease;
    }

    .image-carousel-modal .slide-modal {
      min-width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .image-carousel-modal img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
    }

    .image-carousel-modal .nav-btn-modal {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(255, 255, 255, 0.95);
      color: #1e293b;
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 24px;
      cursor: pointer;
      z-index: 10;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
      transition: all 0.2s;
    }

    .image-carousel-modal .nav-btn-modal:hover {
      background: white;
      transform: translateY(-50%) scale(1.1);
    }

    .image-carousel-modal .prev-modal {
      left: 20px;
    }

    .image-carousel-modal .next-modal {
      right: 20px;
    }

    .image-carousel-modal .dots-modal {
      position: absolute;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 10px;
    }

    .image-carousel-modal .dot-modal {
      width: 12px;
      height: 12px;
      background: rgba(255, 255, 255, 0.5);
      border-radius: 50%;
      cursor: pointer;
      transition: all 0.3s;
    }

    .image-carousel-modal .dot-modal.active {
      background: white;
      transform: scale(1.4);
    }

    /* Price styling: smaller currency code and responsive sizing */
    .currency {
      font-size: 0.8em;
      opacity: 0.95;
      margin-right: 0.35rem;
    }

    .price-wrap {
      word-break: break-word;
      white-space: normal;
    }

    /* Price row: center current price, discount badge, and old price on one line */
    .price-row {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.6rem;
      flex-wrap: wrap;
    }

    .current-price {
      font-size: 1rem;
      font-weight: 700;
    }

    .old-price {
      color: #6b7280;
      text-decoration: line-through;
    }

    .discount-badge {
      background: #fee2e2;
      color: #991b1b;
      font-size: 0.75rem;
      padding: 0.15rem 0.5rem;
      border-radius: 9999px;
    }

    @media (min-width: 640px) {
      .current-price {
        font-size: 1.25rem;
      }
    }

    @media (min-width: 768px) {
      .current-price {
        font-size: 1.5rem;
      }
    }

  </style>

  <!-- ===== Contact success message fade up! ===== -->
  <style>
    #form-message {
      transition: opacity 0.4s ease-out;
    }

    #form-message.hidden {
      opacity: 0;
    }

    #form-message:not(.hidden) {
      opacity: 1;
    }
  </style>

</head>

<body class="bg-gray-50 text-gray-900">

  <!-- ===== HEADER (80% width, centered) – LOGO = car.png ===== -->
  <header class="sticky top-0 z-50 shadow-md" style="background-color: <?= htmlspecialchars($siteSettings['header_color'], ENT_QUOTES, 'UTF-8') ?>; color: <?= htmlspecialchars($siteSettings['header_content_color'], ENT_QUOTES, 'UTF-8') ?>">
    <nav class="max-w-98vw lg:max-w-80vw mx-auto flex items-center justify-between p-4">
      <a href="#home" class="flex items-center space-x-3" aria-label="Go to homepage slider">
        <img src="<?= htmlspecialchars($siteSettings['logo_path'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($siteSettings['site_name'], ENT_QUOTES, 'UTF-8') ?> Logo" class="w-12 h-12 sm:w-14 sm:h-14 rounded-md object-contain">
        <span class="flex flex-col font-bold text-lg uppercase tracking-wide" style="color: <?= htmlspecialchars($siteSettings['header_content_color'], ENT_QUOTES, 'UTF-8') ?>">
          <?= htmlspecialchars($siteSettings['site_name'], ENT_QUOTES, 'UTF-8') ?>
          <?php if ($siteSettings['tagline'] !== ''): ?><small class="mt-0.5 text-sm font-normal normal-case sm:text-base"><?= htmlspecialchars($siteSettings['tagline'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </span>
      </a>
      <div class="flex items-center gap-2">
        <button type="button" id="header-refresh-button"
          aria-label="Refresh page" title="Refresh for new content"
          class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-300/80 bg-white text-black shadow-md transition active:scale-95">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M20 6v5h-5M4 18v-5h5M6.5 9A7 7 0 0118 6.5L20 11M4 13l2 4.5A7 7 0 0017.5 15" />
          </svg>
        </button>
        <?php if ($siteSettings['contact_phone'] !== ''): ?>
          <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $siteSettings['contact_phone']), ENT_QUOTES, 'UTF-8') ?>"
            aria-label="Call <?= htmlspecialchars($siteSettings['contact_phone'], ENT_QUOTES, 'UTF-8') ?>"
            title="Call <?= htmlspecialchars($siteSettings['contact_phone'], ENT_QUOTES, 'UTF-8') ?>"
            class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-300/80 bg-white text-black shadow-md transition hover:scale-105 active:scale-95">
            <svg class="header-phone-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
          </a>
        <?php else: ?>
          <button type="button" class="open-contact-form flex h-10 w-10 items-center justify-center rounded-full border border-slate-300/80 bg-white text-black shadow-md transition hover:scale-105 active:scale-95"
            aria-label="Open contact form" title="Open contact form">
            <svg class="header-phone-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
          </button>
        <?php endif; ?>
      </div>
    </nav>
  </header>

  <!-- ===== HERO CAROUSEL SECTION ===== -->
  <section id="home" class="relative overflow-hidden">
    <div class="absolute inset-0 flex transition-transform duration-700 ease-in-out" id="carousel">
      <div class="hero-slide">
        <img src="https://images.unsplash.com/photo-1494976388531-d1058494cdd8?ixlib=rb-4.0.3&auto=format&fit=max&w=1950&q=80" alt="Featured car">
      </div>
      <div class="hero-slide">
        <img src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?ixlib=rb-4.0.3&auto=format&fit=max&w=1950&q=80" alt="Featured car">
      </div>
      <div class="hero-slide">
        <img src="https://images.unsplash.com/photo-1552519507-da3b142c6e3d?ixlib=rb-4.0.3&auto=format&fit=max&w=1950&q=80" alt="Featured car">
      </div>
      <div class="hero-slide">
        <img src="https://images.unsplash.com/photo-1544636331-90447de51160?ixlib=rb-4.0.3&auto=format&fit=max&w=1950&q=80" alt="Featured car">
      </div>
      <div class="hero-slide">
        <img src="https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?ixlib=rb-4.0.3&auto=format&fit=max&w=1950&q=80" alt="Featured car">
      </div>
      <div class="hero-slide">
        <img src="https://images.unsplash.com/photo-1519641471654-1ce4c62ff8c1?ixlib=rb-4.0.3&auto=format&fit=max&w=1950&q=80" alt="Featured car">
      </div>
    </div>

    <!-- Overlay -->
    <div class="absolute inset-0 bg-black bg-opacity-20"></div>

    <!-- Content -->
    <div
      class="relative z-10 h-full flex flex-col items-center justify-center text-center text-white px-4 max-w-80vw mx-auto">
      <h1 id="hero-title" class="text-xl sm:text-2xl md:text-5xl font-bold mb-4 animate-fade-in px-2">Find Your Dream
        Car</h1>
      <p id="hero-description"
        class="text-sm sm:text-base md:text-xl mb-8 max-w-2xl mx-auto animate-fade-in delay-200 px-2"></p>
    </div>

    <!-- Navigation Arrows -->
    <button id="prev-btn"
      class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 bg-white/20 hover:bg-white/40 backdrop-blur-sm rounded-full flex items-center justify-center transition">
      <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
    </button>
    <button id="next-btn"
      class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 bg-white/20 hover:bg-white/40 backdrop-blur-sm rounded-full flex items-center justify-center transition">
      <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
    </button>

    <!-- DOTS REMOVED -->
  </section>



  <section class="max-w-96vw lg:max-w-80vw mx-auto py-4 sm:py-6 lg:py-8 px-4 -mt-12 relative z-20">
    <div class="bg-white rounded-xl shadow-xl p-4 sm:p-6">
      <div class="grid grid-cols-1">
        <h1 class="font-bold text-center text-lg sm:text-2xl md:text-3xl">
          Welcome To <?= htmlspecialchars($siteSettings['site_name'], ENT_QUOTES, 'UTF-8') ?>!</h1>
      </div>
    </div>
  </section>

  <!-- ===== INVENTORY GRID ===== -->
  <section id="inventory" class="max-w-[98vw] mx-auto py-6 sm:py-8 lg:py-12 px-4">
    <h2 class="text-2xl sm:text-3xl font-bold text-center mb-6 sm:mb-8 lg:mb-10">Featured Vehicles</h2>

    <!-- SEARCH & FILTER BAR (IMPROVED) -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6 sm:gap-4">
        <input type="text" id="search-input" placeholder="Search..."
          class="col-span-2 border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary whitespace-nowrap overflow-hidden text-ellipsis sm:col-span-1">
        <select id="filter-year"
          class="border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary text-[10px] sm:text-sm">
          <option value="">Year</option>
        </select>
        <select id="filter-category"
          class="border rounded-lg px-1 sm:px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary text-[10px] sm:text-sm">
          <option value="">Category</option>
          <option>New</option>
          <option>Secondhand</option>
          <option>Classic</option>
        </select>
        <select id="filter-brand"
          class="border rounded-lg px-1 sm:px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary text-[10px] sm:text-sm">
          <option value="">Brand</option>
        </select>
        <select id="filter-fuel"
          class="border rounded-lg px-1 sm:px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary text-[10px] sm:text-sm">
          <option value="">Fuel</option>
          <option>Petrol</option>
          <option>Diesel</option>
          <option>Electric</option>
          <option>Hybrid</option>
        </select>
        <select id="filter-type"
          class="border rounded-lg px-1 sm:px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary text-[10px] sm:text-sm">
          <option value="">Type</option>
          <option>Manual</option>
          <option>Automatic</option>
        </select>
      </div>
    </div>

    <!-- CAR GRID -->
    <div id="car-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
      <!-- Loaded via JS -->
    </div>

    <!-- NO RESULTS -->
    <p id="no-results" class="text-center text-gray-500 mt-8 hidden">No cars found matching your criteria.</p>
  </section>

  <script>
    // === LOAD CARS FROM DATABASE ===
    let allCars = [];

    // Format numeric or formatted price values into ETB currency string.
    function formatETB(value) {
      if (value === null || value === undefined || value === '') return '';
      const s = String(value);
      const n = Number(s.replace(/[^0-9.-]+/g, ''));
      if (!isNaN(n)) {
        try {
          return new Intl.NumberFormat('en-ET', { style: 'currency', currency: 'ETB', maximumFractionDigits: 0 }).format(n);
        } catch (e) {
          return 'ETB ' + n.toLocaleString();
        }
      }
      if (s.indexOf('$') !== -1) return s.replace('$', 'ETB ');
      return 'ETB ' + s;
    }

    function renderCars(carsToShow) {
      const grid = document.getElementById('car-grid');
      const noResults = document.getElementById('no-results');
      grid.innerHTML = '';

      if (carsToShow.length === 0) {
        noResults.classList.remove('hidden');
        return;
      }

      noResults.classList.add('hidden');

      carsToShow.forEach(car => {
        const div = document.createElement('div');
        div.className = 'bg-white text-black rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition';

        // On the listing page show only the first image; remaining images are available in View Details modal
        const firstImg = (car.images && car.images.length) ? car.images[0] : (car.first_image || 'placeholder.jpg');
        const imageHtml = `<div class="vehicle-image-frame"><img src="${firstImg}" alt="${car.make} ${car.model}" loading="lazy"></div>`;

        div.innerHTML = `
        ${imageHtml}
        <div class="p-4">
          <div class="flex justify-between font-bold text-lg">
            <span>${car.make}</span>
            <span>${car.model}</span>
          </div>
          <div class="w-[96%] mx-auto">
            <div class="price-row text-center text-lg sm:text-xl md:text-2xl font-bold">
              ${(() => {
            const p = formatETB(car.price ?? car.price_formatted) || '';
            const current = `<p class="current-price text-primary">${p.replace(/^ETB\s?/, '<span class="currency">ETB</span> ')}</p>`;
            if (Number(car.discount_percentage) > 0 && car.old_price) {
              const oldPrice = formatETB(car.old_price);
              return `${current}<span class="discount-badge">-${Number(car.discount_percentage).toFixed(Number(car.discount_percentage) % 1 ? 2 : 0)}%</span><span class="old-price text-sm">${oldPrice}</span>`;
            }
            return current;
          })()}
            </div>
          </div>
          <div class="w-[98%] mx-auto text-black">
            <div class="flex justify-between text-sm">
              <span>Year: ${car.year}</span>
              <span>Mileage: ${car.mileage_formatted}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span>Fuel: ${car.fuel_type}</span>
              <span>Transmission: ${car.transmission}</span>
            </div>
          </div>
          <p class="text-sm text-black description-text mt-2" data-full="${car.description ? car.description.replace(/"/g, '&quot;') : ''}"></p>
          ${car.category ? `<span class="inline-block mt-2 bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">${car.category}</span>` : ''}
          ${car.featured ? '<span class="inline-block mt-2 bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Featured</span>' : ''}
          <button class="block mt-4 bg-primary hover:bg-blue-800 text-white text-center py-2 rounded-lg text-sm font-medium transition w-full" data-images='${JSON.stringify(car.images)}' data-make="${car.make}" data-model="${car.model}" data-year="${car.year}" data-price="${car.price || car.price_formatted}" data-old-price="${car.old_price || ''}" data-discount="${car.discount_percentage || 0}" data-mileage="${car.mileage_formatted}" data-fuel="${car.fuel_type}" data-transmission="${car.transmission}" data-description="${car.description.replace(/"/g, '"')}" onclick="openModal(this)">
            View Details
          </button>
        </div>
      `;
        div.setAttribute('data-make', car.make.toLowerCase());
        div.setAttribute('data-model', car.model.toLowerCase());
        div.setAttribute('data-year', car.year);
        div.setAttribute('data-price', car.price);
        grid.appendChild(div);
      });
    }

    // === FETCH CARS ONCE ===
    fetch('api/get_cars.php')
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          allCars = data.data;
          renderCars(allCars);
          initCarousels();
          initReadMore();
          setupFilters();
          populateYearFilter();
          loadBrands();
        } else {
          document.getElementById('car-grid').innerHTML = '<p class="text-center text-red-500">Failed to load cars.</p>';
        }
      })
      .catch(() => {
        document.getElementById('car-grid').innerHTML = '<p class="text-center text-red-500">Failed to load cars.</p>';
      });

    // === LOAD HERO IMAGES ===
    let heroImages = [];
    fetch('api/get_hero_images.php')
      .then(r => r.json())
      .then(data => {
        if (data.success && data.data.length > 0) {
          heroImages = data.data;
          updateHeroContent(0);
          // Initialize hero carousel now that images are available
          initHeroCarousel();
        }
      })
      .catch(() => {
        // Fallback to default if needed
      });

    function updateHeroContent(index) {
      if (heroImages[index]) {
        const img = heroImages[index];
        document.getElementById('hero-title').textContent = img.title || 'Find Your Dream Car';
        document.getElementById('hero-description').textContent = img.description || '';
      }
    }

    // Initialize hero carousel after heroImages are loaded (or use existing slides present in markup)
    function initHeroCarousel() {
      const carousel = document.getElementById('carousel');
      // If heroImages were provided by API, replace static slides
      if (heroImages && heroImages.length) {
        carousel.replaceChildren(...heroImages.map((heroImage, index) => {
          const slide = document.createElement('div');
          slide.className = 'hero-slide';

          const image = document.createElement('img');
          image.src = heroImage.image_url;
          image.alt = heroImage.title || 'Featured car';
          image.loading = index === 0 ? 'eager' : 'lazy';
          image.decoding = 'async';
          slide.appendChild(image);

          return slide;
        }));
      }

      // Build dots container
      let dots = document.getElementById('hero-dots');
      if (!dots) {
        dots = document.createElement('div');
        dots.id = 'hero-dots';
        dots.className = 'dots';
        carousel.parentElement.appendChild(dots);
      }

      // Create dots for slides
      dots.innerHTML = '';
      const slides = carousel.children;
      for (let i = 0; i < slides.length; i++) {
        const d = document.createElement('div');
        d.className = 'dot' + (i === 0 ? ' active' : '');
        d.dataset.index = i;
        d.addEventListener('click', (e) => {
          currentIndex = Number(e.target.dataset.index);
          updateCarousel();
          resetAutoSlide();
        });
        dots.appendChild(d);
      }

      // Reset index and recalc totals
      currentIndex = 0;
      updateCarousel();
      resetAutoSlide();
    }

    // === SETUP LIVE FILTERS ===
    function setupFilters() {
      const inputs = [
        '#search-input',
        '#filter-year',
        '#filter-category',
        '#filter-brand',
        '#filter-fuel',
        '#filter-type'
      ];

      inputs.forEach(selector => {
        document.querySelector(selector).addEventListener('input', filterCars);
        document.querySelector(selector).addEventListener('change', filterCars);
      });
    }

    function filterCars() {
      const query = document.getElementById('search-input').value.toLowerCase().trim();
      const year = document.getElementById('filter-year').value;
      const category = document.getElementById('filter-category').value || '';
      const brand = document.getElementById('filter-brand').value.toLowerCase() || '';
      const fuel = document.getElementById('filter-fuel').value;
      const trans = document.getElementById('filter-type').value;

      const filtered = allCars.filter(car => {
        const text = `${car.make} ${car.model} ${car.year} ${car.price} ${car.category || ''}`.toLowerCase();
        const matchesQuery = text.includes(query);
        const matchesYear = !year || car.year == year;
        const matchesFuel = !fuel || car.fuel_type === fuel;
        const matchesTrans = !trans || car.transmission === trans;
        const matchesCategory = !category || (car.category && car.category === category);
        const matchesBrand = !brand || (car.make && car.make.toLowerCase() === brand);

        return matchesQuery && matchesYear && matchesFuel && matchesTrans && matchesCategory && matchesBrand;
      });

      renderCars(filtered);
      initCarousels();
      initReadMore();
    }

    function populateFilterOptions() {
      // Populate dynamic brand list
      const brandSelect = document.getElementById('filter-brand');
      const brands = Array.from(new Set(allCars.map(c => (c.make || '').trim()).filter(Boolean))).sort((a, b) => a.localeCompare(b));
      brands.forEach(b => {
        const opt = document.createElement('option');
        opt.value = b;
        opt.textContent = b;
        brandSelect.appendChild(opt);
      });
    }

    function populateYearFilter() {
      const yearSelect = document.getElementById('filter-year');
      while (yearSelect.options.length > 1) yearSelect.remove(1);

      const yearCounts = allCars.reduce((counts, car) => {
        const year = String(car.year || '').trim();
        if (/^\d{4}$/.test(year)) counts[year] = (counts[year] || 0) + 1;
        return counts;
      }, {});

      Object.entries(yearCounts)
        .sort((a, b) => Number(b[0]) - Number(a[0]))
        .forEach(([year, count]) => {
          const option = document.createElement('option');
          option.value = year;
          option.textContent = `${year} (${count})`;
          yearSelect.appendChild(option);
        });
    }

    // Load brands from the database via API and populate the brand select
    function loadBrands() {
      const brandSelect = document.getElementById('filter-brand');
      // Clear existing options except the first "All Brands"
      while (brandSelect.options.length > 1) brandSelect.remove(1);

      fetch('api/get_brands.php')
        .then(r => r.json())
        .then(resp => {
          if (resp.success && Array.isArray(resp.data)) {
            resp.data.forEach(b => {
              if (!b) return;
              const opt = document.createElement('option');
              opt.value = b;
              opt.textContent = b;
              brandSelect.appendChild(opt);
            });
          } else {
            // Fallback: use allCars-derived list
            populateFilterOptions();
          }
        })
        .catch(() => {
          // On error, fallback to existing in-memory list
          populateFilterOptions();
        });
    }


    // Image Carousel
    function initCarousels() {
      document.querySelectorAll('.image-carousel').forEach(carousel => {
        const slides = carousel.querySelector('.slides');
        const prev = carousel.querySelector('.prev');
        const next = carousel.querySelector('.next');
        const dots = Array.from(carousel.querySelectorAll('.dot'));
        let index = 0;
        let startX = 0;
        let isDragging = false;
        let autoSlideTimer = null;

        const slidesCount = slides ? slides.children.length : 0;
        if (!slides || slidesCount <= 1) return; // nothing to do for single slide

        const updatePosition = () => {
          const slideWidth = carousel.clientWidth;
          slides.style.transform = `translateX(-${index * slideWidth}px)`;
          dots.forEach((d, di) => d.classList.toggle('active', di === index));
        };

        const goTo = (i) => {
          index = ((i % slidesCount) + slidesCount) % slidesCount;
          updatePosition();
        };

        prev?.addEventListener('click', () => goTo(index - 1));
        next?.addEventListener('click', () => goTo(index + 1));
        dots.forEach((dot, i) => dot.addEventListener('click', () => goTo(i)));

        // Touch events (basic swipe)
        carousel.addEventListener('touchstart', (e) => {
          startX = e.touches[0].clientX;
          isDragging = true;
        }, { passive: true });

        carousel.addEventListener('touchmove', (e) => {
          if (!isDragging) return;
          const currentX = e.touches[0].clientX;
          const diff = startX - currentX;
          if (Math.abs(diff) > 50) {
            if (diff > 0) goTo(index + 1);
            else goTo(index - 1);
            isDragging = false;
          }
        }, { passive: true });

        carousel.addEventListener('touchend', () => { isDragging = false; });

        // Auto-slide (reset on user interaction)
        const startAuto = () => {
          stopAuto();
          autoSlideTimer = setInterval(() => goTo(index + 1), 4000);
        };
        const stopAuto = () => { if (autoSlideTimer) clearInterval(autoSlideTimer); };
        carousel.addEventListener('mouseenter', stopAuto);
        carousel.addEventListener('mouseleave', startAuto);
        startAuto();

        // Recompute on resize
        window.addEventListener('resize', updatePosition);
        // Initialize
        updatePosition();
      });
    }

    // Read More / Read Less
    function toggleDescription(el, full, truncated) {
      if (el.textContent === 'Read More') {
        el.parentElement.innerHTML = `${full}&nbsp;<span class="text-blue-500 cursor-pointer read-toggle" onclick="toggleDescription(this, '${full.replace(/'/g, "\\'")}', '${truncated.replace(/'/g, "\\'")}')">Read Less</span>`;
      } else {
        el.parentElement.innerHTML = `${truncated}&nbsp;<span class="text-blue-500 cursor-pointer read-toggle" onclick="toggleDescription(this, '${full.replace(/'/g, "\\'")}', '${truncated.replace(/'/g, "\\'")}')">Read More</span>`;
      }
    }

    function initReadMore() {
      // Apply 'Read More' truncation at 24 characters for description fields
      document.querySelectorAll('.description-text').forEach(el => {
        const full = el.getAttribute('data-full') || '';
        if (!full) return;
        const limit = 20;
        if (full.length > limit) {
          const truncated = full.substring(0, limit) + '...';
          el.innerHTML = `${truncated}&nbsp;<span class="text-blue-500 cursor-pointer read-toggle" onclick="toggleDescription(this, '${full.replace(/'/g, "\\'")}', '${truncated.replace(/'/g, "\\'")}')">Read More</span>`;
        } else {
          el.textContent = full;
        }
      });
    }

    // Modal
    function openModal(btn) {
      const images = JSON.parse(btn.dataset.images);
      const modal = document.getElementById('image-modal');
      const slides = modal.querySelector('.slides-modal');
      const dots = modal.querySelector('.dots-modal');

      // Images
      slides.innerHTML = images.map((img, i) => `<div class="slide-modal"><img src="${img}" alt="Car Image"></div>`).join('');
      dots.innerHTML = images.map((_, i) => `<div class="dot-modal ${i === 0 ? 'active' : ''}" data-slide="${i}"></div>`).join('');

      // Details
      document.getElementById('modal-make-model').textContent = btn.dataset.make + ' ' + btn.dataset.model;
      // Build centered current-price row
      const modalPriceRaw = formatETB(btn.dataset.price || '');
      const discount = Number(btn.dataset.discount || 0);
      const oldPriceRaw = btn.dataset.oldPrice || '';
      const oldPriceHtml = discount > 0 && oldPriceRaw
        ? `<span class="discount-badge">-${discount.toFixed(discount % 1 ? 2 : 0)}%</span><span class="old-price text-sm">${formatETB(oldPriceRaw)}</span>`
        : '';
      const priceRowHtml = `
      <div class="price-row text-center">
        <p class="current-price text-primary">${modalPriceRaw.replace(/^ETB\s?/, '<span class="currency">ETB</span> ')}</p>
        ${oldPriceHtml}
      </div>
    `;
      const modalPriceRowEl = document.getElementById('modal-price-row');
      if (modalPriceRowEl) modalPriceRowEl.innerHTML = priceRowHtml;
      document.getElementById('modal-year').textContent = 'Year: ' + btn.dataset.year;
      document.getElementById('modal-mileage').textContent = 'Mileage: ' + btn.dataset.mileage;
      document.getElementById('modal-fuel').textContent = 'Fuel: ' + btn.dataset.fuel;
      document.getElementById('modal-transmission').textContent = 'Transmission: ' + btn.dataset.transmission;
      document.getElementById('modal-description').textContent = btn.dataset.description;

      modal.classList.remove('hidden');
      initModalCarousel();
    }

    function closeModal() {
      document.getElementById('image-modal').classList.add('hidden');
    }

    function initModalCarousel() {
      const modal = document.getElementById('image-modal');
      const slides = modal.querySelector('.slides-modal');
      const dots = modal.querySelectorAll('.dot-modal');
      let index = 0;

      const goTo = (i) => {
        index = (i + dots.length) % dots.length;
        slides.style.transform = `translateX(-${index * 100}%)`;
        dots.forEach((d, di) => d.classList.toggle('active', di === index));
      };

      dots.forEach((dot, i) => dot.addEventListener('click', () => goTo(i)));

      // Reset to first
      goTo(0);
    }

  </script>


  <!-- ===== FLOATING CONTACT FORM ===== -->
  <div id="floating-contact-panel" aria-hidden="true"
    class="fixed bottom-24 right-4 z-[70] w-[calc(100%-2rem)] max-w-sm overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl sm:right-6">
    <div class="flex items-center justify-between px-5 py-4 text-white" style="background-color: <?= htmlspecialchars($siteSettings['footer_color'], ENT_QUOTES, 'UTF-8') ?>">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-white/70">Quick message</p>
        <h2 class="text-lg font-bold">Contact <?= htmlspecialchars($siteSettings['site_name'], ENT_QUOTES, 'UTF-8') ?></h2>
      </div>
      <button type="button" id="close-contact-form" aria-label="Close contact form"
        class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-xl text-white transition hover:bg-white/20">&times;</button>
    </div>
    <form id="contact-form" class="space-y-3 p-5">
      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">Your Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" required placeholder="John Doe"
          class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
      </div>
      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">Email Address <span class="text-slate-400">(optional)</span></label>
        <input type="email" name="email" placeholder="john@example.com"
          class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
      </div>
      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">Phone Number <span class="text-red-500">*</span></label>
        <input type="tel" name="phone" required placeholder="+251912345678" pattern="[0-9+()\-\s]{6,30}" inputmode="tel" minlength="6" maxlength="30"
          oninput="this.value = this.value.replace(/[^0-9+()\-\s]/g,'')"
          class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
      </div>
      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">Message <span class="text-red-500">*</span></label>
        <textarea name="message" rows="3" required placeholder="Tell us about your dream car..."
          class="w-full resize-none rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"></textarea>
      </div>
      <button type="submit"
        class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-3 text-sm font-bold text-white transition hover:bg-blue-800">
        <span>Send Message</span>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
        </svg>
      </button>
      <div id="form-message" class="hidden text-center text-sm"></div>
    </form>
  </div>

  <button type="button" id="floating-contact-button" aria-label="Open contact form" aria-controls="floating-contact-panel" aria-expanded="false"
    class="fixed bottom-5 right-4 z-[71] flex h-14 w-14 items-center justify-center rounded-full bg-primary text-white transition hover:scale-105 hover:bg-blue-800 sm:right-6"
    style="background-color: <?= htmlspecialchars($siteSettings['primary_color'], ENT_QUOTES, 'UTF-8') ?>">
    <svg id="contact-button-icon" class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.7 9.7 0 01-4-.84L3 20l1.12-3.36A7.46 7.46 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
    </svg>
  </button>

  <button type="button" id="pwa-install-button" hidden
    class="fixed bottom-5 left-4 z-[71] items-center gap-2 rounded-full bg-slate-900 px-4 py-3 text-sm font-bold text-white shadow-xl transition hover:bg-slate-800 sm:left-6">
    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l5-5m-5 5l-5-5M5 21h14" />
    </svg>
    <span>Install App</span>
  </button>

  <!-- ===== FOOTER ===== -->
  <footer class="border-t border-white/10 py-10" style="background-color: <?= htmlspecialchars($siteSettings['footer_color'], ENT_QUOTES, 'UTF-8') ?>; color: <?= htmlspecialchars($siteSettings['footer_content_color'], ENT_QUOTES, 'UTF-8') ?>">
    <div class="mx-auto max-w-98vw px-6 text-center lg:max-w-80vw">
      <div class="flex flex-col items-center">
        <?php
          $additionalPhones = json_decode($siteSettings['additional_phones'] ?? '[]', true);
          if (!is_array($additionalPhones)) $additionalPhones = [];
          $footerPhones = array_values(array_unique(array_filter(array_merge(
            [$siteSettings['contact_phone']],
            $additionalPhones
          ))));
        ?>
        <?php if (count($footerPhones) > 0 || $siteSettings['contact_email'] !== '' || $siteSettings['address'] !== ''): ?>
          <div class="mb-6 flex w-full flex-wrap items-center justify-evenly gap-2 sm:gap-3 lg:gap-4">
            <?php if ($siteSettings['address'] !== ''): ?>
              <div class="flex min-h-10 max-w-full shrink-0 items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-sm sm:min-h-11">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21s7-5.6 7-12a7 7 0 10-14 0c0 6.4 7 12 7 12zm0-9a3 3 0 100-6 3 3 0 000 6z"/></svg>
                <span class="break-words"><?= htmlspecialchars($siteSettings['address'], ENT_QUOTES, 'UTF-8') ?></span>
              </div>
            <?php endif; ?>
            <?php foreach ($footerPhones as $footerPhone):
              $phoneLink = preg_replace('/[^0-9+]/', '', $footerPhone);
            ?>
              <a href="tel:<?= htmlspecialchars($phoneLink, ENT_QUOTES, 'UTF-8') ?>" class="flex min-h-10 max-w-full shrink-0 items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-sm transition hover:bg-white/15 sm:min-h-11">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6A19.79 19.79 0 012.12 4.18 2 2 0 014.11 2h3a2 2 0 012 1.72c.13.96.35 1.9.66 2.81a2 2 0 01-.45 2.11L8.05 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.31 1.85.53 2.81.66A2 2 0 0122 16.92z"/></svg>
                <span><?= htmlspecialchars($footerPhone, ENT_QUOTES, 'UTF-8') ?></span>
              </a>
            <?php endforeach; ?>
            <?php if ($siteSettings['contact_email'] !== ''): ?>
              <a href="mailto:<?= htmlspecialchars($siteSettings['contact_email'], ENT_QUOTES, 'UTF-8') ?>" class="flex min-h-10 max-w-full shrink-0 items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-sm transition hover:bg-white/15 sm:min-h-11">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5h18v14H3zm0 1l9 7 9-7"/></svg>
                <span class="break-all"><?= htmlspecialchars($siteSettings['contact_email'], ENT_QUOTES, 'UTF-8') ?></span>
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
          <?php
            $footerPlatformNames = [
              'facebook' => 'Facebook',
              'telegram' => 'Telegram',
              'tiktok' => 'TikTok',
              'youtube' => 'YouTube',
              'instagram' => 'Instagram',
              'whatsapp' => 'WhatsApp',
            ];
            $footerPlatforms = array_values(array_unique(array_merge(['facebook', 'tiktok', 'telegram'], array_keys($socialLinks))));
            foreach ($footerPlatforms as $platform):
              $name = $footerPlatformNames[$platform] ?? ucfirst(str_replace('_', ' ', $platform));
              $url = $socialLinks[$platform] ?? '';
              $iconClass = $url !== '' ? 'bg-white text-slate-900 hover:bg-blue-50' : 'border border-white/25 bg-white/10 text-white/55';
              $iconSvg = '';
              switch ($platform) {
                case 'facebook':
                  $iconSvg = '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.413c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.235 2.686.235v2.97h-1.513c-1.49 0-1.956.929-1.956 1.882v2.269h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>';
                  break;
                case 'linkedin':
                  $iconSvg = '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.024-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.049c.476-.9 1.637-1.85 3.37-1.85 3.602 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 110-4.124 2.062 2.062 0 010 4.124zm1.78 13.019H3.555V9h3.562v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0z"/></svg>';
                  break;
                case 'youtube':
                  $iconSvg = '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>';
                  break;
                case 'telegram':
                  $iconSvg = '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0011.944 0zm5.58 8.258l-1.964 9.25c-.148.672-.535.835-1.084.52l-2.994-2.205-1.445 1.39c-.16.16-.294.294-.603.294l.216-3.053 5.56-5.024c.241-.216-.052-.336-.374-.12l-6.869 4.326-2.96-.924c-.644-.203-.657-.643.136-.95l11.57-4.46c.535-.195 1.003.13.83.956z"/></svg>';
                  break;
                case 'instagram':
                  $iconSvg = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="5" stroke-width="2"/><circle cx="12" cy="12" r="4" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>';
                  break;
                case 'tiktok':
                  $iconSvg = '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93v6.16c.01 1.21-.14 2.43-.64 3.54-.7 1.61-2.11 2.95-3.72 3.63-1.56.66-3.33.74-4.94.28-1.62-.46-3.07-1.47-4.04-2.84-1.02-1.43-1.47-3.23-1.26-4.97.24-1.96 1.29-3.82 2.84-5.01 1.53-1.18 3.57-1.73 5.48-1.47.02 1.48-.03 2.96-.03 4.44-.74-.23-1.58-.31-2.32-.05-.96.33-1.75 1.13-2.08 2.09-.34.97-.13 2.13.56 2.89.67.77 1.72 1.17 2.73 1.06 1.06-.11 2.07-.74 2.59-1.7.18-.34.31-.72.34-1.1.04-1.38.02-2.76.02-4.14V.02z"/></svg>';
                  break;
                case 'whatsapp':
                  $iconSvg = '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.52 3.48A11.91 11.91 0 0012.05 0C5.46 0 .12 5.34.12 11.92c0 2.1.55 4.15 1.59 5.96L.05 24l6.27-1.64a11.94 11.94 0 005.73 1.46h.01c6.59 0 11.94-5.34 11.94-11.92a11.84 11.84 0 00-3.48-8.42zm-8.47 18.34h-.01a9.96 9.96 0 01-5.08-1.39l-.36-.22-3.72.98 1-3.63-.24-.37a9.87 9.87 0 01-1.52-5.27c0-5.46 4.44-9.9 9.91-9.9a9.82 9.82 0 017 2.9 9.83 9.83 0 012.9 7c-.01 5.47-4.45 9.9-9.88 9.9zm5.43-7.41c-.3-.15-1.77-.87-2.04-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.49s1.07 2.89 1.22 3.09c.15.2 2.1 3.21 5.09 4.5.71.31 1.27.49 1.7.63.71.23 1.36.2 1.87.12.57-.08 1.77-.72 2.02-1.42.25-.7.25-1.3.17-1.42-.07-.13-.27-.2-.57-.35z"/></svg>';
                  break;
                default:
                  $iconSvg = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m5.656-5.656l3-3a4 4 0 015.656 5.656l-1.5 1.5m-8 3l7-7"/></svg>';
              }
          ?>
            <?php if ($url !== ''): ?>
              <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" class="flex h-10 w-10 items-center justify-center rounded-full shadow-sm transition hover:-translate-y-0.5 <?= $iconClass ?>"><?= $iconSvg ?></a>
            <?php else: ?>
              <span aria-label="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?> (not configured)" aria-disabled="true" title="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?> (not configured)" class="flex h-10 w-10 items-center justify-center rounded-full <?= $iconClass ?>"><?= $iconSvg ?></span>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>

        <div class="mt-7 w-full border-t border-white/10 pt-5">
          <p class="text-xs tracking-wide">Copyrights&copy; <?= date('Y') ?> <?= htmlspecialchars(strtoupper($siteSettings['site_name']), ENT_QUOTES, 'UTF-8') ?>. All rights reserved.</p>
          <p class="mt-2 text-xs tracking-wide">Developed by <a href="https://yared.infinityfreeapp.com/portfolio.php" target="_blank" rel="noopener noreferrer" class="font-semibold underline-offset-4 transition hover:underline">Yared</a></p>
        </div>
      </div>
    </div>
  </footer>



  <script>
    // ===== HERO CAROUSEL (dynamic, with dots + touch + auto) =====
    const carousel = document.getElementById('carousel');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');

    let slides = carousel.children;
    let totalSlides = slides.length;
    let currentIndex = 0;
    let autoSlide;

    function updateDots() {
      const dots = document.getElementById('hero-dots');
      if (!dots) return;
      const ds = dots.children;
      for (let i = 0; i < ds.length; i++) {
        ds[i].classList.toggle('active', i === currentIndex);
      }
    }

    function updateCarousel() {
      // Recalculate slides & totals in case DOM changed (API replaced slides)
      slides = carousel.children;
      totalSlides = slides.length || 1;
      carousel.style.transform = `translateX(-${currentIndex * 100}%)`;
      updateHeroContent(currentIndex);
      updateDots();
    }

    function nextSlide() {
      currentIndex = (currentIndex + 1) % totalSlides;
      updateCarousel();
    }

    function prevSlide() {
      currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
      updateCarousel();
    }

    function startAutoSlide() {
      clearInterval(autoSlide);
      autoSlide = setInterval(() => nextSlide(), 5000);
    }

    function resetAutoSlide() {
      clearInterval(autoSlide);
      startAutoSlide();
    }

    // Event Listeners
    nextBtn && nextBtn.addEventListener('click', () => { nextSlide(); resetAutoSlide(); });
    prevBtn && prevBtn.addEventListener('click', () => { prevSlide(); resetAutoSlide(); });

    // Touch/Swipe Support
    let touchStartX = 0;
    carousel.parentElement.addEventListener('touchstart', e => {
      touchStartX = e.changedTouches[0].screenX;
    });
    carousel.parentElement.addEventListener('touchend', e => {
      const touchEndX = e.changedTouches[0].screenX;
      if (touchStartX - touchEndX > 50) nextSlide();
      if (touchEndX - touchStartX > 50) prevSlide();
      resetAutoSlide();
    });

    // Pause on hover (desktop)
    carousel.parentElement.addEventListener('mouseenter', () => clearInterval(autoSlide));
    carousel.parentElement.addEventListener('mouseleave', startAutoSlide);

    // If there are already slides in the markup, initialize dots (else initHeroCarousel will create them)
    (function ensureDotsForStatic() {
      const dots = document.getElementById('hero-dots');
      if (!dots) {
        const d = document.createElement('div');
        d.id = 'hero-dots';
        d.className = 'dots';
        carousel.parentElement.appendChild(d);
        for (let i = 0; i < carousel.children.length; i++) {
          const dot = document.createElement('div');
          dot.className = i === 0 ? 'dot active' : 'dot';
          dot.dataset.index = i;
          dot.addEventListener('click', (e) => { currentIndex = Number(e.target.dataset.index); updateCarousel(); resetAutoSlide(); });
          d.appendChild(dot);
        }
      }
    })();

    // Initialize
    updateCarousel();
    startAutoSlide();
  </script>


  <script>
    // ===== FLOATING CONTACT PANEL =====
    const floatingContactPanel = document.getElementById('floating-contact-panel');
    const floatingContactButton = document.getElementById('floating-contact-button');
    const closeContactForm = document.getElementById('close-contact-form');

    function openFloatingContact() {
      floatingContactPanel.classList.add('open');
      floatingContactPanel.setAttribute('aria-hidden', 'false');
      floatingContactButton.setAttribute('aria-expanded', 'true');
    }

    function closeFloatingContact() {
      floatingContactPanel.classList.remove('open');
      floatingContactPanel.setAttribute('aria-hidden', 'true');
      floatingContactButton.setAttribute('aria-expanded', 'false');
    }

    floatingContactButton.addEventListener('click', () => {
      if (floatingContactPanel.classList.contains('open')) closeFloatingContact();
      else openFloatingContact();
    });
    closeContactForm.addEventListener('click', closeFloatingContact);
    document.querySelectorAll('.open-contact-form').forEach(button => {
      button.addEventListener('click', openFloatingContact);
    });
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape') closeFloatingContact();
    });

    // ===== CONTACT FORM AJAX – AUTO-HIDE MESSAGE AFTER 3 SECONDS =====
    document.getElementById('contact-form').addEventListener('submit', function (e) {
      e.preventDefault();

      const form = e.target;
      const messageDiv = document.getElementById('form-message');
      const submitBtn = form.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;

      // Clear previous message
      messageDiv.classList.add('hidden');
      clearTimeout(messageDiv.hideTimeout); // Clear any existing timeout

      // Client-side phone validation: block alphabets and invalid formats
      const phoneVal = (form.querySelector('input[name="phone"]') || { value: '' }).value.trim();
      const phoneRegex = /^[0-9+()\-\s]{6,30}$/;
      if (!phoneRegex.test(phoneVal)) {
        messageDiv.classList.remove('hidden');
        messageDiv.classList.add('text-red-400');
        messageDiv.textContent = 'Please enter a valid phone number (digits, +, spaces, dashes allowed).';
        // Auto-hide
        messageDiv.hideTimeout = setTimeout(() => { messageDiv.classList.add('hidden'); }, 3000);
        return;
      }

      // Show loading
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span>Sending...</span>';

      const formData = new FormData(form);

      fetch('submit_contact.php', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          // Show message
          messageDiv.classList.remove('hidden', 'text-green-400', 'text-red-400');
          messageDiv.classList.add(data.success ? 'text-green-400' : 'text-red-400');
          messageDiv.textContent = data.message;

          if (data.success) {
            form.reset();
            clearTimeout(messageDiv.hideTimeout);
            messageDiv.hideTimeout = setTimeout(() => {
              messageDiv.classList.add('hidden');
              closeFloatingContact();
            }, 4000);
            return;
          }

          // Auto-hide after 3 seconds
          messageDiv.hideTimeout = setTimeout(() => {
            messageDiv.classList.add('hidden');
          }, 3000);
        })
        .catch(() => {
          messageDiv.classList.remove('hidden');
          messageDiv.classList.add('text-red-400');
          messageDiv.textContent = 'Network error. Please try again.';

          // Auto-hide error too
          messageDiv.hideTimeout = setTimeout(() => {
            messageDiv.classList.add('hidden');
          }, 3000);
        })
        .finally(() => {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        });
    });
  </script>

  <!-- IMAGE MODAL -->
  <div id="image-modal" class="fixed inset-0 bg-black/20 hidden z-50 flex items-center justify-center">
    <div class="relative w-[90vw] h-[90vh] max-w-5xl bg-white rounded-lg shadow-lg overflow-hidden flex flex-col">
      <button id="close-modal" class="absolute top-4 right-4 text-gray-600 text-3xl z-20 font-bold">&times;</button>
      <div class="image-carousel-modal flex-1">
        <div class="slides-modal"></div>
        <div class="dots-modal"></div>
      </div>
      <div class="p-6 bg-white flex-shrink-0">
        <h3 class="text-2xl font-bold text-black" id="modal-make-model"></h3>
        <div class="flex justify-center items-center mt-2" id="modal-price-row"></div>
        <div class="w-full mx-auto text-black mt-4">
          <div class="flex justify-between text-sm">
            <span id="modal-year"></span>
            <span id="modal-mileage"></span>
          </div>
          <div class="flex justify-between text-sm mt-1">
            <span id="modal-fuel"></span>
            <span id="modal-transmission"></span>
          </div>
        </div>
        <p class="text-sm text-black mt-4" id="modal-description"></p>
      </div>
    </div>
  </div>

  <script>
    // Close modal events
    document.getElementById('close-modal').addEventListener('click', closeModal);
    document.getElementById('image-modal').addEventListener('click', (e) => {
      if (e.target === document.getElementById('image-modal')) closeModal();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !document.getElementById('image-modal').classList.contains('hidden')) closeModal();
    });
  </script>

  <script>
    const headerRefreshButton = document.getElementById('header-refresh-button');
    headerRefreshButton?.addEventListener('click', () => {
      headerRefreshButton.classList.add('refreshing');
      headerRefreshButton.disabled = true;

      const refreshUrl = new URL(window.location.href);
      refreshUrl.searchParams.set('_refresh', Date.now().toString());

      window.location.replace(refreshUrl.toString());
    });

    let pwaInstallPrompt = null;
    const pwaInstallButton = document.getElementById('pwa-install-button');

    window.addEventListener('beforeinstallprompt', event => {
      event.preventDefault();
      pwaInstallPrompt = event;
      if (pwaInstallButton) {
        pwaInstallButton.hidden = false;
        pwaInstallButton.classList.add('flex');
      }
    });

    pwaInstallButton?.addEventListener('click', async () => {
      if (!pwaInstallPrompt) return;
      pwaInstallPrompt.prompt();
      await pwaInstallPrompt.userChoice;
      pwaInstallPrompt = null;
      pwaInstallButton.hidden = true;
    });

    window.addEventListener('appinstalled', () => {
      if (pwaInstallButton) pwaInstallButton.hidden = true;
    });

    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('service-worker.js').catch(() => {});
      });
    }
  </script>

</body>

</html>