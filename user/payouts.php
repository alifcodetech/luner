<?php require_once __DIR__ . '/common.php';
requireApprovedTransaction($mysqli);
?>
<?php require_once __DIR__ . '/../db.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$userId=(int)$_SESSION['user_id'];
$res=$mysqli->query("SELECT * FROM payouts WHERE user_id=$userId ORDER BY id DESC");
?>
  <?php include 'header.php'; ?>

  <?php include 'sidebar.php'; ?>
  <div class="content p-4">
    <h2>Admin Payouts</h2>
    <div class="card shadow">
      <div class="card-body">
        <table class="table table-striped">
          <thead class="table-primary"><tr><th>ID</th><th>Amount</th><th>Note</th><th>Date</th></tr></thead>
          <tbody>
          <?php if($res->num_rows): while($r=$res->fetch_assoc()): ?>
            <tr>
              <td>#<?= $r['id'] ?></td>
              <td>R S<?= number_format($r['amount'],2) ?></td>
              <td><?= $r['note']?:'—' ?></td>
              <td><?= $r['created_at'] ?></td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="4" class="text-center text-muted">No payouts yet.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>
