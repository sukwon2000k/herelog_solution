-- 기존에 이미 만들어진 방을 HereLogRoomMember에 연결하는 보정 SQL입니다.
-- 새로 만든 방은 board/main.php가 자동으로 owner 멤버를 추가하므로 이 파일이 필요 없습니다.
-- 기존 HereLogRoom.ownername이 HereLog.nickname과 같을 때만 연결됩니다.

INSERT IGNORE INTO HereLogRoomMember(room_no, user_id, role)
SELECT r.no, h.id, 'owner'
FROM HereLogRoom r
JOIN HereLog h
    ON h.nickname = r.ownername;
