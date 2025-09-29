<?php require_once __DIR__ . '/db.php'; ?>
<?php
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php');
	exit;
}

// Config: Admin account info to show for payment
$adminAccountName = 'Alif Invest Ltd.';
$adminAccountNumber = '1234567890';
$adminBank = 'Bank of Example';

$userId = (int)$_SESSION['user_id'];
$tid = isset($_GET['tid']) ? (int)$_GET['tid'] : 0;

// Load transaction to ensure ownership
$transaction = null;
if ($tid > 0) {
	$stmt = $mysqli->prepare('SELECT t.id, t.user_id, t.plan_id, t.transaction_id, t.status, t.created_at, p.name AS plan_name, p.amount AS plan_amount FROM transactions t JOIN plans p ON p.id = t.plan_id WHERE t.id = ? AND t.user_id = ? LIMIT 1');
	$stmt->bind_param('ii', $tid, $userId);
	$stmt->execute();
	$res = $stmt->get_result();
	$transaction = $res->fetch_assoc();
	$stmt->close();
}

if (!$transaction) {
	// fallback: latest pending transaction for user
	$stmt = $mysqli->prepare("SELECT t.id, t.user_id, t.plan_id, t.transaction_id, t.status, t.created_at, p.name AS plan_name, p.amount AS plan_amount FROM transactions t JOIN plans p ON p.id = t.plan_id WHERE t.user_id = ? AND t.status = 'pending' ORDER BY t.id DESC LIMIT 1");
	$stmt->bind_param('i', $userId);
	$stmt->execute();
	$res = $stmt->get_result();
	$transaction = $res->fetch_assoc();
	$stmt->close();
	if ($transaction) {
		$tid = (int)$transaction['id'];
	}
}

if (!$transaction) {
	// No pending transaction, redirect back
	header('Location: user.php');
	exit;
}

// Handle transaction ID submission
$successMsg = '';
$errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_txn'])) {
	$enteredTxnId = trim($_POST['transaction_id'] ?? '');
	$enteredAmountRaw = trim($_POST['investment_amount'] ?? '');
	$enteredAmount = is_numeric($enteredAmountRaw) ? (float)$enteredAmountRaw : 0.0;
	if ($enteredTxnId === '') {
		$errorMsg = 'Please enter your transaction ID.';
	} elseif ($enteredAmount <= 0) {
		$errorMsg = 'Please enter a valid investment amount greater than 0.';
	} else {
		$stmt = $mysqli->prepare('UPDATE transactions SET transaction_id = ?, investment_amount = ? WHERE id = ? AND user_id = ?');
		$stmt->bind_param('sdii', $enteredTxnId, $enteredAmount, $tid, $userId);
		$stmt->execute();
		$stmt->close();
		$successMsg = 'Details submitted. Please wait for admin approval.';
		// refresh transaction data
		$stmt = $mysqli->prepare('SELECT t.id, t.user_id, t.plan_id, t.transaction_id, t.status, t.created_at, p.name AS plan_name, p.amount AS plan_amount FROM transactions t JOIN plans p ON p.id = t.plan_id WHERE t.id = ? AND t.user_id = ? LIMIT 1');
		$stmt->bind_param('ii', $tid, $userId);
		$stmt->execute();
		$res = $stmt->get_result();
		$transaction = $res->fetch_assoc();
		$stmt->close();
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Payment Instructions - Alif Invest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <header class="navbar navbar-expand-lg navbar-light bg-light mb-3">
        <div class="container">
            <a class="navbar-brand" href="index.php">Alif Invest</a>
            <nav class="ms-auto">
                <a href="user.php" class="btn btn-sm btn-outline-secondary me-2">Back to Dashboard</a>
                <a href="logout.php" class="btn btn-sm btn-outline-secondary">Logout</a>
            </nav>
        </div>
    </header>

	<main class="section">
		<div class="container">
            <h2 class="mb-3">Complete Your Payment</h2>
            <div class="card p-3 mb-3">
                <p class="mb-1"><strong>Selected Plan:</strong> <?php echo htmlspecialchars($transaction['plan_name']); ?></p>
                <p class="mb-2">Please transfer the plan amount to the admin account below, then submit your transaction ID.</p>
                <ul class="mb-0">
                    <li><strong>Account Name:</strong> <?php echo htmlspecialchars($adminAccountName); ?></li>
                    <li><strong>Account Number:</strong> <?php echo htmlspecialchars($adminAccountNumber); ?></li>
                    <li><strong>Bank:</strong> <?php echo htmlspecialchars($adminBank); ?></li>
                </ul>
            </div>

            <?php if ($successMsg): ?><div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div><?php endif; ?>
            <?php if ($errorMsg): ?><div class="alert alert-danger"><?php echo htmlspecialchars($errorMsg); ?></div><?php endif; ?>

            <div class="card p-3">
                <h3 class="mt-0">Submit Transaction ID</h3>
                <form method="post">
					<input type="hidden" name="submit_txn" value="1" />
					<div>
						<label for="investment_amount">Investment Amount</label>
                        <input class="form-control" id="investment_amount" name="investment_amount" type="number" step="0.01" min="0" required value="<?php echo isset($transaction['plan_amount']) ? number_format((float)$transaction['plan_amount'], 2, '.', '') : ''; ?>" />
					</div>
					<div>
						<label for="transaction_id">Transaction ID</label>
                        <input class="form-control" id="transaction_id" name="transaction_id" type="text" required value="<?php echo htmlspecialchars($transaction['transaction_id'] ?: ''); ?>" />
					</div>
                    <button type="submit" class="btn btn-primary mt-2">Submit</button>
				</form>
			</div>

			<p style="opacity:0.8;">Status: <strong><?php echo htmlspecialchars(ucfirst($transaction['status'])); ?></strong></p>
		</div>
	</main>

    <footer class="footer py-3 mt-4 bg-light">
        <div class="container"><p class="mb-0">© <?php echo date('Y'); ?> Alif Invest.</p></div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>



