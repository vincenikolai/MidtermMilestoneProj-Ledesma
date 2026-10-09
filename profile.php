<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

requireLogin();

$db = Database::getInstance()->getConnection();
$userId = currentUserId();

$statisticsQuery = $db->prepare(
    'SELECT
        u.username,
        COUNT(DISTINCT r.id) AS recipes_posted,
        COUNT(DISTINCT f.recipe_id) AS recipes_favorited,
        COUNT(DISTINCT c.id) AS comments_made
     FROM users u
     LEFT JOIN recipes r ON r.user_id = u.id
     LEFT JOIN favorites f ON f.user_id = u.id
     LEFT JOIN comments c ON c.user_id = u.id
     WHERE u.id = :user_id
     GROUP BY u.id, u.username'
);
$statisticsQuery->execute([':user_id' => $userId]);
$statistics = $statisticsQuery->fetch();

if (!$statistics) {
    http_response_code(500);
    exit('Unable to load your dashboard.');
}

$mostCommentedQuery = $db->prepare(
    'SELECT
        r.title,
        COUNT(c.id) AS comment_count
     FROM recipes r
     LEFT JOIN comments c ON c.recipe_id = r.id
     WHERE r.user_id = :user_id
     GROUP BY r.id, r.title
     ORDER BY comment_count DESC, r.created_at DESC
     LIMIT 1'
);
$mostCommentedQuery->execute([':user_id' => $userId]);
$mostCommented = $mostCommentedQuery->fetch();

$favoritesQuery = $db->prepare(
    'SELECT
        r.*, c.name AS category_name, u.username AS author_name
     FROM recipes r
     INNER JOIN favorites f ON r.id = f.recipe_id
     INNER JOIN categories c ON r.category_id = c.id
     INNER JOIN users u ON r.user_id = u.id
     WHERE f.user_id = :user_id
     ORDER BY f.created_at DESC'
);
$favoritesQuery->execute([':user_id' => $userId]);
$favoriteRecipes = $favoritesQuery->fetchAll();

$pageTitle = 'My dashboard';
require __DIR__ . '/header.php';
?>

<section class="hero">
    <h1><?= e($statistics['username']) ?>'s kitchen</h1>
    <p>Your contribution to the Timplada community.</p>
</section>

<div class="stats">
    <div class="stat">
        <strong><?= e($statistics['recipes_posted']) ?></strong>
        <span>Total Recipes Posted</span>
    </div>

    <div class="stat">
        <strong><?= e($statistics['recipes_favorited']) ?></strong>
        <span>Total Recipes Favorited</span>
    </div>

    <div class="stat">
        <strong><?= e($statistics['comments_made']) ?></strong>
        <span>Total Comments Made</span>
    </div>
</div>

<div class="panel">
    <?php if ($mostCommented): ?>
        <h2>Most discussed recipe</h2>
        <p>
            <strong><?= e($mostCommented['title']) ?></strong>
            has <?= e($mostCommented['comment_count']) ?>
            comment<?= (int) $mostCommented['comment_count'] === 1 ? '' : 's' ?>.
        </p>
    <?php endif; ?>

    <h2>Keep cooking, keep sharing</h2>
    <p>
        Your recipes and conversations help preserve the stories
        behind Filipino food.
    </p>
    <a class="btn" href="create-recipe.php">Share another recipe</a>
</div>

<section class="panel favorites-panel">
    <h2>Saved recipes</h2>

    <?php if (!$favoriteRecipes): ?>
        <p>You have not saved any recipes yet.</p>
    <?php else: ?>
        <div class="recipe-grid">
            <?php foreach ($favoriteRecipes as $favoriteRecipe): ?>
                <article class="card">
                    <div class="meta">
                        <?= e($favoriteRecipe['category_name']) ?>
                        &middot;
                        by <?= e($favoriteRecipe['author_name']) ?>
                    </div>
                    <h3><?= e($favoriteRecipe['title']) ?></h3>
                    <p><?= e($favoriteRecipe['description']) ?></p>
                    <a class="btn btn-secondary" href="recipe.php?id=<?= e($favoriteRecipe['id']) ?>">
                        View recipe
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/footer.php'; ?>
