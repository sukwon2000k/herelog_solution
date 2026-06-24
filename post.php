<?php
require_once __DIR__ . '/config/room.php';
require_login('./index.html');

$roomcode = trim($_GET['code'] ?? '');
$user_id = current_user_id();

$db = db_connect();
$room = require_room_member($db, $roomcode, $user_id, './main.php');
mysqli_close($db);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>새 기록 작성 - HereLog</title>
    <link rel="stylesheet" href="./css/post.css">
    <script type="text/javascript" src="//dapi.kakao.com/v2/maps/sdk.js?appkey=b995ce9beb583c59c83fea479f81d637&libraries=services"></script>
    <script src="./js/post.js" defer></script>
</head>
<body>

    <form
        class="container"
        action="./board/create_post.php"
        method="post"
        enctype="multipart/form-data">

        <input type="hidden" name="roomcode" value="<?php echo e($roomcode); ?>">
        

        <!-- 헤더 -->
        <div id="header">

            <button class="headerBtn backBtn" type="button">
                <img src="./src/image/back.png" alt="뒤로가기">
            </button>

            <div class="page-title">
                새 기록 작성
                <span><?php echo e($room['roomname']); ?></span>
            </div>

            <div class="dummy"></div>

        </div>

        <!-- 본문 -->
        <div class="content">

            <!-- 현재 위치 -->
            <div class="location-info" id="location-info">
                📍 현재 위치 장소명 불러오는 중...
            </div>

            <input type="hidden" name="lat" id="lat">
            <input type="hidden" name="lng" id="lng">

            <input
                type="text"
                name="address"
                id="address"
                class="location-input"
                placeholder="장소명을 직접 수정할 수 있어요"
                autocomplete="off">
            <!-- 사진 선택 -->
            <input
                type="file"
                id="file-input"
                name="post-img"
                accept="image/*"
                onchange="previewImage()"
                hidden>

            <label for="file-input" class="upload-btn">
                <span id="uploadIcon">＋</span>
                <p id="uploadText">사진 등록하기</p>
            </label>

            <!-- 미리보기 -->
            <div class="preview-area" id="preview-area">

                <img
                    id="preview-img"
                    src=""
                    alt="미리보기">

                <button
                    type="button"
                    id="removeImg"
                    class="remove-btn">
                    ✕
                </button>

            </div>

            <!-- 게시글 -->
            <textarea
                id="content"
                name="content"
                class="post-content"
                maxlength="500"
                placeholder="이 순간을 기록해보세요."></textarea>

            <!-- 글자수 -->
            <div class="text-count">
                <span id="count">0</span> / 500
            </div>

        </div>

        <!-- 등록 -->
        <div id="footer">

            <button id="submitBtn" class="submit-btn" type="submit">
                기록 저장하기
            </button>

        </div>

    </form>

</body>
</html>
