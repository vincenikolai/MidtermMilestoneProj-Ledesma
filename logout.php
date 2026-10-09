<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $parameters = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $parameters['path'],
        $parameters['domain'] ?? '',
        (bool) $parameters['secure'],
        (bool) $parameters['httponly']
    );
}

session_destroy();

header('Location: login.php');
exit;
