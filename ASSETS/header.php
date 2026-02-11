<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$isLoggedIn = !empty($_SESSION['logged_in']);
$username = $_SESSION['username'] ?? '';
$isAdmin = !empty($_SESSION['is_admin']);
?>

<header class="flex items-center justify-between mb-8">
  <div class="flex items-center gap-2">
    <div class="w-10 h-10 rounded-lg bg-green-500 flex items-center justify-center">
      <i data-lucide="check-circle-2" class="w-6 h-6 text-white"></i>
    </div>
    <span class="text-xl font-bold text-gray-800">Report Issue App Please We Need A Better Name</span>
  </div>
  <div class="flex flex-wrap gap-2 items-center">
    <div class="text-xs px-2 py-1 rounded-full border <?php echo $isLoggedIn ? 'border-green-200 bg-green-50 text-green-700' : 'border-gray-200 bg-gray-50 text-gray-600'; ?>">
      <?php if ($isLoggedIn): ?>
        Logged in<?php echo $username ? ' as ' . htmlspecialchars($username) : ''; ?><?php echo $isAdmin ? ' (Admin)' : ''; ?>
      <?php else: ?>
        Not logged in
      <?php endif; ?>
    </div>
    <a href="index.php" class="text-sm px-3 py-2 rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">Home</a>
    <a href="report.php" class="text-sm px-3 py-2 rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">New Report</a>
    <a href="login.php" class="text-sm px-3 py-2 rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">Login</a>
    <a href="blog.php" class="text-sm px-3 py-2 rounded-lg border border-sky-200 bg-sky-50 text-sky-800 hover:bg-sky-100">Blog</a>
    <a href="issues.php" class="text-sm px-3 py-2 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100">All Reports</a>
  </div>
</header>
