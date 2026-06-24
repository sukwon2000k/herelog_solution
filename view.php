<?php
require_once __DIR__ . '/config/room.php';
require_once __DIR__ . '/config/post_interactions.php';
require_once __DIR__ . '/config/delete_helpers.php';
require_login('./index.html');

$user_id = current_user_id();
$post_no = (int)($_GET['post_no'] ?? 0);

if ($post_no <= 0) {
    header('Location: ./main.php');
    exit;
}

$db = db_connect();
$sql = '
    SELECT
        p.*,
        r.roomname,
        r.roomcode,
        h.nickname,
        COALESCE(NULLIF(h.profile_img, ""), "uploads/profile/default.png") AS profile_img
    FROM HereLogPost p
    JOIN HereLogRoom r ON r.no = p.room_no
    LEFT JOIN HereLog h ON h.id = p.user_id
    WHERE p.no = ?
    LIMIT 1
';
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, 'i', $post_no);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$post = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$post) {
    mysqli_close($db);
    alert_redirect('존재하지 않는 기록입니다.', './main.php');
}

$room_no = (int)$post['room_no'];
if (!is_room_member($db, $room_no, $user_id)) {
    mysqli_close($db);
    alert_redirect('이 기록을 볼 권한이 없습니다.', './main.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_like') {
        toggle_post_like($db, $post_no, $user_id);
        mysqli_close($db);
        header('Location: ./view.php?post_no=' . $post_no . '#reactions');
        exit;
    }

    if ($action === 'create_comment') {
        create_post_comment($db, $post_no, $user_id, $_POST['content'] ?? '');
        mysqli_close($db);
        header('Location: ./view.php?post_no=' . $post_no . '#comments');
        exit;
    }
}

$displayName = $post['nickname'] ?: $post['user_id'];
$profileImg = profile_img_or_default($post['profile_img'] ?? null);
$roomcode = $post['roomcode'];

$like_count = get_post_like_count($db, $post_no);
$comment_count = get_post_comment_count($db, $post_no);
$user_liked = has_user_liked_post($db, $post_no, $user_id);
$comments = get_post_comments($db, $post_no);
$canDeletePost = can_delete_post($db, $post, $user_id);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e(text_preview($post['content'], 20)); ?> - HereLog</title>
    <link rel="stylesheet" href="./css/view.css?v=2">
    <link rel="stylesheet" href="./css/view_social.css?v=1">
    <link rel="stylesheet" href="./css/delete_actions.css?v=1">
</head>
<body>
    <div class="container">
        <div id="header">
            <button class="headerBtn" type="button" onclick="location.href='./room.php?code=<?php echo urlencode($roomcode); ?>'">←</button>
            <div class="page-title">
                기록 보기
                <span><?php echo e($post['roomname']); ?></span>
            </div>
            <button class="headerBtn" type="button" onclick="location.href='./view_all.php?code=<?php echo urlencode($roomcode); ?>'">목록</button>
        </div>

        <main class="content">
            <div class="author-box">
                <img
                    src="./board/<?php echo e($profileImg); ?>"
                    alt="<?php echo e($displayName); ?>"
                    onerror="this.src='./board/uploads/profile/default.png'">
                <div>
                    <strong><?php echo e($displayName); ?></strong>
                    <p><?php echo e($post['created_at']); ?></p>
                </div>
            </div>

<?php if (!empty($post['imgpath'])) { ?>
            <img class="post-image" src="./board/<?php echo e($post['imgpath']); ?>" alt="기록 이미지">
<?php } ?>

            <div class="post-content">
                <?php echo nl2br(e($post['content'])); ?>
            </div>

<?php
$locationName = trim((string)($post['address'] ?? ''));
if ($locationName !== '' || ($post['lat'] !== null && $post['lng'] !== null)) {
?>
            <div class="location-box">
                <strong>저장 장소</strong>
                <p><?php echo e($locationName !== '' ? $locationName : '장소명을 찾지 못했습니다.'); ?></p>
            </div>
<?php } ?>
<?php if ($canDeletePost) { ?>
    <form
        class="delete-inline-form delete-post-form"
        action="./board/delete_post.php"
        method="post"
        onsubmit="return confirm('이 게시글을 삭제할까요? 댓글, 좋아요, 지도 마커도 함께 삭제됩니다.');">
        <input type="hidden" name="post_no" value="<?php echo (int)$post_no; ?>">
        <button class="delete-button" type="submit">게시글 삭제</button>
    </form>
<?php } ?>

            <section class="reaction-box" id="reactions">
                <form action="./view.php?post_no=<?php echo $post_no; ?>" method="post">
                    <input type="hidden" name="action" value="toggle_like">
                    <button class="like-button<?php echo $user_liked ? ' liked' : ''; ?>" type="submit">
                        <span><?php echo $user_liked ? '♥' : '♡'; ?></span>
                        <?php echo $user_liked ? '좋아요 취소' : '좋아요'; ?>
                        <strong><?php echo $like_count; ?></strong>
                    </button>
                </form>

                <div class="reaction-count">
                    댓글 <?php echo $comment_count; ?>개
                </div>
            </section>

            <section class="comment-section" id="comments">
                <div class="section-title">
                    댓글
                    <span><?php echo $comment_count; ?>개</span>
                </div>

                <form class="comment-form" action="./view.php?post_no=<?php echo $post_no; ?>#comments" method="post">
                    <input type="hidden" name="action" value="create_comment">
                    <textarea name="content" maxlength="1000" placeholder="댓글을 입력하세요." required></textarea>
                    <button type="submit">댓글 쓰기</button>
                </form>

<?php if (count($comments) === 0) { ?>
                <div class="empty-comment">
                    아직 댓글이 없습니다.
                </div>
<?php } ?>

<?php foreach ($comments as $comment) {
    $commentName = $comment['nickname'] ?: $comment['user_id'];
    $commentProfile = profile_img_or_default($comment['profile_img'] ?? null);
?>
                <article class="comment-item">
                    <div class="comment-author">
                        <img
                            src="./board/<?php echo e($commentProfile); ?>"
                            alt="<?php echo e($commentName); ?>"
                            onerror="this.src='./board/uploads/profile/default.png'">
                        <div>
                            <strong><?php echo e($commentName); ?></strong>
                            <time><?php echo e($comment['created_at']); ?></time>
                        </div>
                    </div>
                    <p><?php echo nl2br(e($comment['content'])); ?></p>
                </article>

                <?php
$canDeleteThisComment = can_delete_comment($db, [
    'user_id' => $comment['user_id'],
    'post_user_id' => $post['user_id'],
    'room_no' => $room_no
], $user_id);
?>

<?php if ($canDeleteThisComment) { ?>
    <form
        class="delete-inline-form"
        action="./board/delete_comment.php"
        method="post"
        onsubmit="return confirm('이 댓글을 삭제할까요?');">
        <input type="hidden" name="comment_no" value="<?php echo (int)$comment['no']; ?>">
        <button class="comment-delete-button" type="submit">삭제</button>
    </form>
<?php } ?>
<?php } ?>
            </section>
        </main>
    </div>
</body>
</html>
<?php
mysqli_close($db);
?>
