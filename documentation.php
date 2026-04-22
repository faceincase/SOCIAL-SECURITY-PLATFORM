<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Documentation | GROUP PROJECT - REPORT APP</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gradient-to-br from-gray-200 to-gray-50 min-h-screen p-6 px-40">
  <?php include __DIR__ . '/ASSETS/header.php'; ?>

  <main class="space-y-8">
    <section id="overview" class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300">
      <h1 class="text-3xl font-bold mb-4 text-gray-900">Project Documentation Overview</h1>
      <p class="text-gray-700">This document describes the SOCIAL INPUT PLATFORM. It documents purpose, scope, tech choices, architecture, data model, deployment notes, and maintenance guidelines.</p>
      <hr class="border-gray-300 my-6" />
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <h2 class="text-xl font-semibold border-b-2 border-gray-300 pb-2 mb-3">Purpose & Scope</h2>
          <p class="text-gray-700">The application is a lightweight PHP-based portal to manage blog-style posts, reports, issues, and user authentication. It is designed for a student group project and for demonstration of a small LAMP-like stack with simple routing via PHP pages.</p>
        </div>
        <div>
          <h2 class="text-xl font-semibold border-b-2 border-gray-300 pb-2 mb-3">What you can find here</h2>
          <ul class="list-disc list-inside text-gray-700">
            <li>Project goals and deliverables</li>
            <li>Tech stack decisions</li>
            <li>System architecture and data model</li>
            <li>Deployment steps and environment setup</li>
            <li>Security and data handling guidelines</li>
          </ul>
        </div>
      </div>
    </section>

    <section id="tech-stack" class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300">
      <h2 class="text-xl font-semibold border-b-2 border-gray-300 pb-2 mb-4">Tech Stack</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-2 text-gray-700">
          <p><span class="font-semibold">Backend:</span> PHP 8.x with native PHP files (no heavy frameworks).</p>
          <p><span class="font-semibold">Database:</span> MySQL/MariaDB for persistent storage (configured outside this document).</p>
          <p><span class="font-semibold">Frontend:</span> HTML5, CSS3, minimal JavaScript for UX tweaks.</p>
        </div>
        <div class="space-y-2 text-gray-700">
          <p><span class="font-semibold">Environment:</span> Local development stack (Windows) with a PHP-enabled server (XAMPP/WAMP/LAMP equivalent).</p>
          <p><span class="font-semibold">Security:</span> Basic sanitization and prepared statements in PHP for DB input.</p>
          <p><span class="font-semibold">Version Control:</span> Git for source management.</p>
        </div>
      </div>
    </section>

    <section id="architecture" class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300">
      <h2 class="text-xl font-semibold border-b-2 border-gray-300 pb-2 mb-3">Architecture</h2>
      <p class="text-gray-700">This project uses a simple, page-based architecture rendered by PHP templates. Key folders:</p>
      <ul class="list-disc list-inside text-gray-700 mb-4">
        <li>ROOT: blog.php, index.php, report.php, issues.php, login.php, login_admin.php, new_post.php</li>
        <li>ASSETS/: Static UI fragments like header.php, footer.php</li>
        <li>DATABASE/: Database configuration and schema (managed externally)</li>
        <li>IMAGES/: Media assets</li>
        <li>REPORT_IMAGES/: Report-related images</li>
      </ul>
      <p class="text-gray-700">Typical request flow:</p>
      <ol class="list-decimal list-inside text-gray-700">
        <li>User requests a PHP page (e.g., blog.php).</li>
        <li>Page loads common header/footer via includes.</li>
        <li>PHP interacts with the database to fetch content, then renders HTML.</li>
        <li>Form submissions are processed by corresponding PHP handlers and persisted to DB.</li>
      </ol>
    </section>

    <section id="workflow" class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300">
      <h2 class="text-xl font-semibold border-b-2 border-gray-300 pb-2 mb-3">How it works</h2>
      <ol class="list-decimal list-inside text-gray-700">
        <li><strong>Routing</strong> is file-based (each PHP page acts as a route).</li>
        <li><strong>Templates</strong> header/footer are modularized in ASSETS/ for reuse.</li>
        <li><strong>Data access</strong> through standard PHP MySQLi/PDO connections with prepared statements.</li>
        <li><strong>Forms</strong> Submit to PHP scripts that sanitize input, validate, and persist to DB.</li>
        <li><strong>Security</strong> Basic session handling; role-based access for admin pages (login_admin.php).</li>
      </ol>
    </section>

    <section id="data-model" class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300">
      <h2 class="text-xl font-semibold border-b-2 border-gray-300 pb-2 mb-3">Data Model (Overview)</h2>
      <p class="text-gray-700">Core entities inferred from the project scope:</p>
      <ul class="list-disc list-inside text-gray-700 mb-4">
        <li><strong>Users</strong> (id, username, password_hash, role, created_at)</li>
        <li><strong>Posts</strong> (id, title, content, author_id, created_at, updated_at, status)</li>
        <li><strong>Issues</strong> (id, title, description, status, reporter_id, created_at)</li>
        <li><strong>Reports</strong> (id, title, content, author_id, created_at)</li>
      </ul>
    </section>

    <section id="security" class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300">
      <h2 class="text-xl font-semibold border-b-2 border-gray-300 pb-2 mb-3">Security & Data Handling</h2>
      <ul class="list-disc list-inside text-gray-700">
        <li>Use prepared statements for all DB queries to avoid SQL injection.</li>
        <li>Validate and sanitize all user input on server side; never trust client input.</li>
        <li>Store passwords as salted hashes using password_hash() and verify with password_verify().</li>
        <li>Use session management to protect admin routes; implement logout and session expiration.</li>
      </ul>
    </section>

    <section id="endpoints" class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300">
      <h2 class="text-xl font-semibold border-b-2 border-gray-300 pb-2 mb-3">Pages & Endpoints (Overview)</h2>
      <p class="text-gray-700">List of PHP pages and their core purpose. This helps developers understand navigation and responsibilities.</p>
      <ul class="list-disc list-inside text-gray-700">
        <li><strong>index.php</strong> Landing page with overview and navigation.</li>
        <li><strong>blog.php</strong> Listing and viewing blog posts.</li>
        <li><strong>new_post.php</strong> Create or edit a blog post.</li>
        <li><strong>report.php</strong> View or manage reports.</li>
        <li><strong>issues.php</strong> Track issues or tasks.</li>
        <li><strong>login.php</strong> Public user authentication flow.</li>
        <li><strong>login_admin.php</strong> Admin authentication and admin actions.</li>
        <li><strong>documentation.php</strong> This documentation page.</li>
      </ul>
      <p class="text-gray-700">Note: All routes are served by the server-side; there is no REST API in this minimal setup.</p>
    </section>

    <section id="deployment" class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300">
      <h2 class="text-xl font-semibold border-b-2 border-gray-300 pb-2 mb-3">Deployment & Environment</h2>
      <ol class="list-decimal list-inside text-gray-700">
        <li>Install a PHP-enabled web server (e.g., XAMPP/WAMP/LAMP).</li>
        <li>Configure document root to point to the project folder.</li>
        <li>Set DB credentials in a config file or environment (not in this doc).</li>
        <li>Ensure file permissions allow PHP to read/write where required (uploads if any).</li>
        <li>Run initial DB migrations or create tables manually according to your schema.</li>
      </ol>
      <p class="text-gray-700">Environment notes:</p>
      <ul class="list-disc list-inside text-gray-700">
        <li>OS: Windows</li>
        <li>Terminal: PowerShell</li>
      </ul>
    </section>

    <section id="maintenance" class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300">
      <h2 class="text-xl font-semibold border-b-2 border-gray-300 pb-2 mb-3">Maintenance</h2>
      <ul class="list-disc list-inside text-gray-700">
        <li>Keep PHP up-to-date within supported PHP version for security.</li>
        <li>Version control changes via Git; write meaningful commit messages.</li>
        <li>Document any schema changes and update this documentation accordingly.</li>
      </ul>
    </section>


  </main>

  <?php include __DIR__ . '/ASSETS/footer.php'; ?>

  <script>lucide.createIcons();</script>
</body>
</html>