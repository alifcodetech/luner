<?php require_once __DIR__ . '/../db.php'; ?>
<?php

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'] ?? 'User';
$approved = (int)($_SESSION['approved'] ?? 0);


// Investment summary based on latest transaction joined with plan
$latestTxn = null; {
    $userId = (int)$_SESSION['user_id'];
    $stmt = $mysqli->prepare("SELECT t.id, t.plan_id, t.investment_amount, t.status, t.created_at, p.name AS plan_name, p.daily_percent, p.duration_days FROM transactions t JOIN plans p ON p.id = t.plan_id WHERE t.user_id = ? ORDER BY t.id DESC LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $latestTxn = $res->fetch_assoc();
    $stmt->close();
}



function requireApprovedTransaction($mysqli) {
    $userId = (int)$_SESSION['user_id'];
  
    $stmt = $mysqli->prepare("SELECT id FROM transactions WHERE user_id=? AND status='approved' LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
  
    if ($result->num_rows === 0) {
        echo "<script>alert('You don\'t have any approved transaction.'); window.location.href = 'profit.php';</script>";
    }

}

function dd(...$pre){
    print_r($pre);
    if(end($pre) == 1){
        die('die here');
    }
}