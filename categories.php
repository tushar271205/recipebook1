<?php
// categories.php (root)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/functions.php';

// require login (if you want public viewing, remove the next block)
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// fetch categories safely
$cats = [];
$q = $mysqli->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($q) {
    while ($r = $q->fetch_assoc()) $cats[] = $r;
} else {
    // query failed
    $queryErr = $mysqli->error;
}

require_once __DIR__ . '/inc/header.php';
?>

<section class="categories">
  <h2>Categories</h2>

  <?php if (!empty($queryErr)): ?>
    <div class="alert error">Database error: <?php echo esc($queryErr); ?></div>
  <?php endif; ?>

  <?php if (empty($cats)): ?>
    <p class="muted">No categories found. (If you are admin, add categories via Admin → Categories.)</p>
  <?php else: ?>
    <div class="categories-grid">
      <?php foreach ($cats as $c): ?>
        <a class="cat-card" href="home.php?cat=<?php echo (int)$c['id']; ?>">
          <div>
            <h3><?php echo esc($c['name']); ?></h3>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
