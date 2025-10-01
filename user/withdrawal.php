<?php 
require_once __DIR__ . '/common.php'; 
require_once __DIR__ . '/../db.php'; 
requireApprovedTransaction($mysqli);

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$userId = (int)$_SESSION['user_id'];

// ---------------- Handle Form Submission ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_number = trim($_POST['account_number']);
    $user_name      = trim($_POST['user_name']);
    $bank_name      = trim($_POST['bank_name']);
    $amount         = (float)$_POST['amount'];

    // Recalculate net payable
    $stmt = $mysqli->prepare("SELECT t.investment_amount, t.created_at, 
                                     p.daily_percent, p.duration_days 
                              FROM transactions t 
                              JOIN plans p ON p.id=t.plan_id 
                              WHERE t.user_id=? AND t.status='approved' 
                              ORDER BY t.id DESC LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $txn = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $net = 0;
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

    if ($amount > $net) {
        $error = "Requested amount cannot exceed Net Payable.";
    } else {
        // Insert into withdrawal
        $stmt = $mysqli->prepare("INSERT INTO withdrawal (user_id, account_number, user_name, bank_name, amount) 
                                  VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssd", $userId, $account_number, $user_name, $bank_name, $amount);

        if ($stmt->execute()) {
            $success = "Withdrawal request submitted successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// ---------------- Fetch Latest Net Payable for Display ----------------
$stmt = $mysqli->prepare("SELECT t.investment_amount, t.created_at, 
                                 p.daily_percent, p.duration_days 
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
<?php include 'sidebar.php'; ?>

<div class="content p-4">
  <h2>Bank Transfer Form</h2>
  
  <?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  
  <div class="row">
    <div class="col-md-6">
      <form id="transferForm" action="" method="post" class="shadow p-4 bg-white rounded">
        
        <div class="mb-3">
          <label for="account_number" class="form-label">Account Number</label>
          <input type="text" class="form-control" id="account_number" name="account_number" required>
        </div>

        <div class="mb-3">
          <label for="user_name" class="form-label">User Name</label>
          <input type="text" class="form-control" id="user_name" name="user_name" required>
        </div>

        <div class="mb-3">
          <label for="bank_name" class="form-label">Bank Name</label>
          <input type="text" class="form-control" id="bank_name" name="bank_name" required>
        </div>

        <div class="mb-3">
          <label for="netpayable" class="form-label">Net Payable (RS)</label>
          <input type="number" disabled class="form-control" id="netpayable" value="<?= $net ?>" min="1">
        </div>

        <div class="mb-3">
          <label for="amount" class="form-label">Requested Amount (RS)</label>
          <input type="number" class="form-control" id="amount" value="<?= $net ?>" name="amount" min="1" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Submit</button>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById("transferForm").addEventListener("submit", function(event) {
    let net = parseFloat(document.getElementById("netpayable").value);
    let amount = parseFloat(document.getElementById("amount").value);

    if (amount > net) {
        alert("Requested amount cannot be greater than Net Payable.");
        event.preventDefault();
    }
});
</script>

</body>
</html>
