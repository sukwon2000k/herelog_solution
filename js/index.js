var start = document.querySelector('.start');
var loginSign = document.querySelectorAll('.btn');
var header = document.querySelector('.header');

const loginBtn = document.querySelector('#login');
const signupBtn = document.querySelector('#signup');

const loginBox = document.querySelector('.log');
const signBox = document.querySelector('.sign');

const authBtns = document.querySelector('.btn');
const backBtns = document.querySelectorAll('.backBtn');
//시작하기 누르면 로그인,회원가입 창 뜸
start.onclick = function () {
    loginSign.forEach(btn => {
        btn.classList.add('show');
    });

    header.style.transform = 'translateY(-100px)';
    header.style.transition = '0.6s ease';

    start.style.display = 'none';
};
//로그인 버튼 누르면 로그인 폼 보여줌
loginBtn.onclick = () => {
    authBtns.style.display = 'none';

    loginBox.classList.add('show');
    signBox.classList.remove('show');
};
//회원가입 버튼 누르면 회원가입 폼 보여줌
signupBtn.onclick = () => {
    authBtns.style.display = 'none';

    signBox.classList.add('show');
    document.querySelector('.header').classList.add('signup-open');
    loginBox.classList.remove('show');
};
//뒤로가기 버튼 누르면 뒤로가짐
backBtns.forEach(btn => {
    btn.onclick = () => {
        authBtns.style.display = 'flex';

        document.querySelector('.header').classList.remove('signup-open');
        loginBox.classList.remove('show');
        signBox.classList.remove('show');
    };
});

function previewProfile(){

    const fileInput =
        document.querySelector('#profile_img');

    const file =
        fileInput.files[0];

    if(!file) return;

    const reader =
        new FileReader();

    reader.onload = function(e){

        document
        .querySelector('#previewImg')
        .src = e.target.result;

        document
        .querySelector('#uploadText')
        .innerHTML = '📷 사진 변경';

    };

    reader.readAsDataURL(file);
}

document
.querySelector('#removeProfile')
.onclick = () => {

    document
    .querySelector('#profile_img')
    .value = '';

    document
    .querySelector('#previewImg')
    .src =
    './board/uploads/profile/default.png';

    document
    .querySelector('#uploadText')
    .innerHTML =
    '📷 프로필 사진 등록';
};