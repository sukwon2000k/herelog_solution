<?php
require_once __DIR__ . '/config/room.php';
require_login('./index.html');

$roomcode = trim($_GET['code'] ?? '');
$is_public_entry = (($_GET['public'] ?? '') === '1');
$user_id = current_user_id();

date_default_timezone_set('Asia/Seoul');

$db = db_connect();

if ($is_public_entry) {
    $room = ensure_herelog_public_room_member($db, $user_id);
    $roomcode = (string)$room['roomcode'];
} else {
    $room = require_room_member($db, $roomcode, $user_id, './main.php');
}

$room_no = (int)$room['no'];
$is_public_room = is_herelog_public_room($room);

$today = date('Y-m-d');

$selected_marker_date = trim($_GET['marker_date'] ?? '');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_marker_date)) {
    $selected_marker_date = $today;
}

$member_count = get_room_member_count($db, $room_no);
$members = get_room_members($db, $room_no, 8);
$current_member_role = get_room_member_role($db, $room_no, $user_id) ?? '';
$can_leave_room = (!$is_public_room && $current_member_role !== 'owner');

/*
    마커는 선택된 날짜의 게시글만 가져온다.
    기본값은 오늘.
*/
$posts = get_room_posts_by_date($db, $room_no, $selected_marker_date, 200);

$is_today_marker = ($selected_marker_date === $today);

$markers = [];
foreach ($posts as $post) {
    if ($post['lat'] !== null && $post['lng'] !== null) {
        $markers[] = [
            'post_no' => (int)$post['no'],
            'title' => text_preview($post['content'], 30),
            'lat' => (float)$post['lat'],
            'lng' => (float)$post['lng'],
            'nickname' => $post['nickname'] ?: $post['user_id'],
            'created_at' => $post['created_at'],
            'profile_img' => profile_img_or_default($post['profile_img'] ?? null),
            'imgpath' => $post['imgpath'] ?? '',
            'address' => $post['address'] ?? '',
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($room['roomname']); ?> - HereLog</title>

    <link rel="stylesheet" href="./css/room.css">
    <link rel="stylesheet" href="./css/room_member_modal.css?v=1">
    <link rel="stylesheet" href="./css/weather.css">
    <script src="./js/weather.js?v=2" defer></script>

    <script>
function copyRoomCode(code) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(code).then(() => {
            alert('방 코드가 복사되었습니다!');
        }).catch(() => {
            fallbackCopyRoomCode(code);
        });
        return;
    }

    fallbackCopyRoomCode(code);
}

function fallbackCopyRoomCode(code) {
    const result = prompt('이 방 코드를 친구에게 공유하세요. 확인을 누르면 복사됩니다.', code);

    if (result !== null) {
        const tempInput = document.createElement('input');
        tempInput.value = result;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        alert('방 코드가 복사되었습니다!');
    }
}
    </script>

    <script type="text/javascript" src="//dapi.kakao.com/v2/maps/sdk.js?appkey=b995ce9beb583c59c83fea479f81d637"></script>
    <script>
        window.HERELOG_MARKERS = <?php echo json_encode($markers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="./js/room.js?v=12" defer></script>
    <script src="./js/room_member_modal.js?v=1" defer></script>
</head>
<body>
    <div class="container">
        <div class="petal-layer" id="petalLayer" aria-hidden="true"></div>
        <div id="header">
            <button class="headerBtn backBtn" id="backBtn" type="button">
                <img src="./src/image/back.png" alt="뒤로가기">
            </button>

            <div class="room-title">
                <?php echo e($room['roomname']); ?>
                <span>HERELOG</span>
            </div>

            <button class="headerBtn chatBtn" id="chat" type="button" onclick="location.href='chat.php?code=<?php echo urlencode($roomcode); ?>'">
                <img src="./src/image/message.png" alt="메세지">
            </button>
        </div>

        <div class="marker-date-box">
            <div class="marker-date-title">
                <?php echo $is_today_marker ? '오늘의 기록' : e($selected_marker_date) . ' 기록'; ?>
                <span><?php echo count($markers); ?>개</span>
            </div>

            <form class="marker-date-form" action="./room.php" method="get">
                <input type="hidden" name="code" value="<?php echo e($roomcode); ?>">

                <input
                    type="date"
                    name="marker_date"
                    value="<?php echo e($selected_marker_date); ?>">

                <button type="submit">
                    보기
                </button>

                <?php if (!$is_today_marker) { ?>
                    <a href="./room.php?code=<?php echo urlencode($roomcode); ?>">
                        오늘
                    </a>
                <?php } ?>
            </form>
        </div>

        <div id="map">
            <button
                class="add-photo"
                id="addPhotoBtn"
                type="button"
                aria-label="게시글 메뉴 열기"
                aria-haspopup="dialog"
                aria-controls="postActionSheet">
                +
            </button>
        </div>

        <div class="post-action-dim" id="postActionDim" hidden></div>

        <div
            class="post-action-sheet"
            id="postActionSheet"
            role="dialog"
            aria-modal="true"
            aria-labelledby="postActionTitle"
            hidden>

            <div class="post-action-header">
                <div>
                    <p class="post-action-label">HERELOG</p>
                    <h2 id="postActionTitle">게시글 메뉴</h2>
                </div>

                <button
                    class="post-action-close"
                    id="postActionClose"
                    type="button"
                    aria-label="게시글 메뉴 닫기">
                    ×
                </button>
            </div>

            <a
                class="post-action-item"
                href="./view_all.php?code=<?php echo urlencode($roomcode); ?>">
                <span class="post-action-icon">≡</span>
                <span>
                    <strong>게시글 전체보기</strong>
                    <small>이 방에 저장된 모든 기록을 봅니다.</small>
                </span>
            </a>

            <a
                class="post-action-item primary"
                href="./post.php?code=<?php echo urlencode($roomcode); ?>">
                <span class="post-action-icon">＋</span>
                <span>
                    <strong>게시글 작성하기</strong>
                    <small>현재 위치와 함께 새 기록을 남깁니다.</small>
                </span>
            </a>

<?php if ($can_leave_room) { ?>
            <form
                action="./leave_room.php"
                method="post"
                style="margin:0;"
                onsubmit="return confirm('정말 이 방에서 나갈까요? 방 목록에서 사라지고 다시 들어오려면 방 코드가 필요합니다.');">
                <input type="hidden" name="roomcode" value="<?php echo e($roomcode); ?>">
                <button
                    class="post-action-item"
                    type="submit"
                    style="border:none; cursor:pointer; font:inherit; text-align:left;">
                    <span class="post-action-icon">↩</span>
                    <span>
                        <strong>방에서 나가기</strong>
                        <small>내 방 목록과 참여자 목록에서 나갑니다.</small>
                    </span>
                </button>
            </form>
<?php } ?>

            <button
                class="post-action-cancel"
                id="postActionCancel"
                type="button">
                취소
            </button>
        </div>

        <div id="footer">
            <div class="footer-title">참여자 <?php echo $member_count; ?>명</div>

            <div class="profile-list">
<?php foreach ($members as $member) {
    $displayName = $member['nickname'] ?: $member['user_id'];
    $realName = trim((string)($member['name'] ?? ''));
    $profileImg = trim((string)($member['profile_img'] ?? ''));

    if ($profileImg === '') {
        $profileImg = 'uploads/profile/default.png';
    }

    $roleLabel = (($member['role'] ?? '') === 'owner') ? '방장' : '멤버';
    $joinedDate = '';

    if (!empty($member['joined_at'])) {
        $joinedDate = date('Y.m.d', strtotime($member['joined_at']));
    }

    $postCount = (int)($member['post_count'] ?? 0);
?>
    <button
        class="profile-item member-profile-btn"
        type="button"
        title="<?php echo e($displayName); ?> 정보 보기"
        data-user-id="<?php echo e($member['user_id']); ?>"
        data-name="<?php echo e($realName !== '' ? $realName : $displayName); ?>"
        data-nickname="<?php echo e($displayName); ?>"
        data-role="<?php echo e($roleLabel); ?>"
        data-joined-at="<?php echo e($joinedDate); ?>"
        data-post-count="<?php echo $postCount; ?>"
        data-profile-img="./board/<?php echo e($profileImg); ?>">

        <div class="profile-circle">
            <img
                src="./board/<?php echo e($profileImg); ?>"
                alt="<?php echo e($displayName); ?>"
                onerror="this.src='./board/uploads/profile/default.png'">
        </div>

        <div class="profile-name">
            <?php echo e($displayName); ?>
        </div>
    </button>
<?php } ?>

<?php if (!$is_public_room) { ?>
                <div class="profile-item">
                    <div class="profile-add" onclick="copyRoomCode('<?php echo e($roomcode); ?>')">
                        +
                    </div>

                    <div class="profile-name">
                        초대
                    </div>
                </div>
<?php } else { ?>
                <div class="profile-item" title="코드 없이 누구나 들어올 수 있는 공용 방입니다.">
                    <div class="profile-add">
                        ∞
                    </div>

                    <div class="profile-name">
                        공용
                    </div>
                </div>
<?php } ?>
            </div>
        </div>
        <div class="member-modal" id="memberModal" hidden>
    <div class="member-card" role="dialog" aria-modal="true" aria-labelledby="memberModalName">
        <button
            type="button"
            class="close-member-modal"
            id="closeMemberModal"
            aria-label="참여자 정보 닫기">
            ✕
        </button>

        <img
            src="./board/uploads/profile/default.png"
            alt="참여자 프로필"
            class="member-large"
            id="memberModalImg"
            onerror="this.src='./board/uploads/profile/default.png'">

        <h2 id="memberModalName">참여자</h2>
        <p class="member-nickname" id="memberModalNickname"></p>

        <div class="member-info-list">
            <div class="member-info-row">
                <span>역할</span>
                <strong id="memberModalRole">-</strong>
            </div>

            <div class="member-info-row">
                <span>참여일</span>
                <strong id="memberModalJoinedAt">-</strong>
            </div>

            <div class="member-info-row">
                <span>작성 기록</span>
                <strong id="memberModalPostCount">0개</strong>
            </div>
        </div>
    </div>
</div>

    </div>
</body>
</html>
<?php
mysqli_close($db);
?>
