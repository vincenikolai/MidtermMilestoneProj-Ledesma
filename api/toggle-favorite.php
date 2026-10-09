<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';

header('Content-Type: application/json; charset=utf-8');
requireLogin();

$payload = json_decode(file_get_contents('php://input'), true);
$recipeId = filter_var($payload['recipe_id'] ?? null, FILTER_VALIDATE_INT);
$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

if (!$recipeId) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid recipe.']);
    exit;
}

if (!verifyCsrf($csrfHeader)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $userId = currentUserId();

    $findFavorite = $db->prepare(
        'SELECT id
         FROM favorites
         WHERE user_id = :user_id AND recipe_id = :recipe_id'
    );
    $findFavorite->execute([
        ':user_id' => $userId,
        ':recipe_id' => $recipeId,
    ]);
    $favorite = $findFavorite->fetch();

    if ($favorite) {
        $deleteFavorite = $db->prepare('DELETE FROM favorites WHERE id = :id');
        $deleteFavorite->execute([':id' => (int) $favorite['id']]);
        echo json_encode(['success' => true, 'status' => 'removed']);
        exit;
    }

    $insertFavorite = $db->prepare(
        'INSERT INTO favorites (user_id, recipe_id)
         VALUES (:user_id, :recipe_id)'
    );
    $insertFavorite->execute([
        ':user_id' => $userId,
        ':recipe_id' => $recipeId,
    ]);

    echo json_encode(['success' => true, 'status' => 'added']);
} catch (PDOException $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to update favorite.',
    ]);
}
