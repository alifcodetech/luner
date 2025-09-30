<?php require_once __DIR__ . '/common.php';

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
    header('Location: user_pricing.php?tid=' . (int)$tid);
    exit;
  }
}

// Fetch current user plan
$currentPlan = null; {
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

?>

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>
<div class="content p-4 ">
  <h2>Pricing Plans</h2>
  <div class="row g-4 shadow p-2">
    <?php
    $plansRes = $mysqli->query('SELECT id, name, amount, daily_percent, duration_days FROM plans WHERE is_active = 1 ORDER BY id ASC');
    $hasChosenPlan = isset($latestTxn['id']) || !empty($currentPlan);
    while ($p = $plansRes->fetch_assoc()):
      $selectedByTxn = isset($latestTxn['plan_id']) && (int)$latestTxn['plan_id'] === (int)$p['id'];
      $selectedByUserPlan = $currentPlan && strcasecmp($currentPlan, $p['name']) === 0;
      $isSelected = $selectedByTxn || $selectedByUserPlan;
      $disableAll = $hasChosenPlan;
      $btnDisabledAttr = $disableAll ? 'disabled' : '';
      $btnClass = 'btn' . ($isSelected || $disableAll ? '' : ' primary');
      $btnLabel = $isSelected ? 'Selected' : ($disableAll ? 'Locked' : 'Select');
    ?>
      <div class="col-md-4">
        <div class="card card-blue text-white p-3 h-100">
          <h5><?= $p['name'] ?></h5>
          <p>$<?= $p['amount'] ?> • <?= $p['daily_percent'] ?>% daily • <?= $p['duration_days'] ?> days</p>
          <form method="post" class="mt-auto">
            <input type="hidden" name="select_plan" value="1" />
            <input type="hidden" name="plan_id" value="<?php echo (int)$p['id']; ?>" />
            <button type="submit" class="btn <?php echo ($isSelected) ? 'btn-danger' : 'btn-info'; ?>" <?php echo $btnDisabledAttr; ?>><?php echo $btnLabel; ?></button>
          </form>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>
</body>

</html>