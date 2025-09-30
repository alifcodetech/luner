<div class="sidebar">
  <!-- <img src="assets/images/logo/logo.svg" alt="Logo"> -->
  <img src="https://luner.alifcode.com/assets/images/logo/logo.svg" class="p-4" alt="Luner">

  <a href="index.php" class="<?= basename($_SERVER['PHP_SELF'])=='index.php'?'active':'' ?>">Dashboard</a>
  <a href="profit.php" class="<?= basename($_SERVER['PHP_SELF'])=='profit.php'?'active':'' ?>">Profit to Date</a>
  <a href="plans.php" class="<?= basename($_SERVER['PHP_SELF'])=='plans.php'?'active':'' ?>">Pricing Plans</a>
  <a href="payouts.php" class="<?= basename($_SERVER['PHP_SELF'])=='payouts.php'?'active':'' ?>">Admin Payouts</a>
  <a href="../logout.php">Logout</a>
</div>

