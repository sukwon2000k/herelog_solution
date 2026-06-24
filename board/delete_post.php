<?php
header('Content-Type:text/html; charset=utf-8');
require_once __DIR__ . '/../config/delete_helpers.php';
require_login('../index.html');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../main.php');
    exit;
}

$user_id = current_user_id();
$post_no = (int)($_POST['post_no'] ?? 0);

if ($post_no <= 0) {
    alert_redirect('삭제할 게시글 정보가 없습니다.', '../main.php');
}

$db = db_connect();
$post = get_post_with_room_for_delete($db, $post_no);

if (!$post) {
    mysqli_close($db);
    alert_redirect('이미 삭제되었거나 존재하지 않는 게시글입니다.', '../main.php');
}

$room_no = (int)$post['room_no'];
$roomcode = (string)$post['roomcode'];

if (!is_room_member($db, $room_no, $user_id)) {
    mysqli_close($db);
    alert_redirect('이 방의 게시글을 삭제할 권한이 없습니다.', '../main.php');
}

if (!can_delete_post($db, $post, $user_id)) {
    mysqli_close($db);
    alert_redirect('게시글 작성자 또는 방장만 삭제할 수 있습니다.', '../view.php?post_no=' . $post_no);
}

mysqli_begin_transaction($db);

try {
    $deletedPost = delete_post_bundle($db, $post_no);
    mysqli_commit($db);
} catch (Throwable $e) {
    mysqli_rollback($db);
    mysqli_close($db);
    alert_redirect('게시글 삭제 중 오류가 발생했습니다.', '../view.php?post_no=' . $post_no);
}

mysqli_close($db);

if ($deletedPost && !empty($deletedPost['imgpath'])) {
    safe_unlink_board_file($deletedPost['imgpath']);
}

header('Location: ../view_all.php?code=' . urlencode($roomcode));
exit;
?>
