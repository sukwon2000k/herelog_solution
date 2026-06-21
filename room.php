<?php
require_once __DIR__ . '/config/room.php';
require_login('./index.html');

$roomcode = trim($_GET['code'] ?? '');
$user_id = current_user_id();

$db = db_connect();
$room = require_room_member($db, $roomcode, $user_id, './main.php');
$room_no = (int)$room['no'];

$member_count = get_room_member_count($db, $room_no);
$members = get_room_members($db, $room_no, 8);
$posts = get_room_posts($db, $room_no, 20);

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
            'profile_img' => $post['profile_img'] ?? 'default.png',
            'imgpath' => $post['imgpath'] ?? ''
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

    <script>
function copyRoomCode(code) {
    // 1. 프롬프트 창을 띄우고 사용자의 입력을 받습니다.
    const result = prompt('이 방 코드를 친구에게 공유하세요. 확인을 누르면 복사됩니다.', code);
    
    // 2. 사용자가 '취소'를 누르지 않고 '확인'을 눌렀을 경우
    if (result !== null) {
        // 화면에 보이지 않는 임시 입력창을 만들어 복사를 진행합니다.
        const tempInput = document.createElement('input');
        tempInput.value = result;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        
        // 3. 복사 완료 안내창 띄우기
        alert('방 코드가 복사되었습니다!');
    }
}
</script>

    <!-- 카카오 지도 API: 필요하면 본인 JavaScript 앱 키로 교체하세요. -->
    <script type="text/javascript" src="//dapi.kakao.com/v2/maps/sdk.js?appkey=b995ce9beb583c59c83fea479f81d637"></script>
    <script>
        window.HERELOG_MARKERS = <?php echo json_encode($markers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="./js/room.js" defer></script>
</head>
<body>
    <div class="container">
        <div id="header">
            <button class="headerBtn backBtn" id="backBtn" type="button">
                <img src="./src/image/back.png" alt="뒤로가기">
            </button>

            <div class="room-title">
                <?php echo e($room['roomname']); ?>
                <span>HERELOG</span>
            </div>

            <button class="headerBtn chatBtn" id="chat" type="button">
                <img src="./src/image/message.png" alt="메세지">
            </button>
        </div>

        <div id="map">
            <button
                class="add-photo"
                type="button"
                onclick="location.href='post.php?code=<?php echo urlencode($roomcode); ?>'">
                +
            </button>
        </div>

        <div id="footer">
            <div class="footer-title">참여자 <?php echo $member_count; ?>명</div>

            <div class="profile-list">
<?php foreach ($members as $member) {
    $displayName = $member['nickname'] ?: $member['user_id'];
?>
        <div class="profile-item">

            <div
            class="profile-circle"
            title="<?php echo e($displayName); ?>">

                <img
                src="./board/<?php echo e($member['profile_img']); ?>"
                alt="<?php echo e($displayName); ?>">

            </div>

            <div class="profile-name">
                <?php echo e($displayName); ?>
            </div>
        </div>
<?php } ?>
                <div class="profile-item">
                    <div class="profile-add" onclick="copyRoomCode('<?php echo e($roomcode); ?>')">
                    +
                    </div>

                    <div class="profile-name">
                        초대
                    </div>
                </div>

                </div>
            
            </div>

            <!-- <div class="room-code-box">
                방 코드 <strong><?php echo e($roomcode); ?></strong>
            </div> -->

        
        </div>
    </div>
</body>
</html>
<?php
mysqli_close($db);
?>
