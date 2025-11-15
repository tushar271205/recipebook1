<?php
// add_recipe.php
// Full page — handles add recipe with category select (safe, nullable fields, image upload)

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $ingredients = isset($_POST['ingredients']) ? trim($_POST['ingredients']) : null;
    $instructions = isset($_POST['instructions']) ? trim($_POST['instructions']) : null;
    $category_id = intval($_POST['category_id'] ?? 0);

    if ($title === '') $errors[] = "Title required.";
    // Optional: require ingredients / instructions by uncommenting:
    // if ($ingredients === '' || $ingredients === null) $errors[] = "Ingredients required.";
    // if ($instructions === '' || $instructions === null) $errors[] = "Instructions required.";

    // Handle image upload (optional)
    $imageName = null;
    if (!empty($_FILES['image']['name'])) {
        $img = $_FILES['image'];
        $ext = strtolower(pathinfo($img['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allowed)) $errors[] = "Invalid image type. Allowed: jpg,jpeg,png,gif,webp.";
        if ($img['size'] > 3 * 1024 * 1024) $errors[] = "Image too big (max 3MB).";

        if (empty($errors)) {
            $imageName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $dest = __DIR__ . '/assets/uploads/' . $imageName;
            if (!move_uploaded_file($img['tmp_name'], $dest)) {
                $errors[] = "Failed to move uploaded file.";
            }
        }
    }

    if (empty($errors)) {
        // Convert empty strings to NULL so DB doesn't get weird '0' values
        $description = ($description === '') ? null : $description;
        $ingredients  = ($ingredients === '' || $ingredients === null) ? null : $ingredients;
        $instructions = ($instructions === '' || $instructions === null) ? null : $instructions;
        $catVal = ($category_id > 0) ? $category_id : null;
        $imgVal = $imageName ? $imageName : null;

        // Prepare insert with correct param order:
        // user_id (i), title (s), description (s|null), ingredients (s|null),
        // instructions (s|null), category_id (i|null), image (s|null)
        $sql = "INSERT INTO recipes (user_id, title, description, ingredients, instructions, category_id, image)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            $errors[] = "Database prepare error: " . $mysqli->error;
        } else {
            $uid = $_SESSION['user_id'];
            // types: i s s s s i s  -> 'issssis'
            $stmt->bind_param('issssis', $uid, $title, $description, $ingredients, $instructions, $catVal, $imgVal);

            if ($stmt->execute()) {
                $stmt->close();
                $success = "Recipe added successfully.";
                header('Location: home.php');
                exit;
            } else {
                $errors[] = "Database error: " . $stmt->error;
                $stmt->close();
            }
        }
    }
}

// Fetch categories for the select dropdown (this is the block you asked to set)
$cats = $mysqli->query("SELECT id, name FROM categories ORDER BY name ASC");

require_once __DIR__ . '/inc/header.php';
?>

<section class="form-section">
  <h2>Add Recipe</h2>

  <?php if (!empty($errors)): ?>
    <div class="alert error">
      <?php foreach ($errors as $e) echo '<div>' . esc($e) . '</div>'; ?>
    </div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert success"><?php echo esc($success); ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="form-card" id="addRecipeForm">
    <div class="form-row">
      <div class="field" style="flex:1 1 100%;">
        <label>Title</label>
        <input name="title" type="text" required value="<?php echo esc($_POST['title'] ?? ''); ?>">
      </div>
    </div>

    <!-- Category select (this is the exact block you provided, integrated) -->
    <div class="form-row" style="margin-top:12px;">
      <div class="field" style="flex:1;">
        <label>Category</label>
        <select name="category_id">
          <option value="0">Select (optional)</option>
          <?php if ($cats): while($c = $cats->fetch_assoc()): ?>
            <option value="<?php echo (int)$c['id']; ?>" <?php if(!empty($_POST['category_id']) && (int)$_POST['category_id'] == $c['id']) echo 'selected'; ?>>
              <?php echo esc($c['name']); ?>
            </option>
          <?php endwhile; endif; ?>
        </select>
      </div>
    </div>

    <div class="form-row" style="margin-top:12px;">
      <div class="field">
        <label>Description</label>
        <textarea name="description"><?php echo esc($_POST['description'] ?? ''); ?></textarea>
      </div>
    </div>

    <div class="form-row" style="margin-top:12px;">
      <div class="field">
        <label>Ingredients</label>
        <textarea name="ingredients" placeholder="One per line"><?php echo esc($_POST['ingredients'] ?? ''); ?></textarea>
        <div class="field-help">Write one ingredient per line.</div>
      </div>
    </div>

    <div class="form-row" style="margin-top:12px;">
      <div class="field">
        <label>Instructions</label>
        <textarea name="instructions"><?php echo esc($_POST['instructions'] ?? ''); ?></textarea>
      </div>
    </div>

    <div class="form-row" style="margin-top:12px;">
      <div class="field">
        <label>Image</label>
        <input type="file" name="image" accept="image/*">
      </div>
    </div>

    <div style="margin-top:16px;">
      <button class="btn" type="submit">Add Recipe</button>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
