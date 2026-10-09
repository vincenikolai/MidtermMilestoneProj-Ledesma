<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

final class User
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance()->getConnection();
    }

    public function register(string $username, string $email, string $password): bool
    {
        $statement = $this->db->prepare(
            'INSERT INTO users (username, email, password) VALUES (:username, :email, :password)'
        );
        return $statement->execute([
            ':username' => $username,
            ':email' => strtolower($email),
            ':password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }

    public function emailExists(string $email): bool
    {
        $statement = $this->db->prepare('SELECT 1 FROM users WHERE email = :email LIMIT 1');
        $statement->execute([':email' => strtolower(trim($email))]);
        return (bool) $statement->fetchColumn();
    }

    public function usernameExists(string $username): bool
    {
        $statement = $this->db->prepare('SELECT 1 FROM users WHERE username = :username LIMIT 1');
        $statement->execute([':username' => trim($username)]);
        return (bool) $statement->fetchColumn();
    }

    public function login(string $email, string $password): bool
    {
        $statement = $this->db->prepare('SELECT id, password FROM users WHERE email = :email LIMIT 1');
        $statement->execute([':email' => strtolower($email)]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        return true;
    }
}
