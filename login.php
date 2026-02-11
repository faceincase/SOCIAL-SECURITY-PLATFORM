<?php
session_start();

$showAdminOnly = isset($_GET['info']) && $_GET['info'] === '1';
$showUserOnly = isset($_GET['info']) && $_GET['info'] === '2';

$errors = [];
$success = '';

// DB setup
$dbDir = __DIR__ . DIRECTORY_SEPARATOR . 'DATABASE';
if (!is_dir($dbDir)) {
  mkdir($dbDir, 0755, true);
}
$dbFile = $dbDir . DIRECTORY_SEPARATOR . 'reports.sqlite';
$dsn = 'sqlite:' . $dbFile;
try {
  $pdo = new PDO($dsn);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    type TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
  )");


} catch (PDOException $e) {
  $errors[] = 'Database error: ' . $e->getMessage();
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'logout') {
    session_unset();
    session_destroy();
    session_start();
    $success = 'Logged out successfully.';
  }

  if ($action === 'register') {
    $username = trim($_POST['register_username'] ?? '');
    $password = $_POST['register_password'] ?? '';
    $confirm = $_POST['register_password_confirm'] ?? '';

    if ($username === '') $errors[] = 'Username is required.';
    if ($password === '') $errors[] = 'Password is required.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
      try {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:u, :p)');
        $stmt->execute([':u' => $username, ':p' => $hash]);
        $success = 'Registration successful. You can now log in.';
      } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'UNIQUE') !== false) {
          $errors[] = 'Username already exists.';
        } else {
          $errors[] = 'Database error: ' . $e->getMessage();
        }
      }
    }
  }

  if ($action === 'login') {
    $username = trim($_POST['login_username'] ?? '');
    $password = $_POST['login_password'] ?? '';

    if ($username === '') $errors[] = 'Username is required.';
    if ($password === '') $errors[] = 'Password is required.';

    if (empty($errors)) {
      try {
        $stmt = $pdo->prepare('SELECT id, username, password_hash, type FROM users WHERE username = :u');
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
          $_SESSION['logged_in'] = true;
          $_SESSION['username'] = $user['username'];
          $_SESSION['is_admin'] = isset($user['type']) && $user['type'] === 'admin';
          $success = 'Login successful.';
        } else {
          $errors[] = 'Invalid username or password.';
        }
      } catch (PDOException $e) {
        $errors[] = 'Database error: ' . $e->getMessage();
      }
    }
  }
}

$isLoggedIn = !empty($_SESSION['logged_in']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-gradient-to-br from-gray-200 to-gray-50 min-h-screen p-6 px-80">

  <?php include __DIR__ . '/ASSETS/header.php'; ?>

  <?php if ($showAdminOnly): ?>
    <script>
      alert('Admin only: Please log in to access that page.');
    </script>
  <?php endif; ?>

  <?php if ($showUserOnly): ?>
    <script>
      alert('User only: Please log in to access that page.');
    </script>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="mb-4">
      <div class="text-red-700 bg-red-50 border border-red-100 p-3 rounded">
        <ul class="list-disc pl-5">
          <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <div class="mb-4">
      <div class="text-green-700 bg-green-50 border border-green-100 p-3 rounded">
        <?php echo htmlspecialchars($success); ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Content Area -->
  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200">
        <?php if ($isLoggedIn): ?>
          <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Account Info</h1>
            <form method="POST">
              <input type="hidden" name="action" value="logout" />
              <button type="submit" class="inline-flex items-center justify-center gap-2 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                <span class="font-semibold">Log Out</span>
                <i data-lucide="log-out" class="w-4 h-4"></i>
              </button>
            </form>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
              <div class="text-sm text-gray-500 mb-1">Username</div>
              <div class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></div>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
              <div class="text-sm text-gray-500 mb-1">Admin Access</div>
              <div class="text-lg font-semibold text-gray-900"><?php echo !empty($_SESSION['is_admin']) ? 'Yes' : 'No'; ?></div>
            </div>
          </div>

          <div class="mt-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-2">Session (debug)</h2>
            <pre class="text-xs bg-gray-900 text-gray-100 rounded-lg p-4 overflow-auto"><?php echo htmlspecialchars(print_r($_SESSION, true)); ?></pre>
          </div>
        <?php else: ?>
          <h1 class="text-2xl font-bold text-gray-900 mb-6">Account Access</h1>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <form method="POST" class="bg-white/90 backdrop-blur border border-gray-200 rounded-2xl p-6 shadow-sm">
              <h2 class="text-lg font-semibold text-gray-900 mb-4">LOGIN</h2>
              <input type="hidden" name="action" value="login" />
              <div class="space-y-4">
                <div>
                  <label class="text-sm font-medium text-gray-700">Username</label>
                  <input type="text" name="login_username" placeholder="Enter your username" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white shadow-sm p-3 focus:ring-2 focus:ring-green-300 focus:border-green-300" />
                </div>
                <div>
                  <label class="text-sm font-medium text-gray-700">Password</label>
                  <input type="password" name="login_password" placeholder="Enter your password" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white shadow-sm p-3 focus:ring-2 focus:ring-green-300 focus:border-green-300" />
                </div>
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 transition">
                  <span class="font-semibold">Login</span>
                  <i data-lucide="log-in" class="w-4 h-4"></i>
                </button>
              </div>
            </form>

            <form method="POST" class="bg-white/90 backdrop-blur border border-gray-200 rounded-2xl p-6 shadow-sm">
              <h2 class="text-lg font-semibold text-gray-900 mb-4">REGISTER</h2>
              <input type="hidden" name="action" value="register" />
              <div class="space-y-4">
                <div>
                  <label class="text-sm font-medium text-gray-700">Username</label>
                  <input type="text" name="register_username" placeholder="Choose a username" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white shadow-sm p-3 focus:ring-2 focus:ring-green-300 focus:border-green-300" />
                </div>
                <div>
                  <label class="text-sm font-medium text-gray-700">Password</label>
                  <input type="password" name="register_password" placeholder="Create a password" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white shadow-sm p-3 focus:ring-2 focus:ring-green-300 focus:border-green-300" />
                </div>
                <div>
                  <label class="text-sm font-medium text-gray-700">Confirm Password</label>
                  <input type="password" name="register_password_confirm" placeholder="Re-enter your password" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white shadow-sm p-3 focus:ring-2 focus:ring-green-300 focus:border-green-300" />
                </div>
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 transition">
                  <span class="font-semibold">Register</span>
                  <i data-lucide="user-plus" class="w-4 h-4"></i>
                </button>
              </div>
            </form>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <aside class="bg-white p-8 rounded-xl shadow-sm border border-gray-200 space-y-6">
      <div>
        <h2 class="text-lg font-bold text-gray-900 mb-2">Account Tips</h2>
        <p class="text-sm text-gray-600">Keep your account secure and easy to recover</p>
      </div>

      <div class="bg-green-50 p-4 rounded-lg border border-green-200">
        <img src="IMAGES/safe.jpg" alt="Security tips" class="w-full h-auto rounded">
      </div>

      <ul class="space-y-3 text-sm text-gray-700">
        <li class="flex items-start gap-2">
          <i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0"></i>
          <span>Use a unique, strong password</span>
        </li>
        <li class="flex items-start gap-2">
          <i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0"></i>
          <span>Don’t reuse passwords from other sites</span>
        </li>
        <li class="flex items-start gap-2">
          <i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0"></i>
          <span>Keep your password private</span>
        </li>
        <li class="flex items-start gap-2">
          <i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0"></i>
          <span>Use a password manager if possible</span>
        </li>
      </ul>
    </aside>
  </div>

  <script>lucide.createIcons();</script>
</body>
</html>
