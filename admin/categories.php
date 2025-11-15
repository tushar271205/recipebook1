<?php
// admin/categories.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';

if (!is_admin()) {
    header('Location: ../login.php');
    exit;
}

$errors = [];
$success = '';

// Add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $errors[] = "Category name is required.";
    } else {
        $stmt = $mysqli->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Category already exists.";
            $stmt->close();
        } else {
            $stmt->close();
            $ins = $mysqli->prepare("INSERT INTO categories (name) VALUES (?)");
            $ins->bind_param('s', $name);
            if ($ins->execute()) {
                $success = "Category added.";
                $ins->close();
                header('Location: categories.php'); // refresh to show new category
                exit;
            } else {
                $errors[] = "DB error: " . $ins->error;
                $ins->close();
            }
        }
    }
}

// Delete
if (isset($_GET['delete'])) {
    $delid = intval($_GET['delete']);
    if ($delid > 0) {
        $del = $mysqli->prepare("DELETE FROM categories WHERE id = ?");
        $del->bind_param('i', $delid);
        $del->execute();
        $del->close();
        header('Location: categories.php');
        exit;
    }
}

// fetch categories
$cats = [];
$q = $mysqli->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($q) {
    while ($r = $q->fetch_assoc()) $cats[] = $r;
} else {
    $errors[] = "DB error: " . $mysqli->error;
}

require_once __DIR__ . '/../inc/header.php';
?>

<section class="admin">
  <h2>Manage Categories</h2>

  <?php if ($success): ?><div class="alert success"><?php echo esc($success); ?></div><?php endif; ?>
  <?php if ($errors): ?><div class="alert error"><?php foreach($errors as $e) echo '<div>'.esc($e).'</div>'; ?></div><?php endif; ?>

  <div class="form-section" style="max-width:720px;">
    <form method="post" class="form-card">
      <input type="hidden" name="action" value="add">
      <div class="form-row">
        <div class="field">
          <label>New Category</label>
          <input type="text" name="name" placeholder="e.g. Street Food" required>
        </div>
        <div style="display:flex;align-items:flex-end">
          <button class="btn" type="submit">Add</button>
        </div>
      </div>
    </form>
  </div>

  <h3>Existing Categories</h3>
  <?php if (empty($cats)): ?>
    <p class="muted">No categories found.</p>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>ID</th><th>Name</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach($cats as $c): ?>
          <tr>
            <td><?php echo (int)$c['id']; ?></td>
            <td><?php echo esc($c['name']); ?></td>
            <td>
              <a class="btn small outline" href="categories.php?delete=<?php echo (int)$c['id']; ?>" onclick="return confirm('Delete <?php echo esc($c['name']); ?>?');">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
