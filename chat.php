<?php
// DB 연결 및 기본 설정 파일 불러오기 (경로는 프로젝트에 맞게 수정)
require_once './config/db.php';

// 방 코드 가져오기 (임시로 방 이름을 쿼리에서 가져온다고 가정)
$room_code = $_GET['code'] ?? '';
// 실제로는 DB에서 $room_code로 방 이름을 조회해와야 합니다. 
$room_name = "테스트 방"; // 💡 DB 연결 후 이 부분을 실제 방 이름 변수로 교체하세요!
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>채팅 - HereLog</title>
    <link rel="stylesheet" href="./css/chat.css">
    <script src="./js/chat.js" defer></script>
</head>
    <body>

    <div class="chat-header">
        <a href="room.php?code=<?php echo htmlspecialchars($room_code); ?>" class="back-btn">⟨</a>
        
        <div class="title-box">
            <div class="room-name"><?php echo htmlspecialchars($room_name); ?></div>
            <div class="sub-logo">HERELOG</div>
        </div>
        
        <div class="users-btn" onclick="alert('참여자 목록 모달을 띄울 예정입니다!')">👥</div>
    </div>

    <div class="chat-body" id="chatBody">
        <div class="msg-row other">
            <img src="./board/uploads/profile/20260622_025218_Maple_A_250701_003219.jpg" onerror="this.src='./board/uploads/profile/default.png'" alt="프로필" class="profile-img">
            <div class="msg-content">
                <div class="nickname">쌀숭이</div> <div class="msg-box msg-other">안녕하세요! 여기 뷰가 엄청 좋네요 ☕️</div>
            </div>
        </div>

        <div class="msg-row mine">
            <div class="msg-content">
                <div class="msg-box msg-mine">맞아요! 방금 옥상에서 사진 찍어서 올렸습니다 ㅎㅎ</div>
            </div>
        </div>
    </div>

    <div class="chat-footer">
        <input type="text" id="chatInput" placeholder="메세지를 입력하세요..." onkeypress="if(event.keyCode==13) sendMessage()">
        <button onclick="sendMessage()">➤</button>
    </div>
</body>
</html>
