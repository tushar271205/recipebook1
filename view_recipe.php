<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
$stmt = $mysqli->prepare("SELECT r.*, u.username, c.name as category FROM recipes r LEFT JOIN users u ON r.user_id=u.id LEFT JOIN categories c ON r.category_id=c.id WHERE r.id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$recipe = $res->fetch_assoc();
if (!$recipe) {
    header('Location: home.php');
    exit;
}

require_once __DIR__ . '/inc/header.php';
?>
<section class="view-recipe">
  <h2><?php echo esc($recipe['title']); ?></h2>
  <p class="muted">By <?php echo esc($recipe['username']); ?> • <?php echo esc($recipe['category'] ?? 'Uncategorized'); ?></p>

  <div class="view-grid">
    <div class="view-img">
      <?php if ($recipe['image'] && file_exists('assets/uploads/'.$recipe['image'])): ?>
        <img src="assets/uploads/<?php echo esc($recipe['image']); ?>" alt="<?php echo esc($recipe['title']); ?>">
      <?php else: ?>
        <div class="placeholder">No Image</div>
      <?php endif; ?>
    </div>
    <div class="view-content">
      <h3>Ingredients</h3>
      <p><?php echo nl2br(esc($recipe['ingredients'])); ?></p>

      <h3>Instructions</h3>
      <p><?php echo nl2br(esc($recipe['instructions'])); ?></p>

      <h3>Description</h3>
      <p><?php echo nl2br(esc($recipe['description'])); ?></p>
    </div>
  </div>

  <div class="actions">
    <?php if ($_SESSION['user_id'] == $recipe['user_id'] || is_admin()): ?>
      <a class="btn" href="edit_recipe.php?id=<?php echo $recipe['id']; ?>">Edit</a>
      <a class="btn danger" href="delete_recipe.php?id=<?php echo $recipe['id']; ?>" onclick="return confirm('Delete this recipe?');">Delete</a>
    <?php endif; ?>
    <a class="btn outline" href="home.php">Back</a>
  </div>
</section>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
