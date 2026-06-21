# HereLog 방별 독립 공간 1차 수정본

이 수정본은 기존 HereLog 코드를 다음 구조로 바꿉니다.

```text
로그인
→ 내가 속한 방만 main.php에 출력
→ 새 방 생성 시 만든 사람을 owner로 HereLogRoomMember에 저장
→ 방 코드 입력 시 HereLogRoomMember에 member로 저장
→ room.php는 해당 방 멤버만 접근 가능
→ post.php에서 기록 저장
→ 저장된 위치가 있으면 room.php 지도에 마커 표시
```

## 1. 먼저 SQL 실행

phpMyAdmin에서 아래 파일 내용을 실행하세요.

```text
database/01_required_tables.sql
```

기존 `HereLog`, `HereLogRoom`은 그대로 두고, 아래 3개 테이블을 추가합니다.

```text
HereLogRoomMember
HereLogPost
HereLogMarker
```

## 2. 기존 방이 이미 있다면

기존에 만들어둔 방도 `main.php`에 보이게 하려면 아래 SQL을 추가로 실행하세요.

```text
database/03_backfill_existing_rooms.sql
```

이 SQL은 `HereLogRoom.ownername`과 `HereLog.nickname`이 같은 경우, 기존 방의 만든 사람을 `owner`로 등록합니다.

## 3. 서버에 올릴 파일

이 압축파일의 내용을 기존 프로젝트 폴더에 덮어쓰면 됩니다.
단, 적용 전에는 기존 코드를 백업하세요.

중요 파일은 다음입니다.

```text
config/db.php
config/room.php
main.php
room.php
post.php
board/main.php
board/join_room.php
board/create_post.php
board/login.php
board/signup.php
js/room.js
js/post.js
css/main.css
css/room.css
```

## 4. DB 접속 정보

`config/db.php`에 DB 접속 정보가 모여 있습니다.
기존처럼 각 파일에 DB 정보를 반복해서 쓰지 않습니다.

실제 배포 전에는 DB 비밀번호를 바꾸고, `config/db.php`가 외부에 노출되지 않게 관리하세요.

## 5. 핵심 기준

방 이름은 중복될 수 있으므로 연결 기준으로 쓰지 않습니다.

```text
입장용 코드: HereLogRoom.roomcode
DB 연결 기준: HereLogRoom.no
멤버 연결: HereLogRoomMember.room_no
기록 연결: HereLogPost.room_no
마커 연결: HereLogMarker.room_no
```

## 6. 테스트 순서

1. `database/01_required_tables.sql` 실행
2. 회원가입
3. 로그인
4. 새 방 만들기
5. 만든 방으로 자동 이동되는지 확인
6. main.php에 내가 만든 방만 뜨는지 확인
7. 방 코드 복사
8. 다른 계정으로 로그인
9. 방 코드 입력 후 입장
10. 기록 작성 후 지도에 마커가 뜨는지 확인

브라우저에서 위치 권한을 거부하면 글은 저장되지만 지도 마커는 생성되지 않습니다.
