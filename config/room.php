<?php
require_once __DIR__ . '/db.php';


const HERELOG_PUBLIC_ROOM_CODE = 'public-square';
const HERELOG_PUBLIC_ROOM_NAME = '만남의 광장';
const HERELOG_PUBLIC_ROOM_OWNER = 'HereLog';

function herelog_public_room_code(): string
{
    return HERELOG_PUBLIC_ROOM_CODE;
}

function herelog_public_room_name(): string
{
    return HERELOG_PUBLIC_ROOM_NAME;
}

function is_herelog_public_room_code(string $roomcode): bool
{
    return $roomcode === herelog_public_room_code();
}

function is_herelog_public_room(array $room): bool
{
    return is_herelog_public_room_code((string)($room['roomcode'] ?? ''));
}

function ensure_herelog_public_room(mysqli $db): array
{
    $roomname = herelog_public_room_name();
    $roomcode = herelog_public_room_code();
    $ownername = HERELOG_PUBLIC_ROOM_OWNER;

    /*
        기존 패치의 INSERT ... ON DUPLICATE KEY UPDATE 방식은
        HereLogRoom.roomcode에 UNIQUE KEY가 없으면 중복 생성을 막지 못한다.
        그래서 먼저 가장 오래된 public-square 방을 찾고, 없을 때만 생성한다.
    */
    $room = get_room_by_code($db, $roomcode);

    if ($room) {
        $room_no = (int)$room['no'];
        $sql = '
            UPDATE HereLogRoom
            SET roomname = ?, ownername = ?
            WHERE no = ?
            LIMIT 1
        ';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'ssi', $roomname, $ownername, $room_no);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $room['roomname'] = $roomname;
        $room['ownername'] = $ownername;
        return $room;
    }

    $imgpath = '';
    $sql = '
        INSERT INTO HereLogRoom(roomname, roomcode, imgpath, ownername)
        VALUES(?, ?, ?, ?)
    ';

    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'ssss', $roomname, $roomcode, $imgpath, $ownername);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $room = get_room_by_code($db, $roomcode);
    if (!$room) {
        throw new RuntimeException('만남의 광장 방을 생성하지 못했습니다.');
    }

    return $room;
}

function ensure_herelog_room_member(mysqli $db, int $room_no, string $user_id, string $role = 'member'): void
{
    /*
        HereLogRoomMember(room_no, user_id)에 UNIQUE KEY가 없어도
        같은 사용자가 같은 방에 계속 중복 등록되지 않도록 먼저 확인한다.
    */
    if (is_room_member($db, $room_no, $user_id)) {
        return;
    }

    $sql = '
        INSERT INTO HereLogRoomMember(room_no, user_id, role)
        VALUES(?, ?, ?)
    ';

    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'iss', $room_no, $user_id, $role);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function ensure_herelog_public_room_member(mysqli $db, string $user_id): array
{
    $room = ensure_herelog_public_room($db);
    ensure_herelog_room_member($db, (int)$room['no'], $user_id, 'member');

    return $room;
}

if (!function_exists('default_profile_img')) {
    function default_profile_img(): string
    {
        return 'uploads/profile/default.png';
    }
}

if (!function_exists('profile_img_or_default')) {
    function profile_img_or_default(?string $path): string
    {
        $path = trim((string)$path);
        return $path !== '' ? $path : default_profile_img();
    }
}

function get_room_by_code(mysqli $db, string $roomcode): ?array
{
    $sql = 'SELECT * FROM HereLogRoom WHERE roomcode = ? ORDER BY no ASC LIMIT 1';
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

function get_room_member_role(mysqli $db, int $room_no, string $user_id): ?string
{
    $sql = '
        SELECT role
        FROM HereLogRoomMember
        WHERE room_no = ? AND user_id = ?
        ORDER BY FIELD(role, "owner", "member")
        LIMIT 1
    ';

    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'is', $room_no, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $row ? (string)$row['role'] : null;
}

function leave_room_member(mysqli $db, int $room_no, string $user_id): void
{
    $sql = '
        DELETE FROM HereLogRoomMember
        WHERE room_no = ? AND user_id = ?
    ';

    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'is', $room_no, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function require_room_member(mysqli $db, string $roomcode, string $user_id, string $redirectUrl = './main.php'): array
{
    if ($roomcode === '') {
        header('Location: ' . $redirectUrl);
        exit;
    }

    if (is_herelog_public_room_code($roomcode)) {
        return ensure_herelog_public_room_member($db, $user_id);
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
    $sql = 'SELECT COUNT(DISTINCT user_id) AS cnt FROM HereLogRoomMember WHERE room_no = ?';
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
        SELECT
            m.user_id,
            m.role,
            m.joined_at,
            h.name,
            h.nickname,
            h.profile_img,
            COALESCE(pc.post_count, 0) AS post_count
        FROM HereLogRoomMember m
        LEFT JOIN HereLog h
            ON m.user_id = h.id
        LEFT JOIN (
            SELECT user_id, COUNT(*) AS post_count
            FROM HereLogPost
            WHERE room_no = ?
            GROUP BY user_id
        ) pc
            ON pc.user_id = m.user_id
        WHERE m.room_no = ?
        ORDER BY FIELD(m.role, "owner", "member"), m.joined_at ASC
        LIMIT ?
    ';

    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'iii', $room_no, $room_no, $limit);
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


function get_room_posts_by_date(mysqli $db, int $room_no, string $selected_date, int $limit = 200): array
{
    $start = $selected_date . ' 00:00:00';

    $date = DateTime::createFromFormat('Y-m-d', $selected_date);
    $date->modify('+1 day');
    $end = $date->format('Y-m-d') . ' 00:00:00';

    $sql = '
        SELECT p.*, h.nickname, h.profile_img
        FROM HereLogPost p
        LEFT JOIN HereLog h ON p.user_id = h.id
        WHERE p.room_no = ?
        AND p.created_at >= ?
        AND p.created_at < ?
        AND p.lat IS NOT NULL
        AND p.lng IS NOT NULL
        ORDER BY p.created_at DESC
        LIMIT ?
    ';

    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'issi', $room_no, $start, $end, $limit);
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
