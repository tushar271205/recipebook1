<?php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';

if (!is_admin()) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../inc/header.php';
?>
<section class="admin">
  <h2>Admin Dashboard</h2>
  <div class="admin-cards">
    <a class="admin-card" href="users.php">Manage Users</a>
    <a class="admin-card" href="recipes.php">Manage Recipes</a>
  </div>
</section>
<?php require_once __DIR__ . '/../inc/footer.php'; ?>
