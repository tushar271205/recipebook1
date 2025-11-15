<?php
// inc/header.php - secure header with session and robust baseUrl
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Optional: you can set cache headers for pages that should never be cached.
// For public pages you might not want to disable cache (but for safety we keep it minimal).
// header("Cache-Control: no-cache, no-store, must-revalidate");
// header("Pragma: no-cache");
// header("Expires: 0");

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// derive base url (absolute) so assets load correctly from admin subfolders too
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$scriptDir = dirname($_SERVER['SCRIPT_NAME']); // e.g. /p1 or /p1/admin
// if last segment is admin, remove it (so base points to app root)
$parts = explode('/', trim($scriptDir, '/'));
if (!empty($parts) && end($parts) === 'admin') array_pop($parts);
$webBase = '/' . implode('/', array_filter($parts));
if ($webBase === '/') $webBase = '';
$baseUrl = rtrim($scheme . '://' . $host . $webBase, '/');

// current user info if logged in
$currentUser = null;
if (is_logged_in()) {
    $uid = $_SESSION['user_id'];
    $stmtU = $mysqli->prepare("SELECT id, username, email, avatar, created_at FROM users WHERE id = ? LIMIT 1");
    if ($stmtU) {
        $stmtU->bind_param('i', $uid);
        $stmtU->execute();
        $currentUser = $stmtU->get_result()->fetch_assoc();
        $stmtU->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Recipe Book</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <!-- CSS (absolute URLs to avoid subfolder path issues) -->
  <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/admin.css">
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <a class="logo" href="<?php echo $baseUrl; ?>/index.php">
      <span class="logo-mark">RB</span>
      <span style="margin-left:6px;font-weight:700;color:var(--accent)">RecipeBook</span>
    </a>

    <nav class="main-nav">
      <?php if(is_logged_in()): ?>
        <a href="<?php echo $baseUrl; ?>/home.php">Home</a>
        <a href="<?php echo $baseUrl; ?>/categories.php">Categories</a>
        <a href="<?php echo $baseUrl; ?>/add_recipe.php">Add Recipe</a>
        <a href="<?php echo $baseUrl; ?>/my_recipes.php">My Recipes</a>

        <?php if(is_admin()): ?>
          <a href="<?php echo $baseUrl; ?>/admin/index.php">Admin</a>
        <?php endif; ?>

        <a href="<?php echo $baseUrl; ?>/logout.php" class="btn-logout">Logout</a>

        <span style="margin-left:12px;display:inline-flex;align-items:center;gap:10px;">
          <?php
            if (!empty($currentUser['avatar']) && file_exists(__DIR__ . '/../assets/uploads/avatars/' . $currentUser['avatar'])) {
                $avatarUrl = $baseUrl . '/assets/uploads/avatars/' . $currentUser['avatar'];
            } else {
                $avatarUrl = $baseUrl . '/assets/download.svg';
            }
          ?>
          <img src="<?php echo esc($avatarUrl); ?>" alt="avatar" style="width:36px;height:36px;border-radius:8px;object-fit:cover;border:1px solid rgba(0,0,0,0.06)">
          <span style="font-weight:600;"><?php echo esc($currentUser['username'] ?? $_SESSION['username'] ?? ''); ?></span>
        </span>

      <?php else: ?>
        <a href="<?php echo $baseUrl; ?>/index.php">About</a>
        <a href="<?php echo $baseUrl; ?>/login.php" class="btn-login">Login</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="site-main container">
