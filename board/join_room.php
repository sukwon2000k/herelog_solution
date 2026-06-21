<?php
header('Content-Type:text/html; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_login('../index.html');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../main.php');
    exit;
}

$roomcode = trim($_POST['roomcode'] ?? '');
$user_id = current_user_id();

if ($roomcode === '') {
    alert_back('방 코드를 입력해 주세요.');
}

$db = db_connect();

$roomSql = 'SELECT no, roomcode FROM HereLogRoom WHERE roomcode = ? LIMIT 1';
$roomStmt = mysqli_prepare($db, $roomSql);
mysqli_stmt_bind_param($roomStmt, 's', $roomcode);
mysqli_stmt_execute($roomStmt);
$roomResult = mysqli_stmt_get_result($roomStmt);

if (mysqli_num_rows($roomResult) === 0) {
    mysqli_stmt_close($roomStmt);
    mysqli_close($db);
    alert_back('존재하지 않는 방 코드입니다.');
}

$room = mysqli_fetch_assoc($roomResult);
$room_no = (int)$room['no'];
mysqli_stmt_close($roomStmt);

$checkSql = '
    SELECT no
    FROM HereLogRoomMember
    WHERE room_no = ? AND user_id = ?
    LIMIT 1
';
$checkStmt = mysqli_prepare($db, $checkSql);
mysqli_stmt_bind_param($checkStmt, 'is', $room_no, $user_id);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);
$alreadyMember = mysqli_num_rows($checkResult) > 0;
mysqli_stmt_close($checkStmt);

if (!$alreadyMember) {
    $insertSql = '
        INSERT INTO HereLogRoomMember(room_no, user_id, role)
        VALUES(?, ?, ?)
    ';
    $role = 'member';
    $insertStmt = mysqli_prepare($db, $insertSql);
    mysqli_stmt_bind_param($insertStmt, 'iss', $room_no, $user_id, $role);
    mysqli_stmt_execute($insertStmt);
    mysqli_stmt_close($insertStmt);
}

mysqli_close($db);

header('Location: ../room.php?code=' . urlencode($roomcode));
exit;
?>
