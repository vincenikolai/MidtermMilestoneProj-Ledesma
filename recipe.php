<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/RecipeModel.php';
require_once __DIR__ . '/Comment.php';
requireLogin();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('Invalid recipe.'); }
$recipeModel = new Recipe();
$recipe = $recipeModel->getRecipeWithIngredients($id, currentUserId());
if (!$recipe) { http_response_code(404); exit('Recipe not found.'); }
$commentModel = new Comment();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $message = 'Your session expired.';
    } else {
        try {
            $action = $_POST['action'] ?? '';
            $content = trim((string) ($_POST['content'] ?? ''));
            if ($action === 'add' && $content !== '') $commentModel->add($id, currentUserId(), $content);
            elseif ($action === 'edit' && $content !== '') $commentModel->update((int) $_POST['comment_id'], currentUserId(), $content);
            elseif ($action === 'delete') $commentModel->delete((int) $_POST['comment_id'], currentUserId());
            header("Location: recipe.php?id=$id");
            exit;
        } catch (Throwable $exception) { $message = 'Comment action could not be completed.'; }
    }
}
$pageTitle = $recipe['title']; require __DIR__ . '/header.php';
?>
<div class="detail"><article class="panel"><div class="meta"><?= e($recipe['category_name']) ?> &middot; by <?= e($recipe['username']) ?></div><h1><?= e($recipe['title']) ?> <?php if ((int) $recipe['is_edited'] === 1): ?><span class="edited">(Edited)</span><?php endif; ?></h1><button class="btn btn-muted favorite-toggle<?= (int) $recipe['is_favorited'] === 1 ? ' is-saved' : '' ?>" data-recipe-id="<?= e($id) ?>" type="button"><?= (int) $recipe['is_favorited'] === 1 ? 'Saved' : 'Save' ?></button><p><?= nl2br(e($recipe['description'])) ?></p><h2>Ingredients</h2><ul class="ingredients"><?php foreach ($recipe['ingredients'] as $ingredient): ?><li><?= e($ingredient['ingredient_name']) ?></li><?php endforeach; ?></ul><h2>Method</h2><p><?= nl2br(e($recipe['steps'])) ?></p><?php if ((int) $recipe['user_id'] === currentUserId()): ?><a class="btn btn-muted" href="edit-recipe.php?id=<?= e($id) ?>">Edit recipe</a><?php endif; ?></article>
<aside class="panel"><h2>Comments</h2><?php if ($message): ?><p class="error"><?= e($message) ?></p><?php endif; ?><?php foreach ($commentModel->allForRecipe($id) as $comment): ?><div class="comment"><div class="meta"><?= e($comment['username']) ?><?php if ((int) $comment['is_edited'] === 1): ?> <span class="edited">(Edited)</span><?php endif; ?></div><p><?= nl2br(e($comment['content'])) ?></p><?php if ((int) $comment['user_id'] === currentUserId()): ?><form method="post" class="comment-form"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="comment_id" value="<?= e($comment['id']) ?>"><input type="hidden" name="action" value="edit"><input class="form-control" name="content" maxlength="1000" required value="<?= e($comment['content']) ?>"><button class="btn btn-muted" type="submit">Update</button></form><form method="post" class="comment-form"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="comment_id" value="<?= e($comment['id']) ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-danger" type="submit">Delete</button></form><?php endif; ?></div><?php endforeach; ?><form method="post" class="comment-form"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="add"><textarea class="form-control" name="content" maxlength="1000" required placeholder="Share your thoughts..."></textarea><button class="btn" type="submit">Post comment</button></form></aside></div>
<?php require __DIR__ . '/footer.php'; ?>
