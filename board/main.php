<?php
header('Content-Type:text/html; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_login('../index.html');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../main.php');
    exit;
}

$roomname = trim($_POST['room-name'] ?? '');
$user_id = current_user_id();
$ownername = current_user_nickname();

if ($roomname === '') {
    alert_back('방 이름을 입력해 주세요.');
}

$db = db_connect();

$imgpath = save_uploaded_image(
    $_FILES['room-img'] ?? ['error' => UPLOAD_ERR_NO_FILE],
    __DIR__ . '/uploads',
    'uploads'
) ?? '';

// roomcode는 입장용 코드다. 실제 연결 기준은 HereLogRoom.no다.
do {
    $roomcode = substr(bin2hex(random_bytes(8)), 0, 8);

    $checkSql = 'SELECT no FROM HereLogRoom WHERE roomcode = ? LIMIT 1';
    $checkStmt = mysqli_prepare($db, $checkSql);
    mysqli_stmt_bind_param($checkStmt, 's', $roomcode);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $exists = mysqli_num_rows($checkResult) > 0;
    mysqli_stmt_close($checkStmt);
} while ($exists);

mysqli_begin_transaction($db);

try {
    $insertRoomSql = '
        INSERT INTO HereLogRoom(roomname, roomcode, imgpath, ownername)
        VALUES(?, ?, ?, ?)
    ';
    $insertRoomStmt = mysqli_prepare($db, $insertRoomSql);
    mysqli_stmt_bind_param($insertRoomStmt, 'ssss', $roomname, $roomcode, $imgpath, $ownername);
    mysqli_stmt_execute($insertRoomStmt);
    mysqli_stmt_close($insertRoomStmt);

    $room_no = mysqli_insert_id($db);

    $insertMemberSql = '
        INSERT INTO HereLogRoomMember(room_no, user_id, role)
        VALUES(?, ?, ?)
    ';
    $role = 'owner';
    $insertMemberStmt = mysqli_prepare($db, $insertMemberSql);
    mysqli_stmt_bind_param($insertMemberStmt, 'iss', $room_no, $user_id, $role);
    mysqli_stmt_execute($insertMemberStmt);
    mysqli_stmt_close($insertMemberStmt);

    mysqli_commit($db);
} catch (Throwable $e) {
    mysqli_rollback($db);
    mysqli_close($db);
    alert_back('방 생성 중 오류가 발생했습니다. database/01_required_tables.sql을 먼저 실행했는지 확인해 주세요.');
}

mysqli_close($db);

header('Location: ../room.php?code=' . urlencode($roomcode));
exit;
?>
