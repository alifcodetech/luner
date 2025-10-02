<?php require_once __DIR__ . '/common.php'; ?>

<?php
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
    // Reuse pending transaction if exists
    $stmt = $mysqli->prepare("SELECT id FROM transactions WHERE user_id = ? AND status = 'pending' AND (transaction_id IS NULL OR transaction_id = '') ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $existing = $res->fetch_assoc();
    $stmt->close();

    if ($existing) {
      $updatequery = $mysqli->prepare('UPDATE transactions SET plan_id = ? WHERE id = ?');
      $updatequery->bind_param('ii', $planId, $existing['id']);
      $updatequery->execute();
      $updatequery->close();
      $tid = (int)$existing['id'];
      header('Location: user_pricing.php?tid=' . $tid);
      exit;
    }

    // Create new pending transaction
    $stmt = $mysqli->prepare('INSERT INTO transactions (user_id, plan_id, investment_amount, status) VALUES (?, ?, 0, "pending")');
    $stmt->bind_param('ii', $userId, $planId);
    $stmt->execute();
    $tid = $stmt->insert_id;
    $stmt->close();
    header('Location: user_pricing.php?tid=' . (int)$tid);
    exit;
  }
}

// Fetch current user's active plan
$currentPlan = null;
$userId = (int)$_SESSION['user_id'];
$stmt = $mysqli->prepare('SELECT plan_id FROM users LEFT JOIN transactions ON users.id = transactions.user_id WHERE users.id = ? AND transactions.transaction_id != "" LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
  $currentPlan = $row;
}
$stmt->close();

// Fetch all active plans
$plansRes = $mysqli->query('SELECT id, name, amount, daily_percent, duration_days FROM plans WHERE is_active = 1 ORDER BY id ASC');
$plans = $plansRes->fetch_all(MYSQLI_ASSOC);
$hasChosenPlan = !empty($currentPlan);
?>
<style>body {
  background: #e6e6e6;
  font-family: Rubik;
}

.main-head {
  background: #0D1440;
  box-shadow: 0px 1px 10px -6px rgba(0, 0, 0, .15);
  padding: 1rem;
  margin-bottom: 0;
  margin-top: 5rem;
  color: #fff;
  font-weight: 500;
  text-transform: uppercase;
  border-radius: 4px;
  font-size: 16px;
}

.pricing-table {
  background: #fff;
  box-shadow: 0px 1px 10px -6px rgba(0, 0, 0, .15);
  padding: 2rem;
  border-radius: 4px;
  transition: .3s;
}

.pricing-table:hover {
  box-shadow: 0px 1px 10px -4px rgba(0, 0, 0, .15);
}

.pricing-table .pricing-label {
  border-radius: 2px;
  padding: .25rem .5rem;
  margin-bottom: 1rem;
  display: inline-block;
  font-size: 12px;
  font-weight: 500;
}

.pricing-table h2 {
  color: #3b3b3b;
  font-size: 24px;
  font-weight: 500;
}

.pricing-table h5 {
  color: #B3B3B3;
  font-size: 14px;
  font-weight: 400;
}

.pricing-table .pricing-features {
  margin-top: 2rem;
}

.pricing-table .pricing-features .feature {
  font-size: 14px;
  margin: .5rem 0;
  color: #B3B3B3;
}

.pricing-table .pricing-features .feature span {
  display: inline-block;
  float: right;
  color: #3b3b3b;
  font-weight: 500;
}

.pricing-table  .price-tag {
  margin-top: 2rem;
  text-align: center;
  font-weight: 500;
}

.pricing-table .price-tag .symbol {
  font-size: 24px;
}

.pricing-table .price-tag .amount {
  letter-spacing: -2px;
  font-size: 64px;
}

.pricing-table .price-tag .after {
  color: #3b3b3b;
  font-weight: 500;
}

.pricing-table .price-button {
  display: block;
  color: #fff;
  margin-top: 2rem;
  padding: .75rem;
  border-radius: 2px;
  text-align: center;
  font-weight: 500;
  transition: .3s;
}

.pricing-table .price-button:hover {
  text-decoration: none;
}

.purple .pricing-label {
  background: #cad2ff;
  color: #627afe;
}

.purple .price-tag {
  color: #627afe;
}

.purple .price-button {
  background: #627afe;
}

.purple .price-button:hover {
  background: #546dfe;
}

.turquoise .pricing-label {
  background: #b9edee;
  color: #44cdd2;
}

.turquoise .price-tag {
  color: #44cdd2;
}

.turquoise .price-button {
  background: #44cdd2;
}

.turquoise .price-button:hover {
  background: #2dbcc4;
}

.red .pricing-label {
  background: #ffc4c4;
  color: #ff5e5e;
}

.red .price-tag {
  color: #ff5e5e;
}

.red .price-button {
  background: #ff5e5e;
}

.red .price-button:hover {
  background: #f23c3c;
}</style>

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="content p-4">
  <div class="container">
    <div class="row">
      <div class="col-md-12 mb-5">
        <h2 class="main-head">Pricing Plans</h2>
      </div>

      <?php foreach ($plans as $index => $p): 
        $isSelected = $currentPlan && ((int)$currentPlan['plan_id'] === (int)$p['id']);
        $disableAll = $hasChosenPlan;
        $btnDisabled = $disableAll ? 'disabled' : '';
        $btnLabel = $isSelected ? 'Selected' : ($disableAll ? 'Locked' : 'Select');

        // Assign color class based on index (0=purple, 1=turquoise, 2+=red)
        $colorClasses = ['purple', 'turquoise', 'red'];
        $colorClass = $colorClasses[min($index, 2)]; // fallback to red if more than 3 plans
      ?>

      <div class="col-sm-12  col-lg-4 mb-4">
        <div class="pricing-table <?= $colorClass ?>">
          <div class="pricing-label">Fixed Price</div>
          <h2 class="text-uppercase fw-bold"><?= htmlspecialchars($p['name']) ?></h2>
          <h5>Investment Plan</h5>

          <div class="pricing-features">
            <div class="feature">Investment<span>RS <?= number_format($p['amount']) ?></span></div>
            <div class="feature">Daily Return<span><?= $p['daily_percent'] ?>%</span></div>
            <div class="feature">Duration<span><?= $p['duration_days'] ?> days</span></div>
            <!-- Add more features if your DB supports them -->
          </div>

          <div class="price-tag">
            <span class="symbol">RS </span>
            <span class="amount"><?= number_format($p['amount'], 0, '', '') ?></span>
            <span class="after">/one-time</span>
          </div>

          <form method="post" style="margin-top: 2rem;">
            <input type="hidden" name="select_plan" value="1" />
            <input type="hidden" name="plan_id" value="<?= (int)$p['id'] ?>" />
            <button type="submit" class="price-button" <?= $btnDisabled ?>>
              <?= htmlspecialchars($btnLabel) ?>
            </button>
          </form>
        </div>
      </div>

      <?php endforeach; ?>
    </div>
  </div>
</div>
