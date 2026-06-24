-- HereLog 댓글/좋아요 기능 추가 테이블
-- phpMyAdmin에서 현재 HereLog DB를 선택한 뒤 실행하세요.

CREATE TABLE IF NOT EXISTS HereLogPostLike (
    no INT AUTO_INCREMENT PRIMARY KEY,
    post_no INT NOT NULL,
    user_id VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_herelogpostlike_post_user (post_no, user_id),
    KEY idx_herelogpostlike_post_no (post_no),
    KEY idx_herelogpostlike_user_id (user_id),
    KEY idx_herelogpostlike_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS HereLogPostComment (
    no INT AUTO_INCREMENT PRIMARY KEY,
    post_no INT NOT NULL,
    user_id VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,

    KEY idx_herelogpostcomment_post_no (post_no),
    KEY idx_herelogpostcomment_user_id (user_id),
    KEY idx_herelogpostcomment_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
