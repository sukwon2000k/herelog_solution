<?php
require_once __DIR__ . '/config/db.php';
require_login('./index.html');
// 프로필 사진 가져오기
$user_id = current_user_id();

$db = db_connect();

$userSql = "
SELECT
    name,
    nickname,
    profile_img
FROM HereLog
WHERE id = ?
";

$userStmt = mysqli_prepare($db, $userSql);
mysqli_stmt_bind_param($userStmt, 's', $user_id);
mysqli_stmt_execute($userStmt);

$userResult = mysqli_stmt_get_result($userStmt);
$userRow = mysqli_fetch_assoc($userResult);
$name = $userRow['name'];
$nickname = $userRow['nickname'];
$profileImg = $userRow['profile_img'] ?? 'uploads/profile/default.png';

mysqli_stmt_close($userStmt);

$user_id = current_user_id();
$db = db_connect();

$sql = '
    SELECT r.*
    FROM HereLogRoom r
    JOIN HereLogRoomMember m
        ON r.no = m.room_no
    WHERE m.user_id = ?
    ORDER BY r.no DESC
';
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, 's', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HereLog</title>

    <link rel="stylesheet" href="./css/main.css">
    <script src="./js/main.js" defer></script>
</head>
<body>

    <div class="container">

        <div id="header">
            <div class="logo">
                <h1>
                    ここログ
                    <span>HereLog</span>
                </h1>
            </div>

            <div class="profile-menu">

                <button class="headerBtn profileBtn" id="info" type="button">
                        <img
                        src="./board/<?php echo e($profileImg); ?>"
                        alt="프로필"
                        class="profile-thumb">
                </button>

                <div class="profile-dropdown" id="profileDropdown">
                    <a href="#" id="myInfoBtn">내정보</a>
                    <a href="./board/logout.php">로그아웃</a>
                </div>

            </div>
        </div>

        <div class="join-room-wrap">
            <form action="./board/join_room.php" method="post" class="join-room-form">
                <input
                    type="text"
                    name="roomcode"
                    maxlength="20"
                    placeholder="방 코드 입력">
                <button type="submit">입장</button>
            </form>
        </div>

        <div class="gallery">
            <!-- 새 방 만들기 -->
            <div class="album create-room">
                <span>+</span>
                <p>새 방 만들기</p>
            </div>

            <!-- 방 목록 -->
            <div id="roomList">

<?php if (mysqli_num_rows($result) === 0) { ?>
                <div class="empty-room-message">
                    아직 참여 중인 방이 없습니다.<br>
                    새 방을 만들거나 방 코드로 입장하세요.
                </div>
<?php } ?>

<?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <div class="album" onclick="location.href='./room.php?code=<?php echo urlencode($row['roomcode']); ?>'">

<?php if (!empty($row['imgpath'])) { ?>
                    <img src="./board/<?php echo e($row['imgpath']); ?>" alt="<?php echo e($row['roomname']); ?> 대표 이미지">
<?php } else { ?>
                    <div class="album-placeholder">HereLog</div>
<?php } ?>

                    <div class="album-info">
                        <h3><?php echo e($row['roomname']); ?></h3>
                        <p>👤 <?php echo e($row['ownername']); ?></p>
                    </div>

                </div>
<?php } ?>

            </div> <!-- roomList -->

        </div> <!-- gallery -->

        <!-- 방 생성 -->
        <form
            action="./board/main.php"
            method="post"
            enctype="multipart/form-data">

            <div class="create-box">

                <input
                    type="text"
                    id="room-name"
                    name="room-name"
                    maxlength="100"
                    placeholder="방 이름">

                <input
                    type="file"
                    id="room-img"
                    name="room-img"
                    accept="image/*"
                    onchange="changeImg()">

                <label for="room-img" class="upload-btn">
                    📷 대표 이미지 선택
                </label>

                <img
                    src=""
                    alt=""
                    id="preimg"
                    style="max-width:400px; max-height:400px; display:none;">

                <button type="submit" id="createBtn">
                    방 생성
                </button>

                <button type="button" id="cancelBtn">
                    취소
                </button>

            </div>

        </form>
        
        <div class="profile-modal" id="profileModal">

            <div class="profile-card">

                <button
                    type="button"
                    class="close-profile"
                    id="closeProfile">
                    ✕
                </button>

                <img
                    src="./board/<?php echo e($profileImg); ?>"
                    alt="프로필"
                    class="profile-large">

                <h2><?php echo e($name); ?></h2>
                <p>@<?php echo e($nickname); ?></p>

            </div>

        </div>
    </div> <!-- container -->

</body>
</html>
<?php
mysqli_stmt_close($stmt);
mysqli_close($db);
?>
