<?php

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';

require_admin();

function save_product_edit_upload($fieldName, $productName)
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Image upload failed.');
    }

    if ($_FILES[$fieldName]['size'] > 2 * 1024 * 1024) {
        throw new Exception('Image size must be 2MB or less.');
    }

    $extension = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (!in_array($extension, $allowedExtensions, true)) {
        throw new Exception('Only JPG, PNG, WEBP, and GIF images are allowed.');
    }

    if (!getimagesize($_FILES[$fieldName]['tmp_name'])) {
        throw new Exception('Uploaded file is not a valid image.');
    }

    $uploadDir = __DIR__ . '/assets/products';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $safeName = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $productName));
    $fileName = trim($safeName, '-') . '-' . time() . '.' . $extension;
    $destination = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $destination)) {
        throw new Exception('Could not save uploaded image.');
    }

    return 'products/' . $fileName;
}

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

        if ($name === '' || $price <= 0 || $stock < 0) {
            throw new Exception('Product name, valid price, and stock are required.');
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        if (!$categoryIds) {
            throw new Exception('Select at least one category.');
        }

        $newImage = save_product_edit_upload('image_upload', $name);

        $pdo->beginTransaction();

        if ($newImage !== '') {
            $statement = $pdo->prepare(
                'UPDATE products
                 SET name = ?, description = ?, price = ?, stock = ?, image = ?, status = ?
                 WHERE id = ?'
            );
            $statement->execute([$name, $description, $price, $stock, $newImage, $status, $productId]);
        } else {
            $statement = $pdo->prepare(
                'UPDATE products
                 SET name = ?, description = ?, price = ?, stock = ?, status = ?
                 WHERE id = ?'
            );
            $statement->execute([$name, $description, $price, $stock, $status, $productId]);
        }

        $statement = $pdo->prepare('DELETE FROM product_categories WHERE product_id = ?');
        $statement->execute([$productId]);

        $insertCategory = $pdo->prepare(
            'INSERT IGNORE INTO product_categories (product_id, category_id) VALUES (?, ?)'
        );

        foreach ($categoryIds as $categoryId) {
            $insertCategory->execute([$productId, (int) $categoryId]);
        }

        $pdo->commit();

        flash_success('Product updated successfully.');
        redirect_to('admin-products.php');
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash_error($exception->getMessage() ?: 'Product could not be updated.');
        redirect_to('admin-edit-product.php?id=' . $productId);
    }
}

$error = get_flash_error();

$statement = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$statement->execute([$productId]);
$product = $statement->fetch();

if (!$product) {
    flash_error('Product not found.');
    redirect_to('admin-products.php');
}

$categories = $pdo
    ->query('SELECT id, name FROM categories ORDER BY name')
    ->fetchAll();

$statement = $pdo->prepare('SELECT category_id FROM product_categories WHERE product_id = ?');
$statement->execute([$productId]);
$selectedCategoryIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
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
