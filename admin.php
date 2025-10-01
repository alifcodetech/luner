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
    <title>Admin - Luner Trades</title>

    <style>
        /* ====== Base & Fonts ====== */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
        :root{
            --bg:#f7fbff;
            --card:#ffffff;
            --muted:#6b7280;
            --blue:#1E90FF;
            --blue-dark:#187bcd;
            --radius:12px;
            --shadow: 0 10px 30px rgba(16,24,40,0.06);
            --scroll-bg: #f1f5f9;
        }
        *{box-sizing:border-box}
        body{
            margin:0;
            font-family:"Poppins",system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial;
            background:var(--bg);
            color:#111827;
            -webkit-font-smoothing:antialiased;
            -moz-osx-font-smoothing:grayscale;
        }

        /* ====== Layout ====== */
        .app {
            display:flex;
            min-height:100vh;
        }
        /* Sidebar (fixed) */
        .sidebar {
            width: 260px;
            background: #fff;
            border-right: 1px solid rgba(15,23,42,0.04);
            padding: 24px 18px;
            display:flex;
            flex-direction:column;
            gap:18px;
            position:fixed;
            top:0;
            left:0;
            height:100vh;
            overflow:auto;
        }
        .brand {
            display:flex;
            align-items:center;
            gap:12px;
            margin-bottom:6px;
        }
        .brand img { width:42px; height:42px; object-fit:contain; }
        .brand .title {
            font-weight:700;
            color:var(--blue);
            font-size:1.05rem;
            letter-spacing:0.2px;
        }

        .nav {
            display:flex;
            flex-direction:column;
            gap:8px;
            margin-top:6px;
        }
        .nav a {
            display:flex;
            gap:10px;
            align-items:center;
            padding:10px 12px;
            text-decoration:none;
            color:#0f1724;
            border-radius:10px;
            font-weight:600;
            font-size:0.95rem;
        }
        .nav a.active, .nav a:hover {
            background: linear-gradient(90deg, rgba(30,144,255,0.08), rgba(30,144,255,0.03));
            color:var(--blue);
        }

        /* Content area (adjusted for fixed sidebar) */
        .content {
            margin-left:260px;
            flex:1;
            padding:24px;
            min-height:100vh;
        }

        /* Top bar */
        .topbar {
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:12px;
            margin-bottom:18px;
        }
        .topbar .actions {
            display:flex;
            align-items:center;
            gap:12px;
        }
        .avatar {
            background:linear-gradient(180deg,#e6f0ff,#fff);
            border:1px solid rgba(30,144,255,0.08);
            padding:6px 10px;
            border-radius:999px;
            display:flex;
            align-items:center;
            gap:8px;
            font-weight:600;
        }
        .btn-ghost {
            background:transparent;
            border:1px solid rgba(15,23,42,0.06);
            padding:8px 12px;
            border-radius:10px;
            cursor:pointer;
        }
        .btn-primary {
            background:var(--blue);
            color:#fff;
            border:none;
            padding:10px 14px;
            border-radius:10px;
            cursor:pointer;
            font-weight:700;
        }
        .btn-primary:hover { background:var(--blue-dark); }

        /* Cards and tables */
        .card {
            background:var(--card);
            padding:18px;
            border-radius:var(--radius);
            box-shadow:var(--shadow);
            margin-bottom:18px;
        }
        h2.section-title {
            margin:0 0 12px 0;
            font-size:1.25rem;
            color:#0f1724;
        }

        table {
            width:100%;
            border-collapse:collapse;
            font-size:0.95rem;
        }
        table thead th {
            text-align:left;
            padding:10px 12px;
            color:var(--muted);
            font-weight:700;
            border-bottom:1px solid #eef2f7;
        }
        table tbody td {
            padding:10px 12px;
            border-bottom:1px solid #f1f5f9;
            vertical-align:middle;
        }
        .muted { color:var(--muted); font-weight:500; font-size:0.92rem; }

        /* Scrollable list containers (fixed height) */
        .scroll-list {
            max-height: 320px;
            overflow: auto;
            padding-right: 6px;
            border-radius:8px;
            background: linear-gradient(180deg, #fff, #fbfdff);
        }
        /* custom scrollbar (webkit) */
        .scroll-list::-webkit-scrollbar { width: 10px; }
        .scroll-list::-webkit-scrollbar-track { background: transparent; }
        .scroll-list::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, rgba(30,144,255,0.12), rgba(30,144,255,0.18));
            border-radius: 10px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        .list-item { padding:10px 12px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; }
        .list-item:last-child { border-bottom: none; }

        .list-footer { display:flex; justify-content:flex-end; padding:10px; gap:8px; }

        /* Form inputs */
        .form-row { margin-bottom:12px; }
        .form-row label { display:block; margin-bottom:6px; font-weight:600; color:#111827; }
        .form-row input[type="text"],
        .form-row input[type="email"],
        .form-row input[type="number"],
        .form-row select {
            width:100%;
            padding:10px 12px;
            border-radius:8px;
            border:1px solid #e6edf6;
            background:#fbfdff;
            font-size:1rem;
        }

        /* Responsive */
        @media (max-width: 1100px) {
            .sidebar { width: 220px; }
            .content { margin-left:220px; padding:18px; }
        }
        @media (max-width: 880px) {
            .sidebar { display:none; position:relative; width:100%; height:auto; }
            .content { margin-left:0; padding:16px; }
        }

        /* Small helpers */
        .row { display:flex; gap:12px; flex-wrap:wrap; }
        .col { flex:1; min-width:220px; }
        .small { font-size:0.85rem; color:var(--muted); }
        .text-right { text-align:right; }
        .pill { padding:6px 10px;border-radius:999px;background:#f1f8ff;color:var(--blue); font-weight:700; display:inline-block; }
        .table-actions form { display:inline-block; margin-right:6px; }
        .alert { padding:10px;border-radius:8px;margin-bottom:12px; }
        .alert.error { background:#fff0f0; color:#b91c1c; }
        .alert.success { background:#ecfdf5; color:#047857; }
    </style>
</head>
<body>
<div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar" role="navigation" aria-label="Main navigation">
        <div class="brand">
            <img src="assets/images/logo/logo-2.svg" alt="Luner Trades Logo" />
            <div>
                <div class="title">Luner Trades</div>
                <div class="small">Admin Panel</div>
            </div>
        </div>

        <nav class="nav" id="mainNav">
            <a href="#dashboard" class="active">Dashboard</a>
            <a href="#users">Users</a>
            <a href="#payouts">Payouts</a>
            <a href="#plans">Plans</a>
            <a href="#transactions">Transactions</a>
            <a href="admin_contacts.php">Contacts</a>
        </nav>

        <div style="margin-top:auto;">
            <div class="small muted">Signed in as</div>
            <div style="margin-top:6px; display:flex; gap:8px; align-items:center;">
                <div class="avatar"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username'][0]) : 'A'; ?></div>
                <div>
                    <div style="font-weight:700;"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin'; ?></div>
                    <div class="small muted">Administrator</div>
                </div>
            </div>
            <div style="margin-top:14px;">
                <a href="logout.php" class="btn-ghost" style="display:inline-block;width:100%;">Logout</a>
            </div>
        </div>
    </aside>

    <!-- CONTENT -->
    <main class="content">
        <div class="topbar">
            <div>
                <h1 style="margin:0;font-size:1.4rem;">Dashboard</h1>
                <div class="small muted">Overview & administrative actions</div>
            </div>
            <div class="actions">
                <div class="small muted">Welcome, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin'; ?></div>
                <button class="btn-primary" onclick="location.href='admin.php'">Refresh</button>
            </div>
        </div>

        <!-- STATS -->
        <div class="row" style="margin-bottom:18px;">
            <div class="col card" style="min-width:160px;">
                <div class="small muted">Total Users</div>
                <?php $r = $mysqli->query("SELECT COUNT(*) AS c FROM users"); $cRow = $r->fetch_assoc(); ?>
                <div style="font-size:1.4rem; margin-top:8px; font-weight:800;"><?php echo (int)$cRow['c']; ?></div>
            </div>
            <div class="col card" style="min-width:160px;">
                <div class="small muted">Active Plans</div>
                <?php $r2 = $mysqli->query("SELECT COUNT(*) AS c FROM plans WHERE is_active = 1"); $cRow2 = $r2->fetch_assoc(); ?>
                <div style="font-size:1.4rem; margin-top:8px; font-weight:800;"><?php echo (int)$cRow2['c']; ?></div>
            </div>
            <div class="col card" style="min-width:160px;">
                <div class="small muted">Total Payouts</div>
                <?php $r3 = $mysqli->query("SELECT COALESCE(SUM(amount),0) AS total FROM payouts"); $cRow3 = $r3->fetch_assoc(); ?>
                <div style="font-size:1.4rem; margin-top:8px; font-weight:800;">$<?php echo number_format((float)$cRow3['total'], 2); ?></div>
            </div>
            <div class="col card" style="min-width:160px;">
                <div class="small muted">Pending Transactions</div>
                <?php $r4 = $mysqli->query("SELECT COUNT(*) AS c FROM transactions WHERE status='pending'"); $cRow4 = $r4->fetch_assoc(); ?>
                <div style="font-size:1.4rem; margin-top:8px; font-weight:800;"><?php echo (int)$cRow4['c']; ?></div>
            </div>
        </div>

        <?php if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])): ?>
            <div id="users" class="card" style="max-width:560px;">
                <h2 class="section-title">Admin Login</h2>
                <p class="small muted">Please login as admin.</p>
                <?php if (!empty($loginErrors)) { foreach ($loginErrors as $e) { echo '<div class="alert error">' . htmlspecialchars($e) . '</div>'; } } ?>
                <form method="post" style="max-width:480px;">
                    <input type="hidden" name="admin_login" value="1" />
                    <div class="form-row">
                        <label for="email">Admin Email</label>
                        <input id="email" name="email" type="email" required value="admin@example.com" />
                    </div>
                    <div class="form-row">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" required value="admin123" />
                    </div>
                    <div style="margin-top:8px;">
                        <button type="submit" class="btn-primary">Login</button>
                    </div>
                </form>
            </div>
        <?php else: ?>

            <!-- USERS (table) -->
            <section id="users" class="card" aria-labelledby="users-title">
                <h2 id="users-title" class="section-title">Users</h2>
                <div style="overflow:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:80px;">ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Plan</th>
                                <th>Approved</th>
                                <th style="width:180px;">Action</th>
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
                                <td><?php echo $row['plan'] ? htmlspecialchars(ucfirst($row['plan'])) : '<span class="muted">None</span>'; ?></td>
                                <td><?php echo $row['approved'] ? 'Yes' : 'No'; ?></td>
                                <td class="table-actions">
                                    <?php if (!$row['approved']): ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="approve_user_id" value="<?php echo (int)$row['id']; ?>" />
                                            <button class="btn-primary" type="submit">Approve</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Disapprove this user?');">
                                            <input type="hidden" name="disapprove_user_id" value="<?php echo (int)$row['id']; ?>" />
                                            <button class="btn-ghost" type="submit">Disapprove</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- PAYOUTS (scrollable recent payouts) -->
            <section id="payouts" class="card" aria-labelledby="payouts-title">
                <h2 id="payouts-title" class="section-title">Recent Payouts</h2>
                <div class="row">
                    <div class="col">
                        <div class="scroll-list">
                            <?php
                                $qP = "SELECT p.id, p.user_id, p.amount, p.note, p.created_at, u.username, u.email FROM payouts p JOIN users u ON u.id = p.user_id ORDER BY p.id DESC LIMIT 50";
                                $resP = $mysqli->query($qP);
                                while ($rowP = $resP->fetch_assoc()):
                            ?>
                                <div class="list-item">
                                    <div>
                                        <strong>#<?php echo (int)$rowP['id']; ?></strong>
                                        <div class="small muted"><?php echo htmlspecialchars($rowP['username']); ?> (<?php echo htmlspecialchars($rowP['email']); ?>)</div>
                                    </div>
                                    <div style="text-align:right">
                                        <div style="font-weight:800;">$<?php echo number_format((float)$rowP['amount'], 2); ?></div>
                                        <div class="small muted"><?php echo htmlspecialchars($rowP['created_at']); ?></div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <div class="list-footer">
                            <a href="#payouts" class="btn-ghost">View All</a>
                        </div>
                    </div>

                    <div class="col">
                        <h4 style="margin-top:0;">Record Payout to User</h4>
                        <?php if (!empty($payoutSuccessMsg)): ?><div class="alert success"><?php echo htmlspecialchars($payoutSuccessMsg); ?></div><?php endif; ?>
                        <?php if (!empty($payoutErrorMsg)): ?><div class="alert error"><?php echo htmlspecialchars($payoutErrorMsg); ?></div><?php endif; ?>
                        <form method="post" id="payoutForm" onsubmit="return validatePayoutAmount();">
                            <input type="hidden" name="create_payout" value="1" />
                            <div class="form-row">
                                <label for="payout_user_id">User</label>
                                <select id="payout_user_id" name="payout_user_id" onchange="fetchNetPayable(this.value)" required>
                                    <option value="">Select user</option>
                                    <?php
                                        $usersRes = $mysqli->query('SELECT id, username, email FROM users WHERE is_admin = 0 ORDER BY username ASC');
                                        while ($u = $usersRes->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo (int)$u['id']; ?>"><?php echo htmlspecialchars($u['username']); ?> (<?php echo htmlspecialchars($u['email']); ?>)</option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-row">
                                <label>Net Payable</label>
                                <input id="net_payable_display" type="text" readonly value="$0.00" />
                                <input id="net_payable_value" type="hidden" value="0" />
                            </div>

                            <div class="form-row">
                                <label for="payout_amount">Amount</label>
                                <input id="payout_amount" name="payout_amount" type="number" step="0.01" min="0" required />
                            </div>

                            <div class="form-row">
                                <label for="payout_note">Note (optional)</label>
                                <input id="payout_note" name="payout_note" type="text" />
                            </div>

                            <div style="margin-top:8px;">
                                <button type="submit" class="btn-primary">Record Payout</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            <!-- PLANS (create + scrollable list of existing plans) -->
            <section id="plans" class="card" aria-labelledby="plans-title">
                <h2 id="plans-title" class="section-title">Manage Plans</h2>
                <div class="row">
                    <div class="col card" style="padding:14px;">
                        <h4 style="margin-top:0;">Create New Plan</h4>
                        <form method="post">
                            <input type="hidden" name="create_plan" value="1" />
                            <div class="form-row">
                                <label for="plan_name">Name</label>
                                <input id="plan_name" name="name" type="text" required />
                            </div>
                            <div class="form-row">
                                <label for="plan_amount">Amount</label>
                                <input id="plan_amount" name="amount" type="number" step="0.01" min="0" required />
                            </div>
                            <div class="form-row">
                                <label for="plan_daily">Daily %</label>
                                <input id="plan_daily" name="daily_percent" type="number" step="0.01" min="0" required />
                            </div>
                            <div class="form-row">
                                <label for="plan_duration">Duration (days)</label>
                                <input id="plan_duration" name="duration_days" type="number" min="1" required />
                            </div>
                            <div style="margin-top:8px;">
                                <button type="submit" class="btn-primary">Create Plan</button>
                            </div>
                        </form>
                    </div>

                    <div class="col card" style="padding:0; overflow:hidden;">
                        <h4 style="margin:14px;">Existing Plans</h4>
                        <div class="scroll-list" style="padding:0;">
                            <table style="width:100%; border-collapse:collapse;">
                                <thead>
                                    <tr>
                                        <th style="padding:10px 12px;">ID</th>
                                        <th style="padding:10px 12px;">Name</th>
                                        <th style="padding:10px 12px;">Amount</th>
                                        <th style="padding:10px 12px;">Daily %</th>
                                        <th style="padding:10px 12px;">Duration</th>
                                        <th style="padding:10px 12px;">Active</th>
                                        <th style="padding:10px 12px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $res = $mysqli->query('SELECT id, name, amount, daily_percent, duration_days, is_active FROM plans ORDER BY id DESC');
                                        while ($row = $res->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td style="padding:10px 12px;">#<?php echo (int)$row['id']; ?></td>
                                        <td style="padding:10px 12px;"><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td style="padding:10px 12px;">$<?php echo number_format((float)$row['amount'], 2); ?></td>
                                        <td style="padding:10px 12px;"><?php echo number_format((float)$row['daily_percent'], 2); ?>%</td>
                                        <td style="padding:10px 12px;"><?php echo (int)$row['duration_days']; ?> days</td>
                                        <td style="padding:10px 12px;"><?php echo $row['is_active'] ? 'Yes' : 'No'; ?></td>
                                        <td style="padding:10px 12px;">
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="toggle_plan_id" value="<?php echo (int)$row['id']; ?>" />
                                                <button class="btn-ghost" type="submit"><?php echo $row['is_active'] ? 'Deactivate' : 'Activate'; ?></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="list-footer">
                            <a href="#plans" class="btn-ghost">View All</a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- PENDING TRANSACTIONS (scrollable) -->
            <section id="transactions" class="card" aria-labelledby="txn-title">
                <h2 id="txn-title" class="section-title">Pending Transactions</h2>
                <div class="scroll-list">
                    <table>
                        <thead>
                            <tr>
                                <th style="padding:10px 12px;">Txn ID</th>
                                <th style="padding:10px 12px;">User</th>
                                <th style="padding:10px 12px;">Plan</th>
                                <th style="padding:10px 12px;">Amount</th>
                                <th style="padding:10px 12px;">Transaction Ref</th>
                                <th style="padding:10px 12px;">Created</th>
                                <th style="padding:10px 12px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $q = "SELECT t.id, t.user_id, t.plan_id, t.investment_amount, t.transaction_id, t.status, t.created_at, u.username, u.email FROM transactions t JOIN users u ON u.id = t.user_id WHERE t.status = 'pending' ORDER BY t.id DESC";
                                $res = $mysqli->query($q);
                                while ($row = $res->fetch_assoc()):
                            ?>
                            <tr>
                                <td style="padding:10px 12px;">#<?php echo (int)$row['id']; ?></td>
                                <td style="padding:10px 12px;"><?php echo htmlspecialchars($row['username']); ?> <div class="small muted">(<?php echo htmlspecialchars($row['email']); ?>)</div></td>
                                <td style="padding:10px 12px;"><?php echo htmlspecialchars(ucfirst($row['plan_id'])); ?></td>
                                <td style="padding:10px 12px;">$<?php echo number_format((float)$row['investment_amount'], 2); ?></td>
                                <td style="padding:10px 12px;"><?php echo $row['transaction_id'] ? htmlspecialchars($row['transaction_id']) : '<span class="muted">N/A</span>'; ?></td>
                                <td style="padding:10px 12px;"><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <td style="padding:10px 12px;">
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Approve this transaction and activate the plan?');">
                                        <input type="hidden" name="approve_txn_id" value="<?php echo (int)$row['id']; ?>" />
                                        <button class="btn-primary" type="submit">Approve</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:10px; text-align:right;">
                    <a href="#transactions" class="btn-ghost">View All</a>
                </div>
            </section>

        <?php endif; ?>

        <footer style="margin-top:18px;">
            <div class="small muted">© <?php echo date('Y'); ?> Luner Trades.</div>
        </footer>
    </main>
</div>

<!-- JS: keep existing net_payable + validation logic -->
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

// Scroll nav linking
document.querySelectorAll('.nav a').forEach(function(a){
    a.addEventListener('click', function(e){
        e.preventDefault();
        document.querySelectorAll('.nav a').forEach(x=>x.classList.remove('active'));
        a.classList.add('active');
        var target = a.getAttribute('href');
        if (target && target.startsWith('#')) {
            var el = document.querySelector(target);
            if (el) {
                el.scrollIntoView({behavior:'smooth', block:'start'});
            }
        } else {
            window.location.href = a.href;
        }
    });
});
</script>

</body>
</html>
