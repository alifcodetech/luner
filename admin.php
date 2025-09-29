<?php require_once __DIR__ . '/db.php'; ?>
<?php
// Simple admin bootstrap: if no admin exists, create default admin (email: admin@example.com, pass: admin123)
$mysqli->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) NOT NULL DEFAULT 0");

$adminCheck = $mysqli->query("SELECT id FROM users WHERE is_admin = 1 LIMIT 1");
if ($adminCheck && $adminCheck->num_rows === 0) {
	$hash = password_hash('admin123', PASSWORD_DEFAULT);
	$stmt = $mysqli->prepare('INSERT INTO users (username, email, password_hash, approved, is_admin, created_at) VALUES (\'admin\', \'admin@example.com\', ?, 1, 1, NOW())');
	$stmt->bind_param('s', $hash);
	$stmt->execute();
	$stmt->close();
}

// Handle login if not logged in as admin
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
	$loginErrors = [];
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
		$email = trim($_POST['email'] ?? '');
		$password = $_POST['password'] ?? '';
		$stmt = $mysqli->prepare('SELECT id, username, email, password_hash, approved, is_admin FROM users WHERE email = ? AND is_admin = 1 LIMIT 1');
		$stmt->bind_param('s', $email);
		$stmt->execute();
		$res = $stmt->get_result();
		$admin = $res->fetch_assoc();
		$stmt->close();
		if (!$admin || !password_verify($password, $admin['password_hash'])) {
			$loginErrors[] = 'Invalid admin credentials.';
		} else {
			$_SESSION['user_id'] = (int)$admin['id'];
			$_SESSION['username'] = $admin['username'];
			$_SESSION['approved'] = (int)$admin['approved'];
			$_SESSION['is_admin'] = 1;
			header('Location: admin.php');
			exit;
		}
	}
}

// Handle approvals
if (isset($_SESSION['user_id']) && !empty($_SESSION['is_admin'])) {
    // Helper to compute user's net payable (profit-to-date minus payouts)
    function computeUserNetPayable(mysqli $mysqli, int $userId): float {
        $latestApproved = null;
        $stmt = $mysqli->prepare("SELECT t.id, t.investment_amount, t.created_at, p.daily_percent, p.duration_days FROM transactions t JOIN plans p ON p.id = t.plan_id WHERE t.user_id = ? AND t.status = 'approved' ORDER BY t.id DESC LIMIT 1");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $latestApproved = $res->fetch_assoc();
        $stmt->close();
        if (!$latestApproved) {
            return 0.0;
        }
        $amount = (float)$latestApproved['investment_amount'];
        $dailyPercent = (float)$latestApproved['daily_percent'];
        $durationDays = (int)$latestApproved['duration_days'];
        $dailyProfit = $amount > 0 ? ($amount * $dailyPercent) / 100 : 0.0;
        $startDate = new DateTime((new DateTime($latestApproved['created_at']))->format('Y-m-d'));
        $todayDate = new DateTime(date('Y-m-d'));
        $elapsed = (int)$startDate->diff($todayDate)->days + 1;
        if ($elapsed < 1) { $elapsed = 1; }
        if ($elapsed > $durationDays) { $elapsed = $durationDays; }
        $gross = $dailyProfit * $elapsed;
        $stmt2 = $mysqli->prepare('SELECT COALESCE(SUM(amount),0) AS total_paid FROM payouts WHERE user_id = ?');
        $stmt2->bind_param('i', $userId);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        $sumRow = $res2->fetch_assoc();
        $stmt2->close();
        $totalPaid = (float)($sumRow['total_paid'] ?? 0);
        $net = $gross - $totalPaid;
        if ($net < 0) { $net = 0.0; }
        return $net;
    }

	// Lightweight endpoint to fetch net payable for a user (JSON)
	if (isset($_GET['action']) && $_GET['action'] === 'net_payable' && isset($_GET['user_id'])) {
		$userIdParam = (int)$_GET['user_id'];
		$net = computeUserNetPayable($mysqli, $userIdParam);
		header('Content-Type: application/json');
		echo json_encode(['user_id' => $userIdParam, 'net_payable' => $net, 'formatted' => '$' . number_format($net, 2)]);
		exit;
	}

    $payoutErrorMsg = '';
    $payoutSuccessMsg = '';
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_user_id'])) {
		$userId = (int)$_POST['approve_user_id'];
		$stmt = $mysqli->prepare('UPDATE users SET approved = 1 WHERE id = ?');
		$stmt->bind_param('i', $userId);
		$stmt->execute();
		$stmt->close();
		header('Location: admin.php');
		exit;
	}
	// Handle disapprove
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disapprove_user_id'])) {
		$userId = (int)$_POST['disapprove_user_id'];
		$stmt = $mysqli->prepare('UPDATE users SET approved = 0 WHERE id = ?');
		$stmt->bind_param('i', $userId);
		$stmt->execute();
		$stmt->close();
		header('Location: admin.php');
		exit;
	}
}

// Transactions approval and listing (admin only)
if (isset($_SESSION['user_id']) && !empty($_SESSION['is_admin'])) {
    // Handle creating a payout (admin records that they paid a user)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_payout'])) {
        $payoutUserId = (int)($_POST['payout_user_id'] ?? 0);
        $payoutAmountRaw = trim($_POST['payout_amount'] ?? '');
        $payoutAmount = is_numeric($payoutAmountRaw) ? (float)$payoutAmountRaw : 0.0;
        $payoutNote = trim($_POST['payout_note'] ?? '');
        if ($payoutUserId <= 0) {
            $payoutErrorMsg = 'Please select a user.';
        } elseif ($payoutAmount <= 0) {
            $payoutErrorMsg = 'Please enter a payout amount greater than 0.';
        } else {
            $netAvailable = computeUserNetPayable($mysqli, $payoutUserId);
            if ($payoutAmount - $netAvailable > 0.00001) {
                $payoutErrorMsg = 'Payout exceeds user\'s net payable ($' . number_format($netAvailable, 2) . ').';
            } else {
                $stmt = $mysqli->prepare('INSERT INTO payouts (user_id, amount, note, created_by) VALUES (?, ?, ?, ?)');
                $createdBy = (int)$_SESSION['user_id'];
                $stmt->bind_param('idsi', $payoutUserId, $payoutAmount, $payoutNote, $createdBy);
                $stmt->execute();
                $stmt->close();
                $payoutSuccessMsg = 'Payout recorded successfully.';
            }
        }
    }
	// Create a new plan
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_plan'])) {
		$name = trim($_POST['name'] ?? '');
		$amount = (float)($_POST['amount'] ?? 0);
		$dailyPercent = (float)($_POST['daily_percent'] ?? 0);
		$durationDays = (int)($_POST['duration_days'] ?? 0);
		if ($name !== '' && $amount > 0 && $dailyPercent > 0 && $durationDays > 0) {
			$stmt = $mysqli->prepare('INSERT INTO plans (name, amount, daily_percent, duration_days, is_active) VALUES (?, ?, ?, ?, 1)');
			$stmt->bind_param('sddi', $name, $amount, $dailyPercent, $durationDays);
			$stmt->execute();
			$stmt->close();
		}
		header('Location: admin.php');
		exit;
	}
	// Toggle plan active flag
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_plan_id'])) {
		$planId = (int)$_POST['toggle_plan_id'];
		// Flip is_active
		$mysqli->query('UPDATE plans SET is_active = 1 - is_active WHERE id = ' . $planId);
		header('Location: admin.php');
		exit;
	}
	// Approve a transaction: set transactions.status=approved and activate user plan
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_txn_id'])) {
		$txnId = (int)$_POST['approve_txn_id'];
		// Fetch txn to get user and plan
		$stmt = $mysqli->prepare("SELECT t.id, t.user_id, t.plan_id, t.status FROM transactions t WHERE t.id = ? LIMIT 1");
		$stmt->bind_param('i', $txnId);
		$stmt->execute();
		$res = $stmt->get_result();
		$txn = $res->fetch_assoc();
		$stmt->close();
		if ($txn && $txn['status'] === 'pending') {
			// Mark approved
			$stmt = $mysqli->prepare("UPDATE transactions SET status = 'approved' WHERE id = ?");
			$stmt->bind_param('i', $txnId);
			$stmt->execute();
			$stmt->close();
			// Activate user's plan
			$stmt = $mysqli->prepare('UPDATE users SET plan = ? WHERE id = ?');
			$stmt->bind_param('si', $txn['plan_id'], $txn['user_id']);
			$stmt->execute();
			$stmt->close();
		}
		header('Location: admin.php');
		exit;
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin - Alif Invest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <header class="navbar navbar-expand-lg navbar-light bg-light mb-3">
        <div class="container">
            <a class="navbar-brand" href="index.php">Alif Invest</a>
            <nav class="ms-auto">
				<?php if (isset($_SESSION['user_id']) && !empty($_SESSION['is_admin'])): ?>
                    <a href="admin_contacts.php" class="btn btn-sm btn-outline-primary me-2">Contacts</a>
                    <span class="me-2">Admin: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout.php" class="btn btn-sm btn-outline-secondary">Logout</a>
				<?php endif; ?>
			</nav>
		</div>
	</header>

	<main class="section">
		<div class="container">
            <h2 class="mb-3">Admin Panel</h2>
			<?php if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])): ?>
				<p>Please login as admin.</p>
                <?php if (!empty($loginErrors)) { foreach ($loginErrors as $e) { echo '<div class="alert alert-danger">' . htmlspecialchars($e) . '</div>'; } } ?>
                <form method="post" class="contact-form" style="max-width: 420px;">
					<input type="hidden" name="admin_login" value="1" />
					<div>
						<label for="email">Admin Email</label>
                        <input class="form-control" id="email" name="email" type="email" required value="admin@example.com" />
					</div>
					<div>
						<label for="password">Password</label>
                        <input class="form-control" id="password" name="password" type="password" required value="admin123" />
					</div>
                    <button type="submit" class="btn btn-primary mt-2">Login</button>
				</form>
			<?php else: ?>
				<h3>Users</h3>
                <div class="card" style="overflow-x:auto;">
                    <table class="table table-striped mb-0">
						<thead>
							<tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Plan</th>
                                <th>Approved</th>
                                <th>Action</th>
							</tr>
						</thead>
						<tbody>
							<?php
								$res = $mysqli->query('SELECT id, username, email, plan, approved FROM users ORDER BY id DESC');
								while ($row = $res->fetch_assoc()):
							?>
							<tr>
                                <td>#<?php echo (int)$row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo $row['plan'] ? htmlspecialchars(ucfirst($row['plan'])) : '<span class="text-muted">None</span>'; ?></td>
                                <td><?php echo $row['approved'] ? 'Yes' : 'No'; ?></td>
                                <td>
									<?php if (!$row['approved']): ?>
										<form method="post" style="display:inline;">
											<input type="hidden" name="approve_user_id" value="<?php echo (int)$row['id']; ?>" />
                                            <button class="btn btn-sm btn-primary" type="submit">Approve</button>
										</form>
									<?php else: ?>
										<form method="post" style="display:inline;" onsubmit="return confirm('Disapprove this user?');">
											<input type="hidden" name="disapprove_user_id" value="<?php echo (int)$row['id']; ?>" />
                                            <button class="btn btn-sm btn-outline-secondary" type="submit">Disapprove</button>
										</form>
									<?php endif; ?>
								</td>
							</tr>
							<?php endwhile; ?>
						</tbody>
					</table>
				</div>

				<h3 style="margin-top:24px;">Record Payout to User</h3>
				<div class="row" style="margin-bottom:16px;">
					<div class="col-lg-6" style="overflow-x:auto;">
						<?php if (!empty($payoutSuccessMsg)): ?><div class="alert success"><?php echo htmlspecialchars($payoutSuccessMsg); ?></div><?php endif; ?>
						<?php if (!empty($payoutErrorMsg)): ?><div class="alert error"><?php echo htmlspecialchars($payoutErrorMsg); ?></div><?php endif; ?>
						<form method="post" class="contact-form" id="payoutForm" onsubmit="return validatePayoutAmount();">
							<input type="hidden" name="create_payout" value="1" />
							<div>
								<label for="payout_user_id">User</label>
								<select id="payout_user_id" class="form-control" name="payout_user_id" required onchange="fetchNetPayable(this.value);">
									<option value="">Select user</option>
									<?php
										$usersRes = $mysqli->query('SELECT id, username, email FROM users WHERE is_admin = 0 ORDER BY username ASC');
										while ($u = $usersRes->fetch_assoc()):
									?>
										<option value="<?php echo (int)$u['id']; ?>"><?php echo htmlspecialchars($u['username']); ?> (<?php echo htmlspecialchars($u['email']); ?>)</option>
									<?php endwhile; ?>
								</select>
							</div>
							<div>
								<label>Net Payable</label>
								<input id="net_payable_display" type="text" readonly value="$0.00" />
								<input id="net_payable_value" type="hidden" value="0" />
							</div>
							<div>
								<label for="payout_amount">Amount</label>
								<input id="payout_amount" name="payout_amount" type="number" step="0.01" min="0" required />
							</div>
							<div>
								<label for="payout_note">Note (optional)</label>
								<input id="payout_note" name="payout_note" type="text" />
							</div>
							<button type="submit" class="btn primary">Record Payout</button>
						</form>
					</div>
					<div class="col-lg-6"  style="overflow-x:auto;">
						<h4 style="margin-top:0;">Recent Payouts</h4>
						<table style="width:100%; border-collapse: collapse;">
							<thead>
								<tr>
									<th style="text-align:left; padding:8px; border-bottom:1px solid #e2e8f0;">ID</th>
									<th style="text-align:left; padding:8px; border-bottom:1px solid #e2e8f0;">User</th>
									<th style="text-align:left; padding:8px; border-bottom:1px solid #e2e8f0;">Amount</th>
									<th style="text-align:left; padding:8px; border-bottom:1px solid #e2e8f0;">Note</th>
									<th style="text-align:left; padding:8px; border-bottom:1px solid #e2e8f0;">Created</th>
								</tr>
							</thead>
							<tbody>
								<?php
									$qP = "SELECT p.id, p.user_id, p.amount, p.note, p.created_at, u.username, u.email FROM payouts p JOIN users u ON u.id = p.user_id ORDER BY p.id DESC LIMIT 50";
									$resP = $mysqli->query($qP);
									while ($rowP = $resP->fetch_assoc()):
								?>
								<tr>
									<td style="padding:8px;">#<?php echo (int)$rowP['id']; ?></td>
									<td style="padding:8px; "><?php echo htmlspecialchars($rowP['username']); ?> <span style="opacity:0.7;">(<?php echo htmlspecialchars($rowP['email']); ?>)</span></td>
									<td style="padding:8px; ">$<?php echo number_format((float)$rowP['amount'], 2); ?></td>
									<td style="padding:8px; "><?php echo $rowP['note'] ? htmlspecialchars($rowP['note']) : '<span style="opacity:0.7;">—</span>'; ?></td>
									<td style="padding:8px; "><?php echo htmlspecialchars($rowP['created_at']); ?></td>
								</tr>
								<?php endwhile; ?>
							</tbody>
						</table>
					</div>
				</div>

				<script>
				function fetchNetPayable(userId) {
					var display = document.getElementById('net_payable_display');
					var hidden = document.getElementById('net_payable_value');
					if (!userId) { display.value = '$0.00'; hidden.value = '0'; return; }
					fetch('admin.php?action=net_payable&user_id=' + encodeURIComponent(userId))
						.then(function(r){ return r.json(); })
						.then(function(data){
							display.value = data.formatted;
							hidden.value = (data.net_payable || 0).toString();
						})
						.catch(function(){ display.value = '$0.00'; hidden.value = '0'; });
				}

				function validatePayoutAmount() {
					var maxVal = parseFloat(document.getElementById('net_payable_value').value || '0');
					var amtInput = document.getElementById('payout_amount');
					var val = parseFloat(amtInput.value || '0');
					if (isNaN(val) || val <= 0) { alert('Enter a valid payout amount.'); return false; }
					if (val > maxVal + 0.00001) {
						alert('Payout cannot exceed Net Payable (' + document.getElementById('net_payable_display').value + ').');
						return false;
					}
					return true;
				}
				</script>

				<h3 style="margin-top:24px;">Manage Plans</h3>
                <div class="row g-3" style="margin-bottom:16px;">
                    <div class="col-lg-5">
                        <div class="card p-3">
                        <h4 style="margin-top:0;">Create New Plan</h4>
                        <form method="post" class="contact-form">
							<input type="hidden" name="create_plan" value="1" />
							<div>
								<label for="plan_name">Name</label>
                                <input class="form-control" id="plan_name" name="name" type="text" required />
							</div>
							<div>
								<label for="plan_amount">Amount</label>
                                <input class="form-control" id="plan_amount" name="amount" type="number" step="0.01" min="0" required />
							</div>
							<div>
								<label for="plan_daily">Daily %</label>
                                <input class="form-control" id="plan_daily" name="daily_percent" type="number" step="0.01" min="0" required />
							</div>
							<div>
								<label for="plan_duration">Duration (days)</label>
                                <input class="form-control" id="plan_duration" name="duration_days" type="number" min="1" required />
							</div>
                            <button type="submit" class="btn btn-primary mt-2">Create Plan</button>
						</form>
                        </div>
                    </div>
                    <div class="col-lg-7">
                    <div class="card p-3" style="flex:1; overflow-x:auto;">
						<h4 style="margin-top:0;">Existing Plans</h4>
                        <table class="table table-striped mb-0">
							<thead>
								<tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Amount</th>
                                    <th>Daily %</th>
                                    <th>Duration</th>
                                    <th>Active</th>
                                    <th>Action</th>
								</tr>
							</thead>
							<tbody>
								<?php
									$res = $mysqli->query('SELECT id, name, amount, daily_percent, duration_days, is_active FROM plans ORDER BY id DESC');
									while ($row = $res->fetch_assoc()):
								?>
								<tr>
                                <td>#<?php echo (int)$row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td>$<?php echo number_format((float)$row['amount'], 2); ?></td>
                                <td><?php echo number_format((float)$row['daily_percent'], 2); ?>%</td>
                                <td><?php echo (int)$row['duration_days']; ?> days</td>
                                <td><?php echo $row['is_active'] ? 'Yes' : 'No'; ?></td>
                                <td>
										<form method="post" style="display:inline;">
											<input type="hidden" name="toggle_plan_id" value="<?php echo (int)$row['id']; ?>" />
                                        <button class="btn btn-sm btn-outline-secondary" type="submit"><?php echo $row['is_active'] ? 'Deactivate' : 'Activate'; ?></button>
										</form>
									</td>
								</tr>
								<?php endwhile; ?>
							</tbody>
						</table>
                    </div>
                    </div>
				</div>

				<h3 style="margin-top:24px;">Pending Transactions</h3>
                <div class="card" style="overflow-x:auto;">
                    <table class="table table-striped mb-0">
						<thead>
							<tr>
                                <th>Txn ID</th>
                                <th>User</th>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Transaction Ref</th>
                                <th>Created</th>
                                <th>Action</th>
							</tr>
						</thead>
						<tbody>
							<?php
								$q = "SELECT t.id, t.user_id, t.plan_id, t.investment_amount, t.transaction_id, t.status, t.created_at, u.username, u.email FROM transactions t JOIN users u ON u.id = t.user_id WHERE t.status = 'pending' ORDER BY t.id DESC";
								$res = $mysqli->query($q);
								while ($row = $res->fetch_assoc()):
							?>
							<tr>
                                <td>#<?php echo (int)$row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?> <span class="text-muted">(<?php echo htmlspecialchars($row['email']); ?>)</span></td>
                                <td><?php echo htmlspecialchars(ucfirst($row['plan_id'])); ?></td>
                                <td>$<?php echo number_format((float)$row['investment_amount'], 2); ?></td>
                                <td><?php echo $row['transaction_id'] ? htmlspecialchars($row['transaction_id']) : '<span class="text-muted">N/A</span>'; ?></td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <td>
									<form method="post" style="display:inline;" onsubmit="return confirm('Approve this transaction and activate the plan?');">
										<input type="hidden" name="approve_txn_id" value="<?php echo (int)$row['id']; ?>" />
                                        <button class="btn btn-sm btn-primary" type="submit">Approve</button>
									</form>
								</td>
							</tr>
							<?php endwhile; ?>
						</tbody>
					</table>
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



