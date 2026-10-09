<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

final class Recipe
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? Database::getInstance()->getConnection(); }

    public function createRecipe(int $userId, int $categoryId, string $title, string $description, string $steps, array $ingredients): int
    {
        $this->db->beginTransaction();
        try {
            $recipe = $this->db->prepare('INSERT INTO recipes (user_id, category_id, title, description, steps) VALUES (?, ?, ?, ?, ?)');
            $recipe->execute([$userId, $categoryId, $title, $description, $steps]);
            $recipeId = (int) $this->db->lastInsertId();
            $ingredient = $this->db->prepare('INSERT INTO ingredients (recipe_id, ingredient_name) VALUES (?, ?)');
            foreach ($ingredients as $name) {
                $ingredient->execute([$recipeId, $name]);
            }
            $this->db->commit();
            return $recipeId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function getAllRecipes(
        int $currentUserId,
        string $keyword = '',
        ?int $categoryId = null
    ): array
    {
        $sql = 'SELECT
                    r.*, u.username, c.name AS category_name,
                    CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END AS is_favorited
                FROM recipes r LEFT JOIN users u ON u.id = r.user_id
                LEFT JOIN categories c ON c.id = r.category_id
                LEFT JOIN favorites f
                    ON r.id = f.recipe_id AND f.user_id = :current_user_id
                WHERE (r.title LIKE :keyword_title OR r.description LIKE :keyword_description)';
        $keywordValue = '%' . $keyword . '%';
        $params = [
            ':current_user_id' => $currentUserId,
            ':keyword_title' => $keywordValue,
            ':keyword_description' => $keywordValue,
        ];
        if ($categoryId !== null) { $sql .= ' AND r.category_id = :category_id'; $params[':category_id'] = $categoryId; }
        $sql .= ' ORDER BY r.created_at DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function getRecipeWithIngredients(int $id, int $currentUserId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT
                r.*, u.username, c.name AS category_name,
                CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END AS is_favorited
             FROM recipes r
             LEFT JOIN users u ON u.id = r.user_id
             LEFT JOIN categories c ON c.id = r.category_id
             LEFT JOIN favorites f
                ON r.id = f.recipe_id AND f.user_id = :current_user_id
             WHERE r.id = :recipe_id'
        );
        $statement->execute([
            ':current_user_id' => $currentUserId,
            ':recipe_id' => $id,
        ]);
        $recipe = $statement->fetch();
        if (!$recipe) return null;
        $ingredients = $this->db->prepare('SELECT id, ingredient_name FROM ingredients WHERE recipe_id=? ORDER BY id');
        $ingredients->execute([$id]);
        $recipe['ingredients'] = $ingredients->fetchAll();
        return $recipe;
    }

    public function updateRecipe(int $id, int $userId, int $categoryId, string $title, string $description, string $steps, array $ingredients): bool
    {
        $this->db->beginTransaction();
        try {
            $update = $this->db->prepare('UPDATE recipes SET category_id=?, title=?, description=?, steps=?, is_edited=1 WHERE id=? AND user_id=?');
            $update->execute([$categoryId, $title, $description, $steps, $id, $userId]);
            if ($update->rowCount() === 0) throw new RuntimeException('Recipe not found or not owned by you.');
            $this->db->prepare('DELETE FROM ingredients WHERE recipe_id=?')->execute([$id]);
            $insert = $this->db->prepare('INSERT INTO ingredients (recipe_id, ingredient_name) VALUES (?, ?)');
            foreach ($ingredients as $name) $insert->execute([$id, $name]);
            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function deleteRecipe(int $id, int $userId): bool
    {
        $statement = $this->db->prepare('DELETE FROM recipes WHERE id=? AND user_id=?');
        $statement->execute([$id, $userId]);
        $deleted = $statement->rowCount() > 0;
        if ($deleted) {
            Database::getInstance()->resetAllAutoIncrements();
        }
        return $deleted;
    }

    public function categories(): array
    {
        $statement = $this->db->prepare('SELECT id, name FROM categories ORDER BY name');
        $statement->execute();
        return $statement->fetchAll();
    }

}
