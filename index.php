<?php

/////////////////////////////////////////////////////////////////////////////////////////
// LOG VISIT
date_default_timezone_set('UTC');
session_start();

$shouldLog = true;

// --- JS VERIFICATION ---
if (!isset($_SESSION['js_verified'])) {
    echo '<script>
        fetch("/verify.php", {method: "POST"})
            .then(() => location.reload());
    </script>';
    $shouldLog = false;
}

// --- BOT FILTER ---
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$botPatterns = [
    'bot', 'crawl', 'spider', 'slurp', 'curl', 'wget', 'python', 'scrapy'
];

foreach ($botPatterns as $pattern) {
    if (stripos($userAgent, $pattern) !== false) {
        $shouldLog = false;
        break;
    }
}

// --- LOGGING ---
if ($shouldLog) {
    $visitLogFile = __DIR__ . '/DATABASE/visits.log';
    $visitorIp = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

    $visitEntry = [
        'time' => date('Y-m-d H:i:s'),
        'ip' => $visitorIp,
    ];

    file_put_contents(
        $visitLogFile,
        json_encode($visitEntry) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}
// LOG VISIT
/////////////////////////////////////////////////////////////////////////////////////////


// Database connection
$dbDir = __DIR__ . DIRECTORY_SEPARATOR . 'DATABASE';
$dbFile = $dbDir . DIRECTORY_SEPARATOR . 'reports.sqlite';
$dsn = 'sqlite:' . $dbFile;
$adminUsers = [];

try {
  $pdo = new PDO($dsn);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  
  // Fetch all users with roles
  $stmt = $pdo->prepare("SELECT id, username, type, role, created_at FROM users WHERE type IS NOT NULL ORDER BY created_at ASC");
  $stmt->execute();
  $adminUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  // Silently handle error
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Report Management System</title>
  
  <!-- Open Graph Meta Tags for Social Media -->
  <meta property="og:title" content="Report Management System" />
  <meta property="og:description" content="See Something, Say Something! Report everyday issues in your community and make your neighbourhood a better place." />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://example.com" />
  <meta property="og:image" content="https://via.placeholder.com/1200x630?text=Report+Management+System" />
  
  <!-- Twitter Card Meta Tags -->
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="Report Management System" />
  <meta name="twitter:description" content="See Something, Say Something! Report everyday issues in your community and make your neighbourhood a better place." />
  <meta name="twitter:image" content="https://via.placeholder.com/1200x630?text=Report+Management+System" />
  
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gradient-to-br from-gray-200 to-gray-50 min-h-screen p-6 px-80">

  <?php include __DIR__ . '/ASSETS/header.php'; ?>

  <div id="disclaimerModal" class="fixed inset-0 hidden items-center justify-center bg-black/95 z-50">
    <div class="bg-white border border-gray-300 shadow-2xl w-full max-w-3xl mx-4">
      <div class="h-1.5 bg-gradient-to-r from-red-700 to-red-400"></div>
      <div class="px-6 py-5 border-b border-gray-200">
        <h2 class="text-sm font-semibold tracking-widest text-gray-800 uppercase">Important Disclaimer</h2>
      </div>
      <div class="px-6 py-6 text-sm text-gray-700 leading-relaxed space-y-4">
        <p class="font-semibold text-gray-900">This is a dummy project for demonstration purposes only.</p>
        <p>Please do not use real personal data, real passwords, real usernames, or real images. Use placeholder content only.</p>
        <p>All submissions are for testing and should be treated as non-production data.</p>
      </div>
      <div class="px-6 py-5 border-t border-gray-200 flex items-center justify-end">
        <button id="disclaimerAcknowledge" class="px-5 py-2 text-sm font-semibold bg-gray-900 text-white hover:bg-gray-800 transition">I Understand</button>
      </div>
    </div>
  </div>

  <!-- Content Area -->
  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

    <!-- Left Main Section -->
    <div class="lg:col-span-3 space-y-6">

      <?php /*
      <!-- Text Block -->
      <div class="relative bg-white p-8 rounded-xl shadow-2xl border-2 border-gray-300 overflow-hidden group hover:shadow-[0_20px_50px_rgba(0,0,0,0.15)] transition-all duration-300">
        <!-- Animated background pulse -->
        <div class="absolute inset-0 bg-gray-100 opacity-0 group-hover:opacity-50 transition-opacity duration-500"></div>
        
        <!-- Alert icon with pulse animation -->
        <div class="absolute -right-6 -top-6 w-32 h-32 bg-gray-100 rounded-full flex items-center justify-center animate-pulse">
          <i data-lucide="alert-circle" class="w-16 h-16 text-gray-300"></i>
        </div>
        
        <div class="relative z-10">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 bg-gray-900 rounded-full flex items-center justify-center animate-pulse">
              <i data-lucide="eye" class="w-7 h-7 text-white"></i>
            </div>
            <h1 class="text-4xl font-black text-gray-900 tracking-tight">
              See Something, Say Something!
            </h1>
          </div>
          <p class="text-gray-600 text-lg font-semibold leading-relaxed ml-15">
            🚨 Quick! Spot an issue? Report it in seconds and make your neighbourhood a better place.
          </p>
        </div>
      </div>
      */ ?>

      <div class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300">
        <h1 class="text-3xl font-bold mb-3 text-gray-900">Report an Issue</h1>
        <p class="text-gray-600 leading-relaxed">
          Help us improve by reporting any issues or bugs you encounter. Your feedback is valuable and helps us deliver a better experience.
        </p>
      </div>



      <!-- Action Buttons -->
      <div class="flex gap-4">
        <a href="report.php" class="w-1/2 inline-flex bg-green-600 hover:bg-green-700 px-6 py-4 rounded-xl shadow text-white  hover:shadow-lg transition-all duration-200 items-center justify-center gap-2">
          <span class="text-lg font-semibold">REPORT AN ISSUE</span>
          <i data-lucide="clipboard-plus" class="w-5 h-5"></i>
        </a>
        <a href="blog.php" class="w-1/2 inline-flex bg-sky-500 hover:bg-sky-600 px-6 py-4 rounded-xl shadow text-white hover:shadow-lg transition-all duration-200 items-center justify-center gap-2">
          <span class="text-lg font-semibold">VIEW BLOG</span>
          <i data-lucide="newspaper" class="w-5 h-5"></i>
        </a>
      </div>

      <script>lucide.createIcons();</script>

      <!-- Large Content Card -->
      <div class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300">
        <h2 class="text-2xl font-bold mb-3 text-gray-900">About This System</h2>
        <div class="space-y-4 text-gray-700 leading-relaxed">
          <p class="font-semibold text-gray-900">Every better city starts with someone speaking up.</p>
          
          <p>This platform was built for communities across the UK to report everyday issues that matter — from potholes and broken streetlights to fly-tipping and damaged public spaces.</p>
          
          <p>When you submit a report, it doesn't disappear into the void. Each issue is securely reviewed by authorised moderators and directed to the appropriate authorities for action. Reports remain private and are handled responsibly.</p>
          
          <p class="font-semibold text-gray-900">We're not a council. We're not a complaint box.<br>
          We're a bridge between people who care and the systems that can fix things.</p>
          
          <p class="italic">Because small reports create real change — and better streets start with you.</p>
        </div>
      </div>

      <!-- Feature Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300 hover:shadow-xl transition-shadow">
          <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center mb-4">
            <i data-lucide="shield-check" class="w-6 h-6 text-green-600"></i>
          </div>
          <h3 class="text-lg font-bold text-gray-900 mb-2">Trusted Data Handling</h3>
          <p class="text-sm text-gray-600 leading-relaxed mb-4">
            Reports are handled with role-based access, secure storage, and audit-friendly workflows to support responsible moderation.
          </p>
          <div class="text-xs text-gray-500 space-y-1">
            <p>Encryption in transit and at rest</p>
            <p>UK-focused data governance</p>
          </div>
        </div>

        <div class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300 hover:shadow-xl transition-shadow">
          <div class="w-12 h-12 rounded-lg bg-sky-100 flex items-center justify-center mb-4">
            <i data-lucide="zap" class="w-6 h-6 text-sky-600"></i>
          </div>
          <h3 class="text-lg font-bold text-gray-900 mb-2">Fast Reporting Flow</h3>
          <p class="text-sm text-gray-600 leading-relaxed mb-4">
            Submit issues in a few steps with location details and optional photo evidence to help teams review faster.
          </p>
          <div class="text-xs text-gray-500 space-y-1">
            <p>Simple form structure</p>
            <p>Image-backed submissions</p>
          </div>
        </div>

        <div class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300 hover:shadow-xl transition-shadow">
          <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center mb-4">
            <i data-lucide="trending-up" class="w-6 h-6 text-amber-600"></i>
          </div>
          <h3 class="text-lg font-bold text-gray-900 mb-2">Actionable Oversight</h3>
          <p class="text-sm text-gray-600 leading-relaxed mb-4">
            Built-in status tracking and moderation views support consistent follow-up, clearer accountability, and better outcomes.
          </p>
          <div class="text-xs text-gray-500 space-y-1">
            <p>Clear progress visibility</p>
            <p>Operationally focused dashboard</p>
          </div>
        </div>
      </div>

    </div>

    <!-- Right Sidebar -->
    <aside class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300 space-y-6">
      <div>
        <h2 class="text-lg font-bold text-gray-900 mb-2">Platform Overview</h2>
        <p class="text-sm text-gray-600">Your community reporting hub</p>
      </div>

      <div class="bg-gradient-to-br from-green-50 to-sky-50 p-6 rounded-lg border border-green-200">
        <div class="text-center space-y-2">
          <i data-lucide="users" class="w-12 h-12 text-green-600 mx-auto"></i>
          <h3 class="text-xl font-bold text-gray-900">Community Driven</h3>
          <p class="text-xs text-gray-600">Making our area better together</p>
        </div>
      </div>

      <div class="space-y-3">
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
          <div class="flex items-center gap-2">
            <i data-lucide="map-pin" class="w-4 h-4 text-green-600"></i>
            <span class="text-sm font-medium text-gray-700">Report Issues</span>
          </div>
          <span class="text-xs text-gray-500">Live</span>
        </div>
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
          <div class="flex items-center gap-2">
            <i data-lucide="eye" class="w-4 h-4 text-sky-600"></i>
            <span class="text-sm font-medium text-gray-700">Track Progress</span>
          </div>
          <span class="text-xs text-gray-500">24/7</span>
        </div>
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
          <div class="flex items-center gap-2">
            <i data-lucide="bell" class="w-4 h-4 text-amber-600"></i>
            <span class="text-sm font-medium text-gray-700">Get Updates</span>
          </div>
          <span class="text-xs text-gray-500">Active</span>
        </div>
      </div>
    </aside>

  </div>

  <?php include __DIR__ . '/ASSETS/footer.php'; ?>

  <script>
    (function () {
      const modal = document.getElementById('disclaimerModal');
      const btn = document.getElementById('disclaimerAcknowledge');
      const cookieName = 'disclaimer_acknowledged111';

      function hasCookie() {
        return document.cookie.split(';').some(c => c.trim().startsWith(cookieName + '='));
      }

      function setCookie() {
        const maxAge = 60 * 60 * 24 * 365;
        document.cookie = cookieName + '=true; Max-Age=' + maxAge + '; Path=/; SameSite=Lax';
      }

      if (!hasCookie()) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
      }

      btn.addEventListener('click', function () {
        setCookie();
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      });
    })();
  </script>

</body>
</html>



<script>lucide.createIcons();</script>