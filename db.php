<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secure,
        'samesite' => 'Lax',
    ]);
    session_start();
}

final class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $dsn = 'mysql:host=127.0.0.1;dbname=filipino_recipes_db;charset=utf8mb4';
        $this->connection = new PDO($dsn, 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public static function getInstance(): Database
    {
        return self::$instance ?? (self::$instance = new self());
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function resetAutoIncrement(string $table): void
    {
        $allowedTables = ['users', 'categories', 'recipes', 'ingredients', 'comments', 'favorites'];
        if (!in_array($table, $allowedTables, true)) {
            throw new InvalidArgumentException('Unsupported table for auto-increment reset.');
        }

        $statement = $this->connection->prepare("SELECT COALESCE(MAX(id), 0) + 1 FROM {$table}");
        $statement->execute();
        $nextId = (int) $statement->fetchColumn();
        $this->connection->exec("ALTER TABLE {$table} AUTO_INCREMENT = {$nextId}");
    }

    public function resetAllAutoIncrements(): void
    {
        foreach (['users', 'categories', 'recipes', 'ingredients', 'comments', 'favorites'] as $table) {
            $this->resetAutoIncrement($table);
        }
    }
}
