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
    <title>Admin Contacts - Alif Invest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <header class="navbar navbar-expand-lg navbar-light bg-light mb-3">
        <div class="container">
            <a class="navbar-brand" href="index.php">Alif Invest</a>
            <nav class="ms-auto">
                <a href="admin.php" class="btn btn-sm btn-outline-secondary me-2">Admin Dashboard</a>
                <a href="logout.php" class="btn btn-sm btn-outline-secondary">Logout</a>
            </nav>
        </div>
    </header>

    <main class="section">
        <div class="container">
            <h2 class="mb-3">Contact Messages</h2>
            <div class="card" style="overflow-x:auto;">
                <table class="table table-striped mb-0">
                    <thead>
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
                                echo '<tr><td colspan="6" class="text-muted">No messages found.</td></tr>';
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="footer py-3 mt-4 bg-light">
        <div class="container"><p class="mb-0">© <?php echo date('Y'); ?> Alif Invest.</p></div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>


