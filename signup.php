<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/functions.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // validations
    if ($username === '') $errors[] = "Username is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        // check existing
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
        $stmt->bind_param('ss', $email, $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email or username already taken.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $mysqli->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $ins->bind_param('sss', $username, $email, $hash);
            if ($ins->execute()) {
                header('Location: login.php?registered=1');
                exit;
            } else {
                $errors[] = "Database error: could not create user.";
            }
        }
    }
}

require_once __DIR__ . '/inc/header.php';
?>
<section class="auth">
  <h2>Sign Up</h2>
  <?php if ($errors): ?>
    <div class="errors">
      <?php foreach($errors as $e) echo '<p>'.esc($e).'</p>'; ?>
    </div>
  <?php endif; ?>

  <form method="post" id="signupForm" novalidate>
    <label>Username <input name="username" required value="<?php echo esc($_POST['username'] ?? ''); ?>"></label>
    <label>Email <input name="email" type="email" required value="<?php echo esc($_POST['email'] ?? ''); ?>"></label>
    <label>Password <input name="password" type="password" required></label>
    <label>Confirm Password <input name="confirm_password" type="password" required></label>
    <button class="btn" type="submit">Sign Up</button>
  </form>

  <p class="small">Already registered? <a href="login.php">Login</a></p>
</section>

<script>
document.getElementById('signupForm').addEventListener('submit', function(e){
  // client-side basic validations
  const pw = this.password.value;
  const cpw = this.confirm_password.value;
  if (pw.length < 6) {
    alert('Password must be at least 6 characters.');
    e.preventDefault();
  } else if (pw !== cpw) {
    alert('Passwords do not match.');
    e.preventDefault();
  }
});
</script>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
