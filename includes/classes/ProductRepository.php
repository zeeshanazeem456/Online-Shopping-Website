<?php

class ProductRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function activeProductsWithCategories(): array
    {
        return $this->pdo
            ->query(
                "SELECT p.id, p.name, p.description, p.price, p.stock, p.image, p.status,
                        GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', ') AS category_names
                 FROM products p
                 LEFT JOIN product_categories pc ON pc.product_id = p.id
                 LEFT JOIN categories c ON c.id = pc.category_id
                 WHERE p.status = 'active'
                 GROUP BY p.id, p.name, p.description, p.price, p.stock, p.image, p.status
                 ORDER BY p.id"
            )
            ->fetchAll();
    }

    public function productsWithCategoriesAndOrderCounts(): array
    {
        return $this->pdo
            ->query(
                "SELECT p.id, p.name, p.image,
                        GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', ') AS category_names,
                        p.price, p.stock, p.status, p.created_at,
                        COUNT(DISTINCT oi.id) AS order_item_count
                 FROM products p
                 LEFT JOIN product_categories pc ON pc.product_id = p.id
                 LEFT JOIN categories c ON c.id = pc.category_id
                 LEFT JOIN order_items oi ON oi.product_id = p.id
                 GROUP BY p.id, p.name, p.image, p.price, p.stock, p.status, p.created_at
                 ORDER BY p.id"
            )
            ->fetchAll();
    }

    public function findActiveForCart(int $productId): ?array
    {
        $statement = $this->pdo->prepare("SELECT id, name, stock FROM products WHERE id = ? AND status = 'active'");
        $statement->execute([$productId]);
        $product = $statement->fetch();

        return $product ?: null;
    }

    public function findById(int $productId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM products WHERE id = ?');
        $statement->execute([$productId]);
        $product = $statement->fetch();

        return $product ?: null;
    }

    public function categories(): array
    {
        return $this->pdo
            ->query('SELECT id, name FROM categories ORDER BY name')
            ->fetchAll();
    }

    public function selectedCategoryIds(int $productId): array
    {
        $statement = $this->pdo->prepare('SELECT category_id FROM product_categories WHERE product_id = ?');
        $statement->execute([$productId]);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function create(array $product, array $categoryIds): int
    {
        $this->validate($product, $categoryIds);

        $this->pdo->beginTransaction();

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO products (name, description, price, stock, image, status)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $statement->execute([
                $product['name'],
                $product['description'],
                $product['price'],
                $product['stock'],
                $product['image'],
                $product['status'],
            ]);

            $productId = (int) $this->pdo->lastInsertId();
            $this->replaceCategories($productId, $categoryIds);

            $this->pdo->commit();

            return $productId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function update(int $productId, array $product, array $categoryIds): void
    {
        $this->validate($product, $categoryIds);

        $this->pdo->beginTransaction();

        try {
            if ($product['image'] !== '') {
                $statement = $this->pdo->prepare(
                    'UPDATE products
                     SET name = ?, description = ?, price = ?, stock = ?, image = ?, status = ?
                     WHERE id = ?'
                );
                $statement->execute([
                    $product['name'],
                    $product['description'],
                    $product['price'],
                    $product['stock'],
                    $product['image'],
                    $product['status'],
                    $productId,
                ]);
            } else {
                $statement = $this->pdo->prepare(
                    'UPDATE products
                     SET name = ?, description = ?, price = ?, stock = ?, status = ?
                     WHERE id = ?'
                );
                $statement->execute([
                    $product['name'],
                    $product['description'],
                    $product['price'],
                    $product['stock'],
                    $product['status'],
                    $productId,
                ]);
            }

            $this->replaceCategories($productId, $categoryIds);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function setStatus(int $productId, string $status): void
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new Exception('Invalid product status.');
        }

        $statement = $this->pdo->prepare('UPDATE products SET status = ? WHERE id = ?');
        $statement->execute([$status, $productId]);
    }

    public function delete(int $productId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM products WHERE id = ?');
        $statement->execute([$productId]);
    }

    public function countProducts(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    }

    public function countCategories(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    }

    private function replaceCategories(int $productId, array $categoryIds): void
    {
        $statement = $this->pdo->prepare('DELETE FROM product_categories WHERE product_id = ?');
        $statement->execute([$productId]);

        $insertCategory = $this->pdo->prepare(
            'INSERT IGNORE INTO product_categories (product_id, category_id) VALUES (?, ?)'
        );

        foreach ($categoryIds as $categoryId) {
            $insertCategory->execute([$productId, (int) $categoryId]);
        }
    }

    private function validate(array $product, array $categoryIds): void
    {
        if ($product['name'] === '' || (float) $product['price'] <= 0 || (int) $product['stock'] < 0) {
            throw new Exception('Product name, valid price, and stock are required.');
        }

        if (!$categoryIds) {
            throw new Exception('Select at least one category.');
        }
    }
}
