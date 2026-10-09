<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/RecipeModel.php';

requireLogin();

$recipeModel = new Recipe();
$keyword = trim((string) ($_GET['q'] ?? ''));
$categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: null;

$recipes = $recipeModel->getAllRecipes(currentUserId(), $keyword, $categoryId);
$categories = $recipeModel->categories();
$pageTitle = 'Recipe collection';

require __DIR__ . '/header.php';
?>

<section class="hero">
    <h1>Flavors from our Filipino homes</h1>
    <p>
        Discover recipes made for sharing, from everyday pang-ulam
        to cherished merienda and desserts.
    </p>
</section>

<form class="toolbar" method="get">
    <input
        name="q"
        placeholder="Search recipes..."
        value="<?= e($keyword) ?>"
    >

    <select name="category">
        <option value="">All categories</option>
        <?php foreach ($categories as $category): ?>
            <option
                value="<?= e($category['id']) ?>"
                <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>
            >
                <?= e($category['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button class="btn" type="submit">Find recipes</button>
</form>

<?php if (!$recipes): ?>
    <div class="panel">
        <p>
            No recipes matched your search.
            Be the first to <a href="create-recipe.php">share one</a>.
        </p>
    </div>
<?php endif; ?>

<div class="recipe-grid">
    <?php foreach ($recipes as $recipe): ?>
        <article class="card">
            <div class="meta">
                <?= e($recipe['category_name']) ?>
                &middot;
                by <?= e($recipe['username']) ?>
            </div>

            <h2>
                <?= e($recipe['title']) ?>
                <?php if ((int) $recipe['is_edited'] === 1): ?>
                    <span class="edited">(Edited)</span>
                <?php endif; ?>
            </h2>

            <p><?= e($recipe['description']) ?></p>

            <div class="card-actions">
                <a class="btn btn-secondary" href="recipe.php?id=<?= e($recipe['id']) ?>">
                    View recipe
                </a>

                <button
                    class="btn btn-muted favorite-toggle"
                    data-recipe-id="<?= e($recipe['id']) ?>"
                    type="button"
                >
                    <?= (int) $recipe['is_favorited'] === 1 ? 'Saved' : 'Save' ?>
                </button>

                <?php if ((int) $recipe['user_id'] === currentUserId()): ?>
                    <a class="btn btn-muted" href="edit-recipe.php?id=<?= e($recipe['id']) ?>">
                        Edit
                    </a>

                    <form
                        method="post"
                        action="delete-recipe.php"
                        data-confirm="Delete this recipe?"
                    >
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= e($recipe['id']) ?>">
                        <button class="btn btn-danger" type="submit">Delete</button>
                    </form>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/footer.php'; ?>
