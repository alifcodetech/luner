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

<style>
  body {
    background: linear-gradient(135deg, #f5f7fa 0%, #e4edf9 100%);
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    min-height: 100vh;
  }

  .summary-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    border-left: 4px solid transparent;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
    overflow: hidden;
  }

  .summary-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: 4px;
    opacity: 0.2;
  }

  .summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.1);
  }

  /* Premium color accents with gradients */
  .card-plan        { border-left-color: #4e73df; background: linear-gradient(to right, #f0f5ff, #ffffff); }
  .card-gross       { border-left-color: #1cc88a; background: linear-gradient(to right, #f0fff4, #ffffff); }
  .card-paid        { border-left-color: #f6c23e; background: linear-gradient(to right, #fffbeb, #ffffff); }
  .card-net         { border-left-color: #e74a3b; background: linear-gradient(to right, #fff5f5, #ffffff); }

  .summary-title {
    font-size: 0.75rem;
    font-weight: 700;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .summary-value {
    font-size: 1.25rem;
    font-weight: 800;
    color: #212529;
    line-height: 1.3;
  }

  .summary-icon {
    font-size: 1.5rem;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    margin-bottom: 0.75rem;
  }

  /* Status-specific icon colors */
  .card-plan .summary-icon   { background: rgba(78, 115, 223, 0.15); color: #4e73df; }
  .card-gross .summary-icon  { background: rgba(28, 200, 138, 0.15); color: #1cc88a; }
  .card-paid .summary-icon   { background: rgba(246, 194, 62, 0.15); color: #f6c23e; }
  .card-net .summary-icon    { background: rgba(231, 74, 59, 0.15); color: #e74a3b; }

  /* Info banner styling */
  .info-banner {
    background: white;
    border-radius: 16px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    border-left: 3px solid #4e73df;
  }

  .info-banner .title {
    font-weight: 600;
    color: #4e73df;
    margin-bottom: 0.25rem;
  }

  .info-banner .details {
    color: #6c757d;
    font-size: 0.95rem;
  }

  /* Empty state */
  .empty-state {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  }

  .empty-state i {
    font-size: 3rem;
    color: #ced4da;
    margin-bottom: 1rem;
  }

  .empty-state h5 {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    .summary-card {
      padding: 1.25rem;
    }
    
    .summary-value {
      font-size: 1.1rem;
    }
    
    .info-banner {
      padding: 1rem;
    }
  }
</style>

<body>
  <?php include 'sidebar.php'; ?>
  <div class="content p-4">
    <h2 class="fw-bold mb-4 text-dark d-flex align-items-center gap-2">
      <i class="bi bi-graph-up" style="font-size: 1.8rem; color: #1cc88a;"></i>
      Profit Dashboard
    </h2>

    <?php if ($txn): ?>
      <div class="info-banner">
        <div class="title">Active Investment Plan</div>
        <div class="details">
          <strong><?= htmlspecialchars($txn['plan_name']) ?></strong> • 
          Days accrued: <strong><?= $accruedDays ?></strong> / <?= $txn['duration_days'] ?>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-md-6 col-lg-3">
          <div class="summary-card card-plan">
            <div class="summary-icon"><i class="bi bi-award"></i></div>
            <div class="summary-title">Investment Plan</div>
            <div class="summary-value"><?= htmlspecialchars($txn['plan_name']) ?></div>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="summary-card card-gross">
            <div class="summary-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="summary-title">Gross Accrued</div>
            <div class="summary-value">₨ <?= number_format($gross, 2) ?></div>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="summary-card card-paid">
            <div class="summary-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="summary-title">Total Payouts</div>
            <div class="summary-value">₨ <?= number_format($paid, 2) ?></div>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="summary-card card-net">
            <div class="summary-icon"><i class="bi bi-wallet2"></i></div>
            <div class="summary-title">Net Payable</div>
            <div class="summary-value">₨ <?= number_format($net, 2) ?></div>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <i class="bi bi-wallet2"></i>
        <h5>No Active Investment</h5>
        <p class="text-muted">You don't have any approved transactions yet.<br>Start investing to begin earning profits.</p>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>