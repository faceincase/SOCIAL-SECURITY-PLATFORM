<?php
// issues.php - list all reports
session_start();

if (empty($_SESSION['is_admin'])) {
  header('Location: login.php?info=1');
  exit;
}

$errors = [];
$reports = [];
$success = '';

function format_relative_time($timestamp) {
  $now = time();
  $diff = $now - $timestamp;
  $future = $diff < 0;
  $diff = abs($diff);

  if ($diff < 60) {
    return $future ? 'in a moment' : 'just now';
  }

  $units = [
    31536000 => 'year',
    2592000  => 'month',
    604800   => 'week',
    86400    => 'day',
    3600     => 'hour',
    60       => 'minute'
  ];

  foreach ($units as $secs => $name) {
    if ($diff >= $secs) {
      $value = (int) floor($diff / $secs);
      $label = $name . ($value === 1 ? '' : 's');
      return $future ? "in {$value} {$label}" : "{$value} {$label} ago";
    }
  }

  return $future ? 'in a moment' : 'just now';
}

$dbDir = __DIR__ . DIRECTORY_SEPARATOR . 'DATABASE';
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}
$dbFile = $dbDir . DIRECTORY_SEPARATOR . 'reports.sqlite';
$dsn = 'sqlite:' . $dbFile;
try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ensure status column exists
    $cols = $pdo->query("PRAGMA table_info('reports')")->fetchAll(PDO::FETCH_ASSOC);
    $hasStatus = false;
    foreach ($cols as $c) {
      if (isset($c['name']) && $c['name'] === 'status') {
        $hasStatus = true;
        break;
      }
    }
    if (!$hasStatus) {
      $pdo->exec("ALTER TABLE reports ADD COLUMN status TEXT DEFAULT 'open'");
    }

    // Handle moderation actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $action = $_POST['action'] ?? '';
      $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
      if ($id > 0) {
        if ($action === 'fixed') {
          $upd = $pdo->prepare('UPDATE reports SET status = :status WHERE id = :id');
          $upd->execute([':status' => 'fixed', ':id' => $id]);
          $success = 'Marked as fixed.';
        } elseif ($action === 'invalid') {
          $upd = $pdo->prepare('UPDATE reports SET status = :status WHERE id = :id');
          $upd->execute([':status' => 'invalid', ':id' => $id]);
          $success = 'Marked as invalid.';
        }
      }
      header('Location: issues.php');
      exit;
    }

    $stmt = $pdo->query('SELECT id, post_code, street, category, subcategory, image, created_at, status FROM reports ORDER BY created_at DESC, id DESC');
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Compute city counts using the leading letters of the postcode
    $city_counts = [];
    foreach ($reports as $rep) {
      $pc = strtoupper(trim($rep['post_code'] ?? ''));
      if ($pc === '') continue;
      if (preg_match('/^([A-Z]+)/', $pc, $m)) {
        $city = $m[1];
      } else {
        $city = $pc;
      }
      if (!isset($city_counts[$city])) $city_counts[$city] = 0;
      $city_counts[$city]++;
    }
    $top_city = null;
    $top_count = 0;
    if (!empty($city_counts)) {
      arsort($city_counts);
      $top_city = key($city_counts);
      $top_count = current($city_counts);
    }
} catch (PDOException $e) {
    $errors[] = 'Database error: ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>All Reports</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <style>
    #map { height: 500px; border-radius: 0.75rem; z-index: 1 !important; }
    .leaflet-control-container { z-index: 2 !important; }
    .modal-backdrop { display: none; position: fixed; inset: 0; background-color: rgba(0,0,0,0.7); z-index: 999; }
    .modal-backdrop.active { display: flex; align-items: center; justify-content: center; }
    .modal-content { background: white; border-radius: 0.75rem; max-width: 90vw; max-height: 90vh; overflow: auto; position: relative; z-index: 1000; }
    #modalImage { max-width: 90vw; max-height: 80vh; object-fit: contain; display: block; }
    .modal-close { position: absolute; top: 1rem; right: 1rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.375rem; padding: 0.5rem; cursor: pointer; z-index: 1001; }
    .modal-close:hover { background: #f3f4f6; }
    .issue-thumb { filter: grayscale(100%) saturate(0%) contrast(95%); transition: filter 0.2s ease; }
    .issue-thumb:hover { filter: grayscale(0%) saturate(100%) contrast(100%); }
  </style>
</head>
<body class="bg-gradient-to-br from-gray-200 to-gray-50 min-h-screen p-6 px-80">

  <?php include __DIR__ . '/ASSETS/header.php'; ?>

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

    <div class="lg:col-span-3 space-y-6">

      <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200">
        <h1 class="text-3xl font-bold mb-3 text-gray-900">All Reports</h1>
        <p class="text-gray-600 leading-relaxed">Browse all submitted issues. Click the image to open full size if available.</p>
      </div>

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

      <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
        <h2 class="text-xl font-bold mb-4 text-gray-900">Issue Locations</h2>
        <div id="map"></div>
      </div>

      <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
        <h2 class="text-xl font-bold mb-4 text-gray-900">Statistics</h2>
        <?php if (!empty($city_counts)): ?>
          <?php $max = reset($city_counts); ?>
          <div class="flex gap-6">


            <div class="w-1/2">
              <h3 class="text-sm font-semibold mb-2 text-gray-800">Issues by city</h3>
              <div class="space-y-2">
                <?php foreach ($city_counts as $city => $cnt):
                  $pct = $max ? round(($cnt / $max) * 100) : 0;
                ?>
                  <div class="flex items-center gap-3">
                    <div class="text-xs w-20 text-right text-gray-700"><?php echo htmlspecialchars($city); ?></div>
                    <div class="flex-1 bg-gray-100 rounded overflow-hidden h-4">
                      <div style="width: <?php echo $pct; ?>%; background: linear-gradient(90deg, #a9a9a9ff, #3f3f3fff); height:100%;"></div>
                    </div>
                    <div class="w-10 text-xs text-right text-gray-600"><?php echo (int)$cnt; ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php else: ?>
          <div class="text-sm text-gray-500">No postcode data available.</div>
        <?php endif; ?>
      </div>

      <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
        <?php if (empty($reports)): ?>
          <div class="px-4 py-6 text-center text-gray-500">No reports yet.</div>
        <?php else: ?>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($reports as $r): ?>
              <?php
                $status = $r['status'] ?? 'open';
                $created = $r['created_at'] ?? '';
                $createdText = '';
                $createdTitle = '';
                if (is_numeric($created) && (int)$created > 0) {
                  $createdTs = (int)$created;
                  $createdText = format_relative_time($createdTs);
                  $createdTitle = date('Y-m-d H:i', $createdTs);
                } else {
                  $createdText = htmlspecialchars($created);
                }

                $img = $r['image'] ?? '';
                $localPath = __DIR__ . DIRECTORY_SEPARATOR . 'REPORT_IMAGES' . DIRECTORY_SEPARATOR . $img;
                $src = '';
                if ($img && filter_var($img, FILTER_VALIDATE_URL)) {
                  $src = $img;
                } elseif ($img && file_exists($localPath)) {
                  $src = 'REPORT_IMAGES/' . rawurlencode($img);
                }
              ?>
              <div class="border border-gray-300 rounded-xl overflow-hidden bg-white shadow-sm ring-1 ring-gray-200">
                <div class="flex flex-col md:flex-row">
                  <div class="md:w-1/3 bg-gray-50">
                    <?php if ($src): ?>
                      <img src="<?php echo htmlspecialchars($src); ?>" alt="img" class="w-full h-48 md:h-full object-cover cursor-pointer issue-thumb" data-id="<?php echo (int)$r['id']; ?>" data-category="<?php echo htmlspecialchars($r['category'] ?? ''); ?>" data-subcategory="<?php echo htmlspecialchars($r['subcategory'] ?? ''); ?>" onclick="openImageModal(this)" />
                    <?php else: ?>
                      <div class="w-full h-48 md:h-full flex items-center justify-center text-xs text-gray-400">No image</div>
                    <?php endif; ?>
                  </div>
                  <div class="flex-1 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                      <div class="text-xs text-gray-500">Issue #<?php echo (int)$r['id']; ?></div>
                      <?php if ($status === 'fixed'): ?>
                        <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold uppercase tracking-wider rounded-full bg-emerald-500 text-white shadow-sm ring-2 ring-emerald-200">Solved</span>
                      <?php elseif ($status === 'invalid'): ?>
                        <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold uppercase tracking-wider rounded-full bg-red-600 text-white shadow-sm ring-2 ring-red-200">Invalid</span>
                      <?php else: ?>
                        <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold uppercase tracking-wider rounded-full bg-gray-600 text-white shadow-sm ring-2 ring-gray-200">Open</span>
                      <?php endif; ?>
                    </div>

                    <div>
                      <div class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($r['category'] ?? 'Other'); ?></div>
                      <?php if (!empty($r['subcategory'])): ?>
                        <div class="text-sm text-gray-600"><?php echo htmlspecialchars($r['subcategory']); ?></div>
                      <?php endif; ?>
                    </div>

                    <div class="text-sm text-gray-700">
                      <div class="flex items-center gap-2">
                        <span class="text-gray-500">Street:</span>
                        <span><?php echo htmlspecialchars($r['street'] ?? ''); ?></span>
                      </div>
                      <div class="flex items-center gap-2">
                        <span class="text-gray-500">Postcode:</span>
                        <span><?php echo htmlspecialchars($r['post_code'] ?? ''); ?></span>
                      </div>
                      <div class="flex items-center gap-2">
                        <span class="text-gray-500">Created:</span>
                        <span<?php echo $createdTitle ? ' title="' . htmlspecialchars($createdTitle) . '"' : ''; ?>><?php echo $createdText; ?></span>
                      </div>
                    </div>

                    <div class="flex flex-wrap gap-2 pt-2">
                      <form method="post" class="inline">
                        <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>" />
                        <input type="hidden" name="action" value="fixed" />
                        <button type="submit" class="px-3 py-1.5 text-xs rounded border border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100">SOLVED</button>
                      </form>
                      <form method="post" class="inline" onsubmit="return confirm('Mark this issue as invalid?');">
                        <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>" />
                        <input type="hidden" name="action" value="invalid" />
                        <button type="submit" class="px-3 py-1.5 text-xs rounded border border-red-200 bg-red-50 text-red-700 hover:bg-red-100">INVALID ISSUE</button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div>

    <!-- Right Sidebar -->
    <aside class="bg-white p-8 rounded-xl shadow-sm border border-gray-200 space-y-6">
      <div>
        <h2 class="text-lg font-bold text-gray-900 mb-4">Legend</h2>
        <p class="text-sm text-gray-600 mb-4">Map markers by issue category:</p>
      </div>

      <div class="space-y-3">
        <div class="flex items-center gap-3">
          <div class="w-4 h-4 rounded-full" style="background-color: #ef4444; border: 2px solid #991b1b;"></div>
          <span class="text-sm text-gray-700">Roads</span>
        </div>
        <div class="flex items-center gap-3">
          <div class="w-4 h-4 rounded-full" style="background-color: #8b5cf6; border: 2px solid #6d28d9;"></div>
          <span class="text-sm text-gray-700">Waste</span>
        </div>
        <div class="flex items-center gap-3">
          <div class="w-4 h-4 rounded-full" style="background-color: #f59e0b; border: 2px solid #92400e;"></div>
          <span class="text-sm text-gray-700">Lighting</span>
        </div>
        <div class="flex items-center gap-3">
          <div class="w-4 h-4 rounded-full" style="background-color: #10b981; border: 2px solid #065f46;"></div>
          <span class="text-sm text-gray-700">Parks</span>
        </div>
        <div class="flex items-center gap-3">
          <div class="w-4 h-4 rounded-full" style="background-color: #06b6d4; border: 2px solid #164e63;"></div>
          <span class="text-sm text-gray-700">Pavements</span>
        </div>
        <div class="flex items-center gap-3">
          <div class="w-4 h-4 rounded-full" style="background-color: #6b7280; border: 2px solid #1f2937;"></div>
          <span class="text-sm text-gray-700">Other</span>
        </div>
      </div>

      <div class="border-t border-gray-200 pt-4">
        <h3 class="text-sm font-bold text-gray-900 mb-2">Quick Info</h3>
        <ul class="space-y-2 text-xs text-gray-600">
          <li>• Click markers on map for details</li>
          <li>• Click images in table to view full size</li>
        </ul>
      </div>
    </aside>

  </div>

  <!-- Image Modal -->
  <div id="imageModal" class="modal-backdrop">
    <div class="modal-content">
      <button class="modal-close" onclick="closeImageModal()">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
      <img id="modalImage" src="" alt="" class="w-full h-auto" />
      <div id="modalInfo" class="p-4 text-sm text-gray-700"></div>
    </div>
  </div>

  <script>
    function openImageModal(elOrSrc) {
      const modal = document.getElementById('imageModal');
      const img = document.getElementById('modalImage');
      const info = document.getElementById('modalInfo');

      let src = '';
      let id = '';
      let category = '';
      let subcategory = '';

      if (typeof elOrSrc === 'string') {
        src = elOrSrc;
      } else if (elOrSrc && elOrSrc.getAttribute) {
        src = elOrSrc.src || '';
        id = elOrSrc.getAttribute('data-id') || '';
        category = elOrSrc.getAttribute('data-category') || '';
        subcategory = elOrSrc.getAttribute('data-subcategory') || '';
      }

      img.src = src;
      // Build info HTML
      const parts = [];
      if (id) parts.push(`<div><strong>ID:</strong> ${id}</div>`);
      if (category) parts.push(`<div><strong>Category:</strong> ${escapeHtml(category)}</div>`);
      if (subcategory) parts.push(`<div><strong>Subcategory:</strong> ${escapeHtml(subcategory)}</div>`);
      info.innerHTML = parts.join('');

      modal.classList.add('active');
    }

    function closeImageModal() {
      const modal = document.getElementById('imageModal');
      const img = document.getElementById('modalImage');
      const info = document.getElementById('modalInfo');
      modal.classList.remove('active');
      // Clear to avoid stale content
      img.src = '';
      info.innerHTML = '';
    }

    // Small utility to avoid injecting raw HTML from attributes
    function escapeHtml(str) {
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    // Close modal when clicking outside the image
    document.getElementById('imageModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeImageModal();
      }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeImageModal();
      }
    });

    lucide.createIcons();

    // Initialize map centered on UK
    const map = L.map('map').setView([54.5, -3.5], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors',
      maxZoom: 19,
    }).addTo(map);

    // Category color mapping
    const categoryColors = {
      'Roads': { fill: '#ef4444', border: '#991b1b' },
      'Waste': { fill: '#8b5cf6', border: '#6d28d9' },
      'Lighting': { fill: '#f59e0b', border: '#92400e' },
      'Parks': { fill: '#10b981', border: '#065f46' },
      'Pavements': { fill: '#06b6d4', border: '#164e63' },
      'Other': { fill: '#6b7280', border: '#1f2937' }
    };

    // Geocode postcode and add marker
    const reports = <?php echo json_encode($reports); ?>;
    let geocodedCount = 0;

    reports.forEach((r, idx) => {
      const postcode = r.post_code?.trim();
      if (!postcode) return;

      fetch(`https://api.postcodes.io/postcodes/${encodeURIComponent(postcode)}`)
        .then(res => res.json())
        .then(data => {
          if (data.result && data.result.latitude && data.result.longitude) {

            let lat = data.result.latitude;
            let lon = data.result.longitude;
            ////////////////////////////////////////////////
            // Randomize within ~50 meters to spread multiple reports with same postcode
            const randomOffset = (meters) => {
                // 1 degree of latitude ≈ 111,000 meters
                return (Math.random() - 0.5) * 2 * (meters / 111000);
            };

            // Apply randomization
            lat = lat + randomOffset(50); // ~50 meters north/south
            lon = lon + randomOffset(50) / Math.cos(lat * Math.PI / 180); // adjust for longitude
            ////////////////////////////////////////////////
            
            const category = r.category || 'Other';
            const colors = categoryColors[category] || categoryColors['Other'];
            
            const popup = `
              <div class="text-xs font-semibold">${category}</div>
              <div class="text-xs">${r.street || ''}</div>
              <div class="text-xs text-gray-600">${r.post_code}</div>
              ${r.subcategory ? `<div class="text-xs text-gray-600">${r.subcategory}</div>` : ''}
            `;
            L.circleMarker([lat, lon], {
              radius: 8,
              fillColor: colors.fill,
              color: colors.border,
              weight: 2,
              opacity: 0.8,
              fillOpacity: 0.7,
            })
              .bindPopup(popup)
              .addTo(map);
            geocodedCount++;
          }
        })
        .catch(err => console.log('Geocode error for', postcode, err));
    });
  </script>
</body>
</html>
