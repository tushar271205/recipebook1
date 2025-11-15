<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/functions.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if ($password === '') $errors[] = "Password is required.";

    if (empty($errors)) {
        $stmt = $mysqli->prepare("SELECT id, password, is_admin, username FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($user = $res->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                // login success
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];
                header('Location: home.php');
                exit;
            } else {
                $errors[] = "Invalid credentials.";
            }
        } else {
            $errors[] = "No account found with that email.";
        }
    }
}

require_once __DIR__ . '/inc/header.php';
?>
<section class="auth">
  <h2>Login</h2>
  <?php if (isset($_GET['registered'])): ?>
    <div class="success">Registration successful. Please login.</div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="errors"><?php foreach($errors as $e) echo '<p>'.esc($e).'</p>'; ?></div>
  <?php endif; ?>

  <form method="post" id="loginForm" novalidate>
    <label>Email <input name="email" type="email" required value="<?php echo esc($_POST['email'] ?? ''); ?>"></label>
    <label>Password <input name="password" type="password" required></label>
    <button class="btn" type="submit">Login</button>
  </form>

  <p class="small">New? <a href="signup.php">Sign up here</a></p>
</section>

<script>
document.getElementById('loginForm').addEventListener('submit', function(e){
  if (!this.email.value || !this.password.value) {
    alert('Both fields are required');
    e.preventDefault();
  }
});
</script>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
