<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $conn->prepare("SELECT user_id FROM biodata WHERE id = ?");
    $stmt->execute([$id]);
    $biodata = $stmt->fetch();

    if ($biodata && ($_SESSION['role'] === 'admin' || $biodata['user_id'] === $_SESSION['user_id'])) {
        $conn->prepare("DELETE FROM educational_qualification WHERE biodata_id = ?")->execute([$id]);
        $stmt = $conn->prepare("DELETE FROM biodata WHERE id = ?");
        $stmt->execute([$id]);
    }
}

header("Location: index.php");
exit;
?>