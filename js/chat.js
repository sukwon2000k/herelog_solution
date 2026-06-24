let lastMessageNo = 0; // 마지막으로 화면에 띄운 메시지 번호 기억
const chatBody = document.getElementById('chatBody');

// 세션이나 PHP에서 유저 정보를 전역 변수로 넘겨받았다고 가정하는 임시 데이터입니다.
// 💡 실제 로그인/세션 정보를 바탕으로 아래 변수들을 연동하시면 됩니다.
const myNickname = "쌀숭이"; 
const myProfileImg = "./board/uploads/profile/20260622_025218_Maple_A_250701_003219.jpg";

// URL에서 현재 방의 code 값 추출하기
const urlParams = new URLSearchParams(window.location.search);
const roomCode = urlParams.get('code') || '';

// 페이지가 로드되면 최초 한 번 메시지를 불러오고, 이후 1.5초마다 실시간 확인
document.addEventListener("DOMContentLoaded", () => {
    fetchNewMessages();
    setInterval(fetchNewMessages, 1500); // 1500ms = 1.5초 주기
});

// 📤 메시지 서버로 전송하기
async function sendMessage() {
    const input = document.getElementById('chatInput');
    const messageText = input.value.trim();
    if (messageText === '' || !roomCode) return;

    input.value = ''; // 전송 버튼 누르자마자 입력창은 먼저 비우기

    const formData = new FormData();
    formData.append('room_code', roomCode);
    formData.append('nickname', myNickname);
    formData.append('profile_img', myProfileImg);
    formData.append('message', messageText);

    try {
        const response = await fetch('chat_process.php?action=send', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.status === 'success') {
            fetchNewMessages(); // 전송 성공하면 즉시 최신화 호출
        } else {
            alert("메시지 전송 실패: " + result.message);
        }
    } catch (error) {
        console.error("서버와 통신 중 오류 발생:", error);
    }
}

// 📥 최신 메시지 서버에서 가져오기
async function fetchNewMessages() {
    if (!roomCode) return;

    try {
        const response = await fetch(`chat_process.php?action=fetch&room_code=${roomCode}&last_no=${lastMessageNo}`);
        const messages = await response.json();

        if (messages.length > 0) {
            messages.forEach(msg => {
                const isMine = (msg.nickname === myNickname); // 내 메시지 여부 판단
                const newRow = document.createElement('div');
                newRow.className = `msg-row ${isMine ? 'mine' : 'other'}`;

                if (isMine) {
                    newRow.innerHTML = `
                        <div class="msg-content">
                            <div class="msg-box msg-mine">${escapeHtml(msg.message)}</div>
                        </div>
                    `;
                } else {
                    newRow.innerHTML = `
                        <img src="${msg.profile_img}" onerror="this.src='./board/uploads/profile/default.png'" alt="프로필" class="profile-img">
                        <div class="msg-content">
                            <div class="nickname">${escapeHtml(msg.nickname)}</div>
                            <div class="msg-box msg-other">${escapeHtml(msg.message)}</div>
                        </div>
                    `;
                }

                chatBody.appendChild(newRow);
                lastMessageNo = msg.no; // 가장 마지막에 추가된 메시지 번호 갱신
            });

            // 새 메시지가 오면 스크롤을 부드럽게 맨 아래로 내림
            chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: 'smooth' });
        }
    } catch (error) {
        console.error("새 메시지를 불러오지 못했습니다:", error);
    }
}

// HTML 보안 처리 함수
function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;').replaceAll("'", '&#039;');
}