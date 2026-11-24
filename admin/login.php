<?php
session_start();
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../site_settings.php';
$siteSettings = get_site_settings($pdo);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Login | <?= htmlspecialchars($siteSettings['site_name'], ENT_QUOTES, 'UTF-8') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Inter', 'sans-serif'] },
          colors: { primary: '#1E40AF', accent: '#F59E0B' },
        },
      },
    };
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/heroicons@2.0.18/24/outline/index.min.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-md">
    <div class="bg-white rounded-2xl shadow-xl p-8">
      <div class="text-center mb-8">
        <img src="../<?= htmlspecialchars(ltrim($siteSettings['logo_path'], '/'), ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="w-16 h-16 mx-auto object-contain mb-4">
        <h1 class="text-2xl font-bold text-primary">Admin Login</h1>
        <p class="mt-1 text-sm text-gray-500"><?= htmlspecialchars($siteSettings['site_name'], ENT_QUOTES, 'UTF-8') ?></p>
      </div>

      <form action="auth.php" method="POST">
        <?php if (isset($_GET['error'])): ?>
          <p class="mb-4 text-center text-red-500 text-sm">Invalid phone number or password</p>
        <?php endif; ?>

        <div class="mb-6">
          <label class="block text-sm font-medium mb-2">Phone number</label>
          <input type="tel" name="phone" required autocomplete="tel" inputmode="tel"
                 class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" 
                 placeholder="Enter your phone number">
        </div>

        <div class="mb-6">
          <label class="block text-sm font-medium mb-2">Password</label>
          <div class="relative">
            <input type="password" name="password" id="password" required 
                   class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent pr-12" 
                   placeholder="••••••••">
            <button type="button" 
                    onclick="togglePassword()" 
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 focus:outline-none">
              <svg id="eye-off" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
              </svg>
              <svg id="eye-on" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
          </div>
        </div>

        <div class="mt-4 flex justify-between w-full gap-4">
          <button type="submit" 
                  class="w-1/2 bg-primary hover:bg-blue-800 text-white font-semibold py-3 rounded-lg transition duration-200">
            Login
          </button>
          <a href="../index.php" 
             class="w-1/2 text-center px-6 py-2 bg-primary hover:bg-blue-800 text-white font-semibold rounded-lg shadow-md focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition duration-200">
            Back to Home
          </a>
        </div>
      </form>

    </div>
  </div>

  <script>
    function togglePassword() {
      const passwordField = document.getElementById('password');
      const eyeOn = document.getElementById('eye-on');
      const eyeOff = document.getElementById('eye-off');

      if (passwordField.type === 'password') {
        passwordField.type = 'text';
        eyeOn.classList.remove('hidden');
        eyeOff.classList.add('hidden');
      } else {
        passwordField.type = 'password';
        eyeOn.classList.add('hidden');
        eyeOff.classList.remove('hidden');
      }
    }
  </script>
</body>
</html>