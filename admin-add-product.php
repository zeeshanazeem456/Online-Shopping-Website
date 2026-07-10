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
        <a class="btn secondary" href="admin-products.php">Back to Products</a>
      </div>

      <?php if ($error): ?>
        <div class="alert error"><?php echo h($error); ?></div>
      <?php endif; ?>

      <section class="card">
        <div class="card-body">
          <form method="post" action="admin-add-product.php" enctype="multipart/form-data">
            <div class="form-grid">
              <div class="form-row">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" required>
              </div>

              <div class="form-row">
                <label for="price">Price</label>
                <input type="number" id="price" name="price" min="1" step="0.01" required>
              </div>

              <div class="form-row">
                <label for="stock">Stock</label>
                <input type="number" id="stock" name="stock" min="0" required>
              </div>

              <div class="form-row">
                <label for="status">Status</label>
                <select id="status" name="status">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>

              <div class="form-row">
                <label for="image_upload">Product Image</label>
                <input type="file" id="image_upload" name="image_upload" accept="image/jpeg,image/png,image/webp,image/gif">
              </div>
            </div>

            <div class="form-row">
              <label for="description">Description</label>
              <textarea id="description" name="description"></textarea>
            </div>

            <div class="form-row">
              <label for="category_ids">Categories</label>
              <select id="category_ids" name="category_ids[]" multiple required>
                <?php foreach ($categories as $category): ?>
                  <option value="<?php echo h($category['id']); ?>"><?php echo h($category['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <button class="btn primary" type="submit">Add Product</button>
          </form>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
