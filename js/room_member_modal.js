document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('memberModal');
    const closeBtn = document.getElementById('closeMemberModal');
    const memberButtons = document.querySelectorAll('.member-profile-btn');

    if (!modal || !closeBtn || memberButtons.length === 0) {
        return;
    }

    const img = document.getElementById('memberModalImg');
    const name = document.getElementById('memberModalName');
    const nickname = document.getElementById('memberModalNickname');
    const role = document.getElementById('memberModalRole');
    const userId = document.getElementById('memberModalUserId');
    const joinedAt = document.getElementById('memberModalJoinedAt');
    const postCount = document.getElementById('memberModalPostCount');

    function setText(element, value, fallback = '-') {
        if (!element) return;
        const text = String(value || '').trim();
        element.textContent = text !== '' ? text : fallback;
    }

    function openModal(button) {
        const profileImg = button.dataset.profileImg || './board/uploads/profile/default.png';
        const displayName = button.dataset.name || button.dataset.nickname || button.dataset.userId || '참여자';
        const displayNickname = button.dataset.nickname || button.dataset.userId || '';

        if (img) {
            img.src = profileImg;
            img.alt = displayName;
        }

        setText(name, displayName, '참여자');
        setText(nickname, displayNickname ? '@' + displayNickname.replace(/^@/, '') : '', '');
        setText(role, button.dataset.role, '-');
        setText(userId, button.dataset.userId, '-');
        setText(joinedAt, button.dataset.joinedAt, '-');
        setText(postCount, button.dataset.postCount ? button.dataset.postCount + '개' : '0개', '0개');

        modal.hidden = false;
        document.body.classList.add('member-modal-open');
    }

    function closeModal() {
        modal.hidden = true;
        document.body.classList.remove('member-modal-open');
    }

    memberButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            openModal(button);
        });
    });

    closeBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });
});
