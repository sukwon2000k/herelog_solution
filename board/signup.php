<?php
header('Content-Type:text/html; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.html');
    exit;
}

$name = trim($_POST['name'] ?? '');
$nickname = trim($_POST['nickname'] ?? '');
$id = trim($_POST['id'] ?? '');
$password = $_POST['password'] ?? '';

if ($name === '' || $nickname === '' || $id === '' || $password === '') {
    alert_back('모든 항목을 입력해주세요. 빈 칸은 허용되지 않습니다.');
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$db = db_connect();

$checkSql = 'SELECT no FROM HereLog WHERE id = ? LIMIT 1';
$checkStmt = mysqli_prepare($db, $checkSql);
mysqli_stmt_bind_param($checkStmt, 's', $id);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);

if (mysqli_num_rows($checkResult) > 0) {
    mysqli_stmt_close($checkStmt);
    mysqli_close($db);
    alert_redirect('이미 등록된 아이디입니다. 다시 입력해 주세요.', '../index.html');
}
mysqli_stmt_close($checkStmt);

$nickSql = 'SELECT no FROM HereLog WHERE nickname = ? LIMIT 1';
$nickStmt = mysqli_prepare($db, $nickSql);
mysqli_stmt_bind_param($nickStmt, 's', $nickname);
mysqli_stmt_execute($nickStmt);
$nickResult = mysqli_stmt_get_result($nickStmt);

if (mysqli_num_rows($nickResult) > 0) {
    mysqli_stmt_close($nickStmt);
    mysqli_close($db);
    alert_redirect('이미 등록된 닉네임입니다. 다시 입력해 주세요.', '../index.html');
}
mysqli_stmt_close($nickStmt);

/* =========================
   프로필 이미지 업로드
========================= */

$profilePath = 'uploads/profile/default.png';

if (
    isset($_FILES['profile_img']) &&
    $_FILES['profile_img']['error'] === 0
) {

    $uploadDir = './uploads/profile/';

    $fileName =
        date('Ymd_His') . '_' .
        basename($_FILES['profile_img']['name']);

    $savePath = $uploadDir . $fileName;

    move_uploaded_file(
        $_FILES['profile_img']['tmp_name'],
        $savePath
    );

    $profilePath =
        'uploads/profile/' . $fileName;
}

/* =========================
   회원가입 저장
========================= */

$insertSql = '
INSERT INTO HereLog
(name, nickname, id, password, profile_img)
VALUES (?, ?, ?, ?, ?)
';

$insertStmt = mysqli_prepare($db, $insertSql);

mysqli_stmt_bind_param(
    $insertStmt,
    'sssss',
    $name,
    $nickname,
    $id,
    $hashedPassword,
    $profilePath
);

mysqli_stmt_execute($insertStmt);

mysqli_stmt_close($insertStmt);
mysqli_close($db);

alert_redirect(
    $nickname . ' 님 환영합니다!',
    '../index.html'
);


?>