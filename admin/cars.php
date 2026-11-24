<?php
require_once __DIR__ . '/session.php';

$uploadDir = '../uploads/';
$maxFileSize = 20 * 1024 * 1024; // 20MB
$allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif'];
$maxImages = 5;

// === HANDLE ADD / EDIT ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $make = trim($_POST['make']);
  $model = trim($_POST['model']);
  $year = (int) $_POST['year'];
  $price = (float) $_POST['price'];
  $mileage = (int) $_POST['mileage'];
  $fuel = $_POST['fuel_type'];
  $trans = $_POST['transmission'];
  $description = trim($_POST['description']);
  $featured = isset($_POST['featured']) ? 1 : 0;
  // Validate category/label against allowed values (optional field)
  $allowed_categories = ['New', 'Secondhand', 'Classic'];
  $category = null;
  if (isset($_POST['category']) && in_array($_POST['category'], $allowed_categories, true)) {
    $category = $_POST['category'];
  }
  $discount = (float) $_POST['discount_percentage'];
  $old_price = (float) $_POST['old_price'];

  // Preserve existing images when editing: if edit_id present, load images from DB
  $images = [];
  if (isset($_POST['edit_id']) && $_POST['edit_id'] !== '') {
    $id = (int) $_POST['edit_id'];
    $stmtImgs = $pdo->prepare("SELECT images FROM cars WHERE id = ?");
    $stmtImgs->execute([$id]);
    $rowImgs = $stmtImgs->fetch();
    $images = $rowImgs && $rowImgs['images'] ? (json_decode($rowImgs['images'], true) ?: []) : [];
  }

  if (isset($_FILES['car_images']) && $_FILES['car_images']['error'][0] !== 4) {
    $files = $_FILES['car_images'];
    $uploaded = [];

    for ($i = 0; $i < count($files['name']); $i++) {
      if ($files['error'][$i] !== 0)
        continue;
      if ($files['size'][$i] > $maxFileSize) {
        $error = "Image " . ($i + 1) . " too large (max 20MB).";
        break;
      }
      if (!in_array($files['type'][$i], $allowedTypes)) {
        $error = "Invalid image type for image " . ($i + 1) . ".";
        break;
      }

      $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
      $filename = uniqid('car_') . '.' . $ext;
      $dest = $uploadDir . $filename;

      if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
        $uploaded[] = 'uploads/' . $filename;
      }
    }

    if (!isset($error)) {
      $remaining = $maxImages - count($images);
      $uploaded = array_slice($uploaded, 0, $remaining);
      $images = array_merge($images, $uploaded);
    }
  }

  if (!isset($error)) {
    // Detect whether 'category' column exists; attempt to add it if missing.
    $hasCategory = false;
    try {
      $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'cars' AND column_name = 'category'");
      $check->execute();
      $hasCategory = (bool) $check->fetchColumn();
    } catch (Exception $e) {
      $hasCategory = false;
    }

    if (!$hasCategory) {
      try {
        $pdo->exec("ALTER TABLE cars ADD COLUMN category VARCHAR(32) DEFAULT NULL");
        $hasCategory = true;
      } catch (Exception $e) {
        // ignore - proceed without category column
        $hasCategory = false;
      }
    }

    $imageJson = json_encode($images);
    $primaryImage = $images[0] ?? '';
    if (isset($_POST['edit_id']) && $_POST['edit_id'] !== '') {
      $id = (int) $_POST['edit_id'];
      if ($hasCategory) {
        $stmt = $pdo->prepare("UPDATE cars SET make=?, model=?, year=?, price=?, mileage=?, fuel_type=?, transmission=?, description=?, images=?, image_url=?, featured=?, category=?, discount_percentage=?, old_price=?, created_at=NOW() WHERE id=?");
        $stmt->execute([$make, $model, $year, $price, $mileage, $fuel, $trans, $description, $imageJson, $primaryImage, $featured, $category, $discount, $old_price, $id]);
      } else {
        $stmt = $pdo->prepare("UPDATE cars SET make=?, model=?, year=?, price=?, mileage=?, fuel_type=?, transmission=?, description=?, images=?, image_url=?, featured=?, discount_percentage=?, old_price=?, created_at=NOW() WHERE id=?");
        $stmt->execute([$make, $model, $year, $price, $mileage, $fuel, $trans, $description, $imageJson, $primaryImage, $featured, $discount, $old_price, $id]);
      }
    } else {
      if ($hasCategory) {
        $stmt = $pdo->prepare("INSERT INTO cars (make, model, year, price, mileage, fuel_type, transmission, description, images, image_url, featured, category, discount_percentage, old_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$make, $model, $year, $price, $mileage, $fuel, $trans, $description, $imageJson, $primaryImage, $featured, $category, $discount, $old_price]);
      } else {
        $stmt = $pdo->prepare("INSERT INTO cars (make, model, year, price, mileage, fuel_type, transmission, description, images, image_url, featured, discount_percentage, old_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$make, $model, $year, $price, $mileage, $fuel, $trans, $description, $imageJson, $primaryImage, $featured, $discount, $old_price]);
      }
    }

    header("Location: cars.php?saved=1");
    exit;
  }
}

// === HANDLE DELETE ===
if (isset($_GET['delete'])) {
  $id = (int) $_GET['delete'];
  $stmt = $pdo->prepare("SELECT images FROM cars WHERE id = ?");
  $stmt->execute([$id]);
  $car = $stmt->fetch();
  if ($car && $car['images']) {
    $imgs = json_decode($car['images'], true) ?: [];
    foreach ($imgs as $img) {
      $path = '../' . $img;
      if (file_exists($path))
        unlink($path);
    }
  }
  $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");
  $stmt->execute([$id]);
  header("Location: cars.php?deleted=1");
  exit;
}

// === FETCH CARS ===
$stmt = $pdo->query("SELECT * FROM cars ORDER BY created_at DESC");
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editCar = null;
if (isset($_GET['edit'])) {
  $id = (int) $_GET['edit'];
  $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
  $stmt->execute([$id]);
  $editCar = $stmt->fetch();

  // Handle image deletion
  if (isset($_GET['delete_image'])) {
    $imgs = json_decode($editCar['images'], true) ?: [];
    $index = (int) $_GET['delete_image'];
    if (isset($imgs[$index])) {
      $path = '../' . $imgs[$index];
      if (file_exists($path))
        unlink($path);
      unset($imgs[$index]);
      $imgs = array_values($imgs); // reindex
      $imageJson = json_encode($imgs);
      $stmt = $pdo->prepare("UPDATE cars SET images=? WHERE id=?");
      $stmt->execute([$imageJson, $editCar['id']]);
      header("Location: ?edit=" . $editCar['id']);
      exit;
    }
  }
}
// Helper: format number as Ethiopian Birr (ETB) for display
function format_etb($val)
{
  if ($val === null || $val === '')
    return '';
  // Round to nearest whole birr for display
  $n = round((float) $val);
  return 'ETB ' . number_format($n);
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Cars | <?= htmlspecialchars($siteSettings['site_name'], ENT_QUOTES, 'UTF-8') ?> Admin</title>
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
    html,
    body {
      height: 100%;
      margin: 0;
    }

    .modal {
      transition: opacity 0.3s ease, transform 0.3s ease;
      opacity: 0;
      transform: scale(0.95);
      pointer-events: none;
    }

    .modal.open {
      opacity: 1;
      transform: scale(1);
      pointer-events: auto;
    }

    .carousel {
      scrollbar-width: thin;
    }

    .carousel::-webkit-scrollbar {
      height: 6px;
    }

    .carousel::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 3px;
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

  <?php $activeSidebar = 'cars'; include __DIR__ . '/sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <!-- Add top padding to avoid content going under fixed header -->
  <main id="main-content" class="flex-1 transition-all duration-300 bg-gray-50 w-full pt-16">
    <div class="w-full max-w-[96vw] mx-auto py-10 px-4 lg:px-8">

      <div class="flex flex-col gap-6">
        <div class="flex flex-row items-center justify-between gap-4">
          <h1 class="text-lg xs:text-xl sm:text-2xl md:text-3xl font-bold whitespace-nowrap">Manage Cars</h1>
          <div class="flex justify-end">
            <button id="add-car-btn"
              class="bg-primary hover:bg-blue-800 text-white px-3 py-2 sm:px-5 sm:py-2.5 rounded-lg font-medium transition text-sm sm:text-base whitespace-nowrap">
              Add Car
            </button>
          </div>
        </div>

        <!-- SEARCH BAR -->
        <div class="flex justify-center mb-6 md:mb-8">
          <div class="w-full max-w-2xl">
            <label for="search-input" class="sr-only">Search by brand, model, year, price...</label>
            <input type="text" id="search-input" placeholder="Search by make, model, year, price..."
              class="w-full px-4 py-3 bg-white dark:bg-gray-800 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
          </div>
        </div>
      </div>

      <!-- CARS GRID -->
      <div id="cars-container" class="grid gap-6 grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        <?php foreach ($cars as $car):
          $imgs = json_decode($car['images'], true) ?: [];
          $hasImages = !empty($imgs);
          ?>
          <div class="car-card bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition"
            data-make="<?= strtolower($car['make']) ?>" data-model="<?= strtolower($car['model']) ?>"
            data-year="<?= $car['year'] ?>" data-price="<?= $car['price'] ?>">
            <?php if ($hasImages): ?>
              <div class="relative">
                <div
                  class="w-full h-48 sm:h-56 md:h-64 flex items-center justify-center bg-gray-100 overflow-hidden rounded-t-xl">
                  <img src="../<?= htmlspecialchars($imgs[0]) ?>"
                    alt="<?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?>"
                    class="max-h-full max-w-full object-contain rounded-t-xl" />
                </div>
                <?php if (count($imgs) > 1): ?>
                  <div class="absolute top-2 right-2 bg-black bg-opacity-50 text-white px-2 py-1 rounded-full text-xs">
                    +<?= count($imgs) - 1 ?> more
                  </div>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <div class="w-full h-48 sm:h-56 md:h-64 flex items-center justify-center bg-gray-200 rounded-t-xl">
                <span class="text-gray-500">No Image</span>
              </div>
            <?php endif; ?>
            <div class="p-4">
              <div class="flex justify-between items-center mb-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">Brand: <span
                    class="font-bold text-lg text-gray-800 dark:text-gray-100"><?= htmlspecialchars($car['make']) ?></span></span>
                <span class="text-sm text-gray-600 dark:text-gray-400">Model: <span
                    class="font-bold text-lg text-gray-800 dark:text-gray-100"><?= htmlspecialchars($car['model']) ?></span></span>
              </div>
              <?php if (!empty($car['category'])): ?>
                <div class="mb-2">
                  <span
                    class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full"><?= htmlspecialchars($car['category']) ?></span>
                </div>
              <?php endif; ?>
              <div class="flex items-center justify-between mb-2">
                <?php if ($car['old_price']): ?>
                  <p class="text-sm text-gray-500 line-through"><?= htmlspecialchars(format_etb($car['old_price'])) ?></p>
                <?php endif; ?>
                <p class="text-xl font-bold text-primary"><?= htmlspecialchars(format_etb($car['price'])) ?></p>
                <?php if ($car['discount_percentage'] > 0): ?>
                  <span
                    class="inline-block bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">-<?= $car['discount_percentage'] ?>%</span>
                <?php endif; ?>
              </div>
              <div class="grid grid-cols-2 gap-2 text-sm text-gray-600 dark:text-gray-300">
                <div>Year: <?= $car['year'] ?></div>
                <div>Mileage: <?= number_format($car['mileage']) ?> km</div>
                <div>Fuel: <?= $car['fuel_type'] ?></div>
                <div>Transmission: <?= $car['transmission'] ?></div>
              </div>
              <?php if ($car['description']): ?>
                <div class="mt-2">
                  <p class="text-sm text-gray-700 dark:text-gray-200 description-text"
                    data-full="<?= htmlspecialchars($car['description']) ?>">
                    <?= strlen($car['description']) > 30 ? htmlspecialchars(substr($car['description'], 0, 30)) . '...' : htmlspecialchars($car['description']) ?>
                  </p>
                  <?php if (strlen($car['description']) > 30): ?>
                    <button class="text-primary text-xs mt-1 read-more-btn">Read More</button>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <?php if ($car['featured']): ?>
                <span class="inline-block mt-2 bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Featured</span>
              <?php endif; ?>
              <div class="mt-4 flex gap-2">
                <a href="?edit=<?= $car['id'] ?>"
                  class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-2  rounded-lg text-sm font-medium transition">
                  Edit
                </a>
                <button type="button"
                  class="delete-car-btn flex-1 bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg text-sm font-medium transition"
                  data-id="<?= $car['id'] ?>">
                  Delete
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if (empty($cars)): ?>
        <p class="text-center text-gray-500 mt-8">No cars in inventory.</p>
      <?php endif; ?>

      <!-- ADD CAR MODAL -->
      <div id="add-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 modal">
        <div
          class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto p-6 relative">
          <button type="button" id="close-add-modal"
            class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 z-10">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <h2 class="text-2xl font-bold mb-4 pr-10">Add New Car</h2>

          <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div class="grid md:grid-cols-3 gap-4">
              <div>
                <label for="make" class="block text-sm font-medium mb-1">Brand</label>
                <input type="text" id="make" name="make" required placeholder="Brand"
                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
              </div>
              <div>
                <label for="model" class="block text-sm font-medium mb-1">Model</label>
                <input type="text" id="model" name="model" required placeholder="Model"
                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
              </div>
              <div>
                <label for="year" class="block text-sm font-medium mb-1">Year</label>
                <input type="number" id="year" name="year" required placeholder="Year" min="1980" max="2030"
                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
              </div>
            </div>

            <!-- PRICE / OLD PRICE / DISCOUNT (ONE ROW ON LARGE SCREENS) -->
            <div class="grid md:grid-cols-3 gap-4">
              <div>
                <label for="price" class="block text-sm font-medium mb-1">Current Price (ETB)</label>
                <input type="number" id="price" name="price" required placeholder="Current Price (ETB)" step="0.01"
                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                <p id="price-error" class="mt-1 text-xs text-red-600"></p>
              </div>
              <div>
                <label for="old_price" class="block text-sm font-medium mb-1">Old Price(ETB)</label>
                <input type="number" id="old_price" name="old_price" placeholder="Old Price(ETB)" step="0.01"
                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                <p id="old-price-error" class="mt-1 text-xs text-red-600"></p>
              </div>
              <div>
                <label for="discount_percentage" class="block text-sm font-medium mb-1">Discount %</label>
                <input type="number" id="discount_percentage" name="discount_percentage" placeholder="Discount %"
                  min="0" max="100" step="0.01"
                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                <p id="discount-error" class="mt-1 text-xs text-red-600"></p>
              </div>
            </div>

            <!-- DISCOUNT CALCULATION PREVIEW JUST BELOW PRICE ROW -->
            <div id="discount-preview" class="bg-green-50 dark:bg-green-900 p-4 rounded-lg text-sm">
              <p><strong>Old Price:</strong> <span id="old-price-display"></span></p>
              <p><strong>Current Price:</strong> <span id="current-price-display"></span></p>
              <p><strong>Discount:</strong> <span id="discount-display"></span></p>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label for="mileage" class="block text-sm font-medium mb-1">Mileage (km)</label>
                <input type="number" id="mileage" name="mileage" required placeholder="Mileage (km)"
                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
              </div>
              <div>
                <label for="fuel_type" class="block text-sm font-medium mb-1">Fuel Type</label>
                <select id="fuel_type" name="fuel_type" required
                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                  <option value="">Fuel Type</option>
                  <option>Petrol</option>
                  <option>Diesel</option>
                  <option>Electric</option>
                  <option>Hybrid</option>
                </select>
              </div>
              <div>
                <label for="transmission" class="block text-sm font-medium mb-1">Transmission</label>
                <select id="transmission" name="transmission" required
                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                  <option value="">Transmission</option>
                  <option>Manual</option>
                  <option>Automatic</option>
                </select>
              </div>
              <div class="flex flex-col space-y-2">
                <div>
                  <label for="category" class="block text-sm font-medium mb-1">Category</label>
                  <select id="category" name="category"
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Select label</option>
                    <option value="New">New</option>
                    <option value="Secondhand">Secondhand</option>
                    <option value="Classic">Classic</option>
                  </select>
                </div>
                <div class="flex items-center space-x-2">
                  <input type="checkbox" id="featured" name="featured" class="w-5 h-5">
                  <label for="featured" class="text-sm font-medium">Featured</label>
                </div>
              </div>
            </div>

            <div>
              <label for="description" class="block text-sm font-medium mb-1">Description (optional)</label>
              <textarea id="description" name="description" rows="3" placeholder="Description (optional)"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
            </div>

            <div class="space-y-2">
              <label for="car_images" class="block text-sm font-medium">Car Images (up to 5, max 20MB each)</label>
              <input type="file" id="car_images" name="car_images[]" multiple accept="image/*" required
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-blue-800">
              <p class="text-xs text-gray-500">Hold Ctrl/Cmd to select multiple</p>
            </div>

            <div class="flex gap-3">
              <button type="submit"
                class="flex-1 bg-primary hover:bg-blue-800 text-white py-2 rounded-lg font-medium transition">
                Add Car
              </button>
              <button type="button" id="cancel-add"
                class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-lg font-medium transition">
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- EDIT MODAL -->
      <?php if ($editCar): ?>
        <div id="edit-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 modal open">
          <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto p-6 relative">
            <button type="button" id="close-edit-modal"
              class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 z-10">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>

            <h2 class="text-2xl font-bold mb-4 pr-10">Edit Car</h2>

            <form method="POST" enctype="multipart/form-data" class="space-y-4">
              <input type="hidden" name="edit_id" value="<?= $editCar['id'] ?>">

              <div class="grid md:grid-cols-3 gap-4">
                <div>
                  <label for="edit-make" class="block text-sm font-medium mb-1">Brand</label>
                  <input type="text" id="edit-make" name="make" required placeholder="Brand"
                    value="<?= $editCar['make'] ?>"
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                  <label for="edit-model" class="block text-sm font-medium mb-1">Model</label>
                  <input type="text" id="edit-model" name="model" required placeholder="Model"
                    value="<?= $editCar['model'] ?>"
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                  <label for="edit-year" class="block text-sm font-medium mb-1">Year</label>
                  <input type="number" id="edit-year" name="year" required placeholder="Year" min="1980" max="2030"
                    value="<?= $editCar['year'] ?>"
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
              </div>

              <!-- EDIT PRICE / OLD PRICE / DISCOUNT (ONE ROW ON LARGE SCREENS) -->
              <div class="grid md:grid-cols-3 gap-4">
                <div>
                  <label for="edit-price" class="block text-sm font-medium mb-1">Current Price (ETB)</label>
                  <input type="number" id="edit-price" name="price" required placeholder="Current Price (ETB)" step="0.01"
                    value="<?= $editCar['price'] ?>"
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                  <p id="edit-price-error" class="mt-1 text-xs text-red-600"></p>
                </div>
                <div>
                  <label for="edit-old_price" class="block text-sm font-medium mb-1">Old Price(ETB)</label>
                  <input type="number" id="edit-old_price" name="old_price" placeholder="Old Price(ETB)" step="0.01"
                    value="<?= $editCar['old_price'] ?>"
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                  <p id="edit-old-price-error" class="mt-1 text-xs text-red-600"></p>
                </div>
                <div>
                  <label for="edit-discount_percentage" class="block text-sm font-medium mb-1">Discount %</label>
                  <input type="number" id="edit-discount_percentage" name="discount_percentage" placeholder="Discount %"
                    min="0" max="100" step="0.01" value="<?= $editCar['discount_percentage'] ?>"
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                  <p id="edit-discount-error" class="mt-1 text-xs text-red-600"></p>
                </div>
              </div>

              <!-- EDIT DISCOUNT CALCULATION PREVIEW JUST BELOW PRICE ROW -->
              <div id="edit-discount-preview" class="bg-green-50 dark:bg-green-900 p-4 rounded-lg text-sm">
                <p><strong>Old Price:</strong> <span id="edit-old-price-display"></span></p>
                <p><strong>Current Price:</strong> <span id="edit-current-price-display"></span></p>
                <p><strong>Discount:</strong> <span id="edit-discount-display"></span></p>
              </div>

              <div class="grid md:grid-cols-2 gap-4 mt-4">
                <div>
                  <label for="edit-mileage" class="block text-sm font-medium mb-1">Mileage (km)</label>
                  <input type="number" id="edit-mileage" name="mileage" required placeholder="Mileage (km)"
                    value="<?= $editCar['mileage'] ?>"
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                  <label for="edit-fuel_type" class="block text-sm font-medium mb-1">Fuel Type</label>
                  <select id="edit-fuel_type" name="fuel_type" required
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Fuel Type</option>
                    <option <?= $editCar['fuel_type'] == 'Petrol' ? 'selected' : '' ?>>Petrol</option>
                    <option <?= $editCar['fuel_type'] == 'Diesel' ? 'selected' : '' ?>>Diesel</option>
                    <option <?= $editCar['fuel_type'] == 'Electric' ? 'selected' : '' ?>>Electric</option>
                    <option <?= $editCar['fuel_type'] == 'Hybrid' ? 'selected' : '' ?>>Hybrid</option>
                  </select>
                </div>
                <div>
                  <label for="edit-transmission" class="block text-sm font-medium mb-1">Transmission</label>
                  <select id="edit-transmission" name="transmission" required
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Transmission</option>
                    <option <?= $editCar['transmission'] == 'Manual' ? 'selected' : '' ?>>Manual</option>
                    <option <?= $editCar['transmission'] == 'Automatic' ? 'selected' : '' ?>>Automatic</option>
                  </select>
                </div>
                <div class="flex flex-col space-y-2">
                  <div>
                    <label for="edit-category" class="block text-sm font-medium mb-1">Label</label>
                    <select id="edit-category" name="category"
                      class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                      <option value="">Select label</option>
                      <option value="New" <?= isset($editCar['category']) && $editCar['category'] === 'New' ? 'selected' : '' ?>>New</option>
                      <option value="Secondhand" <?= isset($editCar['category']) && $editCar['category'] === 'Secondhand' ? 'selected' : '' ?>>Secondhand</option>
                      <option value="Classic" <?= isset($editCar['category']) && $editCar['category'] === 'Classic' ? 'selected' : '' ?>>Classic</option>
                    </select>
                  </div>
                  <div class="flex items-center space-x-2">
                    <input type="checkbox" id="edit-featured" name="featured" <?= $editCar['featured'] ? 'checked' : '' ?>
                      class="w-5 h-5">
                    <label for="edit-featured" class="text-sm font-medium">Featured</label>
                  </div>
                </div>
              </div>

              <div>
                <label for="edit-description" class="block text-sm font-medium mb-1">Description (optional)</label>
                <textarea id="edit-description" name="description" rows="3" placeholder="Description (optional)"
                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"><?= $editCar['description'] ?></textarea>
              </div>

              <div class="space-y-2">
                <label for="edit-car_images" class="block text-sm font-medium">Car Images (up to 5, max 20MB each)</label>
                <input type="file" id="edit-car_images" name="car_images[]" multiple accept="image/*"
                  class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-blue-800">
                <p class="text-xs text-gray-500">Hold Ctrl/Cmd to select multiple</p>
                <?php if ($editCar['images']):
                  $imgs = json_decode($editCar['images'], true) ?: []; ?>
                  <div class="carousel flex overflow-x-auto snap-x snap-mandatory mt-2">
                    <?php foreach ($imgs as $index => $img): ?>
                      <div class="snap-center shrink-0 w-48 h-48 mr-2 relative">
                        <div class="w-48 h-48 flex items-center justify-center bg-gray-100 overflow-hidden rounded-md">
                          <img src="../<?= htmlspecialchars($img) ?>" alt="Car"
                            class="max-h-full max-w-full object-contain rounded-md" />
                        </div>
                        <button type="button"
                          class="delete-image-btn absolute top-2 right-2 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-700"
                          data-index="<?= $index ?>">&times;</button>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>

              <div class="flex gap-3">
                <button type="submit"
                  class="flex-1 bg-primary hover:bg-blue-800 text-white py-2 rounded-lg font-medium transition">
                  Update Car
                </button>
                <button type="button" id="cancel-edit"
                  class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-lg font-medium transition">
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      <?php endif; ?>
  </main>

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
          // Shift header brand to appear just after sidebar width (with ~2px gap)
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
    applyLayout();

    document.getElementById('profile-btn').addEventListener('click', (e) => {
      e.stopPropagation();
      document.getElementById('profile-menu').classList.toggle('hidden');
    });

    document.addEventListener('click', () => document.getElementById('profile-menu').classList.add('hidden'));

    // === MODAL CONTROL (Add) ===
    const addModal = document.getElementById('add-modal');
    const addBtn = document.getElementById('add-car-btn');
    const closeAddModal = document.getElementById('close-add-modal');
    const cancelAdd = document.getElementById('cancel-add');

    function openAddModal() { addModal.classList.add('open'); }

    function confirmAndCloseAddModal() {
      // Ask for confirmation before closing the create form
      Swal.fire({
        title: 'Are you sure?',
        text: 'If you close now, any unsaved car information will be lost.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, close',
        cancelButtonText: 'No, keep editing',
        confirmButtonColor: '#d33'
      }).then(result => {
        if (result.isConfirmed) {
          addModal.classList.remove('open');
        }
      });
    }

    addBtn?.addEventListener('click', openAddModal);
    // Only close via explicit buttons, not by clicking outside
    closeAddModal?.addEventListener('click', confirmAndCloseAddModal);
    cancelAdd?.addEventListener('click', confirmAndCloseAddModal);

    // === EDIT MODAL ===
    const editModal = document.getElementById('edit-modal');
    const closeEditModal = document.getElementById('close-edit-modal');
    const cancelEdit = document.getElementById('cancel-edit');

    function confirmAndCloseEditModal() {
      Swal.fire({
        title: 'Close without saving?',
        text: 'If you close now, any unsaved changes will be lost.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, close',
        cancelButtonText: 'No, keep editing'
      }).then(result => {
        if (result.isConfirmed) window.location.href = 'cars.php';
      });
    }

    closeEditModal?.addEventListener('click', confirmAndCloseEditModal);
    cancelEdit?.addEventListener('click', confirmAndCloseEditModal);
    // Prevent closing by clicking outside or pressing escape
    editModal?.addEventListener('click', (e) => { if (e.target === editModal) e.stopPropagation(); });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && editModal.classList.contains('open')) {
        e.preventDefault();
      }
    });

    // === DISCOUNT PREVIEW (ADD) ===
    const priceInput = document.getElementById('price');
    const oldPriceInput = document.getElementById('old_price');
    const discountInput = document.getElementById('discount_percentage');
    const preview = document.getElementById('discount-preview');
    const oldPriceDisplay = document.getElementById('old-price-display');
    const currentPriceDisplay = document.getElementById('current-price-display');
    const discountDisplay = document.getElementById('discount-display');

    const priceError = document.getElementById('price-error');
    const oldPriceError = document.getElementById('old-price-error');
    const discountError = document.getElementById('discount-error');

    function clearAddErrors() {
      if (priceError) priceError.textContent = '';
      if (oldPriceError) oldPriceError.textContent = '';
      if (discountError) discountError.textContent = '';
    }

    function validateAddPricingValues(price, oldPrice, discount) {
      clearAddErrors();
      let valid = true;

      if (price <= 0) {
        if (priceError) priceError.textContent = 'Current price is required and must be greater than 0.';
        valid = false;
      }
      if (oldPrice < 0) {
        if (oldPriceError) oldPriceError.textContent = 'Old price cannot be negative.';
        valid = false;
      }
      if (discount < 0 || discount > 100) {
        if (discountError) discountError.textContent = 'Discount must be between 0 and 100.';
        valid = false;
      }
      if (price > 0 && oldPrice > 0 && oldPrice < price) {
        if (oldPriceError) oldPriceError.textContent = 'Old price should be greater than or equal to current price.';
        valid = false;
      }

      return valid;
    }

    // Track which field the user is actively editing so we do NOT overwrite it
    let isPriceFocused = false;
    let isOldPriceFocused = false;
    let isDiscountFocused = false;

    priceInput?.addEventListener('focus', () => { isPriceFocused = true; });
    priceInput?.addEventListener('blur', () => { isPriceFocused = false; });
    oldPriceInput?.addEventListener('focus', () => { isOldPriceFocused = true; });
    oldPriceInput?.addEventListener('blur', () => { isOldPriceFocused = false; });
    discountInput?.addEventListener('focus', () => { isDiscountFocused = true; });
    discountInput?.addEventListener('blur', () => { isDiscountFocused = false; });

    function updatePreview() {
      if (!priceInput || !oldPriceInput || !discountInput || !preview) return;

      const price = parseFloat(priceInput.value) || 0;
      const oldPrice = parseFloat(oldPriceInput.value) || 0;
      const discount = parseFloat(discountInput.value) || 0;

      const valid = validateAddPricingValues(price, oldPrice, discount);
      // If only current price is set (no old, no discount), it's valid but no discount calculation
      if (!valid || (price > 0 && oldPrice <= 0 && discount <= 0)) {
        preview.classList.add('hidden');
        return;
      }

      let calcOld = oldPrice;
      let calcDiscount = discount;

      // Auto-calc while also reflecting into the fields,
      // but only when one piece is clearly missing AND that field is not being edited
      if (price > 0 && oldPrice > 0 && discount === 0) {
        // Have current + old, missing discount
        calcDiscount = ((oldPrice - price) / oldPrice * 100).toFixed(2);
        if (!isDiscountFocused && discountInput) {
          discountInput.value = calcDiscount;
        }
      } else if (price > 0 && discount > 0 && oldPrice === 0) {
        // Have current + discount, missing old
        calcOld = (price / (1 - discount / 100)).toFixed(2);
        if (!isOldPriceFocused && oldPriceInput) {
          oldPriceInput.value = calcOld;
        }
      } else if (oldPrice > 0 && discount > 0) {
        // Have old + discount, dynamically drive current (unless user is editing current)
        calcOld = oldPrice;
        calcDiscount = discount;
        const calcPrice = oldPrice * (1 - discount / 100);
        if (!isPriceFocused && priceInput) {
          priceInput.value = calcPrice.toFixed(2);
        }
      }

      const displayOld = calcOld > 0 ? calcOld : (oldPriceInput && parseFloat(oldPriceInput.value) > 0 ? parseFloat(oldPriceInput.value) : 0);
      const displayPrice = priceInput && parseFloat(priceInput.value) > 0 ? parseFloat(priceInput.value) : 0;
      const displayDiscount = calcDiscount > 0 ? calcDiscount : (discountInput && parseFloat(discountInput.value) > 0 ? parseFloat(discountInput.value) : 0);

      if (oldPriceDisplay) {
        oldPriceDisplay.textContent = displayOld > 0 ? '$' + Number(displayOld).toLocaleString() : '-';
      }
      if (currentPriceDisplay) {
        currentPriceDisplay.textContent = displayPrice > 0 ? '$' + displayPrice.toLocaleString() : '-';
      }
      if (discountDisplay) {
        discountDisplay.textContent = displayDiscount > 0 ? displayDiscount + '% OFF' : '-';
      }
      preview.classList.remove('hidden');
    }

    [priceInput, oldPriceInput, discountInput].forEach(input => input?.addEventListener('input', updatePreview));

    // Validate on add form submit
    const addForm = addModal?.querySelector('form');
    addForm?.addEventListener('submit', (e) => {
      const price = parseFloat(priceInput?.value || '0') || 0;
      const oldPrice = parseFloat(oldPriceInput?.value || '0') || 0;
      const discount = parseFloat(discountInput?.value || '0') || 0;
      const valid = validateAddPricingValues(price, oldPrice, discount);
      if (!valid) {
        e.preventDefault();
      }
    });

    // === DISCOUNT PREVIEW (EDIT) ===
    const editPrice = document.getElementById('edit-price');
    const editOldPrice = document.getElementById('edit-old_price');
    const editDiscount = document.getElementById('edit-discount_percentage');
    const editPreview = document.getElementById('edit-discount-preview');
    const editOldDisplay = document.getElementById('edit-old-price-display');
    const editCurrentDisplay = document.getElementById('edit-current-price-display');
    const editDiscountDisplay = document.getElementById('edit-discount-display');

    const editPriceError = document.getElementById('edit-price-error');
    const editOldPriceError = document.getElementById('edit-old-price-error');
    const editDiscountError = document.getElementById('edit-discount-error');

    function clearEditErrors() {
      if (editPriceError) editPriceError.textContent = '';
      if (editOldPriceError) editOldPriceError.textContent = '';
      if (editDiscountError) editDiscountError.textContent = '';
    }

    function validateEditPricingValues(price, oldPrice, discount) {
      clearEditErrors();
      let valid = true;

      if (price <= 0) {
        if (editPriceError) editPriceError.textContent = 'Current price is required and must be greater than 0.';
        valid = false;
      }
      if (oldPrice < 0) {
        if (editOldPriceError) editOldPriceError.textContent = 'Old price cannot be negative.';
        valid = false;
      }
      if (discount < 0 || discount > 100) {
        if (editDiscountError) editDiscountError.textContent = 'Discount must be between 0 and 100.';
        valid = false;
      }
      if (price > 0 && oldPrice > 0 && oldPrice < price) {
        if (editOldPriceError) editOldPriceError.textContent = 'Old price should be greater than or equal to current price.';
        valid = false;
      }

      return valid;
    }

    // Track focus for edit fields so we don't overwrite what the user is erasing/typing
    let isEditPriceFocused = false;
    let isEditOldPriceFocused = false;
    let isEditDiscountFocused = false;

    editPrice?.addEventListener('focus', () => { isEditPriceFocused = true; });
    editPrice?.addEventListener('blur', () => { isEditPriceFocused = false; });
    editOldPrice?.addEventListener('focus', () => { isEditOldPriceFocused = true; });
    editOldPrice?.addEventListener('blur', () => { isEditOldPriceFocused = false; });
    editDiscount?.addEventListener('focus', () => { isEditDiscountFocused = true; });
    editDiscount?.addEventListener('blur', () => { isEditDiscountFocused = false; });

    function updateEditPreview() {
      if (!editPrice || !editOldPrice || !editDiscount || !editPreview) return;

      const price = parseFloat(editPrice.value) || 0;
      const oldPrice = parseFloat(editOldPrice.value) || 0;
      const discount = parseFloat(editDiscount.value) || 0;

      const valid = validateEditPricingValues(price, oldPrice, discount);
      // If only current price is set (no old, no discount), it's valid but no discount calculation
      if (!valid || (price > 0 && oldPrice <= 0 && discount <= 0)) {
        editPreview.classList.add('hidden');
        return;
      }

      let calcOld = oldPrice;
      let calcDiscount = discount;

      // Auto-calc while also reflecting into the fields,
      // but only when one piece is clearly missing AND that field is not being actively edited
      if (price > 0 && oldPrice > 0 && discount === 0) {
        // Have current + old, missing discount
        calcDiscount = ((oldPrice - price) / oldPrice * 100).toFixed(2);
        if (!isEditDiscountFocused && editDiscount) {
          editDiscount.value = calcDiscount;
        }
      } else if (price > 0 && discount > 0 && oldPrice === 0) {
        // Have current + discount, missing old
        calcOld = (price / (1 - discount / 100)).toFixed(2);
        if (!isEditOldPriceFocused && editOldPrice) {
          editOldPrice.value = calcOld;
        }
      } else if (oldPrice > 0 && discount > 0) {
        // Have old + discount, dynamically drive current (unless user is editing current)
        calcOld = oldPrice;
        calcDiscount = discount;
        const calcPrice = oldPrice * (1 - discount / 100);
        if (!isEditPriceFocused && editPrice) {
          editPrice.value = calcPrice.toFixed(2);
        }
      }

      const editDisplayOld = calcOld > 0 ? calcOld : (editOldPrice && parseFloat(editOldPrice.value) > 0 ? parseFloat(editOldPrice.value) : 0);
      const editDisplayPrice = editPrice && parseFloat(editPrice.value) > 0 ? parseFloat(editPrice.value) : 0;
      const editDisplayDiscount = calcDiscount > 0 ? calcDiscount : (editDiscount && parseFloat(editDiscount.value) > 0 ? parseFloat(editDiscount.value) : 0);

      if (editOldDisplay) {
        editOldDisplay.textContent = editDisplayOld > 0 ? '$' + Number(editDisplayOld).toLocaleString() : '-';
      }
      if (editCurrentDisplay) {
        editCurrentDisplay.textContent = editDisplayPrice > 0 ? '$' + editDisplayPrice.toLocaleString() : '-';
      }
      if (editDiscountDisplay) {
        editDiscountDisplay.textContent = editDisplayDiscount > 0 ? editDisplayDiscount + '% OFF' : '-';
      }
      editPreview.classList.remove('hidden');
    }

    [editPrice, editOldPrice, editDiscount].forEach(input => input?.addEventListener('input', updateEditPreview));
    <?php if ($editCar): ?> updateEditPreview(); <?php endif; ?>

    // Validate on edit form submit
    const editForm = editModal?.querySelector('form');
    editForm?.addEventListener('submit', (e) => {
      const price = parseFloat(editPrice?.value || '0') || 0;
      const oldPrice = parseFloat(editOldPrice?.value || '0') || 0;
      const discount = parseFloat(editDiscount?.value || '0') || 0;
      const valid = validateEditPricingValues(price, oldPrice, discount);
      if (!valid) {
        e.preventDefault();
      }
    });

    // === LIVE SEARCH ===
    const searchInput = document.getElementById('search-input');
    const container = document.getElementById('cars-container');
    const cards = container.querySelectorAll('.car-card');

    searchInput.addEventListener('input', function () {
      const query = this.value.toLowerCase().trim();
      let visible = 0;
      cards.forEach(card => {
        const text = `${card.dataset.make} ${card.dataset.model} ${card.dataset.year} ${card.dataset.price}`.toLowerCase();
        if (text.includes(query)) {
          card.style.display = ''; visible++;
        } else {
          card.style.display = 'none';
        }
      });
      let noResult = container.querySelector('.no-result');
      if (!noResult && visible === 0 && query !== '') {
        noResult = document.createElement('p');
        noResult.className = 'no-result col-span-full text-center text-gray-500 mt-8';
        noResult.textContent = 'No cars found.';
        container.appendChild(noResult);
      } else if (noResult && visible > 0) noResult.remove();
      else if (noResult && query === '') noResult.remove();
    });

    // === SUCCESS MESSAGES ===
    const params = new URLSearchParams(window.location.search);
    if (params.get('saved') === '1') {
      Swal.fire({ icon: 'success', title: 'Saved!', text: 'Car saved.', timer: 3000, showConfirmButton: false });
      setTimeout(() => window.location.href = 'cars.php', 1000);
    }
    if (params.get('deleted') === '1') {
      Swal.fire({ icon: 'success', title: 'Deleted!', text: 'Car deleted.', timer: 3000, showConfirmButton: false });
    }

    // === DELETE CONFIRMATION ===
    document.querySelectorAll('.delete-car-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        const id = this.dataset.id;
        Swal.fire({
          title: 'Delete Car?', text: "This cannot be undone.", icon: 'warning',
          showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Yes, delete'
        }).then(result => {
          if (result.isConfirmed) window.location.href = `?delete=${id}`;
        });
      });
    });

    <?php if ($editCar): ?>
      // === DELETE IMAGE CONFIRMATION ===
      document.querySelectorAll('.delete-image-btn').forEach(btn => {
        btn.addEventListener('click', function () {
          const index = this.dataset.index;
          Swal.fire({
            title: 'Delete Image?', text: "This cannot be undone.", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Yes, delete'
          }).then(result => {
            if (result.isConfirmed) window.location.href = `?edit=<?= $editCar['id'] ?>&delete_image=${index}`;
          });
        });
      });
    <?php endif; ?>

    // === READ MORE/LESS ===
    document.querySelectorAll('.read-more-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        const descText = this.previousElementSibling;
        const fullText = descText.dataset.full;
        const isExpanded = descText.classList.contains('expanded');

        if (isExpanded) {
          descText.textContent = fullText.length > 30 ? fullText.substring(0, 30) + '...' : fullText;
          this.textContent = 'Read More';
          descText.classList.remove('expanded');
        } else {
          descText.textContent = fullText;
          this.textContent = 'Read Less';
          descText.classList.add('expanded');
        }
      });
    });
  </script>
</body>

</html>