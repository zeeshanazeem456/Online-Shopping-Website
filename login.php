<?php

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';

$error = '';
$users = new UserRepository($pdo);

if (is_logged_in()) {
    if ($_SESSION['role'] === 'admin') {
        redirect_to('admin-panel.php');
    }

    redirect_to('shop.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = $users->findByEmail($email);

    if ($user && $password === $user['password']) {
        $auth->login($user);

        if ($user['role'] === 'admin') {
            redirect_to('admin-panel.php');
        }

        redirect_to('shop.php');
    }

    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WebHive Shop - Login</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <main class="auth-page">
    <section class="card auth-card">
      <div class="card-body">
        <p class="eyebrow">WebHive Shop</p>
        <h1>Login</h1>
        <p class="muted">Use your account to open the correct panel.</p>

        <?php if ($error): ?>
          <div class="alert error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
          <div class="form-row">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
          </div>

          <div class="form-row">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
          </div>

          <button class="btn primary" type="submit">Login</button>
        </form>

        <div class="empty-state mt-18 text-left">
          <strong>Demo Accounts</strong><br>
          Admin: admin@webhive.test / admin123<br>
          User: user@webhive.test / user123
        </div>
      </div>
    </section>
  </main>
</body>
</html>
