

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Report Management System</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gradient-to-br from-gray-200 to-gray-50 min-h-screen p-6 px-80">

  <?php include __DIR__ . '/ASSETS/header.php'; ?>

  <!-- Content Area -->
  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

    <!-- Left Main Section -->
    <div class="lg:col-span-3 space-y-6">

      <!-- Text Block -->
      <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200">
        <h1 class="text-3xl font-bold mb-3 text-gray-900">Report an Issue</h1>
        <p class="text-gray-600 leading-relaxed">
          Help us improve by reporting any issues or bugs you encounter. Your feedback is valuable and helps us deliver a better experience.
        </p>
      </div>

      <!-- Report Button -->
      <a href="report.php" class="w-full inline-flex bg-green-500 px-6 py-4 rounded-xl shadow text-white hover:bg-green-600 hover:shadow-lg transition-all duration-200 items-center justify-center gap-2">
        <span class="text-lg font-semibold">REPORT AN ISSUE</span>
        <i data-lucide="alert-circle" class="w-5 h-5"></i>
      </a>
      <script>lucide.createIcons();</script>

      <!-- Large Content Card -->
      <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200">
        <h2 class="text-2xl font-bold mb-3 text-gray-900">About This System</h2>
        <p class="text-gray-600 leading-relaxed mb-4">
          Our reporting system is designed to streamline the process of documenting and tracking issues. Whether you've encountered a bug, have a feature request, or want to suggest improvements, we're here to listen and act on your feedback.
        </p>
        <p class="text-gray-600 leading-relaxed">
          Every report you submit helps us build a more robust and user-friendly application. Thank you for being part of our community and helping us improve.
        </p>
      </div>
    </div>

    <!-- Right Sidebar -->
    <aside class="bg-white p-8 rounded-xl shadow-sm border border-gray-200 space-y-6">
      <div>
        <h2 class="text-lg font-bold text-gray-900 mb-2">Quick Tips</h2>
        <p class="text-sm text-gray-600">Follow these guidelines when reporting</p>
      </div>

      <div class="bg-green-50 p-4 rounded-lg border border-green-200">
        <img src="IMAGES/lamp.png" alt="Widget" class="w-full h-auto rounded">
      </div>

      <ul class="space-y-3 text-sm text-gray-700">
        <li class="flex items-start gap-2">
          <i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0"></i>
          <span>Be clear and descriptive</span>
        </li>
        <li class="flex items-start gap-2">
          <i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0"></i>
          <span>Include exact location</span>
        </li>
        <li class="flex items-start gap-2">
          <i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0"></i>
          <span>Attach image if possible</span>
        </li>
      </ul>
    </aside>

  </div>

</body>
</html>



<script>lucide.createIcons();</script>