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
            header('Location: user');
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
  <title>Login - luner Trades</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background: url('assets/images/invest-bg.jpg') no-repeat center center/cover;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      color: #333;
      position: relative;
    }

    body::before {
      content: '';
      position: absolute;
      inset: 0;
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(3px);
      z-index: 0;
    }

    header.navbar {
      position: relative;
      z-index: 10;
      padding: 1.2rem 5%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: rgba(255, 255, 255, 0.7);
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      backdrop-filter: blur(5px);
    }

    .navbar .logo a {
      text-decoration: none;
      font-size: 1.6rem;
      font-weight: 700;
      color: #1e90ff;
    }

    main.section {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 2rem;
      position: relative;
      z-index: 10;
    }

    .login-card {
      background: #fff;
      padding: 2.5rem;
      max-width: 420px;
      width: 100%;
      border-radius: 18px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
      text-align: center;
      animation: fadeInUp 0.6s ease;
    }

    .login-card img.logo {
      width: 100px;
      margin-bottom: 1.2rem;
    }

    .login-card h2 {
      font-size: 1.8rem;
      margin-bottom: 1.5rem;
      color: #1e90ff;
      font-weight: 600;
    }

    .alert.error {
      background: #ffe5e5;
      color: #c0392b;
      padding: 0.8rem;
      border-radius: 8px;
      margin-bottom: 1rem;
      font-size: 0.95rem;
      text-align: left;
    }

    form {
      text-align: left;
    }

    label {
      font-weight: 500;
      margin-bottom: 0.5rem;
      display: block;
    }

    input {
      width: 100%;
      padding: 0.9rem;
      margin-bottom: 1.2rem;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 1rem;
      transition: border 0.3s;
    }

    input:focus {
      border-color: #1e90ff;
      outline: none;
      box-shadow: 0 0 6px rgba(30,144,255,0.3);
    }

    button.btn {
      width: 100%;
      background: #1e90ff;
      color: #fff;
      padding: 0.95rem;
      border: none;
      border-radius: 10px;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
    }

    button.btn:hover {
      background: #187bcd;
    }

    .register-link {
      margin-top: 1.2rem;
      font-size: 0.95rem;
      text-align: center;
    }

    .register-link a {
      color: #1e90ff;
      text-decoration: none;
      font-weight: 600;
    }

    .register-link a:hover {
      text-decoration: underline;
    }

    footer.footer {
      text-align: center;
      padding: 1rem;
      background: rgba(255,255,255,0.7);
      font-size: 0.9rem;
      position: relative;
      z-index: 10;
    }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 480px) {
      .login-card {
        padding: 2rem 1.5rem;
      }
      .login-card h2 {
        font-size: 1.6rem;
      }
    }
  </style>
</head>
<body>
  <header class="navbar">
    <div class="logo"><a href="index.php">Luner Trades</a></div>
  </header>

  <main class="section">
    <div class="login-card">
      <img src="assets/images/logo/logo-2.svg" alt="Alif Invest Logo" class="logo" />
      <h2>Welcome Back</h2>

      <?php foreach ($errors as $e): ?>
        <div class="alert error"><?php echo htmlspecialchars($e); ?></div>
      <?php endforeach; ?>

      <form method="post">
        <div>
          <label for="email">Email Address</label>
          <input id="email" name="email" type="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : '';?>" />
        </div>
        <div>
          <label for="password">Password</label>
          <input id="password" name="password" type="password" required />
        </div>
        <button type="submit" class="btn">Login</button>
      </form>

      <div class="register-link">
        Don't have an account? <a href="register.php">Register here</a>
      </div>
    </div>
  </main>

  <footer class="footer">
    <p>© <?php echo date('Y'); ?> Luner Trades. All rights reserved.</p>
  </footer>
</body>
</html>



