<?php
// my_recipes.php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$uid = $_SESSION['user_id'];

// fetch user info (for profile display)
$stmt = $mysqli->prepare("SELECT id, username, email, avatar, created_at FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// fetch user's recipes (most recent first)
$stmt2 = $mysqli->prepare("SELECT r.*, c.name AS category_name FROM recipes r LEFT JOIN categories c ON r.category_id = c.id WHERE r.user_id = ? ORDER BY r.created_at DESC");
$stmt2->bind_param('i', $uid);
$stmt2->execute();
$recipes = $stmt2->get_result();
$stmt2->close();

require_once __DIR__ . '/inc/header.php';
?>

<section class="profile-section card" style="display:flex;gap:18px;align-items:center;padding:18px;margin-bottom:18px;">
  <div style="width:120px;flex:0 0 120px;">
    <?php
      if (!empty($user['avatar']) && file_exists(__DIR__ . '/assets/uploads/avatars/' . $user['avatar'])) {
          $avatarUrl = 'assets/uploads/avatars/' . $user['avatar'];
      } else {
          $hash = md5(strtolower(trim($user['email'] ?? '')));
          $avatarUrl = "assets/download.svg";
      }
    ?>
    <img src="<?php echo esc($avatarUrl); ?>" alt="avatar" style="width:120px;height:120px;border-radius:12px;object-fit:cover;border:1px solid rgba(0,0,0,0.06)">
  </div>

  <div style="flex:1;">
    <h2 style="margin:0 0 8px 0;"><?php echo esc($user['username'] ?? ''); ?></h2>
    <p class="small muted" style="margin:0 0 6px 0;"><?php echo esc($user['email'] ?? ''); ?></p>
    <p class="small muted" style="margin:0;">Member since: <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
    <div style="margin-top:12px;">
      <a class="btn small" href="add_recipe.php">Add Recipe</a>
      <a class="btn small outline" href="edit_profile.php">Edit Profile</a>
    </div>
  </div>
</section>

<section class="card">
  <h2>My Recipes</h2>

  <?php if ($recipes->num_rows === 0): ?>
    <p class="muted">You haven't added any recipes yet. Click "Add Recipe" to create one.</p>
  <?php else: ?>
    <div class="recipes-grid" style="margin-top:12px;">
      <?php while($r = $recipes->fetch_assoc()): ?>
        <div class="recipe-card">
          <a href="view_recipe.php?id=<?php echo (int)$r['id']; ?>">
            <div class="recipe-img">
              <?php if (!empty($r['image']) && file_exists(__DIR__ . '/assets/uploads/' . $r['image'])): ?>
                <img src="assets/uploads/<?php echo esc($r['image']); ?>" alt="<?php echo esc($r['title']); ?>">
              <?php else: ?>
                <div class="placeholder">No Image</div>
              <?php endif; ?>
            </div>
            <div class="recipe-meta">
              <h3><?php echo esc($r['title']); ?></h3>
              <p class="muted"><?php echo esc($r['category_name'] ?? 'Uncategorized'); ?> • <?php echo date('M d, Y', strtotime($r['created_at'])); ?></p>
            </div>
          </a>

          <div class="card-actions">
            <a class="btn small" href="view_recipe.php?id=<?php echo (int)$r['id']; ?>">View</a>
            <a class="btn small outline" href="edit_recipe.php?id=<?php echo (int)$r['id']; ?>">Edit</a>

            <form method="post" action="delete_recipe.php" style="display:inline;">
              <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
              <!-- Since our delete_recipe.php earlier used GET id, we support both.
                   But to be safe and avoid accidental deletes, use JS confirm. -->
              <a class="btn small danger" href="delete_recipe.php?id=<?php echo (int)$r['id']; ?>" onclick="return confirm('Delete this recipe?');">Delete</a>
            </form>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
