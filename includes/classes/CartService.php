<?php

class CartService
{
    public function __construct(private PDO $pdo, private ProductRepository $products)
    {
    }

    public function addProduct(int $userId, int $productId): string
    {
        $product = $this->products->findActiveForCart($productId);

        if (!$product) {
            throw new Exception('Product not found.');
        }

        if ((int) $product['stock'] < 1) {
            throw new Exception('This product is out of stock.');
        }

        $statement = $this->pdo->prepare('SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?');
        $statement->execute([$userId, $productId]);
        $cartItem = $statement->fetch();
        $currentQuantity = $cartItem ? (int) $cartItem['quantity'] : 0;

        if ($currentQuantity >= (int) $product['stock']) {
            throw new Exception('You cannot add more than available stock.');
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO cart_items (user_id, product_id, quantity)
             VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE quantity = quantity + 1'
        );
        $statement->execute([$userId, $productId]);

        return $product['name'] . ' added to cart.';
    }

    public function updateItem(int $userId, int $cartItemId, int $quantity): string
    {
        $statement = $this->pdo->prepare(
            'SELECT c.id, p.stock
             FROM cart_items c
             JOIN products p ON p.id = c.product_id
             WHERE c.id = ? AND c.user_id = ?'
        );
        $statement->execute([$cartItemId, $userId]);
        $cartItem = $statement->fetch();

        if (!$cartItem) {
            throw new Exception('Cart item not found.');
        }

        if ($quantity < 1) {
            $this->removeItem($userId, $cartItemId);

            return 'Item removed from cart.';
        }

        if ($quantity > (int) $cartItem['stock']) {
            throw new Exception('Quantity cannot be greater than available stock.');
        }

        $statement = $this->pdo->prepare('UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?');
        $statement->execute([$quantity, $cartItemId, $userId]);

        return 'Cart updated.';
    }

    public function removeItem(int $userId, int $cartItemId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?');
        $statement->execute([$cartItemId, $userId]);
    }

    public function itemsForUser(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT c.id AS cart_item_id, c.quantity, p.name, p.price, p.stock,
                    c.quantity * p.price AS line_total
             FROM cart_items c
             JOIN products p ON p.id = c.product_id
             WHERE c.user_id = ?
             ORDER BY c.id'
        );
        $statement->execute([$userId]);

        return $statement->fetchAll();
    }

    public function countItems(int $userId): int
    {
        $statement = $this->pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?');
        $statement->execute([$userId]);

        return (int) $statement->fetchColumn();
    }

    public function total(array $cartItems): float
    {
        $total = 0;

        foreach ($cartItems as $item) {
            $total += (float) $item['line_total'];
        }

        return $total;
    }
}
