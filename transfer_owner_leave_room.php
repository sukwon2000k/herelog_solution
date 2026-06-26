<?php
header('Content-Type:text/html; charset=utf-8');

require_once __DIR__ . '/config/room.php';
require_login('./index.html');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ./main.php');
    exit;
}

$user_id = current_user_id();
$roomcode = trim($_POST['roomcode'] ?? '');
$new_owner_id = trim($_POST['new_owner_id'] ?? '');

if ($roomcode === '' || $new_owner_id === '') {
    alert_redirect('방장 이전 정보가 올바르지 않습니다.', './main.php');
}

$db = db_connect();

if (is_herelog_public_room_code($roomcode)) {
    mysqli_close($db);
    alert_redirect('만남의 광장은 기본 공용 방이라 방장을 넘기고 나갈 수 없습니다.', './room.php?public=1');
}

$room = get_room_by_code($db, $roomcode);

if (!$room) {
    mysqli_close($db);
    alert_redirect('존재하지 않는 방입니다.', './main.php');
}

$room_no = (int)$room['no'];
$room_url = './room.php?code=' . urlencode($roomcode);

if (!is_room_owner_user($db, $room_no, $user_id)) {
    mysqli_close($db);
    alert_redirect('방장만 방장을 넘길 수 있습니다.', $room_url);
}

mysqli_begin_transaction($db);

try {
    transfer_room_owner($db, $room_no, $user_id, $new_owner_id);
    leave_room_member($db, $room_no, $user_id);
    mysqli_commit($db);
} catch (Throwable $e) {
    mysqli_rollback($db);
    mysqli_close($db);
    alert_redirect('방장 이전 중 오류가 발생했습니다.', './room_manage.php?code=' . urlencode($roomcode));
}

mysqli_close($db);

alert_redirect('방장을 넘기고 방에서 나갔습니다.', './main.php');
?>
