<?php
require_once __DIR__ . '/inc/header.php';
?>
<section class="about">
  <h1>Welcome to RecipeBook</h1>
  <p>Explore, add, and manage delicious recipes. Browse categories and try new dishes. Sign up to add your own recipes, or just browse as a guest.</p>

  <div class="cta">
    <?php if(!is_logged_in()): ?>
      <a class="btn" href="login.php">Login</a>
      <a class="btn outline" href="signup.php">Sign Up</a>
    <?php else: ?>
      <a class="btn" href="home.php">Go to Home</a>
    <?php endif; ?>
  </div>

  <div class="sample-recipes">
    <h2>Featured Categories</h2>
    <div class="categories-grid">
      <?php
        $result = $mysqli->query("SELECT * FROM categories LIMIT 6");
        while ($cat = $result->fetch_assoc()):
      ?>
      <div class="cat-card">
        <h3><?php echo esc($cat['name']); ?></h3>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
