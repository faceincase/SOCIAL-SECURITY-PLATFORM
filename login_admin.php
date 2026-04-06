<?php
session_start();

$errors = [];
$success = '';
$verified = !empty($_SESSION['admin_pin_verified']);

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'verify_pin') {
    $pin = trim($_POST['pin'] ?? '');
    if ($pin === '123456') {
      $_SESSION['admin_pin_verified'] = true;
      $verified = true;
      $success = 'PIN verified successfully. Admin access granted.';
    } else {
      $errors[] = 'Incorrect PIN. Please try again.';
    }
  }

  if ($action === 'resend') {
    // Dummy resend — no real action
    $success = 'A new PIN has been sent.';
  }
}

$isLoggedIn = !empty($_SESSION['logged_in']);
$isAdmin    = !empty($_SESSION['is_admin']);
$username   = $_SESSION['username'] ?? '';
$method     = $_GET['method'] ?? 'sms'; // sms or email
$otherMethod = $method === 'sms' ? 'email' : 'sms';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Verification</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-gradient-to-br from-gray-200 to-gray-50 min-h-screen p-6 px-80">

  <?php include __DIR__ . '/ASSETS/header.php'; ?>

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

  <?php if (!empty($success)): ?>
    <div class="mb-4">
      <div class="text-green-700 bg-green-50 border border-green-100 p-3 rounded">
        <?php echo htmlspecialchars($success); ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Content Area -->
  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300">

        <?php if ($verified): ?>
          <!-- Verified State -->
          <div class="text-center py-6">
            <div class="flex items-center justify-center mb-4">
              <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center">
                <i data-lucide="shield-check" class="w-10 h-10 text-green-600"></i>
              </div>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Admin Access Granted</h1>
            <p class="text-gray-500 mb-6">Your identity has been verified. You now have full admin access.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-md mx-auto">
              <a href="issues.php" class="inline-flex items-center justify-center gap-2 bg-amber-500 text-white px-4 py-3 rounded-lg hover:bg-amber-600 transition font-semibold">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                Admin Dashboard
              </a>
              <a href="index.php" class="inline-flex items-center justify-center gap-2 bg-gray-600 text-white px-4 py-3 rounded-lg hover:bg-gray-700 transition font-semibold">
                <i data-lucide="home" class="w-4 h-4"></i>
                Go to Home
              </a>
            </div>
          </div>

        <?php elseif (!$isLoggedIn || !$isAdmin): ?>
          <!-- Not an admin / not logged in -->
          <div class="text-center py-6">
            <div class="flex items-center justify-center mb-4">
              <div class="w-20 h-20 rounded-full bg-red-100 flex items-center justify-center">
                <i data-lucide="shield-x" class="w-10 h-10 text-red-500"></i>
              </div>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Admin Access Required</h1>
            <p class="text-gray-500 mb-6">You must be logged in as an admin to access this page.</p>
            <a href="login.php" class="inline-flex items-center justify-center gap-2 bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition font-semibold">
              <i data-lucide="log-in" class="w-4 h-4"></i>
              Go to Login
            </a>
          </div>

        <?php else: ?>
          <!-- PIN Entry -->
          <div class="max-w-md mx-auto">
            <div class="flex items-center gap-3 mb-6">
              <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                <i data-lucide="shield" class="w-5 h-5 text-amber-600"></i>
              </div>
              <div>
                <h1 class="text-2xl font-bold text-gray-900">2-Step Verification</h1>
                <p class="text-sm text-gray-500">Admin account: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($username); ?></span></p>
              </div>
            </div>

            <!-- Method badge -->
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
              <div class="flex items-center gap-3">
                <?php if ($method === 'sms'): ?>
                  <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="smartphone" class="w-5 h-5 text-amber-600"></i>
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-amber-800">Code sent via SMS</p>
                    <p class="text-xs text-amber-600">A 6-digit PIN was sent to your registered phone number<br>ending in <span class="font-bold">••••7890</span></p>
                  </div>
                <?php else: ?>
                  <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="mail" class="w-5 h-5 text-amber-600"></i>
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-amber-800">Code sent via Email</p>
                    <p class="text-xs text-amber-600">A 6-digit PIN was sent to your registered email <span class="font-bold">F••••7@sip.co.uk</span></p>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <!-- PIN Form -->
            <form method="POST" id="pinForm">
              <input type="hidden" name="action" value="verify_pin" />
              <input type="hidden" name="pin" id="pinHidden" />

              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3 text-center">Enter 6-digit PIN</label>
                <div class="flex justify-center gap-3" id="pinBoxes">
                  <?php for ($i = 1; $i <= 6; $i++): ?>
                    <input
                      type="text"
                      inputmode="numeric"
                      maxlength="1"
                      class="pin-input w-12 h-14 text-center text-xl font-bold rounded-xl border-2 border-gray-300 bg-gray-50 text-gray-900 focus:outline-none focus:border-amber-400 focus:bg-white focus:ring-2 focus:ring-amber-100 transition caret-transparent"
                      autocomplete="off"
                    />
                  <?php endfor; ?>
                </div>
                <p class="text-xs text-gray-400 text-center mt-3">Enter the 6-digit code sent to you</p>
              </div>

              <button type="submit" id="submitBtn"
                class="w-full inline-flex items-center justify-center gap-2 bg-amber-500 text-white px-4 py-3 rounded-lg hover:bg-amber-600 transition font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
                <i data-lucide="shield-check" class="w-4 h-4"></i>
                Verify PIN
              </button>
            </form>

            <!-- Resend & switch method -->
            <div class="mt-5 flex flex-col items-center gap-2 text-sm text-gray-500">
              <form method="POST" class="inline">
                <input type="hidden" name="action" value="resend" />
                <button type="submit" class="text-amber-600 hover:text-amber-800 font-medium transition">
                  Didn't receive it? Resend code
                </button>
              </form>
              <a href="login_admin.php?method=<?php echo htmlspecialchars($otherMethod); ?>"
                class="text-gray-400 hover:text-gray-600 transition flex items-center gap-1">
                <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                Switch to <?php echo $otherMethod === 'sms' ? 'SMS' : 'Email'; ?> instead
              </a>
            </div>

            <!-- Hint (dummy)
            <div class="mt-6 bg-gray-50 border border-gray-200 rounded-xl p-4 text-xs text-gray-400 text-center">
              <span class="font-medium text-gray-500">Demo mode:</span> PIN is always <span class="font-mono font-bold text-gray-600">1 2 3 4 5 6</span>
            </div>
             -->

            <!-- Skip link -->
            <div class="mt-4 text-center">
              <a href="index.php" class="text-xs text-gray-400 hover:text-gray-600 underline underline-offset-2 transition">
                Skip verification and continue without admin privilieges
              </a>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>

    <!-- Sidebar -->
    <aside class="bg-white p-8 rounded-xl shadow-lg border-2 border-gray-300 space-y-6">
      <div>
        <h2 class="text-lg font-bold text-gray-900 mb-2">Why 2-Step?</h2>
        <p class="text-sm text-gray-600">An extra layer of security to keep your admin account safe</p>
      </div>

      <div class="bg-amber-50 p-4 rounded-xl border border-amber-200 flex items-center justify-center">
        <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center">
          <i data-lucide="shield" class="w-9 h-9 text-amber-500"></i>
        </div>
      </div>

      <ul class="space-y-3 text-sm text-gray-700">
        <li class="flex items-start gap-2">
          <i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0"></i>
          <span>Protects against stolen passwords</span>
        </li>
        <li class="flex items-start gap-2">
          <i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0"></i>
          <span>PIN expires after a short time</span>
        </li>
        <li class="flex items-start gap-2">
          <i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0"></i>
          <span>Only you can receive the code</span>
        </li>
        <li class="flex items-start gap-2">
          <i data-lucide="check" class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0"></i>
          <span>Never share your PIN with anyone</span>
        </li>
      </ul>

      <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-xs text-gray-500 space-y-1">
        <p class="font-semibold text-gray-700">Having trouble?</p>
        <p>Check your spam folder, or try switching between SMS and Email delivery.</p>
      </div>
    </aside>
  </div>

  <?php include __DIR__ . '/ASSETS/footer.php'; ?>

  <script>lucide.createIcons();</script>
  <script>
    (function () {
      const boxes = Array.from(document.querySelectorAll('.pin-input'));
      const hiddenPin = document.getElementById('pinHidden');
      const form = document.getElementById('pinForm');

      function getPin() {
        return boxes.map(b => b.value).join('');
      }

      function syncHidden() {
        hiddenPin.value = getPin();
      }

      boxes.forEach((box, idx) => {
        // Only allow digits
        box.addEventListener('keydown', function (e) {
          const allowed = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'];
          if (!allowed.includes(e.key) && !/^\d$/.test(e.key)) {
            e.preventDefault();
          }
        });

        box.addEventListener('input', function () {
          // Keep only last typed digit
          this.value = this.value.replace(/\D/g, '').slice(-1);
          syncHidden();
          // Move focus forward
          if (this.value && idx < boxes.length - 1) {
            boxes[idx + 1].focus();
          }
          // Auto-submit when all 6 filled
          if (getPin().length === 6) {
            form.requestSubmit();
          }
        });

        box.addEventListener('keydown', function (e) {
          if (e.key === 'Backspace' && !this.value && idx > 0) {
            boxes[idx - 1].value = '';
            boxes[idx - 1].focus();
            syncHidden();
          }
        });

        // Handle paste
        box.addEventListener('paste', function (e) {
          e.preventDefault();
          const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
          pasted.split('').forEach((ch, i) => {
            if (boxes[i]) boxes[i].value = ch;
          });
          syncHidden();
          const next = Math.min(pasted.length, boxes.length - 1);
          boxes[next].focus();
          if (pasted.length === 6) form.requestSubmit();
        });
      });

      // Focus first box on load
      if (boxes.length) boxes[0].focus();
    })();
  </script>
</body>
</html>
