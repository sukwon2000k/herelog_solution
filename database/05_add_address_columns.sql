-- 기존 DB에 address 컬럼이 없을 때만 추가하는 안전 보정 SQL입니다.
-- 이미 database/01_required_tables.sql로 테이블을 만들었다면 보통 실행하지 않아도 됩니다.

SET @post_table_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'HereLogPost'
);

SET @post_address_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'HereLogPost'
      AND COLUMN_NAME = 'address'
);

SET @post_address_sql := IF(
    @post_table_exists = 1 AND @post_address_exists = 0,
    'ALTER TABLE HereLogPost ADD COLUMN address VARCHAR(255) DEFAULT NULL AFTER lng',
    'SELECT ''HereLogPost.address already exists or HereLogPost table does not exist'' AS message'
);

PREPARE post_address_stmt FROM @post_address_sql;
EXECUTE post_address_stmt;
DEALLOCATE PREPARE post_address_stmt;

SET @marker_table_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'HereLogMarker'
);

SET @marker_address_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'HereLogMarker'
      AND COLUMN_NAME = 'address'
);

SET @marker_address_sql := IF(
    @marker_table_exists = 1 AND @marker_address_exists = 0,
    'ALTER TABLE HereLogMarker ADD COLUMN address VARCHAR(255) DEFAULT NULL AFTER lng',
    'SELECT ''HereLogMarker.address already exists or HereLogMarker table does not exist'' AS message'
);

PREPARE marker_address_stmt FROM @marker_address_sql;
EXECUTE marker_address_stmt;
DEALLOCATE PREPARE marker_address_stmt;
