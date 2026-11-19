<?php
if (session_status() === PHP_SESSION_NONE) { #if session is started
    session_start();
}

function appUrl(string $path): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $directory = dirname($script);
    if (in_array(basename($directory), ['member', 'coach', 'admin'], true)) {
        $directory = dirname($directory);
    }
    $base = rtrim(str_replace('\\', '/', $directory), '/');
    return $base . '/' . ltrim($path, '/');
}

function checkAccess(array $allowedRoles = []) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . appUrl("index.php"));
        exit;
    }

    if (!empty($allowedRoles) && !in_array($_SESSION['role'], $allowedRoles)) {
        http_response_code(403);
        die("<h2>403 No permissions for this section.</h2>");
    }
}
?>