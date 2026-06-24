<?php
// HereLog 게시글 좋아요/댓글 공통 함수
// view.php, view_all.php에서 config/room.php를 먼저 require 한 뒤 이 파일을 require 하세요.

if (!function_exists('get_room_posts_with_counts')) {
    function get_room_posts_with_counts(mysqli $db, int $room_no, int $limit = 100): array
    {
        $sql = '
            SELECT
                p.*,
                h.nickname,
                COALESCE(NULLIF(h.profile_img, ""), "uploads/profile/default.png") AS profile_img,
                COALESCE(l.like_count, 0) AS like_count,
                COALESCE(c.comment_count, 0) AS comment_count
            FROM HereLogPost p
            LEFT JOIN HereLog h ON p.user_id = h.id
            LEFT JOIN (
                SELECT post_no, COUNT(*) AS like_count
                FROM HereLogPostLike
                GROUP BY post_no
            ) l ON l.post_no = p.no
            LEFT JOIN (
                SELECT post_no, COUNT(*) AS comment_count
                FROM HereLogPostComment
                GROUP BY post_no
            ) c ON c.post_no = p.no
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
            $row['profile_img'] = profile_img_or_default($row['profile_img'] ?? null);
            $row['like_count'] = (int)($row['like_count'] ?? 0);
            $row['comment_count'] = (int)($row['comment_count'] ?? 0);
            $posts[] = $row;
        }

        mysqli_stmt_close($stmt);
        return $posts;
    }
}

if (!function_exists('get_post_like_count')) {
    function get_post_like_count(mysqli $db, int $post_no): int
    {
        $sql = 'SELECT COUNT(*) AS cnt FROM HereLogPostLike WHERE post_no = ?';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $post_no);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return (int)($row['cnt'] ?? 0);
    }
}

if (!function_exists('get_post_comment_count')) {
    function get_post_comment_count(mysqli $db, int $post_no): int
    {
        $sql = 'SELECT COUNT(*) AS cnt FROM HereLogPostComment WHERE post_no = ?';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $post_no);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return (int)($row['cnt'] ?? 0);
    }
}

if (!function_exists('has_user_liked_post')) {
    function has_user_liked_post(mysqli $db, int $post_no, string $user_id): bool
    {
        $sql = '
            SELECT no
            FROM HereLogPostLike
            WHERE post_no = ? AND user_id = ?
            LIMIT 1
        ';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'is', $post_no, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $liked = mysqli_num_rows($result) > 0;
        mysqli_stmt_close($stmt);
        return $liked;
    }
}

if (!function_exists('toggle_post_like')) {
    function toggle_post_like(mysqli $db, int $post_no, string $user_id): void
    {
        if (has_user_liked_post($db, $post_no, $user_id)) {
            $sql = '
                DELETE FROM HereLogPostLike
                WHERE post_no = ? AND user_id = ?
            ';
            $stmt = mysqli_prepare($db, $sql);
            mysqli_stmt_bind_param($stmt, 'is', $post_no, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return;
        }

        $sql = '
            INSERT INTO HereLogPostLike(post_no, user_id)
            VALUES(?, ?)
        ';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'is', $post_no, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('create_post_comment')) {
    function create_post_comment(mysqli $db, int $post_no, string $user_id, string $content): void
    {
        $content = trim($content);

        if ($content === '') {
            alert_redirect('댓글 내용을 입력해 주세요.', './view.php?post_no=' . $post_no . '#comments');
        }

        if (function_exists('mb_substr')) {
            $content = mb_substr($content, 0, 1000, 'UTF-8');
        } else {
            $content = substr($content, 0, 1000);
        }

        $sql = '
            INSERT INTO HereLogPostComment(post_no, user_id, content)
            VALUES(?, ?, ?)
        ';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'iss', $post_no, $user_id, $content);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('get_post_comments')) {
    function get_post_comments(mysqli $db, int $post_no): array
    {
        $sql = '
            SELECT
                c.*,
                h.nickname,
                COALESCE(NULLIF(h.profile_img, ""), "uploads/profile/default.png") AS profile_img
            FROM HereLogPostComment c
            LEFT JOIN HereLog h ON c.user_id = h.id
            WHERE c.post_no = ?
            ORDER BY c.created_at ASC, c.no ASC
        ';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $post_no);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $comments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['profile_img'] = profile_img_or_default($row['profile_img'] ?? null);
            $comments[] = $row;
        }

        mysqli_stmt_close($stmt);
        return $comments;
    }
}
?>
