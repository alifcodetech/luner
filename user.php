<?php require_once __DIR__ . '/db.php'; ?>
<?php require_once __DIR__ . '/common.php'; ?>

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
			// Create pending transaction with amount placeholder and redirect to admin_pricing.php
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>User Panel - Alif Invest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <header class="navbar navbar-expand-lg navbar-light bg-light mb-3">
        <div class="container">
            <a class="navbar-brand" href="index.php">Alif Invest</a>
            <nav class="ms-auto">
                <span class="me-2">Hi, <?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="btn btn-sm btn-outline-secondary">Logout</a>
            </nav>
        </div>
    </header>

    <main class="section">
        <div class="container">
            <h2 class="mb-3">User Dashboard</h2>
            <?php if (!$approved): ?>
                <div class="alert alert-danger">Your account is pending approval by the admin.</div>
            <?php else: ?>
                <div class="alert alert-success">Your account is approved.</div>
				<?php
					// Investment summary based on latest transaction joined with plan
					$latestTxn = null;
					{
						$userId = (int)$_SESSION['user_id'];
						$stmt = $mysqli->prepare("SELECT t.id, t.plan_id, t.investment_amount, t.status, t.created_at, p.name AS plan_name, p.daily_percent, p.duration_days FROM transactions t JOIN plans p ON p.id = t.plan_id WHERE t.user_id = ? ORDER BY t.id DESC LIMIT 1");
						$stmt->bind_param('i', $userId);
						$stmt->execute();
						$res = $stmt->get_result();
						$latestTxn = $res->fetch_assoc();
						$stmt->close();
					}

					if ($latestTxn):
						$planName = $latestTxn['plan_name'];
						$amount = (float)$latestTxn['investment_amount'];
						$dailyPercent = (float)$latestTxn['daily_percent'];
						$durationDays = (int)$latestTxn['duration_days'];
						$dailyProfit = $amount > 0 ? ($amount * $dailyPercent) / 100 : 0;
						$totalProfit = $dailyProfit * $durationDays;
				?>
                <div class="card mb-3 p-3">
                    <h3 class="mt-0">Your Investment Summary</h3>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($latestTxn['status'])); ?> • <strong>Plan:</strong> <?php echo htmlspecialchars($planName); ?> • <strong>Amount:</strong> $<?php echo number_format($amount, 2); ?></p>
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3">
                        <div class="col"><div class="card p-3"><h4 class="h6">Daily Percentage</h4><p class="mb-0"><?php echo number_format($dailyPercent, 2); ?>%</p></div></div>
                        <div class="col"><div class="card p-3"><h4 class="h6">Duration</h4><p class="mb-0"><?php echo (int)$durationDays; ?> days</p></div></div>
                        <div class="col"><div class="card p-3"><h4 class="h6">Daily Profit</h4><p class="mb-0">$<?php echo number_format($dailyProfit, 2); ?></p></div></div>
                        <div class="col"><div class="card p-3"><h4 class="h6">Total Profit</h4><p class="mb-0">$<?php echo number_format($totalProfit, 2); ?></p></div></div>
                    </div>
					<?php if ($latestTxn['status'] === 'pending'): ?>
                        <p class="text-muted mt-2">Your transaction is pending approval. You may <a href="admin_pricing.php?tid=<?php echo (int)$latestTxn['id']; ?>">update details here</a>.</p>
					<?php endif; ?>
				</div>
				<?php endif; ?>
                <?php if ($currentPlan): ?>
                    <div class="card mb-3 p-3">
                        <p class="mb-0"><strong>Your current plan:</strong> <?php echo htmlspecialchars(ucfirst($currentPlan)); ?></p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">You have not selected a plan yet.</div>
                <?php endif; ?>

				<?php
					// Profit-to-date section (accrued profit since subscription start up to today, minus admin payouts)
					$latestApproved = null;
					$accruedDays = 0;
					$grossAccruedProfit = 0.0;
					$totalPayouts = 0.0;
					$netAccrued = 0.0;
					{
						$userId = (int)$_SESSION['user_id'];
						// Get the most recent approved transaction with its plan details
						$stmt = $mysqli->prepare("SELECT t.id, t.investment_amount, t.created_at, p.name AS plan_name, p.daily_percent, p.duration_days FROM transactions t JOIN plans p ON p.id = t.plan_id WHERE t.user_id = ? AND t.status = 'approved' ORDER BY t.id DESC LIMIT 1");
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
							// Inclusive day count from subscription date to today (calendar days), capped by plan duration
							$startDate = new DateTime((new DateTime($latestApproved['created_at']))->format('Y-m-d'));
							$todayDate = new DateTime(date('Y-m-d'));
							$elapsed = (int)$startDate->diff($todayDate)->days + 1; // inclusive of start date
							if ($elapsed < 1) { $elapsed = 1; }
							if ($elapsed > $durationDays) { $elapsed = $durationDays; }
							$accruedDays = $elapsed;
							$grossAccruedProfit = $dailyProfitA * $accruedDays;
							// Sum payouts to this user
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
				?>
                <?php if ($latestApproved): ?>
                    <div class="card mb-3 p-3">
                        <h3 class="mt-0">Profit to Date</h3>
                        <p class="text-muted">Plan: <strong><?php echo htmlspecialchars($latestApproved['plan_name']); ?></strong> • Days accrued: <strong><?php echo (int)$accruedDays; ?></strong> / <?php echo (int)$latestApproved['duration_days']; ?></p>
                        <div class="row row-cols-1 row-cols-sm-3 g-3">
                            <div class="col"><div class="card p-3"><h4 class="h6">Gross Accrued</h4><p class="mb-0">$<?php echo number_format($grossAccruedProfit, 2); ?></p></div></div>
                            <div class="col"><div class="card p-3"><h4 class="h6">Total Payouts</h4><p class="mb-0">$<?php echo number_format($totalPayouts, 2); ?></p></div></div>
                            <div class="col"><div class="card p-3"><h4 class="h6">Net Payable</h4><p class="mb-0"><strong>$<?php echo number_format($netAccrued, 2); ?></strong></p></div></div>
                        </div>
                    </div>
                    <div class="card mb-3 p-3" style="overflow-x:auto;">
                        <h4 class="mt-0">Admin Payouts</h4>
                        <table class="table table-striped mb-0">
							<thead>
								<tr>
                                    <th>ID</th>
                                    <th>Amount</th>
                                    <th>Note</th>
                                    <th>Date</th>
								</tr>
							</thead>
							<tbody>
								<?php
									$userId = (int)$_SESSION['user_id'];
									$resP = $mysqli->query('SELECT id, amount, note, created_at FROM payouts WHERE user_id = ' . $userId . ' ORDER BY id DESC');
									if ($resP) {
										while ($rowP = $resP->fetch_assoc()):
									?>
									<tr>
                                    <td>#<?php echo (int)$rowP['id']; ?></td>
                                    <td>$<?php echo number_format((float)$rowP['amount'], 2); ?></td>
                                    <td><?php echo $rowP['note'] ? htmlspecialchars($rowP['note']) : '<span class="text-muted">—</span>'; ?></td>
                                    <td><?php echo htmlspecialchars($rowP['created_at']); ?></td>
									</tr>
									<?php
										endwhile;
									} else {
                                echo '<tr><td colspan="4" class="text-muted">No payouts recorded.</td></tr>';
									}
								?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>

                <h3>Choose Your Pricing Plan</h3>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">
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
                    <div class="col"><div class="card p-3 h-100">
                        <h4 class="h5"><?php echo htmlspecialchars($p['name']); ?></h4>
                        <p class="mb-2">$<?php echo number_format((float)$p['amount'], 2); ?> • <?php echo number_format((float)$p['daily_percent'], 2); ?>% daily • <?php echo (int)$p['duration_days']; ?> days</p>
                        <form method="post" class="mt-auto">
							<input type="hidden" name="select_plan" value="1" />
							<input type="hidden" name="plan_id" value="<?php echo (int)$p['id']; ?>" />
                            <button type="submit" class="btn <?php echo ($isSelected || $disableAll) ? 'btn-outline-secondary' : 'btn-primary'; ?>" <?php echo $btnDisabledAttr; ?>><?php echo $btnLabel; ?></button>
						</form>
                    </div></div>
					<?php endwhile; ?>
				</div>
			<?php endif; ?>
		</div>
	</main>

    <footer class="footer py-3 mt-4 bg-light">
        <div class="container"><p class="mb-0">© <?php echo date('Y'); ?> Alif Invest.</p></div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>


