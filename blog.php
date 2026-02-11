<?php
// issues.php - list all reports
session_start();

if (empty($_SESSION['logged_in'])) {
  header('Location: login.php?info=2');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Blog</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gradient-to-br from-gray-200 to-gray-50 min-h-screen p-6 px-80">

  <?php include __DIR__ . '/ASSETS/header.php'; ?>

  <!-- Content Area -->
  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Community Feed</h1>
            <p class="text-sm text-gray-600">Updates, tips, and announcements from the team</p>
          </div>
          <button class="inline-flex items-center gap-2 text-sm px-3 py-2 rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">
            <i data-lucide="plus" class="w-4 h-4"></i>
            New Post
          </button>
        </div>

        <!-- Post 1 -->
        <article class="bg-white border-2 border-gray-300 rounded-2xl p-6 shadow-md mb-6">
          <div class="flex items-start justify-between">
            <div>
              <h2 class="text-xl font-semibold text-gray-900">Blood Cleanup Completed</h2>
              <p class="text-xs text-gray-500 mt-1">Posted by <span class="font-medium text-gray-700">Dexter Morgan</span> • 2 hours ago • Sanitation</p>
            </div>
            <button class="text-gray-400 hover:text-gray-600">
              <i data-lucide="more-horizontal" class="w-5 h-5"></i>
            </button>
          </div>
          <p class="text-gray-700 mt-4">Thanks to your reports, we’ve completed a thorough blood cleanup at the walkway. Keep sending us issues so we can prioritize the next areas.</p>
          <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
            <img src="https://placehold.co/600x400" alt="Cleanup in progress" class="w-full h-64 object-cover" />
          </div>
          <div class="mt-5 pt-4 border-t border-gray-100 flex items-center justify-between text-sm text-gray-600">
            <div class="flex items-center gap-4">
              <span class="inline-flex items-center gap-1"><i data-lucide="share-2" class="w-4 h-4 text-sky-500"></i> 24</span>
            </div>
            <div class="flex items-center gap-3">
              <button class="hover:text-gray-900">Share</button>
              <?php if (!empty($_SESSION['is_admin'])): ?>
                <button class="text-red-600 hover:text-red-700 font-semibold">Delete</button>
              <?php endif; ?>
            </div>
          </div>
        </article>

        <!-- Post 2 (no image) -->
        <article class="bg-white border-2 border-gray-300 rounded-2xl p-6 shadow-md mb-6">
          <div class="flex items-start justify-between">
            <div>
              <h2 class="text-xl font-semibold text-gray-900">Weekly Maintenance Schedule</h2>
              <p class="text-xs text-gray-500 mt-1">Posted by <span class="font-medium text-gray-700">Jordan Lee</span> • yesterday • Roads</p>
            </div>
            <button class="text-gray-400 hover:text-gray-600">
              <i data-lucide="more-horizontal" class="w-5 h-5"></i>
            </button>
          </div>
          <p class="text-gray-700 mt-4">We’ll be patching potholes on Riverside Ave and Oak Street this Thursday. Expect temporary lane closures between 9am–2pm.</p>
          <div class="mt-5 pt-4 border-t border-gray-100 flex items-center justify-between text-sm text-gray-600">
            <div class="flex items-center gap-4">
              <span class="inline-flex items-center gap-1"><i data-lucide="share-2" class="w-4 h-4 text-sky-500"></i> 13</span>
            </div>
            <div class="flex items-center gap-3">
              <button class="hover:text-gray-900">Share</button>
              <?php if (!empty($_SESSION['is_admin'])): ?>
                <button class="text-red-600 hover:text-red-700 font-semibold">Delete</button>
              <?php endif; ?>
            </div>
          </div>
        </article>

        <!-- Post 3 -->
        <article class="bg-white border-2 border-gray-300 rounded-2xl p-6 shadow-md">
          <div class="flex items-start justify-between">
            <div>
              <h2 class="text-xl font-semibold text-gray-900">Park Clean-Up Highlights</h2>
              <p class="text-xs text-gray-500 mt-1">Posted by <span class="font-medium text-gray-700">Priya Singh</span> • 3 days ago • Parks</p>
            </div>
            <button class="text-gray-400 hover:text-gray-600">
              <i data-lucide="more-horizontal" class="w-5 h-5"></i>
            </button>
          </div>
          <p class="text-gray-700 mt-4">Community volunteers helped remove litter and repaint benches at Meadow Park. Next clean-up is scheduled for next Saturday.</p>
          <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
            <img src="https://placehold.co/600x400" alt="Park clean-up" class="w-full h-64 object-cover" />
          </div>
          <div class="mt-5 pt-4 border-t border-gray-100 flex items-center justify-between text-sm text-gray-600">
            <div class="flex items-center gap-4">
              <span class="inline-flex items-center gap-1"><i data-lucide="share-2" class="w-4 h-4 text-sky-500"></i> 41</span>
            </div>
            <div class="flex items-center gap-3">
              <button class="hover:text-gray-900">Share</button>
              <?php if (!empty($_SESSION['is_admin'])): ?>
                <button class="text-red-600 hover:text-red-700 font-semibold">Delete</button>
              <?php endif; ?>
            </div>
          </div>
        </article>
      </div>
    </div>

    <aside class="bg-white p-8 rounded-xl shadow-sm border border-gray-200 space-y-6">
      <div class="border-t border-gray-200 pt-4">
        <h3 class="text-sm font-bold text-gray-900 mb-2">Quick Stats</h3>
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
            <div class="text-xs text-gray-500">Posts</div>
            <div class="text-lg font-semibold text-gray-900">38</div>
          </div>
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
            <div class="text-xs text-gray-500">Comments</div>
            <div class="text-lg font-semibold text-gray-900">412</div>
          </div>
        </div>
      </div>
    </aside>
  </div>

  <script>lucide.createIcons();</script>
</body>
</html>
