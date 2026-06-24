// =========================
// DOM
// =========================

const textarea = document.querySelector('#content');
const count = document.querySelector('#count');
const locationInfo = document.querySelector('#location-info');
const latInput = document.querySelector('#lat');
const lngInput = document.querySelector('#lng');
const addressInput = document.querySelector('#address');
const form = document.querySelector('form.container');
const submitBtn = document.querySelector('#submitBtn');

let locationReady = true;
let locationPromise = Promise.resolve();
let submittingAfterLocationCheck = false;

function setLocationMessage(message, state = '') {
    if (locationInfo) {
        locationInfo.textContent = message;

        if (state) {
            locationInfo.dataset.state = state;
        } else {
            delete locationInfo.dataset.state;
        }
    }
}

// =========================
// TEXT COUNT
// =========================

if (textarea && count) {
    textarea.addEventListener('input', () => {
        count.textContent = textarea.value.length;
    });
}

// =========================
// CURRENT LOCATION -> PLACE NAME
// =========================

function canUseKakaoGeocoder() {
    return Boolean(
        window.kakao &&
        kakao.maps &&
        kakao.maps.services &&
        kakao.maps.services.Geocoder
    );
}

function formatAddressResult(result) {
    const first = Array.isArray(result) && result.length > 0 ? result[0] : null;

    if (!first) {
        return '';
    }

    const roadAddress = first.road_address || null;
    const lotAddress = first.address || null;

    if (roadAddress) {
        const buildingName = String(roadAddress.building_name || '').trim();
        const roadName = String(roadAddress.address_name || '').trim();

        if (buildingName && roadName) {
            return `${buildingName} · ${roadName}`;
        }

        if (buildingName) {
            return buildingName;
        }

        if (roadName) {
            return roadName;
        }
    }

    if (lotAddress && lotAddress.address_name) {
        return lotAddress.address_name;
    }

    return '';
}

function formatRegionResult(result) {
    if (!Array.isArray(result) || result.length === 0) {
        return '';
    }

    const administrativeRegion = result.find(item => item.region_type === 'H');
    const region = administrativeRegion || result[0];

    return String(region.address_name || '').trim();
}

function reverseGeocodeByKakao(lat, lng) {
    return new Promise(resolve => {
        if (!canUseKakaoGeocoder()) {
            resolve('');
            return;
        }

        const geocoder = new kakao.maps.services.Geocoder();

        geocoder.coord2Address(lng, lat, (addressResult, addressStatus) => {
            if (addressStatus === kakao.maps.services.Status.OK) {
                const addressName = formatAddressResult(addressResult);

                if (addressName) {
                    resolve(addressName);
                    return;
                }
            }

            geocoder.coord2RegionCode(lng, lat, (regionResult, regionStatus) => {
                if (regionStatus === kakao.maps.services.Status.OK) {
                    const regionName = formatRegionResult(regionResult);

                    if (regionName) {
                        resolve(regionName);
                        return;
                    }
                }

                resolve('');
            });
        });
    });
}

// =========================
// CURRENT LOCATION -> MORE SPECIFIC PLACE NAME
// =========================

function canUseKakaoPlaces() {
    return Boolean(
        window.kakao &&
        kakao.maps &&
        kakao.maps.services &&
        kakao.maps.services.Places
    );
}

const HERELOG_PLACE_CATEGORY_CODES = [
    'CE7', // 카페
    'FD6', // 음식점
    'CT1', // 문화시설
    'AT4', // 관광명소
    'AD5', // 숙박
    'CS2', // 편의점
    'MT1', // 대형마트
    'SW8', // 지하철역
    'PK6', // 주차장
    'BK9', // 은행
    'PO3', // 공공기관
    'HP8', // 병원
    'PM9', // 약국
    'SC4', // 학교
    'AC5'  // 학원
];

function searchPlacesByCategory(code, lat, lng, radius) {
    return new Promise(resolve => {
        if (!canUseKakaoPlaces()) {
            resolve([]);
            return;
        }

        const places = new kakao.maps.services.Places();
        const location = new kakao.maps.LatLng(lat, lng);

        places.categorySearch(
            code,
            (data, status) => {
                if (status !== kakao.maps.services.Status.OK || !Array.isArray(data)) {
                    resolve([]);
                    return;
                }

                resolve(data);
            },
            {
                location: location,
                radius: radius,
                size: 5,
                sort: kakao.maps.services.SortBy.DISTANCE
            }
        );
    });
}

async function findNearestPlace(lat, lng, accuracy) {
    const gpsAccuracy = Number(accuracy) || 0;

    const radius = Math.min(
        180,
        Math.max(60, Math.ceil(gpsAccuracy * 1.2))
    );

    const results = await Promise.all(
        HERELOG_PLACE_CATEGORY_CODES.map(code => {
            return searchPlacesByCategory(code, lat, lng, radius);
        })
    );

    const candidates = results
        .flat()
        .map(place => {
            return {
                ...place,
                distanceNumber: Number(place.distance)
            };
        })
        .filter(place => Number.isFinite(place.distanceNumber))
        .sort((a, b) => a.distanceNumber - b.distanceNumber);

    const best = candidates[0];

    if (!best) {
        return null;
    }

    /*
        너무 먼 장소는 제외.
        예: GPS는 길가인데 150m 밖 카페가 잡히면 오히려 부정확함.
    */
    if (best.distanceNumber > 100) {
        return null;
    }

    return best;
}

async function resolveSpecificLocationName(lat, lng, accuracy) {
    const [address, nearestPlace] = await Promise.all([
        reverseGeocodeByKakao(lat, lng),
        findNearestPlace(lat, lng, accuracy)
    ]);

    if (nearestPlace) {
        const placeName = String(nearestPlace.place_name || '').trim();

        const placeAddress = String(
            nearestPlace.road_address_name ||
            nearestPlace.address_name ||
            address ||
            ''
        ).trim();

        const distance = Number(nearestPlace.distance);

        const distanceText = Number.isFinite(distance)
            ? `약 ${Math.round(distance)}m`
            : '근처';

        if (placeName && placeAddress) {
            return `${placeName} · ${placeAddress} · ${distanceText}`;
        }

        if (placeName) {
            return `${placeName} · ${distanceText}`;
        }
    }

    return address || '';
}

function getCurrentPositionAsPromise() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('이 브라우저에서는 위치 기능을 사용할 수 없습니다.'));
            return;
        }

        navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true,
            timeout: 8000,
            maximumAge: 0
        });
    });
}

function wait(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

function setHiddenLocationValues(lat, lng, address) {
    if (latInput) {
        latInput.value = lat ?? '';
    }

    if (lngInput) {
        lngInput.value = lng ?? '';
    }

    if (addressInput) {
        addressInput.value = String(address ?? '').trim();
    }
}

function loadCurrentLocationName() {
    locationReady = false;
    setLocationMessage('📍 현재 위치 장소명 불러오는 중...', 'loading');

    locationPromise = getCurrentPositionAsPromise()
        .then(async position => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const accuracy = position.coords.accuracy || 0;

            const address = await resolveSpecificLocationName(lat, lng, accuracy);

            // 핵심: 여기서 실제 form input에 값을 넣어야 함
            setHiddenLocationValues(lat, lng, address);

            console.log('저장될 위치값:', {
                lat: latInput ? latInput.value : null,
                lng: lngInput ? lngInput.value : null,
                address: addressInput ? addressInput.value : null
            });

            if (address) {
                setLocationMessage(`📍 현재 위치: ${address}`, 'success');
            } else {
                setLocationMessage('📍 현재 위치는 저장됐지만 장소명을 찾지 못했습니다.', 'error');
            }
        })
        .catch(() => {
            setHiddenLocationValues('', '', '');
            setLocationMessage('📍 위치 권한이 없어 장소명 없이 글만 저장됩니다.', 'error');
        })
        .finally(() => {
            locationReady = true;
        });

    return locationPromise;
}

loadCurrentLocationName();

// =========================
// IMAGE PREVIEW
// =========================

function previewImage(){

    const fileInput = document.querySelector('#file-input');
    const previewArea = document.querySelector('#preview-area');
    const previewImg = document.querySelector('#preview-img');
    const uploadBtn = document.querySelector('.upload-btn');

    if (!fileInput || !previewArea || !previewImg || !uploadBtn) {
        return;
    }

    const file = fileInput.files[0];

    if(!file) return;

    const fr = new FileReader();

    fr.onload = function(e){

        previewImg.src = e.target.result;

        previewArea.classList.add('show');

        uploadBtn.classList.add('small');

        const uploadIcon = document.querySelector('#uploadIcon');
        const uploadText = document.querySelector('#uploadText');

        if (uploadIcon) uploadIcon.textContent = '📷';
        if (uploadText) uploadText.textContent = '사진 변경하기';
    };

    fr.readAsDataURL(file);
}

// =========================
// REMOVE IMAGE
// =========================

const removeImgBtn = document.querySelector('#removeImg');

if (removeImgBtn) {
    removeImgBtn.onclick = () => {
        const fileInput = document.querySelector('#file-input');
        const previewImg = document.querySelector('#preview-img');
        const previewArea = document.querySelector('#preview-area');
        const uploadBtn = document.querySelector('.upload-btn');
        const uploadIcon = document.querySelector('#uploadIcon');
        const uploadText = document.querySelector('#uploadText');

        if (fileInput) fileInput.value = '';
        if (previewImg) previewImg.src = '';
        if (previewArea) previewArea.classList.remove('show');
        if (uploadBtn) uploadBtn.classList.remove('small');
        if (uploadIcon) uploadIcon.textContent = '＋';
        if (uploadText) uploadText.textContent = '사진 등록하기';
    };
}

// =========================
// BACK BUTTON
// =========================

const backBtn = document.querySelector('.backBtn');

if (backBtn) {
    backBtn.onclick = () => {
        const urlParams = new URLSearchParams(window.location.search);
        const roomCode = urlParams.get('code');

        if (roomCode) {
            location.href = `./room.php?code=${encodeURIComponent(roomCode)}`;
        } else {
            location.href = './main.php';
        }
    };
}

// =========================
// SUBMIT BUTTON
// =========================

if (form && submitBtn && textarea) {
    form.addEventListener('submit', async event => {
        if (submittingAfterLocationCheck) {
            submitBtn.disabled = true;
            submitBtn.textContent = '저장 중...';
            return;
        }

        if (textarea.value.trim() === '') {
            event.preventDefault();
            alert('기록 내용을 입력해 주세요.');
            return;
        }

        if (!locationReady) {
            event.preventDefault();
            submitBtn.disabled = true;
            submitBtn.textContent = '장소명 확인 중...';

            await Promise.race([
                locationPromise,
                wait(3500)
            ]);

            submittingAfterLocationCheck = true;
            submitBtn.disabled = false;
            form.requestSubmit();
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = '저장 중...';
    });
}
