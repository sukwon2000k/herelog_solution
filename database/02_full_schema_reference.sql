-- HereLog 전체 테이블 구조 참고용
-- 새 DB에서 처음부터 만들 때 쓰는 예시입니다.
-- 이미 테이블이 있으면 덮어쓰기 전에 반드시 백업하세요.

CREATE TABLE IF NOT EXISTS HereLog (
    no INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    nickname VARCHAR(50) NOT NULL,
    id VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_herelog_id (id),
    UNIQUE KEY uq_herelog_nickname (nickname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS HereLogRoom (
    no INT AUTO_INCREMENT PRIMARY KEY,
    roomname VARCHAR(100) NOT NULL,
    roomcode VARCHAR(20) NOT NULL,
    imgpath VARCHAR(255) DEFAULT NULL,
    ownername VARCHAR(50) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_herelogroom_roomcode (roomcode),
    KEY idx_ownername (ownername)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
