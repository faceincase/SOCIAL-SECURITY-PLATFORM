<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$isLoggedIn = !empty($_SESSION['logged_in']);
$username = $_SESSION['username'] ?? '';
$isAdmin = !empty($_SESSION['is_admin']);
?>

<header class="flex items-center justify-between mb-8 pb-6 border-b border-gray-300">
  <div class="flex items-center gap-2">
    <div class="w-10 h-10 rounded-lg bg-green-500 flex items-center justify-center">
      <i data-lucide="check-circle-2" class="w-6 h-6 text-white"></i>
    </div>
    <span class="text-xl font-bold text-gray-800">Social Input Platform</span>
  </div>
  <div class="flex flex-wrap gap-3 items-center">
    <div class="text-xs px-3 py-1.5 rounded-full border <?php echo $isLoggedIn ? 'border-green-200 bg-green-50 text-green-700' : 'border-gray-200 bg-gray-50 text-gray-600'; ?>">
      <?php if ($isLoggedIn): ?>
        <span class="flex items-center gap-1.5">
          <i data-lucide="user-check" class="w-3 h-3"></i>
          <?php echo $username ? htmlspecialchars($username) : 'User'; ?><?php echo $isAdmin ? ' (Admin)' : ''; ?>
        </span>
      <?php else: ?>
        <span class="flex items-center gap-1.5">
          <i data-lucide="user-x" class="w-3 h-3"></i>
          Guest
        </span>
      <?php endif; ?>
    </div>
    <a href="index.php" class="group relative text-sm px-4 py-2.5 rounded-xl bg-gradient-to-br from-gray-50 to-white border-2 border-gray-200 text-gray-700 hover:border-gray-300 hover:shadow-md transition-all duration-200 font-medium">
      <span class="flex items-center gap-2">
        <i data-lucide="home" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
        Home
      </span>
    </a>
    <a href="report.php" class="group relative text-sm px-4 py-2.5 rounded-xl bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-200 text-green-700 hover:border-green-300 hover:shadow-md transition-all duration-200 font-medium">
      <span class="flex items-center gap-2">
        <i data-lucide="clipboard-plus" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
        Report
      </span>
    </a>
    <a href="login.php" class="group relative text-sm px-4 py-2.5 rounded-xl bg-gradient-to-br from-purple-50 to-indigo-50 border-2 border-purple-200 text-purple-700 hover:border-purple-300 hover:shadow-md transition-all duration-200 font-medium">
      <span class="flex items-center gap-2">
        <i data-lucide="user" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
        Account
      </span>
    </a>
    <a href="blog.php" class="group relative text-sm px-4 py-2.5 rounded-xl bg-gradient-to-br from-sky-50 to-blue-50 border-2 border-sky-200 text-sky-700 hover:border-sky-300 hover:shadow-md transition-all duration-200 font-medium">
      <span class="flex items-center gap-2">
        <i data-lucide="newspaper" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
        Blog
      </span>
    </a>
    <?php if ($isLoggedIn): ?>
      <a href="issues.php" class="group relative text-sm px-4 py-2.5 rounded-xl bg-gradient-to-br from-amber-50 to-orange-50 border-2 border-amber-200 text-amber-700 hover:border-amber-300 hover:shadow-md transition-all duration-200 font-medium">
        <span class="flex items-center gap-2">
          <i data-lucide="list" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
          Admin Dashboard
        </span>
      </a>
    <?php endif; ?>
  </div>
</header>
