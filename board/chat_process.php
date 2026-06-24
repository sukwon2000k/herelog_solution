<?php
// DB 연결 설정 파일 불러오기 (가지고 계신 db 연결 파일 경로와 맞춰주세요)
require_once './config/db.php'; 

// 예시: 연결 객체명이 $conn 혹은 $db 일 수 있으니 확인이 필요합니다.
// 여기서는 가장 흔히 쓰이는 PDO 방식 기준으로 작성되었습니다.

$action = $_GET['action'] ?? '';
$room_code = $_REQUEST['room_code'] ?? '';

if (empty($room_code)) {
    echo json_encode(['status' => 'error', 'message' => '방 코드가 없습니다.']);
    exit;
}

// ✍️ 1. 새 메시지 저장하기
if ($action === 'send') {
    $nickname = $_POST['nickname'] ?? '익명';
    $profile_img = $_POST['profile_img'] ?? 'default.png';
    $message = $_POST['message'] ?? '';

    if (trim($message) === '') {
        echo json_encode(['status' => 'error', 'message' => '내용이 비어있습니다.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO HereLogChat (room_code, nickname, profile_img, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$room_code, $nickname, $profile_img, $message]);
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 🔄 2. 주기적으로 새 메시지 긁어가기 (실시간 통신용)
if ($action === 'fetch') {
    $last_no = intval($_GET['last_no'] ?? 0);

    try {
        // 내가 마지막으로 읽은 번호(last_no)보다 큰 최신 데이터만 순서대로 가져옴
        $stmt = $conn->prepare("SELECT * FROM HereLogChat WHERE room_code = ? AND no > ? ORDER BY no ASC");
        $stmt->execute([$room_code, $last_no]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($messages);
    } catch (Exception $e) {
        echo json_encode([]);
    }
    exit;
}
?>