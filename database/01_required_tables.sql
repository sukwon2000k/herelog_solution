-- HereLog 방별 분리 구조에 필요한 테이블
-- 기존 HereLog, HereLogRoom 테이블은 그대로 두고 아래 3개 테이블만 추가하면 됩니다.
-- phpMyAdmin > SQL 탭에 그대로 붙여넣어 실행하세요.

CREATE TABLE IF NOT EXISTS HereLogRoomMember (
    no INT AUTO_INCREMENT PRIMARY KEY,
    room_no INT NOT NULL,
    user_id VARCHAR(100) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'member',
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_room_user (room_no, user_id),
    KEY idx_room_no (room_no),
    KEY idx_user_id (user_id),
    KEY idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS HereLogPost (
    no INT AUTO_INCREMENT PRIMARY KEY,
    room_no INT NOT NULL,
    user_id VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    imgpath VARCHAR(255) DEFAULT NULL,
    lat DECIMAL(10, 7) DEFAULT NULL,
    lng DECIMAL(10, 7) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,

    KEY idx_room_created (room_no, created_at),
    KEY idx_user_id (user_id),
    KEY idx_location (lat, lng)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS HereLogMarker (
    no INT AUTO_INCREMENT PRIMARY KEY,
    room_no INT NOT NULL,
    post_no INT DEFAULT NULL,
    user_id VARCHAR(100) DEFAULT NULL,
    title VARCHAR(100) DEFAULT NULL,
    lat DECIMAL(10, 7) NOT NULL,
    lng DECIMAL(10, 7) NOT NULL,
    address VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    KEY idx_room_no (room_no),
    KEY idx_post_no (post_no),
    KEY idx_location (lat, lng)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
