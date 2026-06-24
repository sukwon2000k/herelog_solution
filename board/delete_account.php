<?php
header('Content-Type:text/html; charset=utf-8');
require_once __DIR__ . '/../config/delete_helpers.php';
require_login('../index.html');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../main.php');
    exit;
}

$user_id = current_user_id();
$password = $_POST['password'] ?? '';
$confirmText = trim($_POST['confirm_text'] ?? '');

if ($password === '') {
    alert_redirect('계정 삭제를 위해 비밀번호를 입력해 주세요.', '../main.php');
}

if ($confirmText !== '계정삭제') {
    alert_redirect('확인 문구에 계정삭제를 정확히 입력해 주세요.', '../main.php');
}

$db = db_connect();

$sql = 'SELECT * FROM HereLog WHERE id = ? LIMIT 1';
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, 's', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    mysqli_close($db);
    alert_redirect('이미 삭제되었거나 존재하지 않는 계정입니다.', '../index.html');
}

if (!password_verify($password, (string)$user['password'])) {
    mysqli_close($db);
    alert_redirect('비밀번호가 일치하지 않습니다.', '../main.php');
}

$filesToDelete = [];
$profileImg = fetch_user_profile_image($db, $user_id);
if ($profileImg) {
    $filesToDelete[] = $profileImg;
}

$userPostImages = fetch_user_post_images($db, $user_id);
foreach ($userPostImages as $imgpath) {
    $filesToDelete[] = $imgpath;
}

$ownedRoomNos = fetch_owned_room_numbers($db, $user_id);

mysqli_begin_transaction($db);

try {
    // 사용자가 작성한 게시글에 달린 좋아요/댓글 먼저 삭제
    $sql = '
        DELETE l
        FROM HereLogPostLike l
        JOIN HereLogPost p ON p.no = l.post_no
        WHERE p.user_id = ?
    ';
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 's', $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $sql = '
        DELETE c
        FROM HereLogPostComment c
        JOIN HereLogPost p ON p.no = c.post_no
        WHERE p.user_id = ?
    ';
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 's', $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 사용자가 누른 좋아요와 작성한 댓글 삭제
    $sql = 'DELETE FROM HereLogPostLike WHERE user_id = ?';
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 's', $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $sql = 'DELETE FROM HereLogPostComment WHERE user_id = ?';
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 's', $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 사용자가 작성한 마커와 게시글 삭제
    $sql = '
        DELETE m
        FROM HereLogMarker m
        LEFT JOIN HereLogPost p ON p.no = m.post_no
        WHERE m.user_id = ? OR p.user_id = ?
    ';
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'ss', $user_id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $sql = 'DELETE FROM HereLogPost WHERE user_id = ?';
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 's', $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 방장인 방은 남은 멤버가 있으면 방장 이전, 없으면 방 삭제
    foreach ($ownedRoomNos as $room_no) {
        delete_empty_owned_room_or_transfer($db, (int)$room_no, $user_id, $filesToDelete);
    }

    // 일반 참여 방에서 탈퇴
    $sql = 'DELETE FROM HereLogRoomMember WHERE user_id = ?';
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 's', $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 계정 삭제
    $sql = 'DELETE FROM HereLog WHERE id = ?';
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 's', $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_commit($db);
} catch (Throwable $e) {
    mysqli_rollback($db);
    mysqli_close($db);
    alert_redirect('계정 삭제 중 오류가 발생했습니다.', '../main.php');
}

mysqli_close($db);

foreach (array_unique($filesToDelete) as $path) {
    safe_unlink_board_file($path);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];
session_destroy();

alert_redirect('계정이 삭제되었습니다.', '../index.html');
?>
