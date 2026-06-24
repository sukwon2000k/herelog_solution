<?php
header('Content-Type:text/html; charset=utf-8');
require_once __DIR__ . '/../config/room.php';
require_login('../index.html');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../main.php');
    exit;
}

$roomcode = trim($_POST['roomcode'] ?? '');
$content = trim($_POST['content'] ?? '');
$user_id = current_user_id();

if ($roomcode === '') {
    alert_redirect('방 정보가 없습니다.', '../main.php');
}

if ($content === '') {
    alert_back('기록 내용을 입력해 주세요.');
}

if (function_exists('mb_strlen') && mb_strlen($content, 'UTF-8') > 500) {
    alert_back('기록은 500자 이내로 입력해 주세요.');
}

$latRaw = trim($_POST['lat'] ?? '');
$lngRaw = trim($_POST['lng'] ?? '');
$address = trim($_POST['address'] ?? '');

$lat = is_numeric($latRaw) ? (float)$latRaw : null;
$lng = is_numeric($lngRaw) ? (float)$lngRaw : null;

if ($address !== '') {
    $address = function_exists('mb_substr')
        ? mb_substr($address, 0, 500, 'UTF-8')
        : substr($address, 0, 500);
} else {
    $address = null;
}

$db = db_connect();
$room = require_room_member($db, $roomcode, $user_id, '../main.php');
$room_no = (int)$room['no'];

$imgpath = save_uploaded_image(
    $_FILES['post-img'] ?? ['error' => UPLOAD_ERR_NO_FILE],
    __DIR__ . '/uploads/posts',
    'uploads/posts'
);

mysqli_begin_transaction($db);

try {
    $insertPostSql = '
        INSERT INTO HereLogPost(room_no, user_id, content, imgpath, lat, lng, address)
        VALUES(?, ?, ?, ?, ?, ?, ?)
    ';
    $insertPostStmt = mysqli_prepare($db, $insertPostSql);
    mysqli_stmt_bind_param($insertPostStmt, 'isssdds', $room_no, $user_id, $content, $imgpath, $lat, $lng, $address);
    mysqli_stmt_execute($insertPostStmt);
    mysqli_stmt_close($insertPostStmt);

    $post_no = mysqli_insert_id($db);

    if ($lat !== null && $lng !== null) {
        $title = text_preview($content, 30);
        $insertMarkerSql = '
            INSERT INTO HereLogMarker(room_no, post_no, user_id, title, lat, lng, address)
            VALUES(?, ?, ?, ?, ?, ?, ?)
        ';
        $insertMarkerStmt = mysqli_prepare($db, $insertMarkerSql);
        mysqli_stmt_bind_param($insertMarkerStmt, 'iissdds', $room_no, $post_no, $user_id, $title, $lat, $lng, $address);
        mysqli_stmt_execute($insertMarkerStmt);
        mysqli_stmt_close($insertMarkerStmt);
    }

    mysqli_commit($db);
} catch (Throwable $e) {
    mysqli_rollback($db);
    mysqli_close($db);
    alert_back('기록 저장 중 오류가 발생했습니다. database/01_required_tables.sql을 먼저 실행했는지 확인해 주세요.');
}

mysqli_close($db);

header('Location: ../room.php?code=' . urlencode($roomcode));
exit;
?>
