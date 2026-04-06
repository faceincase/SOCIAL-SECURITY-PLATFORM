<?php
// blog.php - community feed
session_start();

// DB connection — fetch real posts and handle admin deletes
$dbFile   = __DIR__ . DIRECTORY_SEPARATOR . 'DATABASE' . DIRECTORY_SEPARATOR . 'reports.sqlite';
$realPosts = [];
try {
	$pdo = new PDO('sqlite:' . $dbFile);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// Admin: handle delete
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['is_admin'])) {
		$_action = $_POST['action'] ?? '';
		$_id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		if ($_action === 'delete_post' && $_id > 0) {
			$pdo->prepare('DELETE FROM blog_posts WHERE id = :id')->execute([':id' => $_id]);
		}
		header('Location: blog.php');
		exit;
	}

	$stmt      = $pdo->query('SELECT id, title, category, content, author_username, created_at FROM blog_posts ORDER BY created_at DESC');
	$realPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
	// silently degrade — dummy posts still show
}

function blog_relative_time($datetime) {
	$ts   = is_numeric($datetime) ? (int)$datetime : strtotime($datetime);
	if (!$ts) return '';
	$diff = time() - $ts;
	if ($diff < 60) return 'just now';
	$units = [31536000 => 'year', 2592000 => 'month', 604800 => 'week', 86400 => 'day', 3600 => 'hour', 60 => 'minute'];
	foreach ($units as $secs => $name) {
		if ($diff >= $secs) {
			$v = (int)floor($diff / $secs);
			return $v . ' ' . $name . ($v === 1 ? '' : 's') . ' ago';
		}
	}
	return 'just now';
}

$titles = [
	'Road Resurfacing Completed on Main Street',
	'New Park Benches Installed in Riverside Gardens',
	'Street Light Repairs Scheduled This Weekend',
	'Community Clean-up Drive Success',
	'Traffic Signal Upgrade Near City Centre',
	'Pavement Safety Improvements Underway',
	'Waste Collection Service Update',
	'Public Transport Stop Maintenance Notice',
	'Local Playground Equipment Repaired',
	'Drainage Works Planned for Flood-Prone Area'
];

$authors = ['City Council', 'Transport Team', 'Parks Department', 'Community Officer', 'Maintenance Team', 'Public Works', 'Urban Services', 'Admin Team'];
$categories = ['Roads', 'Parks', 'Lighting', 'Waste', 'Pavements', 'Other'];
$times = ['2h ago', '5h ago', '1 day ago', '2 days ago', '3 days ago', '1 week ago'];
$descriptions = [
	'Thank you for reporting this issue. The team has reviewed the location and completed the required work.',
	'We have logged this concern and scheduled the appropriate team to inspect and resolve it shortly.',
	'Update: works are progressing well and temporary measures are now in place for public safety.',
	'Good news — this item has been addressed and will continue to be monitored over the coming days.',
	'Our officers attended the area and confirmed that corrective action has now been taken.',
	'This case was prioritised based on community feedback and has now reached completion.',
	'A follow-up inspection is planned to ensure long-term quality and safety standards are met.',
	'Thanks for your patience. The issue has been forwarded, tracked, and actioned by the relevant service.'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<title>Community Blog</title>
	<script src="https://cdn.tailwindcss.com"></script>
	<script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gradient-to-br from-gray-200 to-gray-50 min-h-screen p-6 px-80">

	<?php include __DIR__ . '/ASSETS/header.php'; ?>

	<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
		<div class="lg:col-span-3 space-y-6">

			<!-- Community feed header card -->
			<div class="bg-gradient-to-br from-sky-50 to-blue-50 p-8 rounded-xl shadow-lg border-2 border-sky-200">
				<div class="flex items-center justify-between">
					<div>
						<h1 class="text-2xl font-bold text-gray-900">Community Feed</h1>
						<p class="text-sm text-gray-600">Latest updates from local services and community reports</p>
					</div>
					<a href="new_post.php" class="group relative text-sm px-4 py-2.5 rounded-xl bg-gradient-to-br from-sky-50 to-blue-50 border-2 border-sky-200 text-sky-700 hover:border-sky-300 hover:shadow-md transition-all duration-200 font-medium">
						<span class="flex items-center gap-2">
							<i data-lucide="plus" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
							NEW POST
						</span>
					</a>
				</div>
			</div>

			<?php
			$badgeStyles = [
				'Roads'          => 'bg-blue-50 text-blue-700 border-blue-200',
				'Parks'          => 'bg-green-50 text-green-700 border-green-200',
				'Lighting'       => 'bg-amber-50 text-amber-700 border-amber-200',
				'Waste'          => 'bg-purple-50 text-purple-700 border-purple-200',
				'Pavements'      => 'bg-cyan-50 text-cyan-700 border-cyan-200',
				'Sanitation'     => 'bg-orange-50 text-orange-700 border-orange-200',
				'Infrastructure' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
				'Community'      => 'bg-teal-50 text-teal-700 border-teal-200',
				'Other'          => 'bg-gray-50 text-gray-700 border-gray-200',
			];
			?>

			<!-- Real posts from database -->
			<?php foreach ($realPosts as $post):
				$badgeClass = $badgeStyles[$post['category']] ?? $badgeStyles['Other'];
				$relTime    = blog_relative_time($post['created_at']);
			?>
				<article class="bg-white p-6 rounded-xl shadow-lg border-2 border-sky-200">
					<div class="flex items-center justify-between mb-3">
						<div class="flex items-center gap-2 text-xs text-gray-500">
							<i data-lucide="user" class="w-3.5 h-3.5"></i>
							<span><?php echo htmlspecialchars($post['author_username'] ?? 'Community'); ?></span>
							<span>•</span>
							<span><?php echo htmlspecialchars($relTime); ?></span>
						</div>
						<span class="text-xs px-2.5 py-1 rounded-full border <?php echo $badgeClass; ?>">
							<?php echo htmlspecialchars($post['category']); ?>
						</span>
					</div>

					<h2 class="text-lg font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($post['title']); ?></h2>
					<p class="text-sm text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>

					<div class="mt-4 pt-3 border-t border-gray-200 flex items-center justify-end">
						<div class="flex items-center gap-2">
							<button type="button" class="share-btn inline-flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg border border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100 transition">
								<i data-lucide="share-2" class="w-3.5 h-3.5"></i>
								<span class="share-text">Share</span>
							</button>
							<?php if (!empty($_SESSION['is_admin'])): ?>
								<form method="POST" onsubmit="return confirm('Delete this post?');">
									<input type="hidden" name="action" value="delete_post">
									<input type="hidden" name="id" value="<?php echo (int)$post['id']; ?>">
									<button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 transition">
										<i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
										Delete
									</button>
								</form>
							<?php endif; ?>
						</div>
					</div>
				</article>
			<?php endforeach; ?>

			<!-- Divider before demo posts -->
			 <!--
			<?php if (!empty($realPosts)): ?>
				<div class="flex items-center gap-3 my-2">
					<div class="flex-1 h-px bg-gray-300"></div>
					<span class="text-xs text-gray-400 font-medium uppercase tracking-widest">Demo Posts</span>
					<div class="flex-1 h-px bg-gray-300"></div>
				</div>
			<?php endif; ?>
			-->

			<!-- Dummy demo posts -->
			<?php for ($i = 0; $i < 50; $i++): ?>
				<?php
					$title       = $titles[array_rand($titles)];
					$author      = $authors[array_rand($authors)];
					$category    = $categories[array_rand($categories)];
					$time        = $times[array_rand($times)];
					$description = $descriptions[array_rand($descriptions)];
					$hasImage    = rand(0, 1) === 1;
					$views       = rand(50, 5000);
					$badgeClass  = $badgeStyles[$category] ?? $badgeStyles['Other'];
					$imageGradients = [
						'from-blue-100 to-blue-200',
						'from-emerald-100 to-emerald-200',
						'from-amber-100 to-amber-200',
						'from-purple-100 to-purple-200',
						'from-rose-100 to-rose-200'
					];
					$imgGradient = $imageGradients[array_rand($imageGradients)];
				?>

				<article class="bg-white p-6 rounded-xl shadow-lg border-2 border-gray-300">
					<div class="flex items-center justify-between mb-3">
						<div class="flex items-center gap-2 text-xs text-gray-500">
							<i data-lucide="user" class="w-3.5 h-3.5"></i>
							<span><?php echo htmlspecialchars($author); ?></span>
							<span>•</span>
							<span><?php echo htmlspecialchars($time); ?></span>
						</div>
						<span class="text-xs px-2.5 py-1 rounded-full border <?php echo $badgeClass; ?>">
							<?php echo htmlspecialchars($category); ?>
						</span>
					</div>

					<h2 class="text-lg font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($title); ?></h2>
					<p class="text-sm text-gray-600 leading-relaxed mb-4"><?php echo htmlspecialchars($description); ?></p>

					<?php if ($hasImage): ?>
						<div class="h-48 rounded-lg border border-gray-200 bg-gradient-to-br from-gray-100 to-gray-400 flex items-center justify-center text-gray-500 text-sm">
							<span class="inline-flex items-center gap-2">
								<i data-lucide="image" class="w-4 h-4"></i>
								Attached image preview
							</span>
						</div>
					<?php endif; ?>

					<div class="mt-4 pt-3 border-t border-gray-200 flex items-center justify-between">
						<div class="inline-flex items-center gap-1.5 text-xs text-gray-500">
							<i data-lucide="eye" class="w-3.5 h-3.5"></i>
							<span><?php echo number_format($views); ?> views</span>
						</div>
						<div class="flex items-center gap-2">
							<button type="button" class="share-btn inline-flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg border border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100 transition">
								<i data-lucide="share-2" class="w-3.5 h-3.5"></i>
								<span class="share-text">Share</span>
							</button>
							<?php if (!empty($_SESSION['is_admin'])): ?>
								<button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 transition">
									<i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
									Delete
								</button>
							<?php endif; ?>
						</div>
					</div>
				</article>
			<?php endfor; ?>
		</div>

		<!-- Sidebar -->
		<aside class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300 space-y-6">
			<div>
				<h2 class="text-lg font-bold text-gray-900 mb-2">Feed Tips</h2>
				<p class="text-sm text-gray-600">Keep updates clear, short, and useful for residents.</p>
			</div>

			<div class="bg-sky-50 p-4 rounded-lg border border-sky-200">
				<img src="IMAGES/lamp.png" alt="Community" class="w-full h-auto rounded">
			</div>

			<ul class="space-y-3 text-sm text-gray-700">
				<li class="flex items-start gap-2">
					<i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0"></i>
					<span>Share progress updates regularly</span>
				</li>
				<li class="flex items-start gap-2">
					<i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0"></i>
					<span>Use the right category for visibility</span>
				</li>
				<li class="flex items-start gap-2">
					<i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0"></i>
					<span>Add images when available</span>
				</li>
			</ul>
		</aside>
	</div>

	<?php include __DIR__ . '/ASSETS/footer.php'; ?>

	<style>
		@keyframes spin {
			to { transform: rotate(360deg); }
		}
		.loading-spinner {
			display: inline-block;
			animation: spin 1s linear infinite;
		}
	</style>

	<script>
		lucide.createIcons();

		document.querySelectorAll('.share-btn').forEach(btn => {
			btn.addEventListener('click', function() {
				const shareText = this.querySelector('.share-text');
				if (shareText.textContent === 'Share') {
					// Show loading spinner
					shareText.innerHTML = '<i data-lucide="loader" class="w-3.5 h-3.5 loading-spinner"></i>';
					lucide.createIcons();

					// Generate URL and show it after 1.5 seconds
					setTimeout(() => {
						const currentUrl = window.location.pathname;
						const randomCode = Array.from(crypto.getRandomValues(new Uint8Array(3)))
							.map(b => b.toString(16).padStart(2, '0'))
							.join('')
							.substring(0, 5);
						const shareUrl =  'https://www.socialinputplatform.co.uk/blog.php?share=' + randomCode;
						shareText.textContent = shareUrl;
					}, 500);
				}
			});
		});
	</script>
</body>
</html>
