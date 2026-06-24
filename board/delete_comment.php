<?php
header('Content-Type:text/html; charset=utf-8');
require_once __DIR__ . '/../config/delete_helpers.php';
require_login('../index.html');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../main.php');
    exit;
}

$user_id = current_user_id();
$comment_no = (int)($_POST['comment_no'] ?? 0);

if ($comment_no <= 0) {
    alert_redirect('삭제할 댓글 정보가 없습니다.', '../main.php');
}

$db = db_connect();
$comment = get_comment_with_post_for_delete($db, $comment_no);

if (!$comment) {
    mysqli_close($db);
    alert_redirect('이미 삭제되었거나 존재하지 않는 댓글입니다.', '../main.php');
}

$post_no = (int)$comment['post_no'];
$room_no = (int)$comment['room_no'];

if (!is_room_member($db, $room_no, $user_id)) {
    mysqli_close($db);
    alert_redirect('이 방의 댓글을 삭제할 권한이 없습니다.', '../main.php');
}

if (!can_delete_comment($db, $comment, $user_id)) {
    mysqli_close($db);
    alert_redirect('댓글 작성자, 게시글 작성자, 방장만 삭제할 수 있습니다.', '../view.php?post_no=' . $post_no . '#comments');
}

$sql = 'DELETE FROM HereLogPostComment WHERE no = ?';
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, 'i', $comment_no);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
mysqli_close($db);

header('Location: ../view.php?post_no=' . $post_no . '#comments');
exit;
?>
