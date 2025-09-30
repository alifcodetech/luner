<?php require_once __DIR__ . '/../db.php';
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$userId = (int)$_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT t.investment_amount, t.created_at, 
                                 p.name AS plan_name, p.daily_percent, p.duration_days 
                          FROM transactions t 
                          JOIN plans p ON p.id=t.plan_id 
                          WHERE t.user_id=? AND t.status='approved' 
                          ORDER BY t.id DESC LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$txn = $stmt->get_result()->fetch_assoc();
$stmt->close();

$accruedDays = $gross = $paid = $net = 0;
if ($txn) {
  $daily = $txn['investment_amount'] * $txn['daily_percent'] / 100;
  $start = new DateTime(date('Y-m-d', strtotime($txn['created_at'])));
  $today = new DateTime(date('Y-m-d'));
  $days = $start->diff($today)->days + 1;
  $accruedDays = min($days, $txn['duration_days']);
  $gross = $daily * $accruedDays;
  $sum = $mysqli->query("SELECT COALESCE(SUM(amount),0) total FROM payouts WHERE user_id=$userId")->fetch_assoc();
  $paid = $sum['total'];
  $net = max(0, $gross - $paid);
}
?>
  <?php include 'header.php'; ?>


<body>
  <?php include 'sidebar.php'; ?>
  <div class="content p-4">
    <h2>Profit to Date</h2>

    <?php if ($txn): ?>
      <div>
        <p>Plan: <strong><?php echo $txn['plan_name']; ?></strong> •
          Days accrued: <strong><?php echo $accruedDays; ?></strong> / <?php echo $txn['duration_days']; ?></p>
      </div>
      <div class="row g-4 shadow p-2">
        <div class="col-md-3">
          <div class="card card-blue p-3">
            <h6>Plan</h6>
            <p><?= $txn['plan_name'] ?></p>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-green p-3">
            <h6>Gross Accrued</h6>
            <p>$<?= number_format($gross, 2) ?></p>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-orange p-3">
            <h6>Total Payouts</h6>
            <p>$<?= number_format($paid, 2) ?></p>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-red p-3">
            <h6>Net Payable</h6>
            <p>$<?= number_format($net, 2) ?></p>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="alert alert-info">No approved transaction found.</div>
    <?php endif; ?>
  </div>
</body>

</html>