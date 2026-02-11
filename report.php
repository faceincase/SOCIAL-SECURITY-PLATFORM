<?php
// report.php - submit a report and save to SQLite DB with categories/subcategories stored in DB

$errors = [];
$success = false;
$insertId = null;

// DB setup
$dbDir = __DIR__ . DIRECTORY_SEPARATOR . 'DATABASE';
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}
$dbFile = $dbDir . DIRECTORY_SEPARATOR . 'reports.sqlite';
$dsn = 'sqlite:' . $dbFile;
try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS subcategories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        FOREIGN KEY(category_id) REFERENCES categories(id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      post_code TEXT NOT NULL,
      street TEXT NOT NULL,
      category TEXT,
      subcategory TEXT,
      image TEXT,
      created_at INT
    )");

    // Populate default categories/subcategories if empty
    $count = (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    if ($count === 0) {

        $defaults = [
            'Roads' => [
                'Pothole',
                'Road damage',
                'Signage issue',
                'Traffic light problem'
            ],

            'Waste' => [
                'Missed bin',
                'Fly-tipping',
                'Overflowing bin',
                'Damaged bin'
            ],

            'Lighting' => [
                'Broken lamp',
                'Flickering',
                'No light'
            ],

            'Parks' => [
                'Broken equipment',
                'Graffiti',
                'Littering',
                'Overgrown areas'
            ],

            'Pavements' => [
                'Trip hazard',
                'Uneven surface'
            ],

            'Other' => [
                'General enquiry',
                'Something else'
            ]
        ];

        $catStmt = $pdo->prepare('INSERT INTO categories (name) VALUES (:name)');
        $subStmt = $pdo->prepare('INSERT INTO subcategories (category_id, name) VALUES (:cid, :name)');
        foreach ($defaults as $cat => $subs) {
            $catStmt->execute([':name' => $cat]);
            $catId = $pdo->lastInsertId();
            foreach ($subs as $s) {
                $subStmt->execute([':cid' => $catId, ':name' => $s]);
            }
        }
    }

    // Load categories and subcategories
    $categories = [];
    $stmt = $pdo->query('SELECT id, name FROM categories ORDER BY id');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $categories[$row['id']] = $row['name'];
    }

    $subcategories = [];
    $stmt = $pdo->query('SELECT id, category_id, name FROM subcategories ORDER BY name');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $subcategories[$row['category_id']][] = ['id' => $row['id'], 'name' => $row['name']];
    }

} catch (PDOException $e) {
    $errors[] = 'Database error: ' . $e->getMessage();
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_code = trim($_POST['post_code'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $subcategory_id = isset($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : 0;

    if ($post_code === '') {
        $errors[] = 'Postcode is required.';
    }
    if ($street === '') {
        $errors[] = 'Street is required.';
    }
    if ($category_id <= 0) {
        $errors[] = 'Please select a category.';
    }

    // IMAGE
    $imagePath = null;

    if (isset($_FILES['report_image']) && $_FILES['report_image']['error'] === UPLOAD_ERR_OK)
    {
        // Check dir
        $uploadDir = __DIR__ . '/REPORT_IMAGES/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Size check (max 9MB)
        $maxSize = 9 * 1024 * 1024; // 9MB
        if ($_FILES['report_image']['size'] > $maxSize) {
            $errors[] = 'Image size must not exceed 9MB.';
        }
        
        // Validate type
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $fileName = $_FILES['report_image']['name'];
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'Invalid image type. Only JPG and PNG allowed.';
        }
        
        // Generate safe filename
        $fileName = uniqid('issue_', true) . '.png';

        $destination = $uploadDir . $fileName;

        if (!move_uploaded_file($_FILES['report_image']['tmp_name'], $destination)) {
            die('Failed to save uploaded image.');
        }

        // This is what you store in DB
        $imagePath = $fileName;

    }
    // IMAGE

    if (empty($errors)) {
        try {
            // Resolve names
            $catName = null;
            $subName = null;
            if ($category_id > 0) {
                $stmt = $pdo->prepare('SELECT name FROM categories WHERE id = :id');
                $stmt->execute([':id' => $category_id]);
                $catName = $stmt->fetchColumn();
            }
            if ($subcategory_id > 0) {
                $stmt = $pdo->prepare('SELECT name FROM subcategories WHERE id = :id');
                $stmt->execute([':id' => $subcategory_id]);
                $subName = $stmt->fetchColumn();
            }



            $ins = $pdo->prepare('INSERT INTO reports (post_code, street, category, subcategory, image, created_at) VALUES (:post_code, :street, :category, :subcategory, :image, :created_at)');
            $ins->execute([
              ':post_code' => $post_code,
              ':street' => $street,
              ':category' => $catName,
              ':subcategory' => $subName,
              ':image' => $imagePath,
              ':created_at' => time(),
            ]);
            $insertId = $pdo->lastInsertId();
            $success = true;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Report Issue</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    // Prepare subcategories map for client-side dynamic select
    const SUBCATS = <?php echo json_encode($subcategories); ?>;
  </script>
  <script>
    // Simple enable/disable submit until required fields filled
    document.addEventListener('DOMContentLoaded', function(){
      const submitBtn = document.getElementById('submit-btn');
      const postInput = document.querySelector('input[name="post_code"]');
      const catInput = document.getElementById('category_id_input');
      let postCodeValid = false;
      let validationTimeout;

      function meetsCriteria(){
        if(!postInput || !catInput) return false;
        const postOk = postCodeValid;
        const catOk = catInput.value.trim() !== '';
        return postOk && catOk;
      }

      function updateSubmit(){
        const ok = meetsCriteria();
        if(submitBtn) {
          submitBtn.disabled = !ok;
          if(ok){
            submitBtn.classList.remove('opacity-50','cursor-not-allowed');
          } else {
            submitBtn.classList.add('opacity-50','cursor-not-allowed');
          }
        }
      }

      function validatePostcode(){
        const postcode = postInput.value.trim();
        const errorEl = document.getElementById('postcode-error');
        
        if(!postcode){
          postCodeValid = false;
          if(errorEl) errorEl.classList.add('hidden');
          updateSubmit();
          return;
        }

        // Show loading state
        if(errorEl) errorEl.classList.add('hidden');

        fetch(`https://api.postcodes.io/postcodes/${encodeURIComponent(postcode)}`)
          .then(res => res.json())
          .then(data => {
            if(data.result){
              postCodeValid = true;
              if(errorEl) errorEl.classList.add('hidden');
            } else {
              postCodeValid = false;
              if(errorEl){
                errorEl.textContent = 'Invalid UK postcode.';
                errorEl.classList.remove('hidden');
              }
            }
            updateSubmit();
          })
          .catch(err => {
            postCodeValid = false;
            if(errorEl){
              errorEl.textContent = 'Unable to validate postcode.';
              errorEl.classList.remove('hidden');
            }
            updateSubmit();
          });
      }

      if(postInput){
        postInput.addEventListener('input', function(){
          clearTimeout(validationTimeout);
          validationTimeout = setTimeout(validatePostcode, 500);
        });
      }

      // category buttons set hidden input in existing handlers; watch for changes
      const catBtns = document.querySelectorAll('.category-btn');
      catBtns.forEach(b => b.addEventListener('click', function(){ setTimeout(updateSubmit, 10); }));
      document.addEventListener('click', function(e){ if(e.target && e.target.matches('.sub-btn')) setTimeout(updateSubmit, 10); });

      // initialize state
      updateSubmit();
    });
  </script>
</head>
<body class="bg-gradient-to-br from-gray-200 to-gray-50 min-h-screen p-6 px-80">

  <?php include __DIR__ . '/ASSETS/header.php'; ?>

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

    <!-- Left Main Section -->
    <div class="lg:col-span-3 space-y-6">

      <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200">
        <h1 class="text-3xl font-bold mb-3 text-gray-900">Submit an Issue</h1>
        <p class="text-gray-600 leading-relaxed">Please complete the form below to report an issue. Include as much detail as possible.</p>
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

      <?php if ($success): ?>
        <div class="mb-4">
          <div class="text-green-700 bg-green-50 border border-green-100 p-3 rounded">
            Report submitted successfully (ID: <?php echo htmlspecialchars($insertId); ?>).
          </div>
        </div>
      <?php endif; ?>

      <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200">
        <form method="POST" enctype="multipart/form-data" novalidate id="report-form">
          <div class="grid grid-cols-1 gap-4">
            <div>
              <label class="text-sm font-medium text-gray-700">Postcode (Step 1)</label>
              <input type="text" name="post_code" value="<?php echo isset($post_code) ? htmlspecialchars($post_code) : ''; ?>" required class="mt-1 block w-full rounded-lg border border-gray-300 bg-white shadow-sm focus:ring-2 focus:ring-blue-300 focus:border-blue-300 p-3" />
              <div id="postcode-error" class="hidden text-xs text-red-600 mt-1"></div>
            </div>

            <div>
              <label class="text-sm font-medium text-gray-700">Street (Step 2)</label>
              <input type="text" name="street" value="<?php echo isset($street) ? htmlspecialchars($street) : ''; ?>" required class="mt-1 block w-full rounded-lg border border-gray-300 bg-white shadow-sm focus:ring-2 focus:ring-blue-300 focus:border-blue-300 p-3" />
            </div>

            <div class="space-y-4">
              <div>
                <label class="text-sm font-medium text-gray-700">Category (Step 3)</label>
                <input type="hidden" name="category_id" id="category_id_input" value="<?php echo isset($category_id) ? (int)$category_id : ''; ?>">
                <div id="category_buttons" class="mt-2 flex flex-wrap gap-2">
                  <?php foreach ($categories as $cid => $cname): ?>
                    <button type="button" data-cid="<?php echo $cid; ?>" class="category-btn px-3 py-2 rounded-lg border border-gray-200 bg-white text-gray-700"><?php echo htmlspecialchars($cname); ?></button>
                  <?php endforeach; ?>
                </div>
              </div>

              <div>
                <label class="text-sm font-medium text-gray-700">Subcategory (Step 4)</label>
                <input type="hidden" name="subcategory_id" id="subcategory_id_input" value="<?php echo isset($subcategory_id) ? (int)$subcategory_id : ''; ?>">
                <div id="subcategory_buttons" class="mt-2 flex flex-wrap gap-2">
                  <!-- subcategory buttons rendered dynamically -->
                </div>
              </div>
            </div>

            <div>
              <label class="text-sm font-medium text-gray-700">Upload Image (optional)</label>
              <input type="file" id="report_image_input" name="report_image" accept="image/*" class="mt-1 block w-full rounded-lg border border-dashed border-gray-300 bg-white p-3 text-sm text-gray-600" />
              <p class="text-xs text-gray-500 mt-1">Optional — add a photo to help describe the issue. Allowed: JPG, PNG.</p>
              <div id="image_preview_container" class="mt-4 hidden rounded-lg overflow-hidden bg-gray-50 border border-gray-200 p-4">
                <img id="image_preview" src="" alt="Preview" class="max-h-64 mx-auto rounded-lg" />
              </div>
            </div>

            <div class="pt-2">
              <button id="submit-btn" type="submit" disabled class="w-full inline-flex items-center justify-center gap-2 bg-red-600 text-white px-4 py-3 rounded-lg hover:bg-red-700 transition opacity-50 cursor-not-allowed"> 
                <span class="font-semibold">Submit Report</span>
                <i data-lucide="send" class="w-4 h-4"></i>
              </button>
            </div>
          </div>
        </form>
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

  <script>
    lucide.createIcons();
    // Image preview handler
    (function(){
      const fileInput = document.getElementById('report_image_input');
      const previewContainer = document.getElementById('image_preview_container');
      const previewImg = document.getElementById('image_preview');
      if(fileInput){
        fileInput.addEventListener('change', function(e){
          if(e.target.files && e.target.files[0]){
            const reader = new FileReader();
            reader.onload = function(event){
              previewImg.src = event.target.result;
              previewContainer.classList.remove('hidden');
            };
            reader.readAsDataURL(e.target.files[0]);
          } else {
            previewContainer.classList.add('hidden');
            previewImg.src = '';
          }
        });
      }
    })();
    // Render category and subcategory button behaviour
    (function(){
      const subMap = SUBCATS || {};
      const catInput = document.getElementById('category_id_input');
      const subInput = document.getElementById('subcategory_id_input');
      const catBtns = document.querySelectorAll('.category-btn');
      const subContainer = document.getElementById('subcategory_buttons');

      function clearActive(containerSelector){
        const children = document.querySelectorAll(containerSelector);
        children.forEach(c => c.classList.remove('bg-green-500','text-white','border-transparent'));
      }

      function makeSubButtons(cid){
        subContainer.innerHTML = '';
        if(!cid) return;
        const list = subMap[cid] || [];
        list.forEach(function(s){
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'sub-btn px-3 py-2 rounded-lg border border-gray-200 bg-white text-gray-700';
          btn.dataset.sid = s.id;
          btn.textContent = s.name;
          btn.addEventListener('click', function(){
            // set hidden input
            subInput.value = this.dataset.sid;
            // mark active
            document.querySelectorAll('.sub-btn').forEach(b => b.classList.remove('bg-green-600','text-white','border-green-600','shadow'));
            this.classList.add('bg-green-600','text-white','border-green-600','shadow');
          });
          subContainer.appendChild(btn);
        });
        // if subInput already has value, mark corresponding button active
        if(subInput.value){
          const pre = subContainer.querySelector('[data-sid="' + subInput.value + '"]');
          if(pre) pre.classList.add('bg-green-600','text-white','border-green-600','shadow');
        }
      }

      catBtns.forEach(function(b){
        b.addEventListener('click', function(){
          const cid = this.dataset.cid;
          // set hidden input
          catInput.value = cid;
          // visual
          catBtns.forEach(x => x.classList.remove('bg-green-600','text-white','border-green-600','shadow'));
          this.classList.add('bg-green-600','text-white','border-green-600','shadow');
          // reset sub selection
          subInput.value = '';
          makeSubButtons(cid);
        });
      });

      // on load, if category pre-selected, activate it and render subs
      document.addEventListener('DOMContentLoaded', function(){
        const preCid = catInput.value;
        if(preCid){
          const el = document.querySelector('.category-btn[data-cid="' + preCid + '"]');
          if(el) el.classList.add('bg-green-600','text-white','border-green-600','shadow');
          makeSubButtons(preCid);
        }
      });
    })();
  </script>
</body>
</html>
