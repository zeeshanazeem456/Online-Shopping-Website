<?php

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';

require_user();

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cartItemId = (int) ($_POST['cart_item_id'] ?? 0);

    if ($action === 'update_cart') {
        $quantity = (int) ($_POST['quantity'] ?? 1);

        $statement = $pdo->prepare(
            'SELECT c.id, p.stock
             FROM cart_items c
             JOIN products p ON p.id = c.product_id
             WHERE c.id = ? AND c.user_id = ?'
        );
        $statement->execute([$cartItemId, $userId]);
        $cartItem = $statement->fetch();

        if (!$cartItem) {
            flash_error('Cart item not found.');
        } elseif ($quantity < 1) {
            $statement = $pdo->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?');
            $statement->execute([$cartItemId, $userId]);
            flash_success('Item removed from cart.');
        } elseif ($quantity > (int) $cartItem['stock']) {
            flash_error('Quantity cannot be greater than available stock.');
        } else {
            $statement = $pdo->prepare('UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?');
            $statement->execute([$quantity, $cartItemId, $userId]);
            flash_success('Cart updated.');
        }

        redirect_to('cart.php');
    }

    if ($action === 'remove_from_cart') {
        $statement = $pdo->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?');
        $statement->execute([$cartItemId, $userId]);

        flash_success('Item removed from cart.');
        redirect_to('cart.php');
    }
}

$message = get_flash_message();
$error = get_flash_error();

$statement = $pdo->prepare(
    'SELECT c.id AS cart_item_id, c.quantity, p.name, p.price, p.stock, c.quantity * p.price AS line_total
     FROM cart_items c
     JOIN products p ON p.id = c.product_id
     WHERE c.user_id = ?
     ORDER BY c.id'
);
$statement->execute([$userId]);
$cartItems = $statement->fetchAll();

$cartTotal = 0;
foreach ($cartItems as $item) {
    $cartTotal += (float) $item['line_total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WebHive Shop - Cart</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="site-shell">
    <header class="topbar">
      <div class="topbar-inner">
        <a class="brand" href="shop.php">WebHive Shop</a>
        <nav class="nav">
          <a href="shop.php">Products</a>
          <a class="active" href="cart.php">Cart</a>
          <a href="checkout.php">Checkout</a>
          <a href="my-orders.php">My Orders</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="page">
      <div class="page-header">
        <div>
          <p class="eyebrow">User Panel</p>
          <h1>Cart</h1>
          <p class="muted">Review quantities before checkout.</p>
        </div>
        <a class="btn secondary" href="shop.php">Continue Shopping</a>
      </div>

      <?php if ($message): ?>
        <div class="alert success"><?php echo h($message); ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert error"><?php echo h($error); ?></div>
      <?php endif; ?>

      <section class="card">
        <div class="card-body">
          <?php if ($cartItems): ?>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($cartItems as $item): ?>
                    <tr>
                      <td><?php echo h($item['name']); ?></td>
                      <td>Rs <?php echo h(number_format((float) $item['price'], 2)); ?></td>
                      <td><?php echo h($item['stock']); ?></td>
                      <td>
                        <form class="inline-form" method="post" action="cart.php">
                          <input type="hidden" name="action" value="update_cart">
                          <input type="hidden" name="cart_item_id" value="<?php echo h($item['cart_item_id']); ?>">
                          <input type="number" name="quantity" value="<?php echo h($item['quantity']); ?>" min="1">
                          <button class="btn secondary" type="submit">Update</button>
                        </form>
                      </td>
                      <td>Rs <?php echo h(number_format((float) $item['line_total'], 2)); ?></td>
                      <td>
                        <form method="post" action="cart.php">
                          <input type="hidden" name="action" value="remove_from_cart">
                          <input type="hidden" name="cart_item_id" value="<?php echo h($item['cart_item_id']); ?>">
                          <button class="btn danger" type="submit">Remove</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot>
                  <tr class="total-row">
                    <th colspan="4">Cart Total</th>
                    <td colspan="2">Rs <?php echo h(number_format($cartTotal, 2)); ?></td>
                  </tr>
                </tfoot>
              </table>
            </div>
            <div class="button-row mt-18">
              <a class="btn primary" href="checkout.php">Checkout</a>
            </div>
          <?php else: ?>
            <div class="empty-state">Your cart is empty.</div>
          <?php endif; ?>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
