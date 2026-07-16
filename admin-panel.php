<?php

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';

require_admin();

$productsRepository = new ProductRepository($pdo);
$usersRepository = new UserRepository($pdo);
$orderRepository = new OrderRepository($pdo);

$totalProducts = $productsRepository->countProducts();
$totalCategories = $productsRepository->countCategories();
$totalUsers = $usersRepository->countCustomers();
$totalOrders = $orderRepository->countOrders();
$pendingOrders = $orderRepository->countPendingOrders();
$completedSales = $orderRepository->completedSales();
$recentOrders = $orderRepository->recentOrders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WebHive Shop - Admin Dashboard</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="site-shell">
    <header class="topbar">
      <div class="topbar-inner">
        <a class="brand" href="admin-panel.php">WebHive Admin</a>
        <nav class="nav">
          <a class="active" href="admin-panel.php">Dashboard</a>
          <a href="admin-orders.php">Orders</a>
          <a href="admin-products.php">Products</a>
          <a href="admin-add-product.php">Add Product</a>
          <a href="admin-customers.php">Customers</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="page">
      <div class="page-header">
        <div>
          <p class="eyebrow">Admin Panel</p>
          <h1>Dashboard</h1>
          <p class="muted">Welcome, <?php echo h($_SESSION['name']); ?>.</p>
        </div>
      </div>

      <section class="grid stats-grid">
        <div class="card stat-card">
          <div class="stat-label">Products</div>
          <div class="stat-value"><?php echo h($totalProducts); ?></div>
        </div>
        <div class="card stat-card">
          <div class="stat-label">Categories</div>
          <div class="stat-value"><?php echo h($totalCategories); ?></div>
        </div>
        <div class="card stat-card">
          <div class="stat-label">Customers</div>
          <div class="stat-value"><?php echo h($totalUsers); ?></div>
        </div>
        <div class="card stat-card">
          <div class="stat-label">Orders</div>
          <div class="stat-value"><?php echo h($totalOrders); ?></div>
        </div>
        <div class="card stat-card">
          <div class="stat-label">Pending</div>
          <div class="stat-value"><?php echo h($pendingOrders); ?></div>
        </div>
        <div class="card stat-card">
          <div class="stat-label">Completed Sales</div>
          <div class="stat-value">Rs <?php echo h(number_format($completedSales, 2)); ?></div>
        </div>
      </section>

      <section class="card section-gap">
        <div class="card-body">
          <div class="page-header">
            <div>
              <h2>Recent Orders</h2>
              <p class="muted">Latest customer activity.</p>
            </div>
            <a class="btn secondary" href="admin-orders.php">View Orders</a>
          </div>

          <?php if ($recentOrders): ?>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Order</th>
                    <th>User</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentOrders as $order): ?>
                    <tr>
                      <td>#<?php echo h($order['id']); ?></td>
                      <td><?php echo h($order['user_name']); ?></td>
                      <td>Rs <?php echo h(number_format((float) $order['total_amount'], 2)); ?></td>
                      <td><span class="badge warning"><?php echo h($order['order_status']); ?></span></td>
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
