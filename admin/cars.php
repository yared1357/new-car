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
  $discountPercentage = isset($_POST['discount_percentage']) && $_POST['discount_percentage'] !== ''
    ? (float) $_POST['discount_percentage']
    : 0.0;
  $oldPrice = isset($_POST['old_price']) && $_POST['old_price'] !== ''
    ? (float) $_POST['old_price']
    : null;

  if ($oldPrice !== null && $oldPrice > 0) {
    $discountPercentage = $oldPrice > $price
      ? (($oldPrice - $price) / $oldPrice) * 100
      : 0.0;
  } elseif ($discountPercentage > 0) {
    $oldPrice = round($price / (1 - ($discountPercentage / 100)), 2);
  }

  $discountPercentage = round($discountPercentage, 2);
  if ($discountPercentage < 0 || $discountPercentage >= 100) {
    $error = 'Discount percentage must be between 0 and 99.99.';
  }

  // Validate category/label against allowed values (optional field)
  $allowed_categories = ['New', 'Secondhand', 'Classic'];
  $category = null;
  if (isset($_POST['category']) && in_array($_POST['category'], $allowed_categories, true)) {
    $category = $_POST['category'];
  }

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

    $hasDiscountColumns = false;
    try {
      $discountCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'cars' AND column_name IN ('discount_percentage', 'old_price')");
      $discountCheck->execute();
      $hasDiscountColumns = ((int) $discountCheck->fetchColumn() === 2);
    } catch (Exception $e) {
      $hasDiscountColumns = false;
    }

    if (!$hasDiscountColumns) {
      try {
        $pdo->exec("ALTER TABLE cars ADD COLUMN discount_percentage DECIMAL(5,2) DEFAULT 0.00, ADD COLUMN old_price DECIMAL(10,2) DEFAULT NULL");
        $hasDiscountColumns = true;
      } catch (Exception $e) {
        $hasDiscountColumns = false;
      }
    }

    $imageJson = json_encode($images);
    $primaryImage = $images[0] ?? '';
    if (isset($_POST['edit_id']) && $_POST['edit_id'] !== '') {
      $id = (int) $_POST['edit_id'];
      if ($hasCategory && $hasDiscountColumns) {
        $stmt = $pdo->prepare("UPDATE cars SET make=?, model=?, year=?, price=?, mileage=?, fuel_type=?, transmission=?, description=?, images=?, image_url=?, category=?, discount_percentage=?, old_price=?, created_at=NOW() WHERE id=?");
        $stmt->execute([$make, $model, $year, $price, $mileage, $fuel, $trans, $description, $imageJson, $primaryImage, $category, $discountPercentage, $oldPrice, $id]);
      } elseif ($hasDiscountColumns) {
        $stmt = $pdo->prepare("UPDATE cars SET make=?, model=?, year=?, price=?, mileage=?, fuel_type=?, transmission=?, description=?, images=?, image_url=?, discount_percentage=?, old_price=?, created_at=NOW() WHERE id=?");
        $stmt->execute([$make, $model, $year, $price, $mileage, $fuel, $trans, $description, $imageJson, $primaryImage, $discountPercentage, $oldPrice, $id]);
      } elseif ($hasCategory) {
        $stmt = $pdo->prepare("UPDATE cars SET make=?, model=?, year=?, price=?, mileage=?, fuel_type=?, transmission=?, description=?, images=?, image_url=?, category=?, created_at=NOW() WHERE id=?");
        $stmt->execute([$make, $model, $year, $price, $mileage, $fuel, $trans, $description, $imageJson, $primaryImage, $category, $id]);
      } else {
        $stmt = $pdo->prepare("UPDATE cars SET make=?, model=?, year=?, price=?, mileage=?, fuel_type=?, transmission=?, description=?, images=?, image_url=?, created_at=NOW() WHERE id=?");
        $stmt->execute([$make, $model, $year, $price, $mileage, $fuel, $trans, $description, $imageJson, $primaryImage, $id]);
      }
    } else {
      if ($hasCategory && $hasDiscountColumns) {
        $stmt = $pdo->prepare("INSERT INTO cars (make, model, year, price, mileage, fuel_type, transmission, description, images, image_url, category, discount_percentage, old_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$make, $model, $year, $price, $mileage, $fuel, $trans, $description, $imageJson, $primaryImage, $category, $discountPercentage, $oldPrice]);
      } elseif ($hasDiscountColumns) {
        $stmt = $pdo->prepare("INSERT INTO cars (make, model, year, price, mileage, fuel_type, transmission, description, images, image_url, discount_percentage, old_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$make, $model, $year, $price, $mileage, $fuel, $trans, $description, $imageJson, $primaryImage, $discountPercentage, $oldPrice]);
      } elseif ($hasCategory) {
        $stmt = $pdo->prepare("INSERT INTO cars (make, model, year, price, mileage, fuel_type, transmission, description, images, image_url, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$make, $model, $year, $price, $mileage, $fuel, $trans, $description, $imageJson, $primaryImage, $category]);
      } else {
        $stmt = $pdo->prepare("INSERT INTO cars (make, model, year, price, mileage, fuel_type, transmission, description, images, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$make, $model, $year, $price, $mileage, $fuel, $trans, $description, $imageJson, $primaryImage]);
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

    .no-spinner::-webkit-inner-spin-button,
    .no-spinner::-webkit-outer-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }

    .no-spinner {
      appearance: textfield;
      -moz-appearance: textfield;
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
                <p class="text-xl font-bold text-primary"><?= htmlspecialchars(format_etb($car['price'])) ?></p>
                <?php if (!empty($car['discount_percentage']) && (float) $car['discount_percentage'] > 0): ?>
                  <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">
                    -<?= rtrim(rtrim(number_format((float) $car['discount_percentage'], 2, '.', ''), '0'), '.') ?>%
                  </span>
                <?php endif; ?>
              </div>
              <?php if (!empty($car['old_price']) && !empty($car['discount_percentage']) && (float) $car['discount_percentage'] > 0): ?>
                <p class="mb-2 text-sm text-gray-500 line-through">
                  Previous price: <?= htmlspecialchars(format_etb($car['old_price'])) ?>
                </p>
              <?php endif; ?>
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

            <div class="grid md:grid-cols-3 gap-4">
              <div>
                <label for="price" class="block text-sm font-medium mb-1">Current Price (ETB)</label>
                <input type="number" id="price" name="price" required placeholder="Current Price (ETB)" step="0.01"
                  class="no-spinner w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                <p id="price-error" class="mt-1 text-xs text-red-600"></p>
              </div>
              <div>
                <label for="old_price" class="block text-sm font-medium mb-1">Previous/Older Value (optional)</label>
                <input type="number" id="old_price" name="old_price" min="0" step="0.01"
                  placeholder="Older value"
                  class="no-spinner w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
              </div>
              <div>
                <label for="discount_percentage" class="block text-sm font-medium mb-1">Discount (optional)</label>
                <div class="relative">
                  <input type="number" id="discount_percentage" name="discount_percentage" min="0" max="99.99" step="0.01"
                    placeholder="Percent"
                    class="no-spinner w-full px-4 pr-10 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                  <span class="absolute inset-y-0 right-3 flex items-center text-sm font-semibold text-gray-500">%</span>
                </div>
              </div>
            </div>
            <div id="old-price-preview"
              class="mt-3 hidden rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900"></div>

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

              <div class="grid md:grid-cols-3 gap-4">
                <div>
                  <label for="edit-price" class="block text-sm font-medium mb-1">Current Price (ETB)</label>
                  <input type="number" id="edit-price" name="price" required placeholder="Current Price (ETB)" step="0.01"
                    value="<?= $editCar['price'] ?>"
                    class="no-spinner w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                  <p id="edit-price-error" class="mt-1 text-xs text-red-600"></p>
                </div>
                <div>
                  <label for="edit-old_price" class="block text-sm font-medium mb-1">Previous/Older Value (optional)</label>
                  <input type="number" id="edit-old_price" name="old_price" min="0" step="0.01"
                    value="<?= isset($editCar['old_price']) && $editCar['old_price'] !== null ? htmlspecialchars($editCar['old_price']) : '' ?>"
                    placeholder="Older value"
                    class="no-spinner w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                  <label for="edit-discount_percentage" class="block text-sm font-medium mb-1">Discount (optional)</label>
                  <div class="relative">
                    <input type="number" id="edit-discount_percentage" name="discount_percentage" min="0" max="99.99" step="0.01"
                      value="<?= isset($editCar['discount_percentage']) ? htmlspecialchars(rtrim(rtrim(number_format((float) $editCar['discount_percentage'], 2, '.', ''), '0'), '.')) : '0' ?>"
                      placeholder="Percent"
                      class="no-spinner w-full px-4 pr-10 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    <span class="absolute inset-y-0 right-3 flex items-center text-sm font-semibold text-gray-500">%</span>
                  </div>
                </div>
              </div>
              <div id="edit-old-price-preview"
                class="mt-3 hidden rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900"></div>

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

    function setupDiscountPreview(priceId, oldPriceId, discountId, previewId) {
      const priceInput = document.getElementById(priceId);
      const oldPriceInput = document.getElementById(oldPriceId);
      const discountInput = document.getElementById(discountId);
      const preview = document.getElementById(previewId);
      if (!priceInput || !oldPriceInput || !discountInput || !preview) return;

      let updating = false;
      const formatNumber = (value) => Number(value).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });

      const updatePreview = () => {
        const price = Number(priceInput.value);
        const oldPrice = Number(oldPriceInput.value);
        const discount = Number(discountInput.value);

        if (price > 0 && oldPrice > price && discount > 0 && discount < 100) {
          preview.innerHTML = `
            <div class="flex flex-wrap items-center justify-between gap-2">
              <span><strong>Current price:</strong> ETB ${formatNumber(price)}</span>
              <span><strong>Previous/Older value:</strong> ETB ${formatNumber(oldPrice)}</span>
              <span class="rounded-full border border-green-300 bg-white px-2 py-1 font-bold text-green-700">-${discount}%</span>
            </div>`;
          preview.classList.remove('hidden');
        } else {
          preview.innerHTML = '';
          preview.classList.add('hidden');
        }
      };

      priceInput.addEventListener('input', () => {
        if (updating) return;
        updating = true;
        const price = Number(priceInput.value);
        const discount = Number(discountInput.value);
        if (price > 0 && discount > 0 && discount < 100) {
          oldPriceInput.value = (price / (1 - (discount / 100))).toFixed(2);
        }
        updating = false;
        updatePreview();
      });

      discountInput.addEventListener('input', () => {
        if (updating) return;
        updating = true;
        const price = Number(priceInput.value);
        const discount = Number(discountInput.value);
        if (price > 0 && discount > 0 && discount < 100) {
          oldPriceInput.value = (price / (1 - (discount / 100))).toFixed(2);
        } else if (!discount) {
          oldPriceInput.value = '';
        }
        updating = false;
        updatePreview();
      });

      oldPriceInput.addEventListener('input', () => {
        if (updating) return;
        updating = true;
        const price = Number(priceInput.value);
        const oldPrice = Number(oldPriceInput.value);
        if (price > 0 && oldPrice > price) {
          discountInput.value = (((oldPrice - price) / oldPrice) * 100).toFixed(2);
        } else if (!oldPrice) {
          discountInput.value = '';
        }
        updating = false;
        updatePreview();
      });

      updatePreview();
    }

    setupDiscountPreview('price', 'old_price', 'discount_percentage', 'old-price-preview');
    setupDiscountPreview('edit-price', 'edit-old_price', 'edit-discount_percentage', 'edit-old-price-preview');

    // Prevent closing by clicking outside or pressing escape
    editModal?.addEventListener('click', (e) => { if (e.target === editModal) e.stopPropagation(); });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && editModal.classList.contains('open')) {
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