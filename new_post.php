<?php
// new_post.php - create a new blog post
session_start();

if (empty($_SESSION['logged_in'])) {
  header('Location: login.php?info=2');
  exit;
}

$success = false;
$errors  = [];

// DB connection
$dbFile = __DIR__ . DIRECTORY_SEPARATOR . 'DATABASE' . DIRECTORY_SEPARATOR . 'reports.sqlite';
try {
  $pdo = new PDO('sqlite:' . $dbFile);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  $errors[] = 'Database error: ' . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title    = trim($_POST['title']    ?? '');
  $category = trim($_POST['category'] ?? '');
  $content  = trim($_POST['content']  ?? '');

  // Validation
  if (empty($title))    $errors[] = 'Title is required.';
  if (empty($category)) $errors[] = 'Category is required.';
  if (empty($content))  $errors[] = 'Content is required.';

  if (empty($errors) && isset($pdo)) {
    try {
      $stmt = $pdo->prepare(
        'INSERT INTO blog_posts (title, category, content, author_username) VALUES (:title, :category, :content, :author)'
      );
      $stmt->execute([
        ':title'    => $title,
        ':category' => $category,
        ':content'  => $content,
        ':author'   => $_SESSION['username'] ?? 'Anonymous',
      ]);
      header('Location: blog.php');
      exit;
    } catch (PDOException $e) {
      $errors[] = 'Could not save post: ' . $e->getMessage();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Create New Post</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gradient-to-br from-gray-200 to-gray-50 min-h-screen p-6 px-80">

  <?php include __DIR__ . '/ASSETS/header.php'; ?>

  <!-- Content Area -->
  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <div class="lg:col-span-3 space-y-6">
      <!-- Header Card -->
      <div class="bg-gradient-to-br from-sky-50 to-blue-50 p-8 rounded-xl shadow-lg border-2 border-sky-200">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Create New Post</h1>
            <p class="text-sm text-gray-600">Share updates and announcements with the community</p>
          </div>
          <a href="blog.php" class="group relative text-sm px-4 py-2.5 rounded-xl bg-gradient-to-br from-gray-50 to-white border-2 border-gray-200 text-gray-700 hover:border-gray-300 hover:shadow-md transition-all duration-200 font-medium">
            <span class="flex items-center gap-2">
              <i data-lucide="arrow-left" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
              Back to Blog
            </span>
          </a>
        </div>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
          <div class="flex items-start gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 mt-0.5"></i>
            <div>
              <h3 class="font-semibold text-red-900 mb-2">Please fix the following errors:</h3>
              <ul class="list-disc pl-5 space-y-1">
                <?php foreach ($errors as $err): ?>
                  <li class="text-red-700"><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4">
          <div class="flex items-start gap-3">
            <i data-lucide="check-circle" class="w-5 h-5 text-green-600 mt-0.5"></i>
            <div>
              <h3 class="font-semibold text-green-900">Post created successfully!</h3>
              <p class="text-green-700 mt-1">Your post has been published to the community feed.</p>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Form Card -->
      <div class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300">
        <form method="POST" class="space-y-6">
          <div>
            <label class="text-sm font-medium text-gray-700 block mb-2">Post Title</label>
            <input 
              type="text" 
              name="title" 
              value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" 
              placeholder="e.g., Road Maintenance Update" 
              required 
              class="w-full rounded-lg border border-gray-300 bg-white shadow-sm focus:ring-2 focus:ring-blue-300 focus:border-blue-300 p-3 text-gray-900"
            />
          </div>

          <div>
            <label class="text-sm font-medium text-gray-700 block mb-2">Category</label>
            <select 
              name="category" 
              required 
              class="w-full rounded-lg border border-gray-300 bg-white shadow-sm focus:ring-2 focus:ring-blue-300 focus:border-blue-300 p-3 text-gray-900"
            >
              <option value="">-- Select a category --</option>
              <option value="Roads" <?php echo isset($category) && $category === 'Roads' ? 'selected' : ''; ?>>Roads</option>
              <option value="Parks" <?php echo isset($category) && $category === 'Parks' ? 'selected' : ''; ?>>Parks</option>
              <option value="Sanitation" <?php echo isset($category) && $category === 'Sanitation' ? 'selected' : ''; ?>>Sanitation</option>
              <option value="Lighting" <?php echo isset($category) && $category === 'Lighting' ? 'selected' : ''; ?>>Lighting</option>
              <option value="Infrastructure" <?php echo isset($category) && $category === 'Infrastructure' ? 'selected' : ''; ?>>Infrastructure</option>
              <option value="Community" <?php echo isset($category) && $category === 'Community' ? 'selected' : ''; ?>>Community</option>
            </select>
          </div>

          <div>
            <label class="text-sm font-medium text-gray-700 block mb-2">Post Content</label>
            <textarea 
              name="content" 
              placeholder="Write your post content here..." 
              required 
              rows="8"
              class="w-full rounded-lg border border-gray-300 bg-white shadow-sm focus:ring-2 focus:ring-blue-300 focus:border-blue-300 p-3 text-gray-900"
            ><?php echo isset($content) ? htmlspecialchars($content) : ''; ?></textarea>
          </div>

          <div class="flex gap-3">
            <button 
              type="submit" 
              class="flex-1 inline-flex items-center justify-center gap-2 bg-sky-600 text-white px-4 py-3 rounded-lg hover:bg-sky-700 transition font-semibold"
            >
              <i data-lucide="send" class="w-4 h-4"></i>
              Publish Post
            </button>
            <a 
              href="blog.php" 
              class="inline-flex items-center justify-center gap-2 bg-gray-200 text-gray-700 px-4 py-3 rounded-lg hover:bg-gray-300 transition font-semibold"
            >
              <i data-lucide="x" class="w-4 h-4"></i>
              Cancel
            </a>
          </div>
        </form>
      </div>
    </div>

    <!-- Sidebar -->
    <aside class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300 space-y-6">
      <div class="border-t border-gray-200 pt-4">
        <h3 class="text-sm font-bold text-gray-900 mb-2">Quick Tips</h3>
        <div class="space-y-3 text-sm text-gray-600">
          <div class="flex gap-3">
            <i data-lucide="lightbulb" class="w-4 h-4 text-yellow-500 flex-shrink-0 mt-0.5"></i>
            <p>Use clear and descriptive titles to get more engagement</p>
          </div>
          <div class="flex gap-3">
            <i data-lucide="message-circle" class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5"></i>
            <p>Include relevant details and context in your post</p>
          </div>
          <div class="flex gap-3">
            <i data-lucide="tag" class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5"></i>
            <p>Select the appropriate category for your post</p>
          </div>
        </div>
      </div>

      <div class="border-t border-gray-200 pt-4">
        <h3 class="text-sm font-bold text-gray-900 mb-3">Categories</h3>
        <div class="space-y-2">
          <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-50 border border-blue-200">
            <i data-lucide="car" class="w-4 h-4 text-blue-600"></i>
            <span class="text-xs font-medium text-blue-700">Roads</span>
          </div>
          <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-green-50 border border-green-200">
            <i data-lucide="trees" class="w-4 h-4 text-green-600"></i>
            <span class="text-xs font-medium text-green-700">Parks</span>
          </div>
          <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200">
            <i data-lucide="trash-2" class="w-4 h-4 text-amber-600"></i>
            <span class="text-xs font-medium text-amber-700">Sanitation</span>
          </div>
        </div>
      </div>
    </aside>
  </div>

  <?php include __DIR__ . '/ASSETS/footer.php'; ?>

  <script>lucide.createIcons();</script>
</body>
</html>
