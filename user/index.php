<?php require_once __DIR__ . '/common.php';
requireApprovedTransaction($mysqli);
?>
<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

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
  .card-status      { border-left-color: #4e73df; background: linear-gradient(to right, #f0f5ff, #ffffff); }
  .card-plan        { border-left-color: #6f42c1; background: linear-gradient(to right, #f5f0ff, #ffffff); }
  .card-amount      { border-left-color: #1cc88a; background: linear-gradient(to right, #f0fff4, #ffffff); }
  .card-duration    { border-left-color: #36b9cc; background: linear-gradient(to right, #f0f9ff, #ffffff); }
  .card-daily-pct   { border-left-color: #f6c23e; background: linear-gradient(to right, #fffbeb, #ffffff); }
  .card-daily-profit{ border-left-color: #fd7e14; background: linear-gradient(to right, #fff4e6, #ffffff); }
  .card-total-profit{ border-left-color: #e74a3b; background: linear-gradient(to right, #fff5f5, #ffffff); }

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
  .card-status .summary-icon      { background: rgba(78, 115, 223, 0.15); color: #4e73df; }
  .card-plan .summary-icon        { background: rgba(111, 66, 193, 0.15); color: #6f42c1; }
  .card-amount .summary-icon      { background: rgba(28, 200, 138, 0.15); color: #1cc88a; }
  .card-duration .summary-icon    { background: rgba(54, 185, 204, 0.15); color: #36b9cc; }
  .card-daily-pct .summary-icon   { background: rgba(246, 194, 62, 0.15); color: #f6c23e; }
  .card-daily-profit .summary-icon{ background: rgba(253, 126, 20, 0.15); color: #fd7e14; }
  .card-total-profit .summary-icon{ background: rgba(231, 74, 59, 0.15); color: #e74a3b; }

  /* Withdraw section */
  .withdraw-section {
    background: white;
    border-radius: 16px;
    padding: 1.75rem;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    margin-top: 2rem;
  }

  .withdraw-section h3 {
    font-weight: 700;
    color: #212529;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .withdraw-btn {
    background: linear-gradient(135deg, #4e73df, #224abe);
    border: none;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    font-size: 1rem;
    border-radius: 12px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(78, 115, 223, 0.3);
  }

  .withdraw-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(78, 115, 223, 0.4);
  }

  .withdraw-form .form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
  }

  .withdraw-form .form-control {
    border-radius: 10px;
    padding: 0.75rem;
    border: 1px solid #e1e5eb;
    transition: border-color 0.3s;
  }

  .withdraw-form .form-control:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.15);
  }

  .btn-submit {
    background: linear-gradient(135deg, #1cc88a, #17a673);
    border: none;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    border-radius: 10px;
  }

  .btn-cancel {
    background: #f8f9fa;
    border: 1px solid #e1e5eb;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    border-radius: 10px;
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    .summary-card {
      padding: 1.25rem;
    }
    
    .summary-value {
      font-size: 1.1rem;
    }
    
    .withdraw-section {
      padding: 1.5rem;
    }
  }
</style>

<div class="content p-4">
  <h2 class="fw-bold mb-4 text-dark d-flex align-items-center gap-2">
    <i class="bi bi-wallet2" style="font-size: 1.8rem; color: #4e73df;"></i>
    Investment Portfolio
  </h2>

  <?php if ($latestTxn): ?>
    <?php
      $planName = htmlspecialchars($latestTxn['plan_name']);
      $amount = (float)$latestTxn['investment_amount'];
      $dailyPercent = (float)$latestTxn['daily_percent'];
      $durationDays = (int)$latestTxn['duration_days'];
      $dailyProfit = $amount > 0 ? ($amount * $dailyPercent) / 100 : 0;
      $totalProfit = $dailyProfit * $durationDays;
    ?>

    <div class="row g-4">
      <div class="col-md-6 col-lg-4">
        <div class="summary-card card-status">
          <div class="summary-icon"><i class="bi bi-check2-circle"></i></div>
          <div class="summary-title">Status</div>
          <div class="summary-value"><?= ucfirst(htmlspecialchars($latestTxn['status'])) ?></div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="summary-card card-plan">
          <div class="summary-icon"><i class="bi bi-award"></i></div>
          <div class="summary-title">Investment Plan</div>
          <div class="summary-value"><?= $planName ?></div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="summary-card card-amount">
          <div class="summary-icon"><i class="bi bi-currency-rupee"></i></div>
          <div class="summary-title">Principal Amount</div>
          <div class="summary-value">₨ <?= number_format($amount, 2) ?></div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="summary-card card-duration">
          <div class="summary-icon"><i class="bi bi-calendar-check"></i></div>
          <div class="summary-title">Duration</div>
          <div class="summary-value"><?= $durationDays ?> Days</div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="summary-card card-daily-pct">
          <div class="summary-icon"><i class="bi bi-graph-up-arrow"></i></div>
          <div class="summary-title">Daily Return Rate</div>
          <div class="summary-value"><?= number_format($dailyPercent, 2) ?>%</div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="summary-card card-daily-profit">
          <div class="summary-icon"><i class="bi bi-cash"></i></div>
          <div class="summary-title">Daily Profit</div>
          <div class="summary-value">₨ <?= number_format($dailyProfit, 2) ?></div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4 offset-lg-4">
        <div class="summary-card card-total-profit">
          <div class="summary-icon"><i class="bi bi-piggy-bank"></i></div>
          <div class="summary-title">Total Projected Profit</div>
          <div class="summary-value">₨ <?= number_format($totalProfit, 2) ?></div>
        </div>
      </div>
    </div>

    <!-- Withdraw Section -->
    <div class="withdraw-section">
      <h3 class="fw-bold">
        <i class="bi bi-arrow-down-circle text-primary"></i> Withdraw Funds
      </h3>
      
      <button id="withdrawBtn" class="withdraw-btn mb-3">
        <i class="bi bi-wallet2 me-2"></i>Request Withdrawal
      </button>
      
      <form id="withdrawForm" method="post" action="withdraw.php" class="mt-3 withdraw-form" style="display:none;">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="name" class="form-label">Account Holder Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="account" class="form-label">Account Number</label>
            <input type="text" class="form-control" id="account" name="account" required>
          </div>
        </div>
        <div class="d-flex gap-3 mt-4">
          <button type="submit" class="btn btn-submit">
            <i class="bi bi-check-circle me-2"></i>Submit Withdrawal
          </button>
          <button type="button" class="btn btn-cancel" onclick="toggleWithdrawForm()">
            <i class="bi bi-x-circle me-2"></i>Cancel
          </button>
        </div>
      </form>
    </div>

  <?php else: ?>
    <div class="alert alert-light border rounded-4 d-flex align-items-center p-4" role="alert" style="background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
      <div class="me-3" style="font-size: 2rem; color: #6c757d;">
        <i class="bi bi-info-circle-fill"></i>
      </div>
      <div>
        <h5 class="alert-heading fw-bold mb-1">No Active Investment</h5>
        <p class="mb-0">Start your investment journey today to begin earning returns.</p>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
  function toggleWithdrawForm() {
    const form = document.getElementById('withdrawForm');
    const btn = document.getElementById('withdrawBtn');
    if (form.style.display === 'none' || form.style.display === '') {
      form.style.display = 'block';
      btn.style.display = 'none';
    } else {
      form.style.display = 'none';
      btn.style.display = 'inline-block';
    }
  }

  document.getElementById('withdrawBtn')?.addEventListener('click', toggleWithdrawForm);
</script>