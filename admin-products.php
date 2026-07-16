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
  <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
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
      </div>

      <?php if ($message): ?>
        <div class="alert success"><?php echo h($message); ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert error"><?php echo h($error); ?></div>
      <?php endif; ?>

      <section class="card">
        <div class="card-body product-table-card">
          <div class="table-wrap">
            <table class="product-admin-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Image</th>
                  <th>Name</th>
                  <th>Categories</th>
                  <th>Price</th>
                  <th>Stock</th>
                  <th>Status</th>
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
                    <td>
                      <strong class="product-name"><?php echo h($product['name']); ?></strong>
                    </td>
                    <td>
                      <span class="category-list"><?php echo h($product['category_names'] ?? 'No category'); ?></span>
                    </td>
                    <td>
                      <span class="price-cell">Rs <?php echo h(number_format((float) $product['price'], 2)); ?></span>
                    </td>
                    <td>
                      <span class="stock-count <?php echo (int) $product['stock'] <= 5 ? 'is-low' : ''; ?>">
                        <?php echo h($product['stock']); ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge <?php echo $product['status'] === 'active' ? 'success' : 'warning'; ?>">
                        <?php echo h($product['status']); ?>
                      </span>
                    </td>
                    <td>
                      <div class="table-actions">
                        <a class="btn secondary compact" href="admin-edit-product.php?id=<?php echo h($product['id']); ?>">Edit</a>

                        <form method="post" action="admin-products.php">
                          <input type="hidden" name="action" value="set_status">
                          <input type="hidden" name="product_id" value="<?php echo h($product['id']); ?>">
                          <input type="hidden" name="status" value="<?php echo $product['status'] === 'active' ? 'inactive' : 'active'; ?>">
                          <button class="btn secondary compact" type="submit">
                            <?php echo $product['status'] === 'active' ? 'Disable' : 'Enable'; ?>
                          </button>
                        </form>

                        <form method="post" action="admin-products.php" onsubmit="return confirm('This product will be deleted permanently. Do you want to continue?');">
                          <input type="hidden" name="action" value="delete_product">
                          <input type="hidden" name="product_id" value="<?php echo h($product['id']); ?>">
                          <button class="btn danger compact" type="submit">
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
