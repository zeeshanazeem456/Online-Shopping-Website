<?php

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';

require_admin();

$productsRepository = new ProductRepository($pdo);
$imageUploader = new ProductImageUploader(__DIR__ . '/assets/products');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $stock = (int) ($_POST['stock'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        $categoryIds = $_POST['category_ids'] ?? [];

        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $image = $imageUploader->save('image_upload', $name);
        $productsRepository->create([
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'stock' => $stock,
            'image' => $image,
            'status' => $status,
        ], $categoryIds);

        flash_success('Product added successfully.');
        redirect_to('admin-products.php');
    } catch (Throwable $exception) {
        flash_error($exception->getMessage() ?: 'Product could not be added.');
        redirect_to('admin-add-product.php');
    }
}

$error = get_flash_error();
$categories = $productsRepository->categories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WebHive Shop - Add Product</title>
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
          <a class="active" href="admin-add-product.php">Add Product</a>
          <a href="admin-customers.php">Customers</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="page">
      <div class="page-header">
        <div>
          <p class="eyebrow">Admin Panel</p>
          <h1>Add Product</h1>
          <p class="muted">Create a product and assign it to one or more categories.</p>
        </div>
      </div>

      <?php if ($error): ?>
        <div class="alert error"><?php echo h($error); ?></div>
      <?php endif; ?>

      <section class="card form-panel">
        <div class="card-body">
          <form class="product-form" method="post" action="admin-add-product.php" enctype="multipart/form-data">
            <div class="product-form-main">
              <div class="form-section">
                <div class="form-section-title">
                  <h2>Product Details</h2>
                  <p class="muted">Name, description, price, and stock shown to customers.</p>
                </div>

                <div class="form-row">
                  <label for="name">Product Name</label>
                  <input type="text" id="name" name="name" placeholder="e.g. Wireless Headphones" required>
                </div>

                <div class="form-row">
                  <label for="description">Description</label>
                  <textarea id="description" name="description" placeholder="Briefly describe the product, materials, features, or use case."></textarea>
                </div>

                <div class="form-grid">
                  <div class="form-row">
                    <label for="price">Price</label>
                    <input class="wide-number" type="number" id="price" name="price" min="1" step="0.01" placeholder="0.00" required>
                  </div>

                  <div class="form-row">
                    <label for="stock">Stock</label>
                    <input class="wide-number" type="number" id="stock" name="stock" min="0" placeholder="0" required>
                  </div>
                </div>
              </div>
            </div>

            <aside class="product-form-side">
              <div class="form-section">
                <div class="form-section-title">
                  <h2>Publishing</h2>
                  <p class="muted">Control visibility and catalog placement.</p>
                </div>

                <div class="form-row">
                  <label>Status</label>
                  <div class="choice-row">
                    <label class="choice-card">
                      <input type="radio" name="status" value="active" checked>
                      <span>
                        <strong>Active</strong>
                        <small>Visible in shop</small>
                      </span>
                    </label>
                    <label class="choice-card">
                      <input type="radio" name="status" value="inactive">
                      <span>
                        <strong>Inactive</strong>
                        <small>Hidden from users</small>
                      </span>
                    </label>
                  </div>
                </div>

                <div class="form-row">
                  <label>Categories</label>
                  <div class="category-picker">
                    <?php foreach ($categories as $category): ?>
                      <label class="category-option">
                        <input type="checkbox" name="category_ids[]" value="<?php echo h($category['id']); ?>">
                        <span><?php echo h($category['name']); ?></span>
                      </label>
                    <?php endforeach; ?>
                  </div>
                  <p class="form-help">Select at least one category.</p>
                </div>

                <div class="form-row">
                  <label for="image_upload">Product Image</label>
                  <div class="upload-box">
                    <input type="file" id="image_upload" name="image_upload" accept="image/jpeg,image/png,image/webp,image/gif">
                    <p class="form-help">Optional. JPG, PNG, WEBP, or GIF. Maximum size 2MB.</p>
                  </div>
                </div>
              </div>
            </aside>

            <div class="form-actions">
              <a class="btn secondary" href="admin-products.php">Cancel</a>
              <button class="btn primary" type="submit">Add Product</button>
            </div>
          </form>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
