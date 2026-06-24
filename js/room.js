const container = document.getElementById('map');
const savedMarkers = Array.isArray(window.HERELOG_MARKERS) ? window.HERELOG_MARKERS : [];

let center = new kakao.maps.LatLng(37.566826, 126.978656);

const options = {
    center: center,
    level: 4,
};

const map = new kakao.maps.Map(container, options);
const bounds = new kakao.maps.LatLngBounds();
let activeOverlay = null;

/*
    가까운 마커를 묶는 거리 기준.
    슬라임/보노보노처럼 화면에서 겹쳐 보이는 마커가 안 묶이면 60~70으로 올리고,
    너무 많이 묶이면 25~35로 낮추면 됨.
*/
const GROUP_RADIUS_METERS = 30;

if (savedMarkers.length === 0 && navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
        const currentPos = new kakao.maps.LatLng(
            position.coords.latitude,
            position.coords.longitude
        );
        map.setCenter(currentPos);
    }, function(error) {
        console.warn('위치 정보를 가져올 수 없습니다:', error.message);
    });
}

kakao.maps.event.addListener(map, 'click', function() {
    closeActiveOverlay();
});

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function fileUrlFromBoard(path) {
    const cleanPath = String(path ?? '').trim();

    if (cleanPath === '') {
        return './board/uploads/profile/default.png';
    }

    if (cleanPath.startsWith('http://') || cleanPath.startsWith('https://')) {
        return cleanPath;
    }

    if (cleanPath.startsWith('./board/')) {
        return cleanPath;
    }

    return `./board/${cleanPath}`;
}

function formatCreatedAt(value) {
    const raw = String(value ?? '').trim();

    if (raw === '') {
        return '';
    }

    const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);

    if (match) {
        return `${match[1]}.${match[2]}.${match[3]} ${match[4]}:${match[5]}`;
    }

    const parsedDate = new Date(raw.replace(' ', 'T'));

    if (!Number.isNaN(parsedDate.getTime())) {
        const year = parsedDate.getFullYear();
        const month = String(parsedDate.getMonth() + 1).padStart(2, '0');
        const day = String(parsedDate.getDate()).padStart(2, '0');
        const hour = String(parsedDate.getHours()).padStart(2, '0');
        const minute = String(parsedDate.getMinutes()).padStart(2, '0');

        return `${year}.${month}.${day} ${hour}:${minute}`;
    }

    return raw;
}

function stopMapClick(event) {
    if (!event) {
        return;
    }

    event.stopPropagation();

    if (typeof event.stopImmediatePropagation === 'function') {
        event.stopImmediatePropagation();
    }
}

function normalizeMarker(item) {
    const lat = Number(item.lat);
    const lng = Number(item.lng);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return null;
    }

    return {
        ...item,
        lat,
        lng
    };
}

function compareCreatedAtDesc(a, b) {
    const aTime = new Date(String(a.created_at ?? '').replace(' ', 'T')).getTime();
    const bTime = new Date(String(b.created_at ?? '').replace(' ', 'T')).getTime();

    if (Number.isNaN(aTime) && Number.isNaN(bTime)) {
        return 0;
    }

    if (Number.isNaN(aTime)) {
        return 1;
    }

    if (Number.isNaN(bTime)) {
        return -1;
    }

    return bTime - aTime;
}

function getDistanceMeters(a, b) {
    const earthRadius = 6371000;

    const lat1 = Number(a.lat) * Math.PI / 180;
    const lat2 = Number(b.lat) * Math.PI / 180;
    const deltaLat = (Number(b.lat) - Number(a.lat)) * Math.PI / 180;
    const deltaLng = (Number(b.lng) - Number(a.lng)) * Math.PI / 180;

    const sinLat = Math.sin(deltaLat / 2);
    const sinLng = Math.sin(deltaLng / 2);

    const h =
        sinLat * sinLat +
        Math.cos(lat1) * Math.cos(lat2) * sinLng * sinLng;

    return earthRadius * 2 * Math.atan2(
        Math.sqrt(h),
        Math.sqrt(1 - h)
    );
}

function groupMarkersByPosition(markers) {
    const normalizedMarkers = markers
        .map(normalizeMarker)
        .filter(Boolean)
        .sort(compareCreatedAtDesc);

    const groups = [];

    normalizedMarkers.forEach(item => {
        let targetGroup = null;
        let nearestDistance = Infinity;

        groups.forEach(group => {
            const center = {
                lat: group.latSum / group.items.length,
                lng: group.lngSum / group.items.length
            };

            const distance = getDistanceMeters(item, center);

            if (distance <= GROUP_RADIUS_METERS && distance < nearestDistance) {
                targetGroup = group;
                nearestDistance = distance;
            }
        });

        if (!targetGroup) {
            groups.push({
                latSum: item.lat,
                lngSum: item.lng,
                items: [item]
            });

            return;
        }

        targetGroup.latSum += item.lat;
        targetGroup.lngSum += item.lng;
        targetGroup.items.push(item);
    });

    return groups.map(group => {
        group.items.sort(compareCreatedAtDesc);

        return {
            lat: group.latSum / group.items.length,
            lng: group.lngSum / group.items.length,
            items: group.items
        };
    });
}

function closeActiveOverlay() {
    if (activeOverlay) {
        activeOverlay.setMap(null);
        activeOverlay = null;
    }
}

function createSinglePostMarker(item) {
    const position = new kakao.maps.LatLng(item.lat, item.lng);
    bounds.extend(position);

    const profileImgSrc = escapeHtml(fileUrlFromBoard(item.profile_img));
    const markerContent = document.createElement('div');

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
        yAnchor: 1,
        clickable: true,
        zIndex: 10
    });

    customMarker.setMap(map);

    const title = escapeHtml(item.title || '기록');
    const nickname = escapeHtml(item.nickname || '멤버');
    const address = escapeHtml(item.address || '');
    const createdAt = escapeHtml(formatCreatedAt(item.created_at));
    const postNo = Number.parseInt(item.post_no, 10);
    const safePostNo = Number.isFinite(postNo) ? postNo : 0;
    const previewContent = document.createElement('div');

    previewContent.style.background = 'white';
    previewContent.style.borderRadius = '12px';
    previewContent.style.boxShadow = '0 4px 15px rgba(0,0,0,0.15)';
    previewContent.style.overflow = 'hidden';
    previewContent.style.cursor = 'pointer';
    previewContent.style.width = '180px';
    previewContent.style.transform = 'translateY(-15px)';

    const imgPath = String(item.imgpath ?? '').trim();
    const imgHtml = imgPath
        ? `<img src="${escapeHtml(fileUrlFromBoard(imgPath))}" style="width:100%; height:100px; object-fit:cover; display:block;" alt="기록 이미지">`
        : '<div style="height:10px;"></div>';

    const addressHtml = address
        ? `<div style="padding:0 10px 4px; font-size:11px; color:#6B7280; text-align:center; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">📍 ${address}</div>`
        : '';

    const createdAtHtml = createdAt
        ? `<div style="padding:0 10px 10px; font-size:11px; color:#9CA3AF; text-align:center; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">🕒 ${createdAt}</div>`
        : '';

    const viewUrl = safePostNo > 0
        ? `./view.php?post_no=${encodeURIComponent(safePostNo)}`
        : '#';

    previewContent.setAttribute('role', 'button');
    previewContent.setAttribute('tabindex', '0');
    previewContent.setAttribute('aria-label', '게시글 상세보기');
    previewContent.style.pointerEvents = 'auto';

    previewContent.innerHTML = `
        ${imgHtml}
        <div style="padding:10px 10px 4px; font-size:13px; color:#183A5A; font-weight:600; text-align:center; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
            <span style="color:#4FACFE; margin-right:5px; font-size:12px;">${nickname}</span>${title}
        </div>
        ${addressHtml}
        ${createdAtHtml}
    `;

    let isNavigatingToPost = false;

    function moveToPost(event) {
        event.preventDefault();
        stopMapClick(event);

        if (safePostNo <= 0) {
            alert('게시글 번호가 없어 상세보기로 이동할 수 없습니다.');
            return;
        }

        if (isNavigatingToPost) {
            return;
        }

        isNavigatingToPost = true;
        window.location.assign(viewUrl);
    }

    ['mousedown', 'mouseup', 'touchstart'].forEach(eventName => {
        previewContent.addEventListener(eventName, stopMapClick);
    });

    previewContent.addEventListener('click', moveToPost);
    previewContent.addEventListener('touchend', moveToPost, { passive: false });

    previewContent.addEventListener('keydown', function(event) {
        if (event.key === 'Enter' || event.key === ' ') {
            moveToPost(event);
        }
    });

    const previewOverlay = new kakao.maps.CustomOverlay({
        position: position,
        content: previewContent,
        yAnchor: 1.1,
        clickable: true,
        zIndex: 100
    });

    markerContent.addEventListener('click', function(event) {
        event.preventDefault();
        stopMapClick(event);

        closeActiveOverlay();

        previewOverlay.setMap(map);
        activeOverlay = previewOverlay;
    });
}

function createGroupedPostMarker(group) {
    const position = new kakao.maps.LatLng(group.lat, group.lng);
    bounds.extend(position);

    const groupMarker = document.createElement('button');
    groupMarker.className = 'grouped-post-marker';
    groupMarker.type = 'button';
    groupMarker.setAttribute('aria-label', `${group.items.length}개의 기록 보기`);

    groupMarker.innerHTML = `
        <strong>${group.items.length}</strong>
        <span>기록</span>
    `;

    groupMarker.addEventListener('click', function(event) {
        event.preventDefault();
        stopMapClick(event);

        closeActiveOverlay();
        openGroupedPostSheet(group.items);
    });

    const overlay = new kakao.maps.CustomOverlay({
        position: position,
        content: groupMarker,
        yAnchor: 1,
        clickable: true,
        zIndex: 50
    });

    overlay.setMap(map);
}

function ensureGroupedPostSheet() {
    let backdrop = document.getElementById('groupedPostBackdrop');

    if (backdrop) {
        return backdrop;
    }

    backdrop = document.createElement('div');
    backdrop.id = 'groupedPostBackdrop';
    backdrop.className = 'grouped-post-backdrop';
    backdrop.hidden = true;

    backdrop.innerHTML = `
        <div class="grouped-post-sheet" role="dialog" aria-modal="true" aria-labelledby="groupedPostTitle">
            <div class="grouped-post-handle"></div>

            <div class="grouped-post-header">
                <div>
                    <p>HERELOG</p>
                    <h2 id="groupedPostTitle">이 위치의 기록</h2>
                </div>

                <button type="button" class="grouped-post-close" aria-label="닫기">
                    ×
                </button>
            </div>

            <div class="grouped-post-list"></div>
        </div>
    `;

    document.body.appendChild(backdrop);

    const closeBtn = backdrop.querySelector('.grouped-post-close');

    if (closeBtn) {
        closeBtn.addEventListener('click', closeGroupedPostSheet);
    }

    backdrop.addEventListener('click', function(event) {
        if (event.target === backdrop) {
            closeGroupedPostSheet();
        }
    });

    return backdrop;
}

function groupedPostItemHtml(item) {
    const postNo = Number.parseInt(item.post_no, 10);
    const safePostNo = Number.isFinite(postNo) ? postNo : 0;
    const title = escapeHtml(item.title || '기록');
    const nickname = escapeHtml(item.nickname || '멤버');
    const address = escapeHtml(item.address || '');
    const createdAt = escapeHtml(formatCreatedAt(item.created_at || ''));
    const imgPath = String(item.imgpath ?? '').trim();

    const imgHtml = imgPath
        ? `<img src="${escapeHtml(fileUrlFromBoard(imgPath))}" alt="기록 이미지" onerror="this.parentElement.innerHTML='<div class=&quot;grouped-post-empty-img&quot;>기록</div>'">`
        : '<div class="grouped-post-empty-img">기록</div>';

    const href = safePostNo > 0
        ? `./view.php?post_no=${encodeURIComponent(safePostNo)}`
        : '#';

    return `
        <a class="grouped-post-item" href="${href}">
            <div class="grouped-post-thumb">
                ${imgHtml}
            </div>

            <div class="grouped-post-text">
                <strong>${title}</strong>
                <span>${nickname}</span>
                ${address ? `<em>📍 ${address}</em>` : ''}
                ${createdAt ? `<small>🕒 ${createdAt}</small>` : ''}
            </div>
        </a>
    `;
}

function openGroupedPostSheet(items) {
    const backdrop = ensureGroupedPostSheet();
    const list = backdrop.querySelector('.grouped-post-list');

    if (!list) {
        return;
    }

    list.innerHTML = items.map(groupedPostItemHtml).join('');

    backdrop.hidden = false;
    document.body.classList.add('grouped-post-open');
}

function closeGroupedPostSheet() {
    const backdrop = document.getElementById('groupedPostBackdrop');

    if (!backdrop) {
        return;
    }

    backdrop.hidden = true;
    document.body.classList.remove('grouped-post-open');
}

const markerGroups = groupMarkersByPosition(savedMarkers);

markerGroups.forEach(group => {
    if (group.items.length === 1) {
        createSinglePostMarker(group.items[0]);
        return;
    }

    createGroupedPostMarker(group);
});

if (markerGroups.length === 1) {
    const onlyGroup = markerGroups[0];
    map.setCenter(new kakao.maps.LatLng(onlyGroup.lat, onlyGroup.lng));
    map.setLevel(4);
} else if (markerGroups.length > 1) {
    map.setBounds(bounds);
}

const backBtn = document.querySelector('.backBtn');

if (backBtn) {
    backBtn.onclick = () => {
        location.href = './main.php';
    };
}

const addPhotoBtn = document.getElementById('addPhotoBtn');
const postActionDim = document.getElementById('postActionDim');
const postActionSheet = document.getElementById('postActionSheet');
const postActionClose = document.getElementById('postActionClose');
const postActionCancel = document.getElementById('postActionCancel');

function openPostActionSheet() {
    if (!postActionDim || !postActionSheet) {
        return;
    }

    postActionDim.hidden = false;
    postActionSheet.hidden = false;
    document.body.classList.add('action-sheet-open');
}

function closePostActionSheet() {
    if (!postActionDim || !postActionSheet) {
        return;
    }

    postActionDim.hidden = true;
    postActionSheet.hidden = true;
    document.body.classList.remove('action-sheet-open');
}

if (addPhotoBtn) {
    addPhotoBtn.addEventListener('click', openPostActionSheet);
}

[postActionDim, postActionClose, postActionCancel].forEach(element => {
    if (element) {
        element.addEventListener('click', closePostActionSheet);
    }
});

document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
        closePostActionSheet();
        closeGroupedPostSheet();
    }
});