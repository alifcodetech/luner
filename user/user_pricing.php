<?php
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Config: Admin account info to show for payment
$adminAccountName = 'Luner Traders.';
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
    header('Location: index.php');
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

<?php include 'header.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    .box-right {
        padding: 30px 25px;
        background-color: white;
        border-radius: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .box-left {
        padding: 20px 20px;
        background-color: white;
        border-radius: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .textmuted {
        color: #7a7a7a;
    }

    .bg-green {
        background-color: #d4f8f2;
        color: #06e67a;
        padding: 3px 10px;
        display: inline;
        border-radius: 25px;
        font-size: 11px;
    }

    .p-blue {
        font-size: 14px;
        color: #1976d2;
    }

    .fas.fa-circle {
        font-size: 12px;
    }

    .p-org {
        font-size: 14px;
        color: #fbc02d;
    }

    .h7 {
        font-size: 15px;
    }

    .h8 {
        font-size: 12px;
    }

    .bg-blue {
        background-color: #dfe9fc9c;
        border-radius: 5px;
    }

    .form-control {
        box-shadow: none !important;
        border: 1px solid #ddd;
        padding: 8px 12px;
        font-size: 14px;
    }

    input::placeholder {
        font-size: 14px;
    }

    .btn.btn-primary {
        box-shadow: none;
        height: 40px;
        padding: 11px;
        background-color: #1976d2;
        border: none;
    }

    .bg.btn.btn-primary {
        background-color: transparent;
        border: none;
        color: #1976d2;
    }

    .bg.btn.btn-primary:hover {
        color: #539ee9;
    }

    @media (max-width: 768px) {
        .ps-md-5 {
            padding-left: 0 !important;
        }
        .box-left, .box-right {
            margin-bottom: 20px;
        }
    }
</style>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="content p-4">
        <div class="container">
            <h2 class="mb-4">Complete Your Payment</h2>

            <?php if ($successMsg): ?>
                <div class="alert alert-success mb-4"><?php echo htmlspecialchars($successMsg); ?></div>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
                <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($errorMsg); ?></div>
            <?php endif; ?>

            <div class="row m-0">
                <!-- Left Column: Payment Instructions & Form -->
                <div class="col-md-7 col-12 px-0 pe-md-3">
                    <div class="box-right mb-4">
                        <p class="ps-3 textmuted fw-bold h6 mb-0">PAY TO ADMIN ACCOUNT</p>
                        <ul class="mb-0 ps-3" style="list-style: none; padding-left: 0;">
                            <li><strong>Account Name:</strong> <?php echo htmlspecialchars($adminAccountName); ?></li>
                            <li><strong>Account Number:</strong> <?php echo htmlspecialchars($adminAccountNumber); ?></li>
                            <li><strong>Bank:</strong> <?php echo htmlspecialchars($adminBank); ?></li>
                        </ul>
                        <p class="mt-3 textmuted h8">Selected Plan: <strong><?php echo htmlspecialchars($transaction['plan_name']); ?></strong></p>
                    </div>

                    <div class="box-right">
                        <div class="d-flex mb-2">
                            <p class="fw-bold">Submit Transaction Details</p>
                        </div>
                        <form method="post">
                            <input type="hidden" name="submit_txn" value="1" />
                            <input type="hidden" name="investment_amount" value="<?php echo isset($transaction['plan_amount']) ? number_format((float)$transaction['plan_amount'], 2, '.', '') : ''; ?>" />

                            <div class="mb-3">
                                <p class="textmuted h8">Investment Amount</p>
                                <input class="form-control" type="text" disabled value="<?php echo isset($transaction['plan_amount']) ? '$' . number_format((float)$transaction['plan_amount'], 2) : ''; ?>" />
                            </div>

                            <div class="mb-3">
                                <p class="textmuted h8">Transaction ID</p>
                                <input class="form-control" name="transaction_id" type="text" required value="<?php echo htmlspecialchars($transaction['transaction_id'] ?: ''); ?>" placeholder="Enter your bank transaction ID" />
                            </div>

                            <button type="submit" class="btn btn-primary d-block w-100 h8">
                                SUBMIT DETAILS 
                                <span class="fas fa-arrow-right ms-2"></span>
                            </button>
                        </form>
                    </div>

                    <p class="mt-3 textmuted h8">Status: <strong><?php echo htmlspecialchars(ucfirst($transaction['status'])); ?></strong></p>
                </div>

                <!-- Right Column: Invoice Preview -->
                <div class="col-md-5 col-12 ps-md-5 p-0">
                    <div class="box-left">
                        <p class="textmuted h8">Invoice</p>
                        <p class="fw-bold h7">Your Investment</p>
                        <p class="textmuted h8 mb-2">Plan: <?php echo htmlspecialchars($transaction['plan_name']); ?></p>

                        <div class="h8">
                            <div class="row m-0 border mb-3">
                                <div class="col-8 h8 pe-0 ps-2">
                                    <p class="textmuted py-2">Description</p>
                                    <span class="d-block py-2 border-bottom">Investment Plan</span>
                                </div>
                                <div class="col-4 p-0 text-center">
                                    <p class="textmuted p-2">Amount</p>
                                    <span class="d-block py-2 border-bottom">
                                        <span class="fas fa-dollar-sign"></span>
                                        <?php echo number_format((float)$transaction['plan_amount'], 2); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex h7 mb-2">
                                <p>Total Amount</p>
                                <p class="ms-auto">
                                    <span class="fas fa-dollar-sign"></span>
                                    <?php echo number_format((float)$transaction['plan_amount'], 2); ?>
                                </p>
                            </div>
                            <div class="h8 mb-3">
                                <p class="textmuted">Please complete payment to activate your investment plan.</p>
                            </div>
                        </div>

                        <div>
                            <p class="h7 fw-bold mb-1">Payment Instructions</p>
                            <p class="textmuted h8 mb-2">Transfer the amount to the admin bank account shown on the left, then submit your transaction ID.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer py-3 mt-4 bg-light">
        <div class="container">
            <p class="mb-0">© <?php echo date('Y'); ?> Alif Invest.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>