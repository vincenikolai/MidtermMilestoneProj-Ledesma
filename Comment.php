<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

final class Comment
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? Database::getInstance()->getConnection(); }
    public function add(int $recipeId, int $userId, string $content): bool
    {
        $s = $this->db->prepare('INSERT INTO comments (recipe_id, user_id, content) VALUES (?, ?, ?)');
        return $s->execute([$recipeId, $userId, $content]);
    }
    public function allForRecipe(int $recipeId): array
    {
        $s = $this->db->prepare('SELECT c.*, u.username FROM comments c JOIN users u ON u.id=c.user_id WHERE c.recipe_id=? ORDER BY c.created_at DESC');
        $s->execute([$recipeId]);
        return $s->fetchAll();
    }
    public function update(int $id, int $userId, string $content): bool
    {
        $s = $this->db->prepare('UPDATE comments SET content=?, is_edited=1 WHERE id=? AND user_id=?');
        $s->execute([$content, $id, $userId]);
        return $s->rowCount() > 0;
    }
    public function delete(int $id, int $userId): bool
    {
        $s = $this->db->prepare('DELETE FROM comments WHERE id=? AND user_id=?');
        $s->execute([$id, $userId]);
        $deleted = $s->rowCount() > 0;
        if ($deleted) {
            Database::getInstance()->resetAllAutoIncrements();
        }
        return $deleted;
    }
}
