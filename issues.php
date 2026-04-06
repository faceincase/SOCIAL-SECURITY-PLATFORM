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
$currentAction = $_GET['action'] ?? '';
if (!in_array($currentAction, ['visualisation', 'reports'], true)) {
  $currentAction = '';
}

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

function postcode_to_city($prefix) {
  $mapping = [
    'EH' => 'Edinburgh', 'G' => 'Glasgow', 'AB' => 'Aberdeen', 'DD' => 'Dundee',
    'KY' => 'Kirkcaldy', 'PA' => 'Paisley', 'IV' => 'Inverness', 'PH' => 'Perth',
    'KA' => 'Kilmarnock', 'FK' => 'Falkirk', 'DG' => 'Dumfries', 'TD' => 'Galashiels',
    'ML' => 'Motherwell', 'KW' => 'Kirkwall', 'ZE' => 'Lerwick', 'HS' => 'Stornoway',
    'B' => 'Birmingham', 'M' => 'Manchester', 'L' => 'Liverpool', 'LS' => 'Leeds',
    'S' => 'Sheffield', 'NE' => 'Newcastle', 'SR' => 'Sunderland', 'DH' => 'Durham',
    'TS' => 'Teesside', 'DL' => 'Darlington', 'HG' => 'Harrogate', 'YO' => 'York',
    'HU' => 'Hull', 'DN' => 'Doncaster', 'WF' => 'Wakefield', 'HD' => 'Huddersfield',
    'BD' => 'Bradford', 'HX' => 'Halifax', 'OL' => 'Oldham', 'BL' => 'Bolton',
    'WN' => 'Wigan', 'PR' => 'Preston', 'FY' => 'Blackpool', 'LA' => 'Lancaster',
    'CA' => 'Carlisle', 'DH' => 'Durham', 'DL' => 'Darlington',
    'CH' => 'Chester', 'WA' => 'Warrington', 'CW' => 'Crewe', 'ST' => 'Stoke-on-Trent',
    'WS' => 'Walsall', 'WV' => 'Wolverhampton', 'DY' => 'Dudley', 'TF' => 'Telford',
    'SY' => 'Shrewsbury', 'LD' => 'Llandrindod Wells', 'HR' => 'Hereford', 'WR' => 'Worcester',
    'CV' => 'Coventry', 'NN' => 'Northampton', 'MK' => 'Milton Keynes', 'LU' => 'Luton',
    'SG' => 'Stevenage', 'AL' => 'St Albans', 'HP' => 'Hemel Hempstead', 'SL' => 'Slough',
    'RG' => 'Reading', 'OX' => 'Oxford', 'GL' => 'Gloucester', 'BS' => 'Bristol',
    'BA' => 'Bath', 'TA' => 'Taunton', 'EX' => 'Exeter', 'TQ' => 'Torquay',
    'PL' => 'Plymouth', 'TR' => 'Truro', 'BH' => 'Bournemouth', 'SP' => 'Salisbury',
    'SO' => 'Southampton', 'PO' => 'Portsmouth', 'BN' => 'Brighton', 'TN' => 'Tunbridge Wells',
    'ME' => 'Medway', 'CT' => 'Canterbury', 'DA' => 'Dartford', 'BR' => 'Bromley',
    'CR' => 'Croydon', 'SM' => 'Sutton', 'KT' => 'Kingston upon Thames', 'TW' => 'Twickenham',
    'UB' => 'Uxbridge', 'HA' => 'Harrow', 'WD' => 'Watford', 'EN' => 'Enfield',
    'IG' => 'Ilford', 'RM' => 'Romford', 'SS' => 'Southend-on-Sea', 'CM' => 'Chelmsford',
    'CO' => 'Colchester', 'IP' => 'Ipswich', 'NR' => 'Norwich', 'PE' => 'Peterborough',
    'CB' => 'Cambridge', 'SG' => 'Stevenage', 'LE' => 'Leicester', 'DE' => 'Derby',
    'NG' => 'Nottingham', 'LN' => 'Lincoln',
    'E' => 'East London', 'EC' => 'City of London', 'N' => 'North London',
    'NW' => 'North West London', 'SE' => 'South East London', 'SW' => 'South West London',
    'W' => 'West London', 'WC' => 'West Central London'
  ];

  return $mapping[strtoupper($prefix)] ?? $prefix;
}

function city_from_postcode($postcode) {
  $pc = strtoupper(trim((string)$postcode));
  if ($pc === '') {
    return '';
  }

  if (preg_match('/^([A-Z]+)/', $pc, $matches)) {
    return postcode_to_city($matches[1]);
  }

  return $pc;
}

function make_export_filename($label) {
  $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', strtolower((string)$label));
  $safe = trim((string)$safe, '-');
  if ($safe === '') {
    $safe = 'reports';
  }

  return $safe . '-' . date('Ymd-His') . '.json';
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
    
    foreach ($reports as &$report) {
      $report['city'] = city_from_postcode($report['post_code'] ?? '');
    }
    unset($report);

    // Compute city counts using the leading letters of the postcode
    $city_counts = [];
    foreach ($reports as $rep) {
      $city = $rep['city'] ?? '';
      if ($city === '') continue;
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

    $exportType = $_GET['export'] ?? '';
    if ($exportType === 'all' || $exportType === 'city') {
      $selectedCity = trim((string)($_GET['city'] ?? ''));
      $filteredReports = $reports;
      $label = 'all-reports';

      if ($exportType === 'city') {
        $filteredReports = array_values(array_filter($reports, static function ($report) use ($selectedCity) {
          return isset($report['city']) && $report['city'] === $selectedCity;
        }));
        $label = $selectedCity !== '' ? $selectedCity : 'city-reports';
      }

      $payload = [
        'generated_at' => date(DATE_ATOM),
        'export_type' => $exportType,
        'city' => $exportType === 'city' ? $selectedCity : null,
        'total_reports' => count($filteredReports),
        'reports' => array_map(static function ($report) {
          return [
            'id' => (int)($report['id'] ?? 0),
            'city' => $report['city'] ?? '',
            'post_code' => $report['post_code'] ?? '',
            'street' => $report['street'] ?? '',
            'category' => $report['category'] ?? '',
            'subcategory' => $report['subcategory'] ?? '',
            'status' => $report['status'] ?? 'open',
            'created_at' => $report['created_at'] ?? '',
            'image' => $report['image'] ?? ''
          ];
        }, $filteredReports)
      ];

      header('Content-Type: application/json; charset=utf-8');
      header('Content-Disposition: attachment; filename="' . make_export_filename($label) . '"');
      echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
      exit;
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
  <script src="https://d3js.org/d3.v7.min.js"></script>
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
    
    /* Network graph styles */
    #networkGraph { background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%); position: relative; }
    .node { cursor: pointer; transition: opacity 0.2s; }
    .node:hover { opacity: 1 !important; }
    .node text { font-size: 11px; font-weight: 600; pointer-events: none; }
    .link { stroke: #cbd5e1; stroke-opacity: 0.5; fill: none; transition: stroke-opacity 0.2s; }
    .link:hover { stroke-opacity: 0.9; }
    .node-root { fill: #7c3aed; stroke: #5b21b6; stroke-width: 4px; }
    .node-city { fill: #3b82f6; stroke: #1e40af; stroke-width: 3px; }
    .node-category { stroke-width: 2px; }
    .node-subcategory { fill: #e5e7eb; stroke: #9ca3af; stroke-width: 1.5px; opacity: 0.9; }
    .legend-item { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 12px; }
    .legend-circle { border-radius: 50%; }
    .collapsible-header { cursor: pointer; user-select: none; transition: all 0.2s ease; }
    .collapsible-header:hover { opacity: 0.8; }
    .collapsible-content { max-height: 2000px; overflow: hidden; transition: max-height 0.3s ease, opacity 0.3s ease; opacity: 1; }
    .collapsible-content.collapsed { max-height: 0; opacity: 0; transition: max-height 0.3s ease, opacity 0.3s ease; }
    .chevron-icon { transition: transform 0.3s ease; }
    .chevron-icon.collapsed { transform: rotate(-90deg); }
  </style>
</head>
<body class="bg-gradient-to-br from-gray-200 to-gray-50 min-h-screen p-6 px-80">

  <?php include __DIR__ . '/ASSETS/header.php'; ?>

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

    <div class="lg:col-span-3 space-y-6">

      <div class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300">
        <h1 class="text-3xl font-bold mb-3 text-gray-900">Admin Dashboard</h1>
        <p class="text-gray-600 leading-relaxed">Browse all submitted issues. Click the image to open full size if available.</p>

        <div class="mt-6 pt-5 border-t border-gray-200">
          <div class="flex flex-wrap items-center justify-center gap-3">
            <a href="?action=visualisation" class="group inline-flex items-center gap-2 px-5 py-2.5 text-sm rounded-xl border-2 border-sky-200 bg-gradient-to-br from-sky-50 to-blue-50 text-sky-700 hover:border-sky-300 hover:shadow-md transition-all duration-200 font-semibold">
              <i data-lucide="chart-scatter" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
              VIEW VISUALITATION
            </a>
            <a href="?action=reports" class="group inline-flex items-center gap-2 px-5 py-2.5 text-sm rounded-xl border-2 border-gray-200 bg-gradient-to-br from-gray-50 to-white text-gray-700 hover:border-gray-300 hover:shadow-md transition-all duration-200 font-semibold">
              <i data-lucide="list" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
              VIEW ALL REPORTS
            </a>
          </div>
        </div>
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

              

      <?php if ($currentAction === 'visualisation'): ?>

      <div class="bg-white rounded-xl shadow-lg border-2 border-gray-300">
        <div class="collapsible-header p-6 flex items-center justify-between" onclick="toggleCollapsible(this)">
          <div>
            <h2 class="text-xl font-bold text-gray-900">Issue Locations</h2>
            <p class="text-sm text-gray-600 mt-1">Issue distribution by location</p>
          </div>
          <i data-lucide="chevron-down" class="w-5 h-5 text-gray-600 chevron-icon"></i>
        </div>
        <div class="collapsible-content collapsed">
          <div class="px-6 pb-6 border-t border-gray-200">
            <div id="map" style="height: 500px; border-radius: 0.75rem; z-index: 1 !important;"></div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-lg border-2 border-gray-300">
        <div class="collapsible-header p-6 flex items-center justify-between border-b border-gray-200" onclick="toggleCollapsible(this)">
          <div>
            <h2 class="text-xl font-bold text-gray-900">Statistics</h2>
            <p class="text-sm text-gray-600 mt-1">Issue distribution by city</p>
          </div>
          <i data-lucide="chevron-down" class="w-5 h-5 text-gray-600 chevron-icon flex-shrink-0 ml-4"></i>
        </div>
        <div class="collapsible-content collapsed">
          <div class="p-6">
            <div class="flex gap-2 mb-6">
              <a href="?action=visualisation&amp;export=all" class="px-4 py-2 text-sm rounded-lg border border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100 transition font-medium flex items-center gap-2">
                <i data-lucide="download" class="w-4 h-4"></i>
                Export All
              </a>
            </div>

            <?php if (!empty($city_counts)): ?>
              <?php $max = reset($city_counts); ?>
              <div class="space-y-3">
            <?php foreach ($city_counts as $city => $cnt):
              $pct = $max ? round(($cnt / $max) * 100) : 0;
            ?>
              <div class="group p-4 rounded-lg bg-gradient-to-r from-gray-50 to-white border border-gray-200 hover:border-blue-200 hover:shadow-md transition">
                <div class="flex items-center justify-between mb-3">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 flex items-center justify-center">
                      <i data-lucide="map-pin" class="w-5 h-5 text-blue-600"></i>
                    </div>
                    <div>
                      <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($city); ?></div>
                      <div class="text-xs text-gray-500"><?php echo (int)$cnt; ?> issue<?php echo (int)$cnt !== 1 ? 's' : ''; ?></div>
                    </div>
                  </div>
                  <a href="?action=visualisation&amp;export=city&amp;city=<?php echo rawurlencode($city); ?>" class="px-3 py-1.5 text-xs rounded-lg border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 transition whitespace-nowrap font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                    <i data-lucide="download" class="w-3 h-3 inline mr-1"></i>Export
                  </a>
                </div>
                <div class="relative">
                  <div class="flex items-center gap-3">
                    <div class="flex-1 bg-gray-200 rounded-full overflow-hidden h-2.5">
                      <div style="width: <?php echo $pct; ?>%; background: linear-gradient(90deg, #3b82f6, #06b6d4); height:100%; transition: width 0.3s ease;"></div>
                    </div>
                    <div class="text-sm font-semibold text-gray-700 w-12 text-right"><?php echo $pct; ?>%</div>
                  </div>
                </div>
              </div>
                <?php endforeach; ?>
              </div>

              <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="grid grid-cols-3 gap-4">
                  <div class="p-4 rounded-lg bg-blue-50 border border-blue-200">
                    <div class="text-xs text-blue-600 font-semibold uppercase tracking-wide mb-1">Total Reports</div>
                    <div class="text-2xl font-bold text-blue-900"><?php echo array_sum($city_counts); ?></div>
                  </div>
                  <div class="p-4 rounded-lg bg-green-50 border border-green-200">
                    <div class="text-xs text-green-600 font-semibold uppercase tracking-wide mb-1">Cities Covered</div>
                    <div class="text-2xl font-bold text-green-900"><?php echo count($city_counts); ?></div>
                  </div>
                  <div class="p-4 rounded-lg bg-purple-50 border border-purple-200">
                    <div class="text-xs text-purple-600 font-semibold uppercase tracking-wide mb-1">Top City</div>
                    <div class="text-2xl font-bold text-purple-900"><?php echo htmlspecialchars($top_city ?? 'N/A'); ?></div>
                  </div>
                </div>
              </div>
            <?php else: ?>
              <div class="p-8 text-center">
                <i data-lucide="inbox" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                <div class="text-sm text-gray-500">No postcode data available.</div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-lg border-2 border-gray-300">
        <div class="collapsible-header p-6 flex items-center justify-between border-b border-gray-200" onclick="toggleCollapsible(this)">
          <div>
            <h2 class="text-xl font-bold text-gray-900">Issue Network Visualization</h2>
            <p class="text-sm text-gray-600 mt-1">Explore relationships between cities, categories, and subcategories</p>
          </div>
          <i data-lucide="chevron-down" class="w-5 h-5 text-gray-600 chevron-icon flex-shrink-0 ml-4"></i>
        </div>
        <div class="collapsible-content collapsed">
          <div class="p-6 border-t border-gray-200">
            <div id="networkGraph" style="width: 100%; height: 600px; border: 1px solid #e5e7eb; border-radius: 0.5rem; overflow: hidden;"></div>
          </div>
        </div>
      </div>

      <?php endif; ?>

      <?php if ($currentAction === 'reports'): ?>

      <div class="bg-white p-6 rounded-xl shadow-lg border-2 border-gray-300">
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

      <?php endif; ?>

    </div>

    <!-- Right Sidebar -->
    <aside class="space-y-6">
      <div class="bg-gradient-to-br from-sky-50 to-blue-50 p-6 rounded-xl shadow-lg border-2 border-sky-200">
        <h2 class="text-lg font-bold text-gray-900 mb-1">Dashboard Guide</h2>
        <p class="text-sm text-gray-600">Use the controls to explore reports quickly.</p>
      </div>

      <div class="bg-white p-6 rounded-xl shadow-lg border-2 border-gray-300">
        <h3 class="text-sm font-bold text-gray-900 mb-3">Category Legend</h3>
        <div class="space-y-2">
          <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-red-50 border border-red-200 mr-2 mb-2">
            <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span><span class="text-xs font-medium text-red-700">Roads</span>
          </div>
          <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-violet-50 border border-violet-200 mr-2 mb-2">
            <span class="w-2.5 h-2.5 rounded-full bg-violet-500"></span><span class="text-xs font-medium text-violet-700">Waste</span>
          </div>
          <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200 mr-2 mb-2">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span><span class="text-xs font-medium text-amber-700">Lighting</span>
          </div>
          <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-200 mr-2 mb-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span><span class="text-xs font-medium text-emerald-700">Parks</span>
          </div>
          <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-cyan-50 border border-cyan-200 mr-2 mb-2">
            <span class="w-2.5 h-2.5 rounded-full bg-cyan-500"></span><span class="text-xs font-medium text-cyan-700">Pavements</span>
          </div>
          <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-50 border border-gray-200 mr-2 mb-2">
            <span class="w-2.5 h-2.5 rounded-full bg-gray-500"></span><span class="text-xs font-medium text-gray-700">Other</span>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-xl shadow-lg border-2 border-gray-300">
        <h3 class="text-sm font-bold text-gray-900 mb-3">Quick Tips</h3>
        <div class="space-y-3 text-sm text-gray-600">
          <div class="flex gap-2">
            <i data-lucide="map" class="w-4 h-4 text-sky-500 mt-0.5"></i>
            <p>Open <span class="font-medium text-gray-700">Issue Locations</span> to inspect marker clusters.</p>
          </div>
          <div class="flex gap-2">
            <i data-lucide="bar-chart-3" class="w-4 h-4 text-violet-500 mt-0.5"></i>
            <p>Use <span class="font-medium text-gray-700">Statistics</span> to compare city distribution.</p>
          </div>
          <div class="flex gap-2">
            <i data-lucide="network" class="w-4 h-4 text-emerald-500 mt-0.5"></i>
            <p>Expand <span class="font-medium text-gray-700">Network Visualization</span> for relationship mapping.</p>
          </div>
        </div>
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
    function toggleCollapsible(headerElement) {
      const content = headerElement.nextElementSibling;
      const chevron = headerElement.querySelector('.chevron-icon');
      
      content.classList.toggle('collapsed');
      chevron.classList.toggle('collapsed');
    }

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

    // Initialize map centered on UK with bounds restriction
    const ukBounds = L.latLngBounds(
      L.latLng(49.5, -11.0),  // Southwest corner
      L.latLng(61.0, 2.5)     // Northeast corner
    );
    
    const map = L.map('map', {
      maxBounds: ukBounds,
      maxBoundsViscosity: 1.0,
      minZoom: 5,
      maxZoom: 18
    }).setView([55.95, -3.19], 11);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors',
      bounds: ukBounds,
      maxZoom: 18,
      minZoom: 5
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

    // ==================== NETWORK GRAPH ====================
    // UK Postcode prefix to city name mapping
    function postcodeToCity(prefix) {
      const mapping = {
        'EH': 'Edinburgh', 'G': 'Glasgow', 'AB': 'Aberdeen', 'DD': 'Dundee',
        'KY': 'Kirkcaldy', 'PA': 'Paisley', 'IV': 'Inverness', 'PH': 'Perth',
        'KA': 'Kilmarnock', 'FK': 'Falkirk', 'DG': 'Dumfries', 'TD': 'Galashiels',
        'ML': 'Motherwell', 'KW': 'Kirkwall', 'ZE': 'Lerwick', 'HS': 'Stornoway',
        'B': 'Birmingham', 'M': 'Manchester', 'L': 'Liverpool', 'LS': 'Leeds',
        'S': 'Sheffield', 'NE': 'Newcastle', 'SR': 'Sunderland', 'DH': 'Durham',
        'TS': 'Teesside', 'DL': 'Darlington', 'HG': 'Harrogate', 'YO': 'York',
        'HU': 'Hull', 'DN': 'Doncaster', 'WF': 'Wakefield', 'HD': 'Huddersfield',
        'BD': 'Bradford', 'HX': 'Halifax', 'OL': 'Oldham', 'BL': 'Bolton',
        'WN': 'Wigan', 'PR': 'Preston', 'FY': 'Blackpool', 'LA': 'Lancaster',
        'CA': 'Carlisle', 'CH': 'Chester', 'WA': 'Warrington', 'CW': 'Crewe',
        'ST': 'Stoke-on-Trent', 'WS': 'Walsall', 'WV': 'Wolverhampton', 'DY': 'Dudley',
        'TF': 'Telford', 'SY': 'Shrewsbury', 'LD': 'Llandrindod Wells', 'HR': 'Hereford',
        'WR': 'Worcester', 'CV': 'Coventry', 'NN': 'Northampton', 'MK': 'Milton Keynes',
        'LU': 'Luton', 'SG': 'Stevenage', 'AL': 'St Albans', 'HP': 'Hemel Hempstead',
        'SL': 'Slough', 'RG': 'Reading', 'OX': 'Oxford', 'GL': 'Gloucester',
        'BS': 'Bristol', 'BA': 'Bath', 'TA': 'Taunton', 'EX': 'Exeter',
        'TQ': 'Torquay', 'PL': 'Plymouth', 'TR': 'Truro', 'BH': 'Bournemouth',
        'SP': 'Salisbury', 'SO': 'Southampton', 'PO': 'Portsmouth', 'BN': 'Brighton',
        'TN': 'Tunbridge Wells', 'ME': 'Medway', 'CT': 'Canterbury', 'DA': 'Dartford',
        'BR': 'Bromley', 'CR': 'Croydon', 'SM': 'Sutton', 'KT': 'Kingston upon Thames',
        'TW': 'Twickenham', 'UB': 'Uxbridge', 'HA': 'Harrow', 'WD': 'Watford',
        'EN': 'Enfield', 'IG': 'Ilford', 'RM': 'Romford', 'SS': 'Southend-on-Sea',
        'CM': 'Chelmsford', 'CO': 'Colchester', 'IP': 'Ipswich', 'NR': 'Norwich',
        'PE': 'Peterborough', 'CB': 'Cambridge', 'LE': 'Leicester', 'DE': 'Derby',
        'NG': 'Nottingham', 'LN': 'Lincoln',
        'E': 'East London', 'EC': 'City of London', 'N': 'North London',
        'NW': 'North West London', 'SE': 'South East London', 'SW': 'South West London',
        'W': 'West London', 'WC': 'West Central London'
      };
      return mapping[prefix.toUpperCase()] || prefix;
    }
    
    // Process data for network visualization
    const networkData = processNetworkData(reports);
    createNetworkGraph(networkData);

    function processNetworkData(reports) {
      const nodes = [];
      const links = [];
      const nodeMap = new Map();
      
      // Count occurrences for sizing
      const cityCount = {};
      const categoryCount = {};
      const subcategoryCount = {};
      
      reports.forEach(r => {
        const pc = (r.post_code || '').trim().toUpperCase();
        if (!pc) return;
        
        const cityMatch = pc.match(/^([A-Z]+)/);
        const cityPrefix = cityMatch ? cityMatch[1] : pc;
        const city = postcodeToCity(cityPrefix);
        const category = r.category || 'Other';
        const subcategory = r.subcategory || '';
        
        cityCount[city] = (cityCount[city] || 0) + 1;
        
        const catKey = `${city}::${category}`;
        categoryCount[catKey] = (categoryCount[catKey] || 0) + 1;
        
        if (subcategory) {
          const subKey = `${city}::${category}::${subcategory}`;
          subcategoryCount[subKey] = (subcategoryCount[subKey] || 0) + 1;
        }
      });
      
      // Create nodes
      let nodeId = 0;
      
      // Add central root node
      const totalReports = Object.values(cityCount).reduce((a, b) => a + b, 0);
      nodeMap.set('root', nodeId);
      nodes.push({
        id: nodeId++,
        label: 'All Reports',
        type: 'root',
        count: totalReports,
        fullId: 'root'
      });
      
      // City nodes
      Object.keys(cityCount).forEach(city => {
        const id = `city-${city}`;
        nodeMap.set(id, nodeId);
        nodes.push({
          id: nodeId++,
          label: city,
          type: 'city',
          count: cityCount[city],
          fullId: id
        });
        
        // Connect city to root node
        const rootId = nodeMap.get('root');
        links.push({
          source: rootId,
          target: nodeId - 1,
          value: cityCount[city]
        });
      });
      
      // Category nodes
      Object.keys(categoryCount).forEach(catKey => {
        const [city, category] = catKey.split('::');
        const id = `cat-${city}-${category}`;
        nodeMap.set(id, nodeId);
        nodes.push({
          id: nodeId++,
          label: category,
          type: 'category',
          count: categoryCount[catKey],
          fullId: id,
          city: city
        });
        
        // Link city to category
        const cityId = nodeMap.get(`city-${city}`);
        if (cityId !== undefined) {
          links.push({
            source: cityId,
            target: nodeId - 1,
            value: categoryCount[catKey]
          });
        }
      });
      
      // Subcategory nodes
      Object.keys(subcategoryCount).forEach(subKey => {
        const [city, category, subcategory] = subKey.split('::');
        const id = `sub-${city}-${category}-${subcategory}`;
        nodeMap.set(id, nodeId);
        nodes.push({
          id: nodeId++,
          label: subcategory,
          type: 'subcategory',
          count: subcategoryCount[subKey],
          fullId: id,
          city: city,
          category: category
        });
        
        // Link category to subcategory
        const catId = nodeMap.get(`cat-${city}-${category}`);
        if (catId !== undefined) {
          links.push({
            source: catId,
            target: nodeId - 1,
            value: subcategoryCount[subKey]
          });
        }
      });
      
      return { nodes, links };
    }

    function createNetworkGraph(data) {
      const container = document.getElementById('networkGraph');
      const width = container.clientWidth;
      const height = 600;
      
      // Clear any existing content
      container.innerHTML = '';
      
      // Create SVG
      const svg = d3.select('#networkGraph')
        .append('svg')
        .attr('width', width)
        .attr('height', height)
        .attr('viewBox', [0, 0, width, height]);
      
      // Add zoom behavior
      const g = svg.append('g');
      
      svg.call(d3.zoom()
        .extent([[0, 0], [width, height]])
        .scaleExtent([0.3, 4])
        .on('zoom', (event) => {
          g.attr('transform', event.transform);
        }));
      
      // Category colors (matching map legend)
      const categoryColorMap = {
        'Roads': '#ef4444',
        'Waste': '#8b5cf6',
        'Lighting': '#f59e0b',
        'Parks': '#10b981',
        'Pavements': '#06b6d4',
        'Other': '#6b7280'
      };
      
      // Create simulation
      const simulation = d3.forceSimulation(data.nodes)
        .force('link', d3.forceLink(data.links)
          .id(d => d.id)
          .distance(d => {
            // Vary distance based on node types
            const source = data.nodes.find(n => n.id === d.source.id || n.id === d.source);
            if (source && source.type === 'root') return 150;
            if (source && source.type === 'city') return 120;
            return 80;
          })
          .strength(d => {
            const source = data.nodes.find(n => n.id === d.source.id || n.id === d.source);
            if (source && source.type === 'root') return 0.8;
            return 1;
          })
        )
        .force('charge', d3.forceManyBody().strength(-300))
        .force('center', d3.forceCenter(width / 2, height / 2))
        .force('collision', d3.forceCollide().radius(d => {
          if (d.type === 'root') return 25;
          if (d.type === 'city') return Math.sqrt(d.count) * 8 + 15;
          if (d.type === 'category') return Math.sqrt(d.count) * 5 + 10;
          return Math.sqrt(d.count) * 3 + 8;
        }));
      
      // Create links
      const link = g.append('g')
        .selectAll('line')
        .data(data.links)
        .join('line')
        .attr('class', 'link')
        .attr('stroke-width', d => Math.sqrt(d.value) * 0.8);
      
      // Create nodes
      const node = g.append('g')
        .selectAll('g')
        .data(data.nodes)
        .join('g')
        .attr('class', 'node')
        .call(drag(simulation));
      
      // Add circles to nodes
      node.append('circle')
        .attr('r', d => {
          if (d.type === 'root') return 20;
          if (d.type === 'city') return Math.sqrt(d.count) * 8 + 10;
          if (d.type === 'category') return Math.sqrt(d.count) * 5 + 8;
          return Math.sqrt(d.count) * 3 + 6;
        })
        .attr('class', d => `node-${d.type}`)
        .attr('fill', d => {
          if (d.type === 'root') return '#7c3aed';
          if (d.type === 'city') return '#3b82f6';
          if (d.type === 'category') return categoryColorMap[d.label] || '#6b7280';
          return '#e5e7eb';
        })
        .attr('stroke', d => {
          if (d.type === 'root') return '#5b21b6';
          if (d.type === 'city') return '#1e40af';
          if (d.type === 'category') {
            const baseColor = categoryColorMap[d.label] || '#6b7280';
            return d3.rgb(baseColor).darker(1);
          }
          return '#9ca3af';
        });
      
      // Add labels
      node.append('text')
        .text(d => {
          // Truncate long labels
          if (d.label.length > 12) return d.label.substring(0, 10) + '...';
          return d.label;
        })
        .attr('text-anchor', 'middle')
        .attr('dy', d => {
          if (d.type === 'root') return 32;
          if (d.type === 'city') return Math.sqrt(d.count) * 8 + 22;
          if (d.type === 'category') return Math.sqrt(d.count) * 5 + 18;
          return Math.sqrt(d.count) * 3 + 14;
        })
        .attr('fill', '#1f2937');
      
      // Add count badges
      node.append('text')
        .text(d => d.count)
        .attr('text-anchor', 'middle')
        .attr('dy', 4)
        .attr('fill', d => {
          if (d.type === 'root') return 'white';
          if (d.type === 'city') return 'white';
          if (d.type === 'category') return 'white';
          return '#4b5563';
        })
        .style('font-size', d => {
          if (d.type === 'root') return '13px';
          if (d.type === 'city') return '12px';
          if (d.type === 'category') return '10px';
          return '8px';
        })
        .style('font-weight', 'bold');
      
      // Add tooltips
      node.append('title')
        .text(d => {
          let text = `${d.label}\n${d.count} report${d.count !== 1 ? 's' : ''}`;
          if (d.city && d.type !== 'city') text += `\nCity: ${d.city}`;
          if (d.category && d.type === 'subcategory') text += `\nCategory: ${d.category}`;
          return text;
        });
      
      // Update positions on tick
      simulation.on('tick', () => {
        link
          .attr('x1', d => d.source.x)
          .attr('y1', d => d.source.y)
          .attr('x2', d => d.target.x)
          .attr('y2', d => d.target.y);
        
        node.attr('transform', d => `translate(${d.x},${d.y})`);
      });
      
      // Drag functions
      function drag(simulation) {
        function dragstarted(event) {
          if (!event.active) simulation.alphaTarget(0.3).restart();
          event.subject.fx = event.subject.x;
          event.subject.fy = event.subject.y;
        }
        
        function dragged(event) {
          event.subject.fx = event.x;
          event.subject.fy = event.y;
        }
        
        function dragended(event) {
          if (!event.active) simulation.alphaTarget(0);
          event.subject.fx = null;
          event.subject.fy = null;
        }
        
        return d3.drag()
          .on('start', dragstarted)
          .on('drag', dragged)
          .on('end', dragended);
      }
      
      // Add legend
      const legend = svg.append('g')
        .attr('class', 'legend')
        .attr('transform', `translate(${width - 150}, 20)`);
      
      const legendData = [
        { label: 'All Reports', color: '#7c3aed', size: 14 },
        { label: 'City', color: '#3b82f6', size: 12 },
        { label: 'Category', color: '#6b7280', size: 10 },
        { label: 'Subcategory', color: '#e5e7eb', size: 8 }
      ];
      
      legendData.forEach((item, i) => {
        const g = legend.append('g')
          .attr('transform', `translate(0, ${i * 25})`);
        
        g.append('circle')
          .attr('cx', 0)
          .attr('cy', 0)
          .attr('r', item.size)
          .attr('fill', item.color)
          .attr('stroke', item.color === '#e5e7eb' ? '#9ca3af' : d3.rgb(item.color).darker(1))
          .attr('stroke-width', 2);
        
        g.append('text')
          .attr('x', 20)
          .attr('y', 4)
          .text(item.label)
          .style('font-size', '11px')
          .style('font-weight', '600')
          .attr('fill', '#374151');
      });
      
      // Add instructions
      const instructions = svg.append('text')
        .attr('x', 10)
        .attr('y', height - 10)
        .style('font-size', '10px')
        .style('fill', '#6b7280')
        .text('💡 Drag nodes to reorganize • Scroll to zoom • Node size = report count');
    }
  </script>

  <?php include __DIR__ . '/ASSETS/footer.php'; ?>

</body>
</html>
