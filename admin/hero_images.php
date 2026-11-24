<?php
require_once __DIR__ . '/session.php';

$uploadDir = '../admin/images/';
$maxFileSize = 20 * 1024 * 1024; // 20MB

// === HANDLE ADD / EDIT / DELETE ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title']);
  $description = trim($_POST['description']);
  $order = (int) $_POST['order'];

  $imagePath = '';
  if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    $error = 'Could not create the hero image upload directory.';
  }

  if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $uploadError = $_FILES['image']['error'];
    if ($uploadError !== UPLOAD_ERR_OK) {
      $error = match ($uploadError) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Image is too large for the server upload limit.',
        UPLOAD_ERR_PARTIAL => 'Image upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_TMP_DIR => 'The server upload temporary folder is missing.',
        UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded image.',
        UPLOAD_ERR_EXTENSION => 'A server extension stopped the image upload.',
        default => 'Image upload failed. Please try again.',
      };
    } elseif ($_FILES['image']['size'] > $maxFileSize) {
      $error = "Image is too large (max 20MB).";
    } else {
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mime = $finfo->file($_FILES['image']['tmp_name']);
      $extensions = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
      ];

      if (!isset($extensions[$mime])) {
        $error = "Invalid file type. Upload a PNG, JPG, or WEBP image.";
      } else {
        $filename = 'hero_' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
        $dest = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
          $imagePath = 'admin/images/' . $filename;
        } else {
          error_log('Hero image upload could not move uploaded file to ' . $dest);
          $error = "Failed to save image. Check that the XAMPP Apache user can write to admin/images.";
        }
      }
    }
  }

  if (!isset($error)) {
    if (!empty($_POST['edit_id'])) {
      $id = (int) $_POST['edit_id'];
      if ($id <= 0) {
        $error = 'Invalid edit id.';
      } else {
        $stmt = $pdo->prepare("UPDATE hero_images SET title=?, description=?, image_path=?, `order`=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$title, $description, $imagePath ?: $_POST['existing_image'], $order, $id]);
      }
    } else {
      if (!$imagePath) {
        $error = "Image is required.";
      } else {
        $stmt = $pdo->prepare("INSERT INTO hero_images (title, description, image_path, `order`) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $description, $imagePath, $order]);
      }
    }

    if (!isset($error)) {
      header("Location: hero_images.php?saved=1");
      exit;
    }
  }
}

// === DELETE ===
if (isset($_GET['delete'])) {
  $id = (int) $_GET['delete'];
  // Protect against invalid ids (0 or negative) which could match unintended rows
  if ($id <= 0) {
    header("Location: hero_images.php?error=invalid_id");
    exit;
  }
  $stmt = $pdo->prepare("SELECT image_path FROM hero_images WHERE id = ?");
  $stmt->execute([$id]);
  $img = $stmt->fetch();
  if ($img && !empty($img['image_path']) && file_exists('../' . $img['image_path'])) {
    @unlink('../' . $img['image_path']);
  }
  $stmt = $pdo->prepare("DELETE FROM hero_images WHERE id = ?");
  $stmt->execute([$id]);
  header("Location: hero_images.php?deleted=1");
  exit;
}

// === FETCH IMAGES ===
$stmt = $pdo->query("SELECT * FROM hero_images ORDER BY `order` ASC, created_at DESC");
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editImage = null;
if (isset($_GET['edit'])) {
  $id = (int) $_GET['edit'];
  if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM hero_images WHERE id = ?");
    $stmt->execute([$id]);
    $editImage = $stmt->fetch();
  }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Hero Images | <?= htmlspecialchars($siteSettings['site_name'], ENT_QUOTES, 'UTF-8') ?> Admin</title>
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

  <!-- SIDEBAR -->
  <?php $activeSidebar = 'slides'; include __DIR__ . '/sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <main id="main-content" class="flex-1 transition-all duration-300 bg-gray-50 w-full pt-16 px-4">
    <div class="max-w-7xl mx-auto py-12">
      <div class="flex justify-between items-center mb-10">
        <h1
          class="text-lg xs:text-xl sm:text-2xl md:text-3xl lg:text-4xl font-extrabold text-gray-800 whitespace-nowrap">
          Manage Slides</h1>
        <button id="add-image-btn"
          class="bg-primary hover:bg-blue-700 text-white px-4 py-2 sm:px-6 sm:py-3 rounded-full font-medium transition text-sm sm:text-base whitespace-nowrap">
          Add New Image
        </button>
      </div>

      <!-- ADD/EDIT MODAL -->
      <div id="form-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 modal">
        <div
          class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto p-6 relative">
          <button type="button" id="close-modal" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 z-10">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <h2 class="text-2xl font-bold mb-4 pr-10"><?= $editImage ? 'Edit' : 'Add' ?> Hero Image</h2>

          <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <?php if ($editImage): ?>
              <input type="hidden" name="edit_id" value="<?= $editImage['id'] ?>">
              <input type="hidden" name="existing_image" value="<?= $editImage['image_path'] ?>">
            <?php endif; ?>

            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($editImage['title'] ?? '') ?>"
                  placeholder="Optional slide title"
                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Order</label>
                <input type="number" name="order"
                  value="<?= isset($editImage['order']) ? (int) $editImage['order'] : 0 ?>"
                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" min="0">
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Description</label>
              <textarea name="description" rows="3"
                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"><?= htmlspecialchars($editImage['description'] ?? '') ?></textarea>
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-medium">Image</label>
              <input type="file" name="image" accept="image/*" <?= $editImage ? '' : 'required' ?>
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-blue-800">
              <?php if ($editImage): ?>
                <img src="../<?= htmlspecialchars($editImage['image_path']) ?>"
                  class="mt-2 w-32 h-20 object-cover rounded">
              <?php endif; ?>
            </div>

            <div class="flex gap-3">
              <button type="submit"
                class="flex-1 bg-primary hover:bg-blue-800 text-white py-2 rounded-lg font-medium transition">
                <?= $editImage ? 'Update' : 'Add' ?> Image
              </button>
              <button type="button" id="cancel-modal"
                class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-lg font-medium transition">
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- IMAGES LIST -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($images as $img): ?>
          <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <img src="../<?= htmlspecialchars($img['image_path']) ?>" class="w-full h-32 object-contain rounded mb-4">
            <?php if (trim((string) $img['title']) !== ''): ?>
              <h3 class="font-bold text-sm sm:text-base leading-tight break-words mb-1">
                <?= htmlspecialchars($img['title']) ?></h3>
            <?php endif; ?>
            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 mb-3 line-clamp-2">
              <?= htmlspecialchars($img['description']) ?>
            </p>
            <p class="text-xs text-gray-500">Order: <?= (int) $img['order'] ?></p>
            <div class="mt-4 w-[96%] mx-auto flex justify-center gap-4">
              <a href="?edit=<?= $img['id'] ?>"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-full text-sm transition">Edit</a>
              <button onclick="deleteImage(<?= $img['id'] ?>)"
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-full text-sm transition">Delete</button>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($images)): ?>
          <p class="col-span-full text-center text-gray-500 mt-4">No hero images added yet.</p>
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

    // Modal
    const formModal = document.getElementById('form-modal');
    const addBtn = document.getElementById('add-image-btn');
    const closeModalBtn = document.getElementById('close-modal');
    const cancelModal = document.getElementById('cancel-modal');

    function openModal() { formModal.classList.add('open'); }

    function closeModalImmediate() {
      formModal.classList.remove('open');
      setTimeout(() => window.location.href = 'hero_images.php', 300);
    }

    function confirmAndCloseModal() {
      Swal.fire({
        title: 'Close without saving?',
        text: 'If you close now, any unsaved changes will be lost.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, close',
        cancelButtonText: 'No, keep editing'
      }).then(result => {
        if (result.isConfirmed) closeModalImmediate();
      });
    }

    addBtn?.addEventListener('click', openModal);
    closeModalBtn?.addEventListener('click', confirmAndCloseModal);
    cancelModal?.addEventListener('click', confirmAndCloseModal);
    document.addEventListener('keydown', e => e.key === 'Escape' && formModal.classList.contains('open') && e.preventDefault());

    <?php if ($editImage): ?>
      document.addEventListener('DOMContentLoaded', () => {
        openModal();
      });
    <?php endif; ?>

    function deleteImage(id) {
      Swal.fire({
        title: 'Delete Image?',
        text: "This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete'
      }).then(result => {
        if (result.isConfirmed) {
          window.location.href = '?delete=' + id;
        }
      });
    }

    <?php if (isset($_GET['saved'])): ?>
      Swal.fire({ icon: 'success', title: 'Saved!', text: 'Hero image saved.', timer: 2500, showConfirmButton: false });
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
      Swal.fire({ icon: 'success', title: 'Deleted!', text: 'Hero image deleted.', timer: 2500, showConfirmButton: false });
    <?php endif; ?>
  </script>
</body>

</html>