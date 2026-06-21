// =========================
// TEXT COUNT
// =========================

const textarea = document.querySelector('#content');
const count = document.querySelector('#count');

textarea.addEventListener('input', () => {
    count.innerHTML = textarea.value.length;
});

// =========================
// CURRENT LOCATION
// =========================

const locationInfo = document.querySelector('#location-info');
const latInput = document.querySelector('#lat');
const lngInput = document.querySelector('#lng');

if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
        position => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            latInput.value = lat;
            lngInput.value = lng;

            locationInfo.innerHTML = `📍 현재 위치 저장됨 (${lat.toFixed(5)}, ${lng.toFixed(5)})`;
        },
        () => {
            locationInfo.innerHTML = '📍 위치 권한이 없어 글만 저장됩니다.';
        },
        {
            enableHighAccuracy: true,
            timeout: 8000,
            maximumAge: 0
        }
    );
} else {
    locationInfo.innerHTML = '📍 이 브라우저에서는 위치 기능을 사용할 수 없습니다.';
}

// =========================
// IMAGE PREVIEW
// =========================

function previewImage(){

    const fileInput = document.querySelector('#file-input');
    const previewArea = document.querySelector('#preview-area');
    const previewImg = document.querySelector('#preview-img');
    const uploadBtn = document.querySelector('.upload-btn');

    const file = fileInput.files[0];

    if(!file) return;

    const fr = new FileReader();

    fr.onload = function(e){

        previewImg.src = e.target.result;

        previewArea.classList.add('show');

        uploadBtn.classList.add('small');

        document.querySelector('#uploadIcon').innerHTML = '📷';
        document.querySelector('#uploadText').innerHTML = '사진 변경하기';
    };

    fr.readAsDataURL(file);
}

// =========================
// REMOVE IMAGE
// =========================

document.querySelector('#removeImg').onclick = () => {

    document.querySelector('#file-input').value = '';

    document.querySelector('#preview-img').src = '';

    document.querySelector('#preview-area')
        .classList.remove('show');

    document.querySelector('.upload-btn')
        .classList.remove('small');

    document.querySelector('#uploadIcon').innerHTML = '＋';
    document.querySelector('#uploadText').innerHTML = '사진 등록하기';
};

// =========================
// BACK BUTTON
// =========================

document.querySelector('.backBtn').onclick = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const roomCode = urlParams.get('code');

    if (roomCode) {
        location.href = `./room.php?code=${roomCode}`;
    } else {
        location.href = './main.php';
    }
};

// =========================
// SUBMIT BUTTON
// =========================

const form = document.querySelector('form.container');
const submitBtn = document.querySelector('#submitBtn');

form.addEventListener('submit', event => {
    if (textarea.value.trim() === '') {
        event.preventDefault();
        alert('기록 내용을 입력해 주세요.');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '저장 중...';
});
