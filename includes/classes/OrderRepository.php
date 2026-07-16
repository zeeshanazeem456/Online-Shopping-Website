<?php

class OrderRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function placeOrder(int $userId, string $shippingAddress, string $paymentMethod): int
    {
        if ($shippingAddress === '') {
            throw new Exception('Shipping address is required.');
        }

        if (!in_array($paymentMethod, ['cod', 'card'], true)) {
            $paymentMethod = 'cod';
        }

        $this->pdo->beginTransaction();

        try {
            $statement = $this->pdo->prepare(
                'SELECT c.product_id, c.quantity, p.name, p.price, p.stock
                 FROM cart_items c
                 JOIN products p ON p.id = c.product_id
                 WHERE c.user_id = ?
                 FOR UPDATE'
            );
            $statement->execute([$userId]);
            $cartItems = $statement->fetchAll();

            if (!$cartItems) {
                throw new Exception('Your cart is empty.');
            }

            $totalAmount = 0;
            foreach ($cartItems as $item) {
                if ((int) $item['quantity'] > (int) $item['stock']) {
                    throw new Exception($item['name'] . ' does not have enough stock.');
                }

                $totalAmount += (float) $item['price'] * (int) $item['quantity'];
            }

            $statement = $this->pdo->prepare(
                'INSERT INTO orders (user_id, total_amount, payment_method, order_status, shipping_address)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $statement->execute([$userId, $totalAmount, $paymentMethod, 'pending', $shippingAddress]);
            $orderId = (int) $this->pdo->lastInsertId();

            $insertItem = $this->pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, quantity, price)
                 VALUES (?, ?, ?, ?)'
            );
            $updateStock = $this->pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');

            foreach ($cartItems as $item) {
                $insertItem->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
                $updateStock->execute([$item['quantity'], $item['product_id']]);
            }

            $statement = $this->pdo->prepare('DELETE FROM cart_items WHERE user_id = ?');
            $statement->execute([$userId]);

            $this->pdo->commit();

            return $orderId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function userOrders(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, total_amount, payment_method, order_status, shipping_address, created_at
             FROM orders
             WHERE user_id = ?
             ORDER BY id DESC'
        );
        $statement->execute([$userId]);

        return $statement->fetchAll();
    }

    public function adminOrders(): array
    {
        return $this->pdo
            ->query(
                'SELECT o.id, o.total_amount, o.payment_method, o.order_status, o.shipping_address,
                        o.created_at, u.name AS user_name, COUNT(oi.id) AS item_count
                 FROM orders o
                 JOIN users u ON u.id = o.user_id
                 LEFT JOIN order_items oi ON oi.order_id = o.id
                 GROUP BY o.id, o.total_amount, o.payment_method, o.order_status,
                          o.shipping_address, o.created_at, u.name
                 ORDER BY o.id DESC'
            )
            ->fetchAll();
    }

    public function updateStatus(int $orderId, string $orderStatus): void
    {
        $allowedStatuses = ['pending', 'processing', 'completed', 'cancelled'];

        if (!in_array($orderStatus, $allowedStatuses, true)) {
            throw new Exception('Invalid order status.');
        }

        $statement = $this->pdo->prepare('UPDATE orders SET order_status = ? WHERE id = ?');
        $statement->execute([$orderStatus, $orderId]);
    }

    public function countOrders(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    }

    public function countPendingOrders(): int
    {
        return (int) $this->pdo
            ->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")
            ->fetchColumn();
    }

    public function completedSales(): float
    {
        return (float) $this->pdo
            ->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE order_status = 'completed'")
            ->fetchColumn();
    }

    public function recentOrders(int $limit = 5): array
    {
        $statement = $this->pdo->prepare(
            'SELECT o.id, o.total_amount, o.order_status, o.created_at, u.name AS user_name
             FROM orders o
             JOIN users u ON u.id = o.user_id
             ORDER BY o.id DESC
             LIMIT ?'
        );
        $statement->bindValue(1, $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
