<?php

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';

require_user();

$userId = (int) $_SESSION['user_id'];
$productsRepository = new ProductRepository($pdo);
$cartService = new CartService($pdo, $productsRepository);
$orderRepository = new OrderRepository($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'cod';

    try {
        $orderId = $orderRepository->placeOrder($userId, $shippingAddress, $paymentMethod);
        flash_success('Order placed successfully. Order ID: ' . $orderId);
        redirect_to('my-orders.php');
    } catch (Throwable $exception) {
        flash_error($exception->getMessage());
        redirect_to('checkout.php');
    }
}

$message = get_flash_message();
$error = get_flash_error();

$cartItems = $cartService->itemsForUser($userId);
$cartTotal = $cartService->total($cartItems);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WebHive Shop - Checkout</title>
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
          <a class="active" href="checkout.php">Checkout</a>
          <a href="my-orders.php">My Orders</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="page">
      <div class="page-header">
        <div>
          <p class="eyebrow">User Panel</p>
          <h1>Checkout</h1>
          <p class="muted">Complete your order details.</p>
        </div>
      </div>

      <?php if ($message): ?>
        <div class="alert success"><?php echo h($message); ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert error"><?php echo h($error); ?></div>
      <?php endif; ?>

      <?php if ($cartItems): ?>
        <div class="checkout-layout">
          <section class="card">
            <div class="card-body">
              <h2>Delivery</h2>
              <form method="post" action="checkout.php">
                <div class="form-row">
                  <label for="shipping_address">Shipping Address</label>
                  <textarea id="shipping_address" name="shipping_address" required></textarea>
                </div>

                <div class="form-row">
                  <label for="payment_method">Payment Method</label>
                  <select id="payment_method" name="payment_method">
                    <option value="cod">Cash on Delivery</option>
                    <option value="card">Card</option>
                  </select>
                </div>

                <button class="btn primary" type="submit">Place Order</button>
              </form>
            </div>
          </section>

          <aside class="card">
            <div class="card-body">
              <h2>Summary</h2>
              <div class="table-wrap">
                <table>
                  <tbody>
                    <?php foreach ($cartItems as $item): ?>
                      <tr>
                        <td><?php echo h($item['name']); ?> x <?php echo h($item['quantity']); ?></td>
                        <td>Rs <?php echo h(number_format((float) $item['line_total'], 2)); ?></td>
                      </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                      <th>Total</th>
                      <td>Rs <?php echo h(number_format($cartTotal, 2)); ?></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </aside>
        </div>
      <?php else: ?>
        <div class="empty-state">Add products to your cart before checkout.</div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
