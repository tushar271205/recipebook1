<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$search = trim($_GET['q'] ?? '');
$cat_filter = intval($_GET['cat'] ?? 0);

$sql = "SELECT r.*, u.username, c.name as category FROM recipes r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN categories c ON r.category_id = c.id
        WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (r.title LIKE ? OR r.ingredients LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like;
    $types .= 'ss';
}

if ($cat_filter > 0) {
    $sql .= " AND r.category_id = ?";
    $params[] = $cat_filter;
    $types .= 'i';
}

$sql .= " ORDER BY r.created_at DESC LIMIT 100";
$stmt = $mysqli->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

$cats = $mysqli->query("SELECT * FROM categories");
require_once __DIR__ . '/inc/header.php';
?>

<section class="listings">
  <h2>All Recipes</h2>

  <form class="searchbar" method="get">
    <input name="q" placeholder="Search recipes or ingredients..." value="<?php echo esc($search); ?>">
    <select name="cat">
      <option value="0">All Categories</option>
      <?php while ($c = $cats->fetch_assoc()): ?>
        <option value="<?php echo $c['id']; ?>" <?php if($c['id']==$cat_filter) echo 'selected'; ?>><?php echo esc($c['name']); ?></option>
      <?php endwhile; ?>
    </select>
    <button class="btn" type="submit">Search</button>
  </form>

  <div class="recipes-grid">
    <?php while ($row = $res->fetch_assoc()): ?>
      <div class="recipe-card">
        <a href="view_recipe.php?id=<?php echo $row['id']; ?>">
          <div class="recipe-img">
            <?php if ($row['image'] && file_exists('assets/uploads/'.$row['image'])): ?>
              <img src="assets/uploads/<?php echo esc($row['image']); ?>" alt="<?php echo esc($row['title']); ?>">
            <?php else: ?>
              <div class="placeholder">No Image</div>
            <?php endif; ?>
          </div>
          <div class="recipe-meta">
            <h3><?php echo esc($row['title']); ?></h3>
            <p class="muted">By <?php echo esc($row['username']); ?> • <?php echo esc($row['category'] ?? 'Uncategorized'); ?></p>
          </div>
        </a>

        <div class="card-actions">
          <?php if ($_SESSION['user_id'] == $row['user_id'] || is_admin()): ?>
            <a class="btn small" href="edit_recipe.php?id=<?php echo $row['id']; ?>">Edit</a>
            <a class="btn small danger" href="delete_recipe.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Delete this recipe?');">Delete</a>
          <?php endif; ?>
          <a class="btn small" href="view_recipe.php?id=<?php echo $row['id']; ?>">View</a>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</section>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
