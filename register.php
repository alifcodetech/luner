<?php require_once __DIR__ . '/db.php'; ?>
<?php
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$username = trim($_POST['username'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';

	if ($username === '' || $email === '' || $password === '') {
		$errors[] = 'All fields are required.';
	}
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errors[] = 'Invalid email address.';
	}
	if (strlen($password) < 6) {
		$errors[] = 'Password must be at least 6 characters.';
	}

	if (!$errors) {
		// Check if username or email exists
		$stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
		$stmt->bind_param('ss', $email, $username);
		$stmt->execute();
		$stmt->store_result();
		if ($stmt->num_rows > 0) {
			$errors[] = 'Username or email already exists.';
		}
		$stmt->close();
	}

	if (!$errors) {
		$hash = password_hash($password, PASSWORD_DEFAULT);
		$approved = 1;
		$now = date('Y-m-d H:i:s');
		$stmt = $mysqli->prepare('INSERT INTO users (username, email, password_hash, approved, created_at) VALUES (?, ?, ?, ?, ?)');
		$stmt->bind_param('sssds', $username, $email, $hash, $approved, $now);
		if ($stmt->execute()) {
			$success = true;
		} else {
			$errors[] = 'Registration failed. Please try again.';
		}
		$stmt->close();
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>Register - Alif Invest</title>
	<link rel="stylesheet" href="style.css" />
</head>
<body>
	<header class="navbar">
		<div class="container nav-content">
			<div class="logo"><a href="index.php">Alif Invest</a></div>
			<nav>
				<a href="login.php" class="btn small">Login</a>
			</nav>
		</div>
	</header>

	<main class="section">
		<div class="container" style="max-width: 480px;">
			<h2>Create Account</h2>
			<?php if ($success): ?>
				<div class="alert success">Registration successful. You can now <a href="login.php">login</a>.</div>
			<?php endif; ?>
			<?php foreach ($errors as $e): ?>
				<div class="alert error"><?php echo htmlspecialchars($e); ?></div>
			<?php endforeach; ?>
			<form method="post" class="contact-form">
				<div>
					<label for="username">Username</label>
					<input id="username" name="username" type="text" required value="<?php echo isset($username) ? htmlspecialchars($username) : '';?>" />
				</div>
				<div>
					<label for="email">Email</label>
					<input id="email" name="email" type="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : '';?>" />
				</div>
				<div>
					<label for="password">Password</label>
					<input id="password" name="password" type="password" required />
				</div>
				<button type="submit" class="btn primary">Register</button>
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


