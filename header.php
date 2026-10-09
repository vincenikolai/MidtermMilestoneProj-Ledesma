<?php
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'Timplada';
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Timplada</title>
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <link rel="stylesheet" href="style.css">
    <script src="app.js?v=2" defer></script>
</head>
<body>
<header class="site-header">
    <div class="nav-wrap">
        <a class="brand" href="index.php"><span class="brand-mark">TI</span><span>Timplada<small>Filipino Home Cooking</small></span></a>
        <?php if (!empty($_SESSION['user_id'])): ?>
            <nav>
                <a href="index.php">Recipes</a>
                <a href="create-recipe.php">Share a Recipe</a>
                <a href="profile.php">My Dashboard</a>
                <a href="favorites.php">Favorites <span class="nav-star" aria-hidden="true">★</span></a>
                <a class="nav-logout" href="logout.php">Log out</a>
            </nav>
        <?php endif; ?>
    </div>
</header>
<main class="container">
