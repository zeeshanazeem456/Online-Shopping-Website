<?php

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';

require_admin();

$usersRepository = new UserRepository($pdo);
$customers = $usersRepository->customerSummaries();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WebHive Shop - Admin Customers</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="site-shell">
    <header class="topbar">
      <div class="topbar-inner">
        <a class="brand" href="admin-panel.php">WebHive Admin</a>
        <nav class="nav">
          <a href="admin-panel.php">Dashboard</a>
          <a href="admin-orders.php">Orders</a>
          <a href="admin-products.php">Products</a>
          <a href="admin-add-product.php">Add Product</a>
          <a class="active" href="admin-customers.php">Customers</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="page">
      <div class="page-header">
        <div>
          <p class="eyebrow">Admin Panel</p>
          <h1>Customers</h1>
          <p class="muted">View registered customers and their order activity.</p>
        </div>
      </div>

      <section class="card">
        <div class="card-body">
          <?php if ($customers): ?>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Orders</th>
                    <th>Total Spent</th>
                    <th>Joined</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($customers as $customer): ?>
                    <tr>
                      <td><?php echo h($customer['id']); ?></td>
                      <td><?php echo h($customer['name']); ?></td>
                      <td><?php echo h($customer['email']); ?></td>
                      <td><?php echo h($customer['order_count']); ?></td>
                      <td>Rs <?php echo h(number_format((float) $customer['total_spent'], 2)); ?></td>
                      <td><?php echo h($customer['created_at']); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty-state">No customers found.</div>
          <?php endif; ?>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
