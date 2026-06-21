<?php
// HereLog 공통 DB/유틸 함수 파일
// 실제 배포 전에는 이 파일을 외부에 공개하지 마세요.

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function db_connect(): mysqli
{
    try {
        $db = mysqli_connect(
            'localhost',
            'DB_USER',
            'DB_PASSWORD',
            'DB_NAME'
        );
        mysqli_set_charset($db, 'utf8mb4');
        return $db;
    } catch (Throwable $e) {
        die('DB 연결 실패: ' . $e->getMessage());
    }
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function text_preview(string $value, int $length = 30): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $length, 'UTF-8');
    }
    return substr($value, 0, $length);
}

function alert_back(string $message): void
{
    $msg = json_encode($message, JSON_UNESCAPED_UNICODE);
    echo "<script>alert($msg); history.back();</script>";
    exit;
}

function alert_redirect(string $message, string $url): void
{
    $msg = json_encode($message, JSON_UNESCAPED_UNICODE);
    $to = json_encode($url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo "<script>alert($msg); location.href=$to;</script>";
    exit;
}

function require_login(string $loginPage = './index.html'): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['id'])) {
        header('Location: ' . $loginPage);
        exit;
    }
}

function current_user_id(): string
{
    return (string)($_SESSION['id'] ?? '');
}

function current_user_nickname(): string
{
    return (string)($_SESSION['nickname'] ?? ($_SESSION['id'] ?? ''));
}

function save_uploaded_image(array $file, string $absoluteDir, string $relativeDir): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        alert_back('이미지 업로드에 실패했습니다.');
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        alert_back('정상적인 업로드 파일이 아닙니다.');
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        alert_back('이미지 파일만 업로드할 수 있습니다.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowedExt, true)) {
        alert_back('jpg, jpeg, png, gif, webp 이미지만 업로드할 수 있습니다.');
    }

    if (!is_dir($absoluteDir)) {
        mkdir($absoluteDir, 0775, true);
    }

    $fileName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = rtrim($absoluteDir, '/') . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        alert_back('이미지를 저장하지 못했습니다.');
    }

    return trim($relativeDir, '/') . '/' . $fileName;
}
?>
