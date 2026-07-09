<?php

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';

require_admin();

$orderService = new OrderService($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_order_status') {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $orderStatus = $_POST['order_status'] ?? 'pending';

        try {
            $orderService->updateStatus($orderId, $orderStatus);
            flash_success('Order status updated.');
        } catch (Throwable $exception) {
            flash_error($exception->getMessage());
        }

        redirect_to('admin-orders.php');
    }
}

$message = get_flash_message();
$error = get_flash_error();

$orders = $orderService->adminOrders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WebHive Shop - Admin Orders</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="site-shell">
    <header class="topbar">
      <div class="topbar-inner">
        <a class="brand" href="admin-panel.php">WebHive Admin</a>
        <nav class="nav">
          <a href="admin-panel.php">Dashboard</a>
          <a class="active" href="admin-orders.php">Orders</a>
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
          <h1>Orders</h1>
          <p class="muted">Review and update order statuses.</p>
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
                    <th>Order</th>
                    <th>User</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Address</th>
                    <th>Date</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($orders as $order): ?>
                    <tr>
                      <td>#<?php echo h($order['id']); ?></td>
                      <td><?php echo h($order['user_name']); ?></td>
                      <td><?php echo h($order['item_count']); ?></td>
                      <td>Rs <?php echo h(number_format((float) $order['total_amount'], 2)); ?></td>
                      <td><?php echo h($order['payment_method']); ?></td>
                      <td><span class="badge warning"><?php echo h($order['order_status']); ?></span></td>
                      <td><?php echo h($order['shipping_address']); ?></td>
                      <td><?php echo h($order['created_at']); ?></td>
                      <td>
                        <form class="inline-form" method="post" action="admin-orders.php">
                          <input type="hidden" name="action" value="update_order_status">
                          <input type="hidden" name="order_id" value="<?php echo h($order['id']); ?>">
                          <select name="order_status">
                            <?php foreach (['pending', 'processing', 'completed', 'cancelled'] as $status): ?>
                              <option value="<?php echo h($status); ?>" <?php echo $order['order_status'] === $status ? 'selected' : ''; ?>>
                                <?php echo h($status); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                          <button class="btn secondary" type="submit">Update</button>
                        </form>
                      </td>
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
