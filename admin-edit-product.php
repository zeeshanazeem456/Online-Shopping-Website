<?php

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';

require_admin();

$productsRepository = new ProductRepository($pdo);
$imageUploader = new ProductImageUploader(__DIR__ . '/assets/products');

$productId = (int) ($_GET['id'] ?? $_POST['product_id'] ?? 0);

if ($productId <= 0) {
    flash_error('Product not found.');
    redirect_to('admin-products.php');
}

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

        $newImage = $imageUploader->save('image_upload', $name);
        $productsRepository->update($productId, [
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'stock' => $stock,
            'image' => $newImage,
            'status' => $status,
        ], $categoryIds);

        flash_success('Product updated successfully.');
        redirect_to('admin-products.php');
    } catch (Throwable $exception) {
        flash_error($exception->getMessage() ?: 'Product could not be updated.');
        redirect_to('admin-edit-product.php?id=' . $productId);
    }
}

$error = get_flash_error();

$product = $productsRepository->findById($productId);

if (!$product) {
    flash_error('Product not found.');
    redirect_to('admin-products.php');
}

$categories = $productsRepository->categories();
$selectedCategoryIds = $productsRepository->selectedCategoryIds($productId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WebHive Shop - Edit Product</title>
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
          <a class="active" href="admin-products.php">Products</a>
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
          <h1>Edit Product</h1>
          <p class="muted"><?php echo h($product['name']); ?></p>
        </div>
        <a class="btn secondary" href="admin-products.php">Back to Products</a>
      </div>

      <?php if ($error): ?>
        <div class="alert error"><?php echo h($error); ?></div>
      <?php endif; ?>

      <section class="card">
        <div class="card-body">
          <form method="post" action="admin-edit-product.php" enctype="multipart/form-data">
            <input type="hidden" name="product_id" value="<?php echo h($product['id']); ?>">

            <div class="form-grid">
              <div class="form-row">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" value="<?php echo h($product['name']); ?>" required>
              </div>

              <div class="form-row">
                <label for="price">Price</label>
                <input type="number" id="price" name="price" min="1" step="0.01" value="<?php echo h($product['price']); ?>" required>
              </div>

              <div class="form-row">
                <label for="stock">Stock</label>
                <input type="number" id="stock" name="stock" min="0" value="<?php echo h($product['stock']); ?>" required>
              </div>

              <div class="form-row">
                <label for="status">Status</label>
                <select id="status" name="status">
                  <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                  <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
              </div>

              <div class="form-row">
                <label for="image_upload">Replace Image</label>
                <input type="file" id="image_upload" name="image_upload" accept="image/jpeg,image/png,image/webp,image/gif">
                <p class="form-help">Optional. Leave empty to keep the current image.</p>
              </div>
            </div>

            <?php if (!empty($product['image']) && file_exists(__DIR__ . '/assets/' . $product['image'])): ?>
              <div class="form-row">
                <label>Current Image</label>
                <img class="edit-preview" src="assets/<?php echo h($product['image']); ?>" alt="<?php echo h($product['name']); ?>">
              </div>
            <?php endif; ?>

            <div class="form-row">
              <label for="description">Description</label>
              <textarea id="description" name="description"><?php echo h($product['description']); ?></textarea>
            </div>

            <div class="form-row">
              <label for="category_ids">Categories</label>
              <select id="category_ids" name="category_ids[]" multiple required>
                <?php foreach ($categories as $category): ?>
                  <option value="<?php echo h($category['id']); ?>" <?php echo in_array((int) $category['id'], $selectedCategoryIds, true) ? 'selected' : ''; ?>>
                    <?php echo h($category['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <button class="btn primary" type="submit">Save Changes</button>
          </form>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
