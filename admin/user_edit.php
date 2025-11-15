<?php
// admin/user_edit.php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
if (!is_admin()) { header('Location: ../login.php'); exit; }

$id = intval($_GET['id'] ?? 0);
if ($id<=0) { header('Location: users.php'); exit; }

// fetch
$stmt = $mysqli->prepare("SELECT id, username, email, is_admin, avatar FROM users WHERE id=? LIMIT 1");
$stmt->bind_param('i',$id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$user) { header('Location: users.php'); exit; }

$errors=[]; $success='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $is_admin = isset($_POST['is_admin'])?1:0;

    if ($username==='') $errors[]='Username required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[]='Valid email required.';

    // unique check excluding self
    $chk = $mysqli->prepare("SELECT id FROM users WHERE (username=? OR email=?) AND id != ? LIMIT 1");
    $chk->bind_param('ssi', $username, $email, $id);
    $chk->execute(); $chk->store_result();
    if ($chk->num_rows>0) $errors[]='Username or email taken by another user.';
    $chk->close();

    // avatar upload (optional)
    $avatarName = $user['avatar'];
    if (!empty($_FILES['avatar']['name'])) {
        $img = $_FILES['avatar'];
        $ext = strtolower(pathinfo($img['name'], PATHINFO_EXTENSION));
        $allowed=['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext,$allowed)) $errors[]='Invalid avatar type.';
        if ($img['size'] > 2*1024*1024) $errors[]='Avatar too big (max 2MB).';
        if (empty($errors)) {
            $dir = __DIR__ . '/../assets/uploads/avatars/';
            if (!is_dir($dir)) mkdir($dir,0755,true);
            $new = time().'_'.bin2hex(random_bytes(6)).'.'.$ext;
            $dest = $dir.$new;
            if (!move_uploaded_file($img['tmp_name'],$dest)) $errors[]='Failed to upload avatar.';
            else $avatarName = $new;
        }
    }

    // optional password change
    $newpw = $_POST['new_password'] ?? '';
    $passwordSqlPart = '';
    $passwordHash = null;
    if ($newpw !== '') {
        if (strlen($newpw) < 6) $errors[]='New password min 6 chars.';
        else $passwordHash = password_hash($newpw, PASSWORD_DEFAULT);
    }

    if (empty($errors)) {
        if ($passwordHash !== null) {
            $up = $mysqli->prepare("UPDATE users SET username=?, email=?, is_admin=?, avatar=?, password=? WHERE id=?");
            $up->bind_param('ssissi', $username, $email, $is_admin, $avatarName, $passwordHash, $id);
        } else {
            $up = $mysqli->prepare("UPDATE users SET username=?, email=?, is_admin=?, avatar=? WHERE id=?");
            $up->bind_param('ssisi', $username, $email, $is_admin, $avatarName, $id);
        }
        if ($up->execute()) {
            // remove old avatar if replaced
            if (!empty($user['avatar']) && $avatarName !== $user['avatar']) {
                @unlink(__DIR__ . '/../assets/uploads/avatars/' . $user['avatar']);
            }
            header('Location: users.php'); exit;
        } else {
            $errors[] = 'DB error: '.$up->error;
        }
    }
}

require_once __DIR__ . '/../inc/header.php';
?>

<section class="form-section">
  <h2>Edit User</h2>
  <?php if ($errors): ?><div class="errors"><?php foreach($errors as $e) echo '<div>'.esc($e).'</div>'; ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <label>Username<input name="username" value="<?php echo esc($_POST['username'] ?? $user['username']); ?>"></label>
    <label>Email<input name="email" value="<?php echo esc($_POST['email'] ?? $user['email']); ?>"></label>

    <label>Avatar (optional)
      <input type="file" name="avatar" accept="image/*">
    </label>

    <label><input type="checkbox" name="is_admin" <?php if(($_POST['is_admin'] ?? $user['is_admin'])) echo 'checked'; ?>> Make Admin</label>

    <hr>
    <p class="small muted">Change password (optional):</p>
    <label>New Password<input type="password" name="new_password"></label>

    <div style="margin-top:12px;">
      <button class="btn" type="submit">Save</button>
      <a class="btn outline" href="users.php">Cancel</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
