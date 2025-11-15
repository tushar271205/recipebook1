<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
$stmt = $mysqli->prepare("SELECT * FROM recipes WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$recipe = $res->fetch_assoc();
if (!$recipe) {
    header('Location: home.php');
    exit;
}
if ($_SESSION['user_id'] != $recipe['user_id'] && !is_admin()) {
    header('Location: home.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $ingredients = trim($_POST['ingredients'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);

    if ($title === '') $errors[] = "Title required.";
    if ($ingredients === '') $errors[] = "Ingredients required.";
    if ($instructions === '') $errors[] = "Instructions required.";

    // handle new image
    $imageName = $recipe['image'];
    if (!empty($_FILES['image']['name'])) {
        $img = $_FILES['image'];
        $ext = pathinfo($img['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png','gif'];
        if (!in_array(strtolower($ext), $allowed)) $errors[] = "Invalid image type.";
        if ($img['size'] > 2 * 1024 * 1024) $errors[] = "Image too big (max 2MB).";

        if (empty($errors)) {
            $imageName = time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
            $dest = __DIR__ . '/assets/uploads/' . $imageName;
            if (!move_uploaded_file($img['tmp_name'], $dest)) {
                $errors[] = "Failed to move uploaded file.";
            } else {
                // remove old
                if ($recipe['image'] && file_exists(__DIR__ . '/assets/uploads/' . $recipe['image'])) {
                    @unlink(__DIR__ . '/assets/uploads/' . $recipe['image']);
                }
            }
        }
    }

    if (empty($errors)) {
        $sql = "UPDATE recipes SET title=?, description=?, ingredients=?, instructions=?, category_id=?, image=? WHERE id=?";
        $catVal = $category_id > 0 ? $category_id : null;
        $stmt2 = $mysqli->prepare($sql);
        $stmt2->bind_param('ssssisi', $title, $description, $ingredients, $instructions, $catVal, $imageName, $id);
        if ($stmt2->execute()) {
            header('Location: view_recipe.php?id=' . $id);
            exit;
        } else {
            $errors[] = "Database error: could not update.";
        }
    }
}

$cats = $mysqli->query("SELECT * FROM categories");
require_once __DIR__ . '/inc/header.php';
?>
<section class="form-section">
  <h2>Edit Recipe</h2>
  <?php if ($errors): ?>
    <div class="errors"><?php foreach($errors as $e) echo '<p>'.esc($e).'</p>'; ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <label>Title <input name="title" required value="<?php echo esc($recipe['title']); ?>"></label>
    <label>Category
      <select name="category_id">
        <option value="0">Select (optional)</option>
        <?php while($c=$cats->fetch_assoc()): ?>
          <option value="<?php echo $c['id']; ?>" <?php if($c['id']==$recipe['category_id']) echo 'selected'; ?>><?php echo esc($c['name']); ?></option>
        <?php endwhile; ?>
      </select>
    </label>
    <label>Description <textarea name="description"><?php echo esc($recipe['description']); ?></textarea></label>
    <label>Ingredients <textarea name="ingredients" required><?php echo esc($recipe['ingredients']); ?></textarea></label>
    <label>Instructions <textarea name="instructions" required><?php echo esc($recipe['instructions']); ?></textarea></label>
    <label>Image (leave empty to keep current)
      <input type="file" name="image" accept="image/*">
    </label>

    <button class="btn" type="submit">Update Recipe</button>
  </form>
</section>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
