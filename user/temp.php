<?php require_once __DIR__ . '/../db.php'; ?>
<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'] ?? 'User';
$approved = (int)($_SESSION['approved'] ?? 0);

// Handle plan selection (only for approved users)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_plan']) && $approved) {
    $userId = (int)$_SESSION['user_id'];
    $planId = (int)($_POST['plan_id'] ?? 0);

    // Verify plan exists and is active
    $stmt = $mysqli->prepare('SELECT id FROM plans WHERE id = ? AND is_active = 1 LIMIT 1');
    $stmt->bind_param('i', $planId);
    $stmt->execute();
    $res = $stmt->get_result();
    $planRow = $res->fetch_assoc();
    $stmt->close();

    if ($planRow) {
        // If user already has a pending transaction without a transaction_id, reuse it
        $stmt = $mysqli->prepare("SELECT id FROM transactions WHERE user_id = ? AND status = 'pending' AND (transaction_id IS NULL OR transaction_id = '') ORDER BY id DESC LIMIT 1");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $existing = $res->fetch_assoc();
        $stmt->close();

        if ($existing) {
            $tid = (int)$existing['id'];
            header('Location: admin_pricing.php?tid=' . $tid);
            exit;
        }

        // Create pending transaction
        $stmt = $mysqli->prepare('INSERT INTO transactions (user_id, plan_id, investment_amount, status) VALUES (?, ?, 0, "pending")');
        $stmt->bind_param('ii', $userId, $planId);
        $stmt->execute();
        $tid = $stmt->insert_id;
        $stmt->close();
        header('Location: admin_pricing.php?tid=' . (int)$tid);
        exit;
    }
}

// Fetch current user plan
$currentPlan = null;
{
    $userId = (int)$_SESSION['user_id'];
    $stmt = $mysqli->prepare('SELECT plan FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $currentPlan = $row['plan'] ?: null;
    }
    $stmt->close();
}

// Fetch latest transaction (for investment summary)
$latestTxn = null;
{
    $userId = (int)$_SESSION['user_id'];
    $stmt = $mysqli->prepare("SELECT t.id, t.plan_id, t.investment_amount, t.status, t.created_at, 
                                      p.name AS plan_name, p.daily_percent, p.duration_days 
                               FROM transactions t 
                               JOIN plans p ON p.id = t.plan_id 
                               WHERE t.user_id = ? 
                               ORDER BY t.id DESC LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $latestTxn = $res->fetch_assoc();
    $stmt->close();
}

// Profit-to-date logic
$latestApproved = null;
$accruedDays = 0;
$grossAccruedProfit = 0.0;
$totalPayouts = 0.0;
$netAccrued = 0.0;
{
    $userId = (int)$_SESSION['user_id'];
    $stmt = $mysqli->prepare("SELECT t.id, t.investment_amount, t.created_at, 
                                     p.name AS plan_name, p.daily_percent, p.duration_days 
                              FROM transactions t 
                              JOIN plans p ON p.id = t.plan_id 
                              WHERE t.user_id = ? AND t.status = 'approved' 
                              ORDER BY t.id DESC LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $latestApproved = $res->fetch_assoc();
    $stmt->close();

    if ($latestApproved) {
        $amount = (float)$latestApproved['investment_amount'];
        $dailyPercent = (float)$latestApproved['daily_percent'];
        $durationDays = (int)$latestApproved['duration_days'];
        $dailyProfitA = $amount > 0 ? ($amount * $dailyPercent) / 100 : 0;

        // Inclusive day count
        $startDate = new DateTime((new DateTime($latestApproved['created_at']))->format('Y-m-d'));
        $todayDate = new DateTime(date('Y-m-d'));
        $elapsed = (int)$startDate->diff($todayDate)->days + 1;
        if ($elapsed < 1) { $elapsed = 1; }
        if ($elapsed > $durationDays) { $elapsed = $durationDays; }
        $accruedDays = $elapsed;

        $grossAccruedProfit = $dailyProfitA * $accruedDays;

        // Sum payouts
        $q = $mysqli->prepare('SELECT COALESCE(SUM(amount),0) AS total_paid FROM payouts WHERE user_id = ?');
        $q->bind_param('i', $userId);
        $q->execute();
        $r = $q->get_result();
        $sumRow = $r->fetch_assoc();
        $q->close();
        $totalPayouts = (float)($sumRow['total_paid'] ?? 0);

        $netAccrued = max(0.0, $grossAccruedProfit - $totalPayouts);
    }
}

// Fetch payouts for display
$userId = (int)$_SESSION['user_id'];
$resP = $mysqli->query('SELECT id, amount, note, created_at FROM payouts WHERE user_id = ' . $userId . ' ORDER BY id DESC');

// Fetch all plans
$plansRes = $mysqli->query('SELECT id, name, amount, daily_percent, duration_days FROM plans WHERE is_active = 1 ORDER BY id ASC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>User Panel - Alif Invest</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .sidebar {
      height: 100vh; background: #0d6efd; color: #fff;
      position: fixed; top: 0; left: 0; width: 250px; padding-top: 60px;
    }
    .sidebar a { color: #fff; display: block; padding: 12px 20px; text-decoration: none; border-radius: 5px; }
    .sidebar a:hover, .sidebar a.active { background: #0b5ed7; }
    .content { margin-left: 250px; padding: 20px; }
    header.navbar { position: fixed; top: 0; left: 250px; width: calc(100% - 250px); z-index: 1030; }
    .card { border-radius: 10px; }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <h4 class="px-3">Alif Invest</h4>
    <a href="#dashboard" class="active">Dashboard</a>
    <a href="#investment">Investment Summary</a>
    <a href="#profit">Profit to Date</a>
    <a href="#plans">Pricing Plans</a>
    <a href="#payouts">Admin Payouts</a>
    <a href="logout.php">Logout</a>
  </div>

  <!-- Top Navbar -->
  <header class="navbar navbar-light bg-white shadow-sm">
    <div class="container-fluid">
      <span class="navbar-text">Hi, <?php echo htmlspecialchars($username); ?></span>
    </div>
  </header>

  <!-- Main Content -->
  <div class="content">
    <div class="container-fluid">

      <!-- Dashboard -->
      <section id="dashboard" class="mb-4">
        <h2>User Dashboard</h2>
        <?php if (!$approved): ?>
          <div class="alert alert-danger">Your account is pending approval by the admin.</div>
        <?php else: ?>
          <div class="alert alert-success">Your account is approved.</div>
        <?php endif; ?>
      </section>

      <!-- Investment Summary -->
      <?php if ($latestTxn): ?>
      <?php
        $planName = $latestTxn['plan_name'];
        $amount = (float)$latestTxn['investment_amount'];
        $dailyPercent = (float)$latestTxn['daily_percent'];
        $durationDays = (int)$latestTxn['duration_days'];
        $dailyProfit = $amount > 0 ? ($amount * $dailyPercent) / 100 : 0;
        $totalProfit = $dailyProfit * $durationDays;
      ?>
      <section id="investment" class="mb-4">
        <div class="card p-3 shadow-sm">
          <h3>Your Investment Summary</h3>
          <p><strong>Status:</strong> <?php echo ucfirst($latestTxn['status']); ?> • 
             <strong>Plan:</strong> <?php echo $planName; ?> • 
             <strong>Amount:</strong> $<?php echo number_format($amount, 2); ?>
          </p>
          <div class="row text-center">
            <div class="col"><div class="card p-3"><h6>Daily %</h6><p><?php echo number_format($dailyPercent, 2); ?>%</p></div></div>
            <div class="col"><div class="card p-3"><h6>Duration</h6><p><?php echo $durationDays; ?> days</p></div></div>
            <div class="col"><div class="card p-3"><h6>Daily Profit</h6><p>$<?php echo number_format($dailyProfit, 2); ?></p></div></div>
            <div class="col"><div class="card p-3"><h6>Total Profit</h6><p>$<?php echo number_format($totalProfit, 2); ?></p></div></div>
          </div>
          <?php if ($latestTxn['status'] === 'pending'): ?>
            <p class="text-muted mt-2">Your transaction is pending approval. You may 
               <a href="admin_pricing.php?tid=<?php echo (int)$latestTxn['id']; ?>">update details here</a>.
            </p>
          <?php endif; ?>
        </div>
      </section>
      <?php endif; ?>

      <!-- Profit to Date -->
      <?php if ($latestApproved): ?>
      <section id="profit" class="mb-4">
        <div class="card p-3 shadow-sm">
          <h3>Profit to Date</h3>
          <p>Plan: <strong><?php echo $latestApproved['plan_name']; ?></strong> • 
             Days accrued: <strong><?php echo $accruedDays; ?></strong> / <?php echo $latestApproved['duration_days']; ?></p>
          <div class="row text-center">
            <div class="col"><div class="card p-3"><h6>Gross Accrued</h6><p>$<?php echo number_format($grossAccruedProfit, 2); ?></p></div></div>
            <div class="col"><div class="card p-3"><h6>Total Payouts</h6><p>$<?php echo number_format($totalPayouts, 2); ?></p></div></div>
            <div class="col"><div class="card p-3"><h6>Net Payable</h6><p><strong>$<?php echo number_format($netAccrued, 2); ?></strong></p></div></div>
          </div>
        </div>
      </section>
      <?php endif; ?>

      <!-- Pricing Plans -->
      <section id="plans" class="mb-4">
        <h3>Choose Your Pricing Plan</h3>
        <div class="row g-3">
          <?php
          $hasChosenPlan = isset($latestTxn['id']) || !empty($currentPlan);
          while ($p = $plansRes->fetch_assoc()):
            $selectedByTxn = isset($latestTxn['plan_id']) && (int)$latestTxn['plan_id'] === (int)$p['id'];
            $selectedByUserPlan = $currentPlan && strcasecmp($currentPlan, $p['name']) === 0;
            $isSelected = $selectedByTxn || $selectedByUserPlan;
            $disableAll = $hasChosenPlan;
            $btnDisabledAttr = $disableAll ? 'disabled' : '';
            $btnLabel = $isSelected ? 'Selected' : ($disableAll ? 'Locked' : 'Select');
          ?>
          <div class="col-md-4">
            <div class="card p-3 h-100 shadow-sm">
              <h5><?php echo htmlspecialchars($p['name']); ?></h5>
              <p>$<?php echo number_format((float)$p['amount'], 2); ?> • 
                 <?php echo number_format((float)$p['daily_percent'], 2); ?>% daily • 
                 <?php echo (int)$p['duration_days']; ?> days</p>
              <form method="post">
                <input type="hidden" name="select_plan" value="1">
                <input type="hidden" name="plan_id" value="<?php echo (int)$p['id']; ?>">
                <button type="submit" class="btn <?php echo ($isSelected || $disableAll) ? 'btn-outline-secondary' : 'btn-primary'; ?> w-100" <?php echo $btnDisabledAttr; ?>>
                  <?php echo $btnLabel; ?>
                </button>
              </form>
            </div>
          </div>
          <?php endwhile; ?>
        </div>
      </section>

      <!-- Admin Payouts -->
      <section id="payouts" class="mb-4">
        <div class="card p-3 shadow-sm">
          <h4>Admin Payouts</h4>
          <table class="table table-striped">
            <thead><tr><th>ID</th><th>Amount</th><th>Note</th><th>Date</th></tr></thead>
            <tbody>
              <?php
              if ($resP && $resP->num_rows > 0):
                while ($rowP = $resP->fetch_assoc()):
              ?>
              <tr>
                <td>#<?php echo (int)$rowP['id']; ?></td>
                <td>$<?php echo number_format((float)$rowP['amount'], 2); ?></td>
                <td><?php echo $rowP['note'] ? htmlspecialchars($rowP['note']) : '—'; ?></td>
                <td><?php echo htmlspecialchars($rowP['created_at']); ?></td>
              </tr>
              <?php endwhile; else: ?>
              <tr><td colspan="4" class="text-muted">No payouts recorded.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
