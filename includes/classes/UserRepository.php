<?php

class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function countCustomers(): int
    {
        return (int) $this->pdo
            ->query("SELECT COUNT(*) FROM users WHERE role = 'user'")
            ->fetchColumn();
    }

    public function customerSummaries(): array
    {
        return $this->pdo
            ->query(
                "SELECT u.id, u.name, u.email, u.created_at,
                        COUNT(o.id) AS order_count,
                        COALESCE(SUM(o.total_amount), 0) AS total_spent
                 FROM users u
                 LEFT JOIN orders o ON o.user_id = u.id
                 WHERE u.role = 'user'
                 GROUP BY u.id, u.name, u.email, u.created_at
                 ORDER BY u.id DESC"
            )
            ->fetchAll();
    }
}
