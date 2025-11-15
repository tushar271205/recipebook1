<?php
// admin/users.php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
if (!is_admin()) { header('Location: ../login.php'); exit; }

require_once __DIR__ . '/../inc/header.php';

// fetch users (ascending by id)
$users = $mysqli->query("SELECT id, username, email, is_admin, avatar, created_at FROM users ORDER BY id ASC");
?>

<section class="admin">
  <h2>Manage Users</h2>

  <p style="margin-bottom:12px;">
    <a class="btn" href="user_add.php">Add New User</a>
  </p>

  <?php if (!$users): ?>
    <div class="empty">No users found.</div>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>ID</th><th>Avatar</th><th>Username</th><th>Email</th><th>Admin</th><th>Joined</th><th>Actions</th></tr></thead>
      <tbody>
        <?php while($u = $users->fetch_assoc()): ?>
          <tr>
            <td data-label="ID"><?php echo (int)$u['id']; ?></td>
            <td data-label="Avatar">
              <?php $av = !empty($u['avatar']) ? '../assets/uploads/avatars/'.$u['avatar'] : '../assets/download.svg'; ?>
              <img src="<?php echo esc($av); ?>" style="width:44px;height:44px;border-radius:8px;object-fit:cover;">
            </td>
            <td data-label="Username"><?php echo esc($u['username']); ?></td>
            <td data-label="Email"><?php echo esc($u['email']); ?></td>
            <td data-label="Admin"><?php echo $u['is_admin'] ? 'Yes' : 'No'; ?></td>
            <td data-label="Joined"><?php echo esc($u['created_at']); ?></td>
            <td data-label="Actions">
              <div class="table-actions">
                <a class="btn small outline" href="user_edit.php?id=<?php echo (int)$u['id']; ?>">Edit</a>
                <form method="post" action="user_delete.php" style="display:inline" onsubmit="return confirm('Delete this user?');">
                  <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                  <button class="btn small danger" type="submit">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
