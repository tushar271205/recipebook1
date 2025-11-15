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

$del = $mysqli->prepare("DELETE FROM recipes WHERE id = ?");
$del->bind_param('i', $id);
if ($del->execute()) {
    // delete image
    if ($recipe['image'] && file_exists(__DIR__ . '/assets/uploads/' . $recipe['image'])) {
        @unlink(__DIR__ . '/assets/uploads/' . $recipe['image']);
    }
}
header('Location: home.php');
exit;
