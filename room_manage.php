<?php
require_once __DIR__ . '/config/room.php';
require_login('./index.html');

$roomcode = trim($_GET['code'] ?? '');
$user_id = current_user_id();

$db = db_connect();
$room = require_room_member($db, $roomcode, $user_id, './main.php');
$room_no = (int)$room['no'];
$is_public_room = is_herelog_public_room($room);
$room_url = './room.php?code=' . urlencode($roomcode);

if ($is_public_room) {
    mysqli_close($db);
    alert_redirect('만남의 광장은 기본 공용 방이라 관리할 수 없습니다.', './room.php?public=1');
}

if (!is_room_owner_user($db, $room_no, $user_id)) {
    mysqli_close($db);
    alert_redirect('방장만 방을 관리할 수 있습니다.', $room_url);
}

$transfer_targets = get_room_owner_transfer_targets($db, $room_no, $user_id);
$member_count = get_room_member_count($db, $room_no);
mysqli_close($db);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($room['roomname']); ?> 방 관리 - HereLog</title>
    <style>
        *{box-sizing:border-box}
        body{
            min-height:100vh;
            margin:0;
            padding:22px 16px 34px;
            font-family:Arial, sans-serif;
            color:#183A5A;
            background:linear-gradient(180deg,#dff3ff,#f7fbff);
        }
        .page{
            width:100%;
            max-width:420px;
            margin:0 auto;
        }
        .top{
            display:flex;
            align-items:center;
            gap:12px;
            margin-bottom:18px;
        }
        .back{
            width:44px;
            height:44px;
            border:none;
            border-radius:16px;
            background:rgba(255,255,255,.8);
            color:#183A5A;
            font-size:23px;
            font-weight:800;
            box-shadow:0 6px 18px rgba(24,58,90,.12);
            cursor:pointer;
        }
        .title h1{
            margin:0;
            font-size:24px;
        }
        .title p{
            margin:4px 0 0;
            color:#6B7280;
            font-size:13px;
        }
        .card{
            margin-bottom:14px;
            padding:18px;
            border-radius:24px;
            background:rgba(255,255,255,.78);
            backdrop-filter:blur(14px);
            box-shadow:0 10px 28px rgba(24,58,90,.12);
        }
        .card h2{
            margin:0 0 8px;
            font-size:18px;
        }
        .card p{
            margin:0 0 14px;
            color:#6B7280;
            font-size:13px;
            line-height:1.55;
        }
        select{
            width:100%;
            margin-bottom:12px;
            padding:13px 14px;
            border:1px solid rgba(24,58,90,.12);
            border-radius:15px;
            background:rgba(255,255,255,.92);
            color:#183A5A;
            font-size:14px;
            outline:none;
        }
        .btn{
            width:100%;
            min-height:48px;
            border:none;
            border-radius:16px;
            color:white;
            font-size:15px;
            font-weight:800;
            cursor:pointer;
            box-shadow:0 8px 20px rgba(0,168,232,.20);
        }
        .btn-transfer{
            background:linear-gradient(135deg,#4FACFE,#00F2FE);
        }
        .btn-delete{
            background:linear-gradient(135deg,#ff6b6b,#ff3b6b);
            box-shadow:0 8px 20px rgba(255,59,107,.22);
        }
        .empty{
            padding:13px 14px;
            border-radius:16px;
            background:rgba(255,255,255,.65);
            color:#6B7280;
            font-size:13px;
            line-height:1.55;
        }
        .danger{
            border:1px solid rgba(255,59,107,.18);
        }
        .meta{
            margin-bottom:14px;
            padding:13px 14px;
            border-radius:18px;
            background:rgba(255,255,255,.65);
            font-size:13px;
            color:#6B7280;
        }
        .meta strong{
            color:#183A5A;
        }
    </style>
</head>
<body>
    <main class="page">
        <div class="top">
            <button class="back" type="button" onclick="location.href='<?php echo $room_url; ?>'">‹</button>
            <div class="title">
                <h1>방 관리</h1>
                <p><?php echo e($room['roomname']); ?></p>
            </div>
        </div>

        <div class="meta">
            현재 참여자 <strong><?php echo number_format($member_count); ?>명</strong> · 방장 전용 메뉴
        </div>

        <section class="card">
            <h2>방장 넘겨주고 나가기</h2>
            <p>선택한 멤버에게 방장 권한을 넘긴 뒤, 내 계정은 이 방의 멤버 목록에서 빠집니다. 내가 쓴 게시글은 삭제되지 않습니다.</p>

<?php if (count($transfer_targets) > 0) { ?>
            <form
                action="./transfer_owner_leave_room.php"
                method="post"
                onsubmit="return confirm('선택한 멤버에게 방장을 넘기고 이 방에서 나갈까요?');">
                <input type="hidden" name="roomcode" value="<?php echo e($roomcode); ?>">
                <select name="new_owner_id" required>
                    <option value="">새 방장을 선택하세요</option>
<?php foreach ($transfer_targets as $member) {
    $displayName = trim((string)($member['nickname'] ?? ''));
    if ($displayName === '') {
        $displayName = trim((string)($member['name'] ?? ''));
    }
    if ($displayName === '') {
        $displayName = (string)$member['user_id'];
    }
?>
                    <option value="<?php echo e($member['user_id']); ?>">
                        <?php echo e($displayName); ?><?php echo $displayName !== (string)$member['user_id'] ? ' (' . e($member['user_id']) . ')' : ''; ?>
                    </option>
<?php } ?>
                </select>
                <button class="btn btn-transfer" type="submit">방장 넘기고 나가기</button>
            </form>
<?php } else { ?>
            <div class="empty">
                넘겨줄 멤버가 없습니다. 혼자 있는 방은 방장을 넘기고 나갈 수 없고, 방 삭제만 가능합니다.
            </div>
<?php } ?>
        </section>

        <section class="card danger">
            <h2>방 삭제</h2>
            <p>방, 멤버 목록, 게시글, 마커, 좋아요, 댓글, 채팅 기록을 삭제합니다. 삭제 후에는 되돌릴 수 없습니다.</p>
            <form
                action="./delete_room.php"
                method="post"
                onsubmit="return confirm('정말 이 방을 삭제할까요? 게시글과 댓글까지 모두 삭제되며 되돌릴 수 없습니다.');">
                <input type="hidden" name="roomcode" value="<?php echo e($roomcode); ?>">
                <button class="btn btn-delete" type="submit">방 삭제</button>
            </form>
        </section>
    </main>
</body>
</html>
