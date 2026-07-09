<?php

try {
    require __DIR__ . '/includes/db.php';

    $statement = $pdo->query('SELECT DATABASE() AS database_name');
    $database = $statement->fetch();

    echo '<h2>Database connected successfully.</h2>';
    echo '<p>Connected database: ' . htmlspecialchars($database['database_name']) . '</p>';
} catch (PDOException $exception) {
    echo '<h2>Database connection failed.</h2>';
    echo '<p>' . htmlspecialchars($exception->getMessage()) . '</p>';
}
