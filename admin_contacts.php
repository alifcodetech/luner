<?php require_once __DIR__ . '/db.php'; ?>
<?php
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Contacts - Luner Trades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css" />
    <style>
        body {
            background-color: #e3f2fd; /* Light blue background */
            color: #0d3b66;
        }
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 220px;
            background-color: #0d47a1; /* Dark blue sidebar */
            color: #fff;
            padding-top: 60px;
        }
        .sidebar a {
            display: block;
            color: #cfd8dc;
            padding: 12px 20px;
            text-decoration: none;
            transition: 0.2s;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #1565c0;
            color: #fff;
        }
        .main-content {
            margin-left: 230px;
            padding: 20px;
        }
        .card-scroll {
            max-height: 500px;
            overflow-y: auto;
        }
        table th, table td {
            vertical-align: middle;
        }
        .navbar-brand img {
            height: 30px;
            margin-right: 10px;
        }
        .card {
            border-radius: 0.75rem;
        }
        .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: #bbdefb; /* light blue stripes */
        }
        .table-dark {
            background-color: #1976d2 !important; /* dark blue header */
        }
        footer {
            background-color: #0d47a1;
            color: #fff;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center mb-4">
            <img src="assets/images/logo/logo.svg" alt="Luner Trades Logo" class="img-fluid">
            <h5 class="mt-2">Luner Trades</h5>
        </div>
        <a href="admin.php">Dashboard</a>
        <a href="contacts.php" class="active">Contacts</a>
        <a href="logout.php">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Contact Messages</h2>
        </div>

        <div class="card shadow-sm">
            <div class="card-body card-scroll p-0">
                <table class="table table-striped mb-0">
                    <thead class="table-dark sticky-top">
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Message</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $res = $mysqli->query('SELECT id, full_name, email, phone, message, created_at FROM contacts ORDER BY id DESC');
                            if ($res) {
                                while ($row = $res->fetch_assoc()):
                        ?>
                        <tr>
                            <td>#<?php echo (int)$row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><a href="mailto:<?php echo htmlspecialchars($row['email']); ?>"><?php echo htmlspecialchars($row['email']); ?></a></td>
                            <td><?php echo $row['phone'] ? htmlspecialchars($row['phone']) : '<span class="text-muted">—</span>'; ?></td>
                            <td style="white-space: pre-wrap; max-width: 520px;"><?php echo htmlspecialchars($row['message']); ?></td>
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        </tr>
                        <?php
                                endwhile;
                            } else {
                                echo '<tr><td colspan="6" class="text-muted text-center">No messages found.</td></tr>';
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <footer class="footer py-3 mt-4 text-center" style="margin-left:230px;">
        <div class="container">
            <p class="mb-0">© <?php echo date('Y'); ?> Luner Trades.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
