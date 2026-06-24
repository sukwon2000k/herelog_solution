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

if ($roomcode === '') {
    alert_redirect('방 정보가 올바르지 않습니다.', './main.php');
}

$db = db_connect();

if (is_herelog_public_room_code($roomcode)) {
    mysqli_close($db);
    alert_redirect('만남의 광장은 기본 공용 방이라 나갈 수 없습니다.', './room.php?public=1');
}

$room = get_room_by_code($db, $roomcode);

if (!$room) {
    mysqli_close($db);
    alert_redirect('존재하지 않는 방입니다.', './main.php');
}

$room_no = (int)$room['no'];
$room_url = './room.php?code=' . urlencode($roomcode);

if (!is_room_member($db, $room_no, $user_id)) {
    mysqli_close($db);
    alert_redirect('이미 나갔거나 참여 중인 방이 아닙니다.', './main.php');
}

$role = get_room_member_role($db, $room_no, $user_id);

if ($role === 'owner') {
    mysqli_close($db);
    alert_redirect('방장은 방에서 나갈 수 없습니다. 먼저 방장 이전 또는 방 삭제 기능이 필요합니다.', $room_url);
}

leave_room_member($db, $room_no, $user_id);
mysqli_close($db);

alert_redirect('방에서 나갔습니다.', './main.php');
?>
