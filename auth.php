<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    $statement = Database::getInstance()->getConnection()->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
    $statement->execute([(int) $_SESSION['user_id']]);
    if (!$statement->fetchColumn()) {
        $_SESSION = [];
        session_destroy();
        header('Location: login.php?session=expired');
        exit;
    }
}

function currentUserId(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(?string $token): bool
{
    return is_string($token) && hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token);
}
