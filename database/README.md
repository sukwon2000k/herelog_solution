# HereLog DB 적용 순서

이미 `HereLog`, `HereLogRoom` 테이블이 있다면 `01_required_tables.sql`만 실행하면 됩니다.

이 파일은 다음 테이블을 추가합니다.

1. `HereLogRoomMember`
   - 사용자가 어떤 방에 속해 있는지 저장합니다.
   - `main.php`는 이 테이블을 기준으로 내가 속한 방만 보여줍니다.

2. `HereLogPost`
   - 방 안에서 작성한 기록을 저장합니다.
   - `room_no`가 `HereLogRoom.no`와 연결됩니다.

3. `HereLogMarker`
   - 지도 마커 데이터를 저장합니다.
   - 기록 작성 시 위치 권한을 허용하면 위도/경도가 저장됩니다.

`02_full_schema_reference.sql`은 새 DB에서 처음부터 만들 때 참고하는 전체 구조입니다.
기존 DB가 있다면 바로 실행하지 말고 백업 후 확인하세요.

## 기존 방 보정

이미 만들어진 방이 있다면 `03_backfill_existing_rooms.sql`도 실행하세요.
이 파일은 `HereLogRoom.ownername`과 `HereLog.nickname`이 같은 기존 방을 `HereLogRoomMember`에 owner로 등록합니다.
