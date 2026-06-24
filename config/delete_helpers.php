<?php
// HereLog 삭제 기능 공통 함수
// 이 파일은 config/room.php를 통해 config/db.php의 함수들을 함께 사용합니다.

require_once __DIR__ . '/room.php';

if (!function_exists('db_column_exists')) {
    function db_column_exists(mysqli $db, string $table, string $column): bool
    {
        $sql = '
            SELECT COUNT(*) AS cnt
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'ss', $table, $column);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return (int)($row['cnt'] ?? 0) > 0;
    }
}

if (!function_exists('get_room_member_role')) {
    function get_room_member_role(mysqli $db, int $room_no, string $user_id): ?string
    {
        $sql = '
            SELECT role
            FROM HereLogRoomMember
            WHERE room_no = ? AND user_id = ?
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
}

if (!function_exists('is_room_owner_user')) {
    function is_room_owner_user(mysqli $db, int $room_no, string $user_id): bool
    {
        $role = get_room_member_role($db, $room_no, $user_id);
        if ($role === 'owner') {
            return true;
        }

        if (db_column_exists($db, 'HereLogRoom', 'owner_id')) {
            $sql = 'SELECT no FROM HereLogRoom WHERE no = ? AND owner_id = ? LIMIT 1';
            $stmt = mysqli_prepare($db, $sql);
            mysqli_stmt_bind_param($stmt, 'is', $room_no, $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $isOwner = mysqli_num_rows($result) > 0;
            mysqli_stmt_close($stmt);
            return $isOwner;
        }

        return false;
    }
}

if (!function_exists('get_post_with_room_for_delete')) {
    function get_post_with_room_for_delete(mysqli $db, int $post_no): ?array
    {
        $sql = '
            SELECT
                p.*,
                r.roomcode,
                r.roomname
            FROM HereLogPost p
            JOIN HereLogRoom r ON r.no = p.room_no
            WHERE p.no = ?
            LIMIT 1
        ';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $post_no);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $post = mysqli_fetch_assoc($result) ?: null;
        mysqli_stmt_close($stmt);

        return $post;
    }
}

if (!function_exists('can_delete_post')) {
    function can_delete_post(mysqli $db, array $post, string $user_id): bool
    {
        if ((string)($post['user_id'] ?? '') === $user_id) {
            return true;
        }

        $room_no = (int)($post['room_no'] ?? 0);
        return $room_no > 0 && is_room_owner_user($db, $room_no, $user_id);
    }
}

if (!function_exists('get_comment_with_post_for_delete')) {
    function get_comment_with_post_for_delete(mysqli $db, int $comment_no): ?array
    {
        $sql = '
            SELECT
                c.*,
                p.no AS post_no,
                p.user_id AS post_user_id,
                p.room_no,
                r.roomcode
            FROM HereLogPostComment c
            JOIN HereLogPost p ON p.no = c.post_no
            JOIN HereLogRoom r ON r.no = p.room_no
            WHERE c.no = ?
            LIMIT 1
        ';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $comment_no);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $comment = mysqli_fetch_assoc($result) ?: null;
        mysqli_stmt_close($stmt);

        return $comment;
    }
}

if (!function_exists('can_delete_comment')) {
    function can_delete_comment(mysqli $db, array $comment, string $user_id): bool
    {
        if ((string)($comment['user_id'] ?? '') === $user_id) {
            return true;
        }

        if ((string)($comment['post_user_id'] ?? '') === $user_id) {
            return true;
        }

        $room_no = (int)($comment['room_no'] ?? 0);
        return $room_no > 0 && is_room_owner_user($db, $room_no, $user_id);
    }
}

if (!function_exists('safe_unlink_board_file')) {
    function safe_unlink_board_file(?string $relativePath): void
    {
        $relativePath = trim((string)$relativePath);
        if ($relativePath === '') {
            return;
        }

        $relativePath = str_replace('\\', '/', $relativePath);
        $relativePath = ltrim($relativePath, '/');

        $allowedPrefixes = [
            'uploads/posts/',
            'uploads/rooms/',
            'uploads/profile/'
        ];

        $allowed = false;
        foreach ($allowedPrefixes as $prefix) {
            if (strpos($relativePath, $prefix) === 0) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            return;
        }

        if ($relativePath === 'uploads/profile/default.png') {
            return;
        }

        $boardDir = realpath(__DIR__ . '/../board');
        if ($boardDir === false) {
            return;
        }

        $absolutePath = realpath($boardDir . '/' . $relativePath);
        if ($absolutePath === false) {
            return;
        }

        if (strpos($absolutePath, $boardDir . DIRECTORY_SEPARATOR) !== 0) {
            return;
        }

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }
}

if (!function_exists('delete_post_bundle')) {
    function delete_post_bundle(mysqli $db, int $post_no): ?array
    {
        $post = get_post_with_room_for_delete($db, $post_no);
        if (!$post) {
            return null;
        }

        $sql = 'DELETE FROM HereLogPostLike WHERE post_no = ?';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $post_no);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $sql = 'DELETE FROM HereLogPostComment WHERE post_no = ?';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $post_no);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $sql = 'DELETE FROM HereLogMarker WHERE post_no = ?';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $post_no);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $sql = 'DELETE FROM HereLogPost WHERE no = ?';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $post_no);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $post;
    }
}

if (!function_exists('fetch_user_post_images')) {
    function fetch_user_post_images(mysqli $db, string $user_id): array
    {
        $sql = 'SELECT imgpath FROM HereLogPost WHERE user_id = ? AND imgpath IS NOT NULL AND imgpath <> ""';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 's', $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $images = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $images[] = $row['imgpath'];
        }

        mysqli_stmt_close($stmt);
        return $images;
    }
}

if (!function_exists('fetch_user_profile_image')) {
    function fetch_user_profile_image(mysqli $db, string $user_id): ?string
    {
        if (!db_column_exists($db, 'HereLog', 'profile_img')) {
            return null;
        }

        $sql = 'SELECT profile_img FROM HereLog WHERE id = ? LIMIT 1';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 's', $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return $row['profile_img'] ?? null;
    }
}

if (!function_exists('fetch_owned_room_numbers')) {
    function fetch_owned_room_numbers(mysqli $db, string $user_id): array
    {
        $roomNos = [];

        $sql = 'SELECT room_no FROM HereLogRoomMember WHERE user_id = ? AND role = "owner"';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 's', $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $roomNos[] = (int)$row['room_no'];
        }
        mysqli_stmt_close($stmt);

        if (db_column_exists($db, 'HereLogRoom', 'owner_id')) {
            $sql = 'SELECT no FROM HereLogRoom WHERE owner_id = ?';
            $stmt = mysqli_prepare($db, $sql);
            mysqli_stmt_bind_param($stmt, 's', $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $roomNos[] = (int)$row['no'];
            }
            mysqli_stmt_close($stmt);
        }

        return array_values(array_unique(array_filter($roomNos)));
    }
}

if (!function_exists('fetch_next_room_owner')) {
    function fetch_next_room_owner(mysqli $db, int $room_no, string $exclude_user_id): ?array
    {
        $sql = '
            SELECT m.user_id, h.nickname
            FROM HereLogRoomMember m
            LEFT JOIN HereLog h ON h.id = m.user_id
            WHERE m.room_no = ? AND m.user_id <> ?
            ORDER BY m.joined_at ASC, m.no ASC
            LIMIT 1
        ';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'is', $room_no, $exclude_user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result) ?: null;
        mysqli_stmt_close($stmt);

        return $row;
    }
}

if (!function_exists('delete_empty_owned_room_or_transfer')) {
    function delete_empty_owned_room_or_transfer(mysqli $db, int $room_no, string $owner_user_id, array &$filesToDelete): void
    {
        $nextOwner = fetch_next_room_owner($db, $room_no, $owner_user_id);

        if ($nextOwner) {
            $newOwnerId = (string)$nextOwner['user_id'];
            $newOwnerName = (string)($nextOwner['nickname'] ?: $newOwnerId);

            $sql = 'UPDATE HereLogRoomMember SET role = "member" WHERE room_no = ? AND role = "owner"';
            $stmt = mysqli_prepare($db, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $room_no);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $sql = 'UPDATE HereLogRoomMember SET role = "owner" WHERE room_no = ? AND user_id = ?';
            $stmt = mysqli_prepare($db, $sql);
            mysqli_stmt_bind_param($stmt, 'is', $room_no, $newOwnerId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if (db_column_exists($db, 'HereLogRoom', 'owner_id')) {
                $sql = 'UPDATE HereLogRoom SET owner_id = ?, ownername = ? WHERE no = ?';
                $stmt = mysqli_prepare($db, $sql);
                mysqli_stmt_bind_param($stmt, 'ssi', $newOwnerId, $newOwnerName, $room_no);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            } else {
                $sql = 'UPDATE HereLogRoom SET ownername = ? WHERE no = ?';
                $stmt = mysqli_prepare($db, $sql);
                mysqli_stmt_bind_param($stmt, 'si', $newOwnerName, $room_no);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            return;
        }

        $sql = 'SELECT imgpath FROM HereLogRoom WHERE no = ? LIMIT 1';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $room_no);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $room = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!empty($room['imgpath'])) {
            $filesToDelete[] = $room['imgpath'];
        }

        $sql = 'SELECT imgpath FROM HereLogPost WHERE room_no = ? AND imgpath IS NOT NULL AND imgpath <> ""';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $room_no);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $filesToDelete[] = $row['imgpath'];
        }
        mysqli_stmt_close($stmt);

        $sql = '
            DELETE l
            FROM HereLogPostLike l
            JOIN HereLogPost p ON p.no = l.post_no
            WHERE p.room_no = ?
        ';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $room_no);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $sql = '
            DELETE c
            FROM HereLogPostComment c
            JOIN HereLogPost p ON p.no = c.post_no
            WHERE p.room_no = ?
        ';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $room_no);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $sql = 'DELETE FROM HereLogMarker WHERE room_no = ?';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $room_no);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $sql = 'DELETE FROM HereLogPost WHERE room_no = ?';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $room_no);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $sql = 'DELETE FROM HereLogRoomMember WHERE room_no = ?';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $room_no);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $sql = 'DELETE FROM HereLogRoom WHERE no = ?';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $room_no);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>
