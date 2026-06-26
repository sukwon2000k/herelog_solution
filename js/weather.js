// ==========================================
// 0. 날씨별 배경 이미지 설정
// ==========================================
const BACKGROUNDIMG = {
    default: "url('../src/image/background.png')",
    good: "url('../src/image/background_spring.png')",
    rain: "url('../src/image/background_rain.png')",
    snow: "url('../src/image/background_snow.png')"
};

const WEATHER_CACHE_KEY = 'herelog_weather_pty';
const WEATHER_CACHE_MS = 10 * 60 * 1000; // 10분

function getWeatherTypeFromPty(ptyCode) {
    const code = String(ptyCode);

    if (code === '1' || code === '2' || code === '5') {
        return 'weather-rain';
    }

    if (code === '3' || code === '6' || code === '7') {
        return 'weather-snow';
    }

    return 'weather-flower';
}

function setWeatherBackground(type) {
    const bg =
        type === 'weather-rain'
            ? BACKGROUNDIMG.rain
            : type === 'weather-snow'
                ? BACKGROUNDIMG.snow
                : type === 'weather-flower'
                    ? BACKGROUNDIMG.good
                    : BACKGROUNDIMG.default;

    document.documentElement.style.setProperty('--herelog-bg-image', bg);
}

function getCachedWeatherPty() {
    try {
        const raw = localStorage.getItem(WEATHER_CACHE_KEY);

        if (!raw) {
            return null;
        }

        const cached = JSON.parse(raw);

        if (!cached || cached.pty === undefined || !cached.savedAt) {
            return null;
        }

        if (Date.now() - cached.savedAt > WEATHER_CACHE_MS) {
            return null;
        }

        return String(cached.pty);
    } catch (e) {
        return null;
    }
}

function saveCachedWeatherPty(pty) {
    try {
        localStorage.setItem(
            WEATHER_CACHE_KEY,
            JSON.stringify({
                pty: String(pty),
                savedAt: Date.now()
            })
        );
    } catch (e) {
        // localStorage가 막혀 있어도 날씨 효과는 계속 동작한다.
    }
}

// ==========================================
// 1. 기상청 API용 좌표 변환 공식 (기존 유지)
// ==========================================
function dfs_xy_conv(code, v1, v2) {
    const RE = 6371.00877; const GRID = 5.0; const SLAT1 = 30.0; const SLAT2 = 60.0;
    const OLON = 126.0; const OLAT = 38.0; const XO = 43; const YO = 136;
    const DEGRAD = Math.PI / 180.0;
    const re = RE / GRID; const slat1 = SLAT1 * DEGRAD; const slat2 = SLAT2 * DEGRAD;
    const olon = OLON * DEGRAD; const olat = OLAT * DEGRAD;
    let sn = Math.tan(Math.PI * 0.25 + slat2 * 0.5) / Math.tan(Math.PI * 0.25 + slat1 * 0.5);
    sn = Math.log(Math.cos(slat1) / Math.cos(slat2)) / Math.log(sn);
    let sf = Math.tan(Math.PI * 0.25 + slat1 * 0.5);
    sf = Math.pow(sf, sn) * Math.cos(slat1) / sn;
    let ro = Math.tan(Math.PI * 0.25 + olat * 0.5);
    ro = re * sf / Math.pow(ro, sn);
    let rs = {};
    if (code == "toXY") {
        rs['lat'] = v1; rs['lng'] = v2;
        let ra = Math.tan(Math.PI * 0.25 + (v1) * DEGRAD * 0.5);
        ra = re * sf / Math.pow(ra, sn);
        let theta = v2 * DEGRAD - olon;
        if (theta > Math.PI) theta -= 2.0 * Math.PI;
        if (theta < -Math.PI) theta += 2.0 * Math.PI;
        theta *= sn;
        rs['x'] = Math.floor(ra * Math.sin(theta) + XO + 0.5);
        rs['y'] = Math.floor(ro - ra * Math.cos(theta) + YO + 0.5);
    }
    return rs;
}

// ==========================================
// 2. GPS 위치 탐색 및 기상청 API 실행
// ==========================================
function initWeatherService() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition((position) => {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            const grid = dfs_xy_conv("toXY", lat, lon);
            fetchWeather(grid.x, grid.y);
        }, (error) => {
            console.warn("GPS 허용 안 됨 또는 지연됨, 기본 위치(서울) 실행");
            fetchWeather(60, 127);
        }, {
            enableHighAccuracy: false,
            timeout: 2000,
            maximumAge: 10 * 60 * 1000
        });
    } else {
        fetchWeather(60, 127);
    }
}

// ==========================================
// 3. 기상청 API 호출 함수
// ==========================================
async function fetchWeather(nx, ny) {
    const API_KEY = "3f9c43096b143c16f55fd75c2fc562e5e074861f7de173220025e6c501016fd8";
    const now = new Date();

    let hours = now.getHours();

    if (now.getMinutes() < 40) {
        hours -= 1;
    }

    if (hours < 0) {
        hours = 23;
        now.setDate(now.getDate() - 1);
    }

    // toISOString()은 UTC라 한국 날짜와 어긋날 수 있으므로 로컬 날짜 사용
    const yyyy = now.getFullYear();
    const mm = String(now.getMonth() + 1).padStart(2, '0');
    const dd = String(now.getDate()).padStart(2, '0');

    const base_date = `${yyyy}${mm}${dd}`;
    const base_time = String(hours).padStart(2, '0') + "00";
    const url = `https://apis.data.go.kr/1360000/VilageFcstInfoService_2.0/getUltraSrtNcst?serviceKey=${API_KEY}&pageNo=1&numOfRows=10&dataType=JSON&base_date=${base_date}&base_time=${base_time}&nx=${nx}&ny=${ny}`;

    try {
        const res = await fetch(url);
        const text = await res.text();

        // API 제한/오류로 JSON이 아닌 텍스트가 오는 경우 방어
        if (!text.trim().startsWith('{')) {
            console.warn("기상청 API가 JSON이 아닌 응답을 반환함:", text);
            return;
        }

        const data = JSON.parse(text);
        const items = data?.response?.body?.items?.item;

        if (!Array.isArray(items)) {
            console.warn("기상청 API 응답 구조가 예상과 다름:", data);
            return;
        }

        let pty = "0";

        items.forEach((i) => {
            if (i.category === "PTY") {
                pty = String(i.obsrValue);
            }
        });

        saveCachedWeatherPty(pty);
        applyWeatherEffect(pty);
    } catch (e) {
        console.error("날씨 정보 불러오기 실패:", e);
    }
}

// ==========================================
// 4. 날씨 효과 실행
// ==========================================
function applyWeatherEffect(ptyCode) {
    const type = getWeatherTypeFromPty(ptyCode);

    setWeatherBackground(type);

    let container = document.getElementById('weather-container');

    if (!container) {
        container = document.createElement('div');
        container.id = 'weather-container';
        document.body.appendChild(container);
    }

    container.innerHTML = '';
    container.dataset.weather = type;

    const count =
        type === 'weather-rain'
            ? 100
            : type === 'weather-snow'
                ? 45
                : 36;

    function random(min, max) {
        return Math.random() * (max - min) + min;
    }

    for (let i = 0; i < count; i++) {
        const p = document.createElement('div');
        p.className = type;

        const duration =
            type === 'weather-rain'
                ? random(0.7, 1.4)
                : type === 'weather-snow'
                    ? random(9, 20)
                    : random(12, 26);

        p.style.left = `${random(-5, 105)}vw`;

        /*
            delay를 음수로 주면 페이지가 열렸을 때
            이미 떨어지는 중인 상태로 시작한다.
        */
        p.style.setProperty('--duration', `${duration}s`);
        p.style.setProperty('--delay', `${-random(0, duration)}s`);

        p.style.setProperty('--start-y', `${random(-45, 10)}vh`);
        p.style.setProperty('--size', `${random(7, 18)}px`);
        p.style.setProperty('--drift', `${random(-90, 90)}px`);
        p.style.setProperty('--drift-half', `${random(-55, 55)}px`);
        p.style.setProperty('--sway', `${random(-38, 38)}px`);
        p.style.setProperty('--rotate', `${random(0, 360)}deg`);
        p.style.setProperty('--opacity', random(0.38, 0.86).toFixed(2));
        p.style.setProperty('--blur', `${random(0, 0.8).toFixed(2)}px`);

        container.appendChild(p);
    }
}

// 페이지 로드 직후 빠르게 이전 날씨 또는 맑음 배경을 먼저 보여주고,
// 실제 날씨는 뒤에서 다시 확인한다.
document.addEventListener('DOMContentLoaded', () => {
    const cachedPty = getCachedWeatherPty();

    applyWeatherEffect(cachedPty !== null ? cachedPty : "0");
    initWeatherService();
});
