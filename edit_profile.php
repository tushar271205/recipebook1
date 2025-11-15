<?php
// edit_profile.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$uid = (int) $_SESSION['user_id'];
$errors = [];
$success = '';

// fetch current user
$stmt = $mysqli->prepare("SELECT id, username, email, avatar, created_at, password FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $errors[] = "User not found.";
}

// POST handler: when user clicks OK
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect inputs
    $new_username = trim($_POST['username'] ?? '');
    $new_email = trim($_POST['email'] ?? '');

    $current_pw = $_POST['current_password'] ?? '';
    $new_pw = $_POST['new_password'] ?? '';
    $confirm_pw = $_POST['confirm_password'] ?? '';

    // Basic validation
    if ($new_username === '') $errors[] = "Username is required.";
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";

    // Ensure username/email uniqueness (other users)
    $chk = $mysqli->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ? LIMIT 1");
    $chk->bind_param('ssi', $new_username, $new_email, $uid);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $errors[] = "Username or email already taken by another account.";
    }
    $chk->close();

    // Avatar upload (optional)
    $avatarDir = __DIR__ . '/assets/uploads/avatars/';
    if (!is_dir($avatarDir)) {
        @mkdir($avatarDir, 0755, true);
    }
    $avatarName = $user['avatar']; // default keep old

    if (!empty($_FILES['avatar']['name'])) {
        $img = $_FILES['avatar'];
        $ext = strtolower(pathinfo($img['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allowed)) {
            $errors[] = "Avatar must be an image (jpg, jpeg, png, gif, webp).";
        } elseif ($img['size'] > 2 * 1024 * 1024) {
            $errors[] = "Avatar too big (max 2MB).";
        } elseif ($img['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Avatar upload error (code {$img['error']}).";
        } else {
            // save file
            $newName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $dest = $avatarDir . $newName;
            if (!move_uploaded_file($img['tmp_name'], $dest)) {
                $errors[] = "Failed to move uploaded avatar. Check folder permissions.";
            } else {
                // on success, set avatarName to new; old removal after DB success
                $avatarName = $newName;
            }
        }
    }

    // Password change (optional)
    $updatePasswordHash = null;
    if ($current_pw !== '' || $new_pw !== '' || $confirm_pw !== '') {
        // require all three to proceed
        if ($current_pw === '' || $new_pw === '' || $confirm_pw === '') {
            $errors[] = "To change password, fill current, new and confirm password fields.";
        } else {
            if (!password_verify($current_pw, $user['password'])) {
                $errors[] = "Current password is incorrect.";
            } else {
                if (strlen($new_pw) < 6) $errors[] = "New password must be at least 6 characters.";
                if ($new_pw !== $confirm_pw) $errors[] = "New password and confirm password do not match.";
                if (empty($errors)) {
                    $updatePasswordHash = password_hash($new_pw, PASSWORD_DEFAULT);
                }
            }
        }
    }

    // If no validation errors, update DB
    if (empty($errors)) {
        if ($updatePasswordHash !== null) {
            $sql = "UPDATE users SET username = ?, email = ?, avatar = ?, password = ? WHERE id = ?";
            $stmt2 = $mysqli->prepare($sql);
            if ($stmt2) $stmt2->bind_param('ssssi', $new_username, $new_email, $avatarName, $updatePasswordHash, $uid);
        } else {
            $sql = "UPDATE users SET username = ?, email = ?, avatar = ? WHERE id = ?";
            $stmt2 = $mysqli->prepare($sql);
            if ($stmt2) $stmt2->bind_param('sssi', $new_username, $new_email, $avatarName, $uid);
        }

        if (!$stmt2) {
            $errors[] = "DB prepare error: " . $mysqli->error;
            // if we uploaded a new avatar but DB failed, remove uploaded file
            if (!empty($avatarName) && $avatarName !== $user['avatar']) {
                @unlink($avatarDir . $avatarName);
            }
        } else {
            if ($stmt2->execute()) {
                $stmt2->close();
                // If update success: remove old avatar file if replaced
                if (!empty($user['avatar']) && $avatarName !== $user['avatar']) {
                    $old = $avatarDir . $user['avatar'];
                    if (file_exists($old)) @unlink($old);
                }

                // update session and local $user so header reflects changes immediately
                $_SESSION['username'] = $new_username;
                // optional: keep avatar filename in session if you use it
                $_SESSION['avatar'] = $avatarName;

                $user['username'] = $new_username;
                $user['email'] = $new_email;
                $user['avatar'] = $avatarName;

                $success = "Profile updated successfully.";
                // redirect to avoid form re-submit (keeps flash message)
                header("Location: edit_profile.php?updated=1");
                exit;
            } else {
                $errors[] = "DB error: " . $stmt2->error;
                // cleanup uploaded avatar on failure
                if (!empty($avatarName) && $avatarName !== $user['avatar']) {
                    @unlink($avatarDir . $avatarName);
                }
                $stmt2->close();
            }
        }
    } else {
        // if there were validation errors and we uploaded a brand-new avatar file, remove it to avoid orphan files
        if (!empty($avatarName) && isset($newName) && $avatarName === $newName) {
            @unlink($avatarDir . $avatarName);
            // reset avatarName to old for display
            $avatarName = $user['avatar'];
        }
    }
}

// if redirected after success, show simple success
if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    $success = "Profile updated successfully.";
}

require_once __DIR__ . '/inc/header.php';
?>

<section class="form-section">
  <h2>Edit Profile</h2>

  <?php if (!empty($errors)): ?>
    <div class="alert error">
      <?php foreach ($errors as $e) echo "<div>" . esc($e) . "</div>"; ?>
    </div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert success"><?php echo esc($success); ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="form-card" id="editProfileForm">
    <div class="form-row" style="align-items:center;">
      <div style="flex:0 0 140px;">
        <?php
          $avatarDirWeb = 'assets/uploads/avatars/';
          $avatarDir = __DIR__ . '/' . $avatarDirWeb;
          if (!empty($user['avatar']) && file_exists($avatarDir . $user['avatar'])) {
              $avatarUrl = $avatarDirWeb . $user['avatar'];
          } else {
              // default svg fallback (place download.svg in project root or change path)
              $avatarUrl = "assets/download.svg";
          }
        ?>
        <img id="avatarPreview" src="<?php echo esc($avatarUrl); ?>" alt="avatar" style="width:120px;height:120px;border-radius:12px;object-fit:cover;border:1px solid rgba(0,0,0,0.06)">
      </div>

      <div style="flex:1;margin-left:16px;">
        <div style="display:flex;gap:10px;">
          <div style="flex:1;">
            <label>Username</label>
            <input type="text" name="username" required value="<?php echo esc($user['username'] ?? ''); ?>">
          </div>
          <div style="flex:1;">
            <label>Email</label>
            <input type="email" name="email" required value="<?php echo esc($user['email'] ?? ''); ?>">
          </div>
        </div>

        <div style="margin-top:12px;">
          <label>Change Avatar (optional)</label>
          <input type="file" name="avatar" id="avatarInput" accept="image/*">
          <div class="field-help">Max 2MB. JPG/PNG/GIF/WEBP allowed. If you upload, old avatar will be replaced.</div>
        </div>
      </div>
    </div>

    <hr style="margin:18px 0;border:none;border-top:1px solid rgba(0,0,0,0.04)">

    <h3 style="margin-bottom:8px;">Change Password (optional)</h3>
    <div class="form-row">
      <div class="field"><label>Current Password</label><input type="password" name="current_password" autocomplete="current-password"></div>
      <div class="field"><label>New Password</label><input type="password" name="new_password" autocomplete="new-password"></div>
      <div class="field"><label>Confirm New Password</label><input type="password" name="confirm_password" autocomplete="new-password"></div>
    </div>

    <div style="margin-top:18px; display:flex; gap:12px; align-items:center;">
      <button class="btn" type="submit">OK</button>
      <a class="btn outline" href="my_recipes.php">Cancel</a>
    </div>
  </form>
</section>

<script>
// avatar preview
document.getElementById('avatarInput')?.addEventListener('change', function(e){
  const file = this.files[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) { alert('Avatar too big (max 2MB).'); this.value=''; return; }
  const allowed = ['image/jpeg','image/png','image/gif','image/webp'];
  if (!allowed.includes(file.type)) { alert('Invalid image type.'); this.value=''; return; }
  const reader = new FileReader();
  reader.onload = function(ev){ document.getElementById('avatarPreview').src = ev.target.result; }
  reader.readAsDataURL(file);
});
</script>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
