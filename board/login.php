<?php
header('Content-Type:text/html; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.html');
    exit;
}

$id = trim($_POST['id'] ?? '');
$password = $_POST['password'] ?? '';

if ($id === '' || $password === '') {
    alert_back('모든 항목을 입력해주세요. 빈 칸은 허용되지 않습니다.');
}

$db = db_connect();

$sql = 'SELECT * FROM HereLog WHERE id = ? LIMIT 1';
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, 's', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    mysqli_stmt_close($stmt);
    mysqli_close($db);
    alert_redirect('등록되지 않은 아이디입니다.', '../index.html');
}

$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
mysqli_close($db);

if (password_verify($password, $row['password'])) {
    $_SESSION['id'] = $row['id'];
    $_SESSION['nickname'] = $row['nickname'];
    alert_redirect($row['nickname'] . ' 님 환영합니다!', '../main.php');
}

alert_redirect('비밀번호가 틀렸습니다. 다시 확인하세요.', '../index.html');
?>
