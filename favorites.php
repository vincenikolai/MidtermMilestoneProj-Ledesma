<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

requireLogin();

$db = Database::getInstance()->getConnection();
$favoriteQuery = $db->prepare(
    'SELECT
        r.*,
        c.name AS category_name,
        u.username AS author_name
     FROM recipes r
     INNER JOIN favorites f ON r.id = f.recipe_id
     INNER JOIN categories c ON r.category_id = c.id
     INNER JOIN users u ON r.user_id = u.id
     WHERE f.user_id = :user_id
     ORDER BY f.created_at DESC'
);
$favoriteQuery->execute([':user_id' => currentUserId()]);
$favoriteRecipes = $favoriteQuery->fetchAll();

$pageTitle = 'Favorites';
require __DIR__ . '/header.php';
?>

<section class="hero">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <h1><span class="hero-star" aria-hidden="true">★</span> Your favorites</h1>
    <p>Recipes you saved for your next Filipino home-cooked meal.</p>
</section>

<?php if (!$favoriteRecipes): ?>
    <section class="panel empty-state">
        <div class="empty-star" aria-hidden="true">☆</div>
        <h2>Your favorites are waiting</h2>
        <p>Tap the star on a recipe to save it here.</p>
        <a class="btn" href="index.php">Explore recipes</a>
    </section>
<?php else: ?>
    <div class="recipe-grid">
        <?php foreach ($favoriteRecipes as $recipe): ?>
            <article class="card">
                <div class="meta">
                    <?= e($recipe['category_name']) ?>
                    &middot;
                    by <?= e($recipe['author_name']) ?>
                </div>
                <h2><?= e($recipe['title']) ?></h2>
                <p><?= e($recipe['description']) ?></p>
                <div class="card-actions">
                    <a class="btn btn-secondary" href="recipe.php?id=<?= e($recipe['id']) ?>">
                        View recipe
                    </a>
                    <button
                        class="btn btn-muted favorite-toggle is-saved"
                        data-recipe-id="<?= e($recipe['id']) ?>"
                        data-remove-on-unsave="true"
                        aria-label="Remove from favorites"
                        aria-pressed="true"
                        type="button"
                    >★ Saved</button>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
