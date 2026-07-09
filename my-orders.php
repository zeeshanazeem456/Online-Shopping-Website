<?php

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';

require_user();

$userId = (int) $_SESSION['user_id'];
$orderService = new OrderService($pdo);
$message = get_flash_message();
$error = get_flash_error();

$orders = $orderService->userOrders($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WebHive Shop - My Orders</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="site-shell">
    <header class="topbar">
      <div class="topbar-inner">
        <a class="brand" href="shop.php">WebHive Shop</a>
        <nav class="nav">
          <a href="shop.php">Products</a>
          <a href="cart.php">Cart</a>
          <a href="checkout.php">Checkout</a>
          <a class="active" href="my-orders.php">My Orders</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="page">
      <div class="page-header">
        <div>
          <p class="eyebrow">User Panel</p>
          <h1>My Orders</h1>
          <p class="muted">Track your order status.</p>
        </div>
      </div>

      <?php if ($message): ?>
        <div class="alert success"><?php echo h($message); ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert error"><?php echo h($error); ?></div>
      <?php endif; ?>

      <section class="card">
        <div class="card-body">
          <?php if ($orders): ?>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Order ID</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Address</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($orders as $order): ?>
                    <tr>
                      <td>#<?php echo h($order['id']); ?></td>
                      <td>Rs <?php echo h(number_format((float) $order['total_amount'], 2)); ?></td>
                      <td><?php echo h($order['payment_method']); ?></td>
                      <td><span class="badge warning"><?php echo h($order['order_status']); ?></span></td>
                      <td><?php echo h($order['shipping_address']); ?></td>
                      <td><?php echo h($order['created_at']); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty-state">No orders yet.</div>
          <?php endif; ?>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
