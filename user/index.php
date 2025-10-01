<?php require_once __DIR__ . '/common.php';
 requireApprovedTransaction($mysqli);
?>
  <?php include 'header.php'; ?>

  <?php include 'sidebar.php'; ?>
  <div class="content p-4">
    <h2>Investment Summary</h2>
    <?php
    if ($latestTxn):
						$planName = $latestTxn['plan_name'];
						$amount = (float)$latestTxn['investment_amount'];
						$dailyPercent = (float)$latestTxn['daily_percent'];
						$durationDays = (int)$latestTxn['duration_days'];
						$dailyProfit = $amount > 0 ? ($amount * $dailyPercent) / 100 : 0;
						$totalProfit = $dailyProfit * $durationDays;
    ?>
    <div class="row g-4 shadow p-2">
      <div class="col-md-3"><div class="card card-blue p-3"><h6>Status</h6><p><?= $latestTxn['status'] ?></p></div></div>
      <div class="col-md-3"><div class="card card-blue p-3"><h6>Plan</h6><p><?= $latestTxn['plan_name'] ?></p></div></div>
      <div class="col-md-3"><div class="card card-green p-3"><h6>Amount</h6><p>RS <?= number_format($amount,2) ?></p></div></div>
      <div class="col-md-3"><div class="card card-green p-3"><h6>Duration</h6><p><?= number_format($durationDays,2) ?></p></div></div>
      <div class="col-md-3"><div class="card card-green p-3"><h6>Daily percentage</h6><p><?= number_format($dailyPercent,2) ?>%</p></div></div>
      <div class="col-md-3"><div class="card card-orange p-3"><h6>Daily Profit</h6><p>RS <?= number_format($dailyProfit,2) ?></p></div></div>
      <div class="col-md-3"><div class="card card-red p-3"><h6>Total Profit</h6><p>RS <?= number_format($totalProfit,2) ?></p></div></div>
    </div>
    <?php else: ?>
      <div class="alert alert-info">No investment yet.</div>
    <?php endif; ?>
  </div>
</body>
</html>
