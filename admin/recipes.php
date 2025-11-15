<?php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';

if (!is_admin()) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../inc/header.php';

// Recipes fetch — ASCENDING ORDER BY ID
$recipes = $mysqli->query("
    SELECT r.id, r.title, r.created_at,
           u.username AS owner,
           c.name AS category
    FROM recipes r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN categories c ON r.category_id = c.id
    ORDER BY r.id ASC
");
?>

<h2>Manage Recipes</h2>

<table class="admin-table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Title</th>
      <th>Category</th>
      <th>Owner</th>
      <th>Created</th>
      <th>Actions</th>
    </tr>
  </thead>

  <tbody>
    <?php while($r = $recipes->fetch_assoc()): ?>
      <tr>
        <td data-label="ID"><?php echo $r['id']; ?></td>

        <td data-label="Title"><?php echo esc($r['title']); ?></td>

        <td data-label="Category">
          <?php echo esc($r['category'] ?? "None"); ?>
        </td>

        <td data-label="Owner">
          <?php echo esc($r['owner'] ?? "Unknown"); ?>
        </td>

        <td data-label="Created">
          <?php echo $r['created_at']; ?>
        </td>

        <td data-label="Actions">
          <div class="table-actions">
            <a href="../view_recipe.php?id=<?php echo $r['id']; ?>" class="btn small outline">View</a>
            <a href="../edit_recipe.php?id=<?php echo $r['id']; ?>" class="btn small">Edit</a>
            <a href="../delete_recipe.php?id=<?php echo $r['id']; ?>" class="btn small danger"
               onclick="return confirm('Delete this recipe?');">
               Delete
            </a>
          </div>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
