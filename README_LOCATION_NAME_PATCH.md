# HereLog 위치명 변환 패치

## 목표

기록 작성 화면에서 현재 위치를 `(위도, 경도)` 숫자로 보여주지 않고, 카카오 지도 Geocoder로 변환한 장소명/주소를 보여주고 저장합니다.

내부적으로는 지도 마커를 찍기 위해 `lat`, `lng`를 계속 저장합니다. 대신 사용자 화면에는 `address` 값을 보여줍니다.

## 바뀐 흐름

```text
post.php
→ 위치 권한 허용
→ 브라우저 Geolocation으로 lat/lng 획득
→ Kakao Geocoder coord2Address()로 장소명/주소 변환
→ hidden address에 저장
→ board/create_post.php가 HereLogPost.address, HereLogMarker.address에 저장
→ room.php / view.php / view_all.php에서는 좌표 대신 장소명/주소 출력
```

## 적용 파일

```text
post.php
js/post.js
board/create_post.php
room.php
js/room.js
view.php
view_all.php
css/post.css
css/view.css
database/05_add_address_columns.sql
```

## 적용 방법

1. 기존 프로젝트를 백업합니다.
2. ZIP 안의 파일을 기존 프로젝트에 덮어씁니다.
3. 기존 DB에 `address` 컬럼이 없다면 phpMyAdmin에서 `database/05_add_address_columns.sql`을 실행합니다.
4. `post.php?code=방코드`로 들어가 위치 권한을 허용합니다.
5. 작성 화면에 `현재 위치: 장소명/주소`가 나오는지 확인합니다.
6. 기록 저장 후 `room.php`, `view.php`, `view_all.php`에서 좌표 대신 장소명/주소가 나오는지 확인합니다.

## 주의

- 지도 마커를 찍으려면 `lat`, `lng`는 내부적으로 계속 필요합니다. 이번 패치는 사용자 화면에서 좌표를 숨기고 `address`를 보여주는 방식입니다.
- `coord2Address()` 결과에 건물명이 있으면 `건물명 · 도로명주소` 형식으로 저장합니다.
- 건물명이 없으면 도로명주소, 지번주소, 행정동 이름 순서로 대체합니다.
- 실제 상호명, 예: 특정 카페명이나 식당명까지 항상 잡아내는 구조는 아닙니다. 그건 별도의 장소 검색 로직이 필요합니다.
- 카카오 지도 JavaScript 키의 플랫폼 도메인 설정에 현재 실행 도메인이 등록되어 있어야 합니다. 로컬 테스트라면 Kakao Developers에서 `localhost` 또는 사용하는 로컬 주소를 허용해야 합니다.
