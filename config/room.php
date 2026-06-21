<?php
require_once __DIR__ . '/db.php';

function get_room_by_code(mysqli $db, string $roomcode): ?array
{
    $sql = 'SELECT * FROM HereLogRoom WHERE roomcode = ? LIMIT 1';
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 's', $roomcode);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $room = mysqli_fetch_assoc($result) ?: null;
    mysqli_stmt_close($stmt);
    return $room;
}

function is_room_member(mysqli $db, int $room_no, string $user_id): bool
{
    $sql = '
        SELECT no
        FROM HereLogRoomMember
        WHERE room_no = ? AND user_id = ?
        LIMIT 1
    ';
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'is', $room_no, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $isMember = mysqli_num_rows($result) > 0;
    mysqli_stmt_close($stmt);
    return $isMember;
}

function require_room_member(mysqli $db, string $roomcode, string $user_id, string $redirectUrl = './main.php'): array
{
    if ($roomcode === '') {
        header('Location: ' . $redirectUrl);
        exit;
    }

    $room = get_room_by_code($db, $roomcode);
    if (!$room) {
        alert_redirect('존재하지 않는 방입니다.', $redirectUrl);
    }

    $room_no = (int)$room['no'];
    if (!is_room_member($db, $room_no, $user_id)) {
        alert_redirect('이 방에 접근할 권한이 없습니다.', $redirectUrl);
    }

    return $room;
}

function get_room_member_count(mysqli $db, int $room_no): int
{
    $sql = 'SELECT COUNT(*) AS cnt FROM HereLogRoomMember WHERE room_no = ?';
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $room_no);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return (int)($row['cnt'] ?? 0);
}

function get_room_members(mysqli $db, int $room_no, int $limit = 8): array
{
    $sql = '
        SELECT m.user_id, m.role, m.joined_at, h.nickname, h.profile_img
        FROM HereLogRoomMember m
        LEFT JOIN HereLog h ON m.user_id = h.id
        WHERE m.room_no = ?
        ORDER BY FIELD(m.role, "owner", "member"), m.joined_at ASC
        LIMIT ?
    ';
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $room_no, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $members = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $members[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $members;
}

function get_room_posts(mysqli $db, int $room_no, int $limit = 20): array
{
    $sql = '
        SELECT p.*, h.nickname, h.profile_img
        FROM HereLogPost p
        LEFT JOIN HereLog h ON p.user_id = h.id
        WHERE p.room_no = ?
        ORDER BY p.created_at DESC
        LIMIT ?
    ';
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $room_no, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $posts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $posts[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $posts;
}
?>
