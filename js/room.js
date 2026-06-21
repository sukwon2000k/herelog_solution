const container = document.getElementById('map');
const savedMarkers = Array.isArray(window.HERELOG_MARKERS) ? window.HERELOG_MARKERS : [];

// 기본 중심 좌표 (서울시청 기준)
let center = new kakao.maps.LatLng(37.566826, 126.978656);

const options = {
    center: center,
    level: 4,
};

// 1. 지도 객체 생성
const map = new kakao.maps.Map(container, options);
const bounds = new kakao.maps.LatLngBounds();

// 현재 열려있는 미리보기 창을 기억하는 변수
let activeOverlay = null;

// ==========================================
// 📍 [기능 1] 내 위치 가져오기 (마커가 없을 때만)
// ==========================================
if (savedMarkers.length === 0 && navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
        const currentPos = new kakao.maps.LatLng(position.coords.latitude, position.coords.longitude);
        map.setCenter(currentPos); // 내 위치로 이동
    }, function(error) {
        console.warn('위치 정보를 가져올 수 없습니다:', error.message);
    });
}

// 지도 빈 곳을 클릭하면 열려있는 미리보기 창 닫기
kakao.maps.event.addListener(map, 'click', function() {
    if (activeOverlay) {
        activeOverlay.setMap(null);
        activeOverlay = null;
    }
});

// HTML 특수문자 처리 함수 (보안용)
function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

// ==========================================
// 📍 [기능 2] 게시글 데이터로 프로필 마커 생성
// ==========================================
savedMarkers.forEach(item => {
    const position = new kakao.maps.LatLng(item.lat, item.lng);
    bounds.extend(position);

    // --- (1) 동그란 프로필 마커 만들기 ---
    const profileImgSrc = item.profile_img ? `./board/${item.profile_img}` : './board/uploads/profile/default.png';
    const markerContent = document.createElement('div');
    
    // JS에서 직접 스타일 적용 (CSS 꼬임 방지)
    markerContent.style.width = '46px';
    markerContent.style.height = '46px';
    markerContent.style.cursor = 'pointer';
    markerContent.style.borderRadius = '50%';
    markerContent.style.border = '3px solid white';
    markerContent.style.boxShadow = '0 2px 5px rgba(0,0,0,0.3)';
    markerContent.style.overflow = 'hidden';
    markerContent.innerHTML = `<img src="${profileImgSrc}" alt="profile" style="width:100%; height:100%; object-fit:cover;" onerror="this.src='./board/uploads/profile/default.png'">`;

    const customMarker = new kakao.maps.CustomOverlay({
        position: position,
        content: markerContent,
        yAnchor: 1 // 중심을 마커 하단으로
    });
    customMarker.setMap(map);

    // --- (2) 마커 클릭 시 나타날 미리보기 창 만들기 ---
    const title = escapeHtml(item.title || '기록');
    const nickname = escapeHtml(item.nickname || '멤버');
    const previewContent = document.createElement('div');
    
    previewContent.style.background = 'white';
    previewContent.style.borderRadius = '12px';
    previewContent.style.boxShadow = '0 4px 15px rgba(0,0,0,0.15)';
    previewContent.style.overflow = 'hidden';
    previewContent.style.cursor = 'pointer';
    previewContent.style.width = '180px';
    previewContent.style.transform = 'translateY(-15px)'; // 마커 위로 살짝 띄움
    
    // 이미지가 있으면 띄우고, 없으면 빈 공간 10px만 줌
    const imgHtml = item.imgpath ? `<img src="./board/${item.imgpath}" style="width:100%; height:100px; object-fit:cover; display:block;">` : `<div style="height:10px;"></div>`;
    
    // 클릭하면 view.php 로 이동 (게시글 번호 포함)
    previewContent.innerHTML = `
        <div onclick="location.href='view.php?post_no=${item.post_no}'">
            ${imgHtml}
            <div style="padding:10px; font-size:13px; color:#183A5A; font-weight:600; text-align:center; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                <span style="color:#4FACFE; margin-right:5px; font-size:12px;">${nickname}</span>${title}
            </div>
        </div>
    `;

    const previewOverlay = new kakao.maps.CustomOverlay({
        position: position,
        content: previewContent,
        yAnchor: 1.1 
    });

    // --- (3) 프로필 마커 클릭 이벤트 ---
    markerContent.onclick = () => {
        // 이미 다른 창이 열려있으면 닫기
        if (activeOverlay) {
            activeOverlay.setMap(null);
        }
        // 클릭한 마커의 미리보기 창 띄우기
        previewOverlay.setMap(map);
        activeOverlay = previewOverlay;
    };
});

// ==========================================
// 📍 [기능 3] 마커가 한 개 이상일 때 뷰 조절
// ==========================================
if (savedMarkers.length > 0) {
    map.setBounds(bounds); // 모든 마커가 보이게 자동 줌 아웃/인
}

// 뒤로가기 버튼 기능
const backBtn = document.querySelector('.backBtn');
if (backBtn) {
    backBtn.onclick = () => {
        location.href = './main.php';
    };
}