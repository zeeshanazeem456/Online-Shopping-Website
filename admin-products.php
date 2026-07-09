<?php

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';

require_admin();

$productsRepository = new ProductRepository($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'set_status') {
            $productId = (int) ($_POST['product_id'] ?? 0);
            $status = $_POST['status'] ?? 'inactive';

            if (!in_array($status, ['active', 'inactive'], true)) {
                throw new Exception('Invalid product status.');
            }

            $productsRepository->setStatus($productId, $status);

            flash_success('Product status updated.');
            redirect_to('admin-products.php');
        }

        if ($action === 'delete_product') {
            $productId = (int) ($_POST['product_id'] ?? 0);

            $productsRepository->delete($productId);

            flash_success('Product removed.');
            redirect_to('admin-products.php');
        }
    } catch (Throwable $exception) {
        flash_error($exception->getMessage() ?: 'Product action failed.');
        redirect_to('admin-products.php');
    }
}

$message = get_flash_message();
$error = get_flash_error();

$products = $productsRepository->productsWithCategoriesAndOrderCounts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WebHive Shop - Admin Products</title>
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
          <a href="admin-products.php" class="active">Products</a>
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
          <h1>Products</h1>
          <p class="muted">View, edit, disable, or remove products.</p>
        </div>
        <a class="btn primary" href="admin-add-product.php">Add Product</a>
      </div>

      <?php if ($message): ?>
        <div class="alert success"><?php echo h($message); ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert error"><?php echo h($error); ?></div>
      <?php endif; ?>

      <section class="card">
        <div class="card-body">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Image</th>
                  <th>Name</th>
                  <th>Categories</th>
                  <th>Price</th>
                  <th>Stock</th>
                  <th>Status</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($products as $product): ?>
                  <tr>
                    <td><?php echo h($product['id']); ?></td>
                    <td>
                      <?php if (!empty($product['image']) && file_exists(__DIR__ . '/assets/' . $product['image'])): ?>
                        <img class="table-thumb" src="assets/<?php echo h($product['image']); ?>" alt="<?php echo h($product['name']); ?>">
                      <?php else: ?>
                        <span class="badge">No image</span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo h($product['name']); ?></td>
                    <td><?php echo h($product['category_names'] ?? 'No category'); ?></td>
                    <td>Rs <?php echo h(number_format((float) $product['price'], 2)); ?></td>
                    <td><?php echo h($product['stock']); ?></td>
                    <td>
                      <span class="badge <?php echo $product['status'] === 'active' ? 'success' : 'warning'; ?>">
                        <?php echo h($product['status']); ?>
                      </span>
                    </td>
                    <td><?php echo h($product['created_at']); ?></td>
                    <td>
                      <div class="button-row">
                        <a class="btn secondary" href="admin-edit-product.php?id=<?php echo h($product['id']); ?>">Edit</a>

                        <form method="post" action="admin-products.php">
                          <input type="hidden" name="action" value="set_status">
                          <input type="hidden" name="product_id" value="<?php echo h($product['id']); ?>">
                          <input type="hidden" name="status" value="<?php echo $product['status'] === 'active' ? 'inactive' : 'active'; ?>">
                          <button class="btn secondary" type="submit">
                            <?php echo $product['status'] === 'active' ? 'Disable' : 'Enable'; ?>
                          </button>
                        </form>

                        <form method="post" action="admin-products.php">
                          <input type="hidden" name="action" value="delete_product">
                          <input type="hidden" name="product_id" value="<?php echo h($product['id']); ?>">
                          <button class="btn danger" type="submit" <?php echo (int) $product['order_item_count'] > 0 ? 'disabled' : ''; ?>>
                            Remove
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
