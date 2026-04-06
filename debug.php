<?php
session_start();

if (empty($_SESSION['is_admin'])) {
  header('Location: login.php?info=1');
  exit;
}

if (($_SESSION['username'] ?? '') !== 'Face') {
  header('Location: index.php');
  exit;
}

$errors = [];
$users = [];
$logs = [];

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

  // Fetch all users
  $stmt = $pdo->query('SELECT id, username, password_hash, type, created_at FROM users ORDER BY created_at DESC');
  $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  // Fetch all logs
  $stmt = $pdo->query('SELECT id, action, status, user_id, username, entity_type, entity_id, ip_address, created_at FROM logs ORDER BY created_at DESC LIMIT 100');
  $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $errors[] = 'Database error: ' . $e->getMessage();
}

// Helper function to mask IP address
function maskIpAddress($ip) {
  if (empty($ip) || $ip === 'unknown') {
    return 'unknown';
  }
  
  // IPv6
  if (strpos($ip, ':') !== false) {
    return 'LOCAL';
  }
  
  // IPv4
  $parts = explode('.', $ip);
  if (count($parts) === 4) {
    return $parts[0] . '.xxx.xxx.xxx';
  }
  
  return $ip;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Debug Console</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gradient-to-br from-gray-200 to-gray-50 min-h-screen p-6 px-80">

  <?php include __DIR__ . '/ASSETS/header.php'; ?>

  <!-- Content Area -->
  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <div class="lg:col-span-3 space-y-6">

      <!-- Page Header -->
      <div class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300">
        <div class="flex items-center gap-3 mb-2">
          <i data-lucide="bug" class="w-8 h-8 text-amber-600"></i>
          <h1 class="text-3xl font-bold text-gray-900">Debug Console</h1>
        </div>
        <p class="text-gray-600">System information and  status</p>
      </div>

      <!-- Session Information -->
      <div class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300">
        <h2 class="text-xl font-bold mb-4 text-gray-900">Session Information</h2>
        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
          <pre class="text-xs text-gray-100 font-mono whitespace-pre-wrap break-words"><?php echo htmlspecialchars(json_encode($_SESSION, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
        </div>
      </div>

      <!-- Database Status -->
      <div class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300">
        <h2 class="text-xl font-bold mb-4 text-gray-900">Status</h2>
        <div class="grid grid-cols-3 gap-4 mb-6">
          <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-4">
            <div class="text-sm text-green-700">Database File</div>
            <div class="text-lg font-semibold text-green-900 break-all"><?php echo htmlspecialchars(basename($dbFile)); ?></div>
            <div class="text-xs text-green-600 mt-1"><?php echo file_exists($dbFile) ? '✓ Exists' : '✗ Missing'; ?></div>
          </div>
          <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-4">
            <div class="text-sm text-blue-700">Total Users</div>
            <div class="text-lg font-semibold text-blue-900"><?php echo count($users); ?></div>
            <div class="text-xs text-blue-600 mt-1">Accounts</div>
          </div>
          <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-lg p-4">
            <div class="text-sm text-purple-700">PHP Version</div>
            <div class="text-lg font-semibold text-purple-900"><?php echo phpversion(); ?></div>
            <div class="text-xs text-purple-600 mt-1">Runtime</div>
          </div>
        </div>
        
        <!-- Images Statistics -->
        <div class="grid grid-cols-2 gap-4 mb-6">
          <?php
            $imagesDir = __DIR__ . '/REPORT_IMAGES';
            $imageCount = 0;
            $totalSize = 0;
            
            if (is_dir($imagesDir)) {
              $files = scandir($imagesDir);
              foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                  $filePath = $imagesDir . '/' . $file;
                  if (is_file($filePath)) {
                    $imageCount++;
                    $totalSize += filesize($filePath);
                  }
                }
              }
            }
            
            $sizeMB = $totalSize / 1024 / 1024;
            $sizeGB = $sizeMB / 1024;
            $displaySize = $sizeGB >= 1 ? round($sizeGB, 2) . ' GB' : round($sizeMB, 2) . ' MB';
          ?>
          <div class="bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200 rounded-lg p-4">
            <div class="text-sm text-orange-700">Stored Images</div>
            <div class="text-lg font-semibold text-orange-900"><?php echo $imageCount; ?></div>
            <div class="text-xs text-orange-600 mt-1">Files</div>
          </div>
          <div class="bg-gradient-to-br from-pink-50 to-pink-100 border border-pink-200 rounded-lg p-4">
            <div class="text-sm text-pink-700">Total Image Size</div>
            <div class="text-lg font-semibold text-pink-900"><?php echo $displaySize; ?></div>
            <div class="text-xs text-pink-600 mt-1">Storage Used</div>
          </div>
        </div>
        <?php if (!empty($errors)): ?>
          <div class="text-red-700 bg-red-50 border border-red-100 p-3 rounded">
            <?php foreach ($errors as $err): ?>
              <div><?php echo htmlspecialchars($err); ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Users Table -->
      <div class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300">
        <h2 class="text-xl font-bold mb-4 text-gray-900">All User Accounts</h2>
        <?php if (empty($users)): ?>
          <div class="text-center py-8 text-gray-500">
            <p>No user accounts found in database.</p>
          </div>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b-2 border-gray-300 bg-gray-50">
                  <th class="text-left px-4 py-3 font-semibold text-gray-900">ID</th>
                  <th class="text-left px-4 py-3 font-semibold text-gray-900">Username</th>
                  <th class="text-left px-4 py-3 font-semibold text-gray-900">Type</th>
                  <th class="text-left px-4 py-3 font-semibold text-gray-900">Created At</th>
                  <th class="text-left px-4 py-3 font-semibold text-gray-900">Password Hash</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <?php foreach ($users as $user): ?>
                  <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 text-gray-700 font-medium"><?php echo htmlspecialchars($user['id']); ?></td>
                    <td class="px-4 py-3 text-gray-700 font-medium"><?php echo htmlspecialchars($user['username']); ?></td>
                    <td class="px-4 py-3">
                      <?php if (!empty($user['type']) && $user['type'] === 'admin'): ?>
                        <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">Admin</span>
                      <?php else: ?>
                        <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-semibold">User</span>
                      <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-gray-700"><?php echo htmlspecialchars($user['created_at'] ?? 'N/A'); ?></td>
                    <td class="px-4 py-3 text-gray-600 font-mono text-xs break-all">
                      <span class="text-gray-400"><?php echo htmlspecialchars(substr($user['password_hash'], 0, 20)) . '...'; ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- Logs Table -->
      <div class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300">
        <h2 class="text-xl font-bold mb-4 text-gray-900">Activity Logs (Latest 100)</h2>
        <?php if (empty($logs)): ?>
          <div class="text-center py-8 text-gray-500">
            <p>No activity logs found.</p>
          </div>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b-2 border-gray-300 bg-gray-50">
                  <th class="text-left px-4 py-3 font-semibold text-gray-900">ID</th>
                  <th class="text-left px-4 py-3 font-semibold text-gray-900">Action</th>
                  <th class="text-left px-4 py-3 font-semibold text-gray-900">Status</th>
                  <th class="text-left px-4 py-3 font-semibold text-gray-900">Username</th>
                  <th class="text-left px-4 py-3 font-semibold text-gray-900">Entity</th>
                  <th class="text-left px-4 py-3 font-semibold text-gray-900">IP Address</th>
                  <th class="text-left px-4 py-3 font-semibold text-gray-900">Timestamp</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <?php foreach ($logs as $log): ?>
                  <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 text-gray-700 font-medium"><?php echo htmlspecialchars($log['id']); ?></td>
                    <td class="px-4 py-3">
                      <?php 
                        $actionColors = [
                          'login' => 'bg-blue-100 text-blue-800',
                          'register' => 'bg-green-100 text-green-800',
                          'logout' => 'bg-gray-100 text-gray-800',
                          'issue_create' => 'bg-purple-100 text-purple-800',
                          'blog_create' => 'bg-orange-100 text-orange-800'
                        ];
                        $color = $actionColors[$log['action']] ?? 'bg-gray-100 text-gray-800';
                      ?>
                      <span class="px-3 py-1 <?php echo $color; ?> rounded-full text-xs font-semibold"><?php echo htmlspecialchars($log['action']); ?></span>
                    </td>
                    <td class="px-4 py-3">
                      <?php if ($log['status'] === 'success'): ?>
                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">✓ Success</span>
                      <?php elseif ($log['status'] === 'failed'): ?>
                        <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">✗ Failed</span>
                      <?php else: ?>
                        <span class="text-gray-600">—</span>
                      <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-gray-700"><?php echo htmlspecialchars($log['username'] ?? '(guest)'); ?></td>
                    <td class="px-4 py-3 text-gray-600 text-xs">
                      <?php echo htmlspecialchars($log['entity_type'] ?? '—'); ?>
                      <?php if ($log['entity_id']): ?>
                        <span class="text-gray-400">#<?php echo htmlspecialchars($log['entity_id']); ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-gray-600 text-xs font-mono"><?php echo htmlspecialchars(maskIpAddress($log['ip_address'])); ?></td>
                    <td class="px-4 py-3 text-gray-700 text-xs"><?php echo htmlspecialchars($log['created_at']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

    </div>

    <!-- Sidebar -->
    <aside class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300 space-y-6">
      <div>
        <h2 class="text-lg font-bold text-gray-900 mb-2">Debug Tools</h2>
        <p class="text-sm text-gray-600">Development utilities</p>
      </div>

      <div class="space-y-3">
        <div class="bg-gradient-to-br from-amber-50 to-orange-50 p-4 rounded-lg border border-amber-200">
          <div class="text-sm font-semibold text-amber-900 mb-2">
            <i data-lucide="alert-circle" class="w-4 h-4 inline mr-1"></i>
            Debug Mode
          </div>
          <p class="text-xs text-amber-800">You are viewing the debug console. This page shows sensitive information and should only be accessible to administrators.</p>
        </div>
      </div>

      <div class="border-t border-gray-200 pt-4">
        <h3 class="text-sm font-bold text-gray-900 mb-3">System Info</h3>
        <div class="space-y-2 text-xs text-gray-600">
          <div>
            <span class="font-semibold text-gray-700">OS:</span>
            <span><?php echo PHP_OS; ?></span>
          </div>
          <div>
            <span class="font-semibold text-gray-700">Memory Usage:</span>
            <span><?php echo round(memory_get_usage() / 1024 / 1024, 2) . ' MB'; ?></span>
          </div>
          <div>
            <span class="font-semibold text-gray-700">Peak Memory:</span>
            <span><?php echo round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB'; ?></span>
          </div>
          <div>
            <span class="font-semibold text-gray-700">Disk Free:</span>
            <span><?php 
              $free = disk_free_space(__DIR__);
              echo $free !== false ? round($free / 1024 / 1024 / 1024, 2) . ' GB' : 'N/A';
            ?></span>
          </div>
          <div>
            <span class="font-semibold text-gray-700">Disk Total:</span>
            <span><?php 
              $total = disk_total_space(__DIR__);
              echo $total !== false ? round($total / 1024 / 1024 / 1024, 2) . ' GB' : 'N/A';
            ?></span>
          </div>
        </div>
      </div>
    </aside>

  </div>

  <?php include __DIR__ . '/ASSETS/footer.php'; ?>

  <script>lucide.createIcons();</script>
</body>
</html>
