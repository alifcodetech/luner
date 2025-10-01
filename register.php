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
  <title>Register - Luner Trades</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    body {
      background: #f8faff;
      color: #333;
    }
    header {
      background: #fff;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      padding: 1rem 3rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    header .logo img {
      height: 38px;
    }
    header nav a {
      background: #1E90FF;
      color: #fff;
      text-decoration: none;
      padding: 0.6rem 1.4rem;
      border-radius: 8px;
      font-size: 0.95rem;
      transition: 0.3s;
    }
    header nav a:hover {
      background: #187bcd;
    }

    .auth-container {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 90vh;
      background: url('assets/images/invest-bg.jpg') center/cover no-repeat;
      position: relative;
    }
    .auth-container::before {
      content: "";
      position: absolute;
      inset: 0;
      background: rgba(255,255,255,0.92);
      backdrop-filter: blur(4px);
    }

    .auth-card {
      position: relative;
      z-index: 2;
      background: #fff;
      padding: 2.8rem;
      width: 100%;
      max-width: 440px;
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.08);
      text-align: center;
    }
    .auth-card img.logo {
      width: 90px;
      margin-bottom: 1.2rem;
    }
    .auth-card h2 {
      font-size: 1.9rem;
      margin-bottom: 1.8rem;
      color: #1E90FF;
    }
    form {
      text-align: left;
    }
    label {
      display: block;
      margin-bottom: 0.4rem;
      font-weight: 500;
      font-size: 0.95rem;
    }
    input {
      width: 100%;
      padding: 0.8rem;
      margin-bottom: 1.3rem;
      border: 1.5px solid #dce3f0;
      border-radius: 8px;
      font-size: 1rem;
      background: #f9fbff;
      transition: border 0.3s;
    }
    input:focus {
      outline: none;
      border-color: #1E90FF;
      background: #fff;
    }
    .btn-primary {
      width: 100%;
      padding: 0.9rem;
      background: #1E90FF;
      color: #fff;
      font-size: 1.05rem;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 600;
      transition: background 0.3s;
    }
    .btn-primary:hover {
      background: #187bcd;
    }
    .alert {
      padding: 0.8rem;
      margin-bottom: 1rem;
      border-radius: 8px;
      font-size: 0.95rem;
    }
    .alert.error {
      background: #ffe6e6;
      color: #b00020;
    }
    .alert.success {
      background: #e7f8ee;
      color: #22863a;
    }
    .auth-footer {
      margin-top: 1.2rem;
      font-size: 0.95rem;
      text-align: center;
    }
    .auth-footer a {
      color: #1E90FF;
      text-decoration: none;
      font-weight: 500;
    }
    .auth-footer a:hover {
      text-decoration: underline;
    }
    footer {
      text-align: center;
      padding: 1rem;
      font-size: 0.9rem;
      color: #888;
    }
    @media (max-width: 600px) {
      .auth-card {
        padding: 2rem 1.5rem;
      }
      header {
        padding: 1rem 1.5rem;
      }
    }
  </style>
</head>
<body>
<header>
  <div class="logo">
    <a href="index.php"><img src="assets/images/logo/logo-2.svg" alt="Logo"></a>
  </div>
  <nav>
    <a href="login.php">Login</a>
  </nav>
</header>

<main class="auth-container">
  <div class="auth-card">
    <img src="assets/images/logo/logo-2.svg" class="logo" alt="Logo" />
    <h2>Create Account</h2>

    <?php if ($success): ?>
      <div class="alert success">Registration successful. You can now <a href="login.php">login</a>.</div>
    <?php endif; ?>
    <?php foreach ($errors as $e): ?>
      <div class="alert error"><?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>

    <form method="post">
      <label for="username">Username</label>
      <input id="username" name="username" type="text" required value="<?php echo isset($username) ? htmlspecialchars($username) : '';?>" />

      <label for="email">Email</label>
      <input id="email" name="email" type="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : '';?>" />

      <label for="password">Password</label>
      <input id="password" name="password" type="password" required />

      <button type="submit" class="btn-primary">Register</button>
    </form>

    <div class="auth-footer">
      Already have an account? <a href="login.php">Login here</a>
    </div>
  </div>
</main>

<footer>
  <p>© <?php echo date('Y'); ?> Luner Trades.</p>
</footer>
</body>
</html>
