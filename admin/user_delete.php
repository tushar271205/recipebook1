<?php
// admin/user_delete.php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
if (!is_admin()) { header('Location: ../login.php'); exit; }

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) { header('Location: users.php'); exit; }

// prevent deleting yourself
if ($id === (int)$_SESSION['user_id']) {
    die('You cannot delete your own account.');
}

// optional: fetch avatar to remove
$stmt = $mysqli->prepare("SELECT avatar FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i',$id); $stmt->execute();
$row = $stmt->get_result()->fetch_assoc(); $stmt->close();

$del = $mysqli->prepare("DELETE FROM users WHERE id = ?");
$del->bind_param('i',$id);
if ($del->execute()) {
    if (!empty($row['avatar'])) {
        @unlink(__DIR__ . '/../assets/uploads/avatars/' . $row['avatar']);
    }
    header('Location: users.php?deleted=1'); exit;
} else {
    die('DB error: '.$del->error);
}
