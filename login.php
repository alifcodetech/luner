<?php require_once __DIR__ . '/db.php'; ?>
<?php
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = trim($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';

	if ($email === '' || $password === '') {
		$errors[] = 'Email and password are required.';
	}

	if (!$errors) {
		$stmt = $mysqli->prepare('SELECT id, username, email, password_hash, approved, is_admin FROM users WHERE email = ? LIMIT 1');
		$stmt->bind_param('s', $email);
		$stmt->execute();
		$result = $stmt->get_result();
		$user = $result->fetch_assoc();
		$stmt->close();

		if (!$user || !password_verify($password, $user['password_hash'])) {
			$errors[] = 'Invalid credentials.';
		} else {
			$_SESSION['user_id'] = (int)$user['id'];
			$_SESSION['username'] = $user['username'];
			$_SESSION['approved'] = (int)$user['approved'];
			$_SESSION['is_admin'] = isset($user['is_admin']) ? (int)$user['is_admin'] : 0;

			if (!empty($_SESSION['is_admin'])) {
				header('Location: admin.php');
				exit;
			}

			// Regular user
			header('Location: user/index.php');
			exit;
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>Login - Alif Invest</title>
	<link rel="stylesheet" href="style.css" />
</head>
<body>
	<header class="navbar">
		<div class="container nav-content">
			<div class="logo"><a href="index.php">Alif Invest</a></div>
			<nav>
				<a href="register.php" class="btn small">Register</a>
			</nav>
		</div>
	</header>

	<main class="section">
		<div class="container" style="max-width: 480px;">
			<h2>Login</h2>
			<?php foreach ($errors as $e): ?>
				<div class="alert error"><?php echo htmlspecialchars($e); ?></div>
			<?php endforeach; ?>
			<form method="post" class="contact-form">
				<div>
					<label for="email">Email</label>
					<input id="email" name="email" type="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : '';?>" />
				</div>
				<div>
					<label for="password">Password</label>
					<input id="password" name="password" type="password" required />
				</div>
				<button type="submit" class="btn primary">Login</button>
			</form>
		</div>
	</main>

	<footer class="footer">
		<div class="container">
			<p>© <?php echo date('Y'); ?> Alif Invest.</p>
		</div>
	</footer>
</body>
</html>


