<?php
require_once __DIR__ . '/config/room.php';
require_once __DIR__ . '/config/post_interactions.php';
require_login('./index.html');

$roomcode = trim($_GET['code'] ?? '');
$user_id = current_user_id();

$db = db_connect();
$room = require_room_member($db, $roomcode, $user_id, './main.php');
$room_no = (int)$room['no'];
$posts = get_room_posts_with_counts($db, $room_no, 100);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($room['roomname']); ?> 기록 목록 - HereLog</title>
    <style>
        @import url('./css/common.css');

        :root{
            --va-navy:#183A5A;
            --va-gray:#6B7280;
            --va-light-gray:#8AA0B3;
            --va-blue:#4FACFE;
            --va-card:rgba(255,255,255,.82);
            --va-card-strong:rgba(255,255,255,.94);
            --va-line:rgba(24,58,90,.10);
            --va-shadow:0 8px 20px rgba(0,0,0,.07);
        }

        html,
        body{
            width:100%;
            min-height:100%;
        }

        body{
            min-height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            background-image:url('./src/image/background.png');
            background-size:cover;
            background-position:center;
            color:var(--va-navy);
            font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .va-page{
            width:100%;
            max-width:420px;
            height:100vh;
            min-height:100vh;
            display:flex;
            flex-direction:column;
            overflow:hidden;
            background:rgba(255,255,255,.15);
            backdrop-filter:blur(12px);
        }

        .va-header{
            flex:0 0 auto;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            padding:18px 20px;
            background:rgba(255,255,255,.68);
            backdrop-filter:blur(15px);
            box-shadow:0 4px 15px rgba(0,0,0,.05);
        }

        .va-back,
        .va-space{
            width:42px;
            height:42px;
            flex:0 0 42px;
        }

        .va-back{
            border:0;
            border-radius:16px;
            display:flex;
            align-items:center;
            justify-content:center;
            background:rgba(255,255,255,.78);
            box-shadow:0 4px 12px rgba(0,0,0,.05);
            cursor:pointer;
        }

        .va-back img{
            width:28px;
            height:28px;
            display:block;
            object-fit:contain;
        }

        .va-title{
            flex:1;
            min-width:0;
            text-align:center;
            color:var(--va-navy);
            font-size:20px;
            font-weight:900;
            line-height:1.2;
        }

        .va-title span{
            display:block;
            margin-top:4px;
            color:var(--va-gray);
            font-size:11px;
            font-weight:700;
            letter-spacing:1.5px;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .va-list{
            flex:1 1 auto;
            overflow-y:auto;
            overscroll-behavior:contain;
            padding:18px 18px 30px;
        }

        .va-list::-webkit-scrollbar{
            width:4px;
        }

        .va-list::-webkit-scrollbar-thumb{
            border-radius:999px;
            background:rgba(24,58,90,.20);
        }

        .va-empty{
            padding:34px 20px;
            border-radius:24px;
            background:var(--va-card);
            box-shadow:var(--va-shadow);
            color:var(--va-navy);
            text-align:center;
            font-size:14px;
            font-weight:800;
        }

        .va-card{
            display:block;
            width:100%;
            margin:0 0 16px;
            border:1px solid rgba(255,255,255,.62);
            border-radius:24px;
            overflow:hidden;
            background:var(--va-card);
            box-shadow:var(--va-shadow);
            cursor:pointer;
        }

        .va-card:last-child{
            margin-bottom:0;
        }

        .va-thumb{
            display:block;
            width:100%;
            height:178px;
            object-fit:cover;
            background:rgba(255,255,255,.72);
        }

        .va-no-thumb{
            height:116px;
            display:flex;
            align-items:center;
            justify-content:center;
            background:linear-gradient(135deg, rgba(79,172,254,.18), rgba(255,255,255,.86));
            color:var(--va-light-gray);
            font-size:13px;
            font-weight:800;
        }

        .va-info{
            display:block;
            padding:12px 14px 14px;
            background:var(--va-card-strong);
        }

        .va-top{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            margin-bottom:9px;
        }

        .va-author{
            display:flex;
            align-items:center;
            gap:8px;
            min-width:0;
        }

        .va-author img{
            width:30px;
            height:30px;
            flex:0 0 30px;
            border-radius:50%;
            object-fit:cover;
            background:white;
            box-shadow:0 4px 10px rgba(0,0,0,.08);
        }

        .va-author strong{
            display:block;
            min-width:0;
            color:var(--va-navy);
            font-size:13px;
            font-weight:900;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .va-stats{
            display:flex;
            align-items:center;
            justify-content:flex-end;
            gap:8px;
            flex:0 0 auto;
            color:var(--va-navy);
            font-size:12px;
            font-weight:900;
            white-space:nowrap;
        }

        .va-location{
            display:flex;
            align-items:center;
            gap:6px;
            width:100%;
            margin-bottom:8px;
            padding:7px 10px;
            border-radius:14px;
            background:rgba(79,172,254,.13);
            color:var(--va-navy);
            font-size:12px;
            font-weight:800;
            line-height:1.3;
        }

        .va-location.is-empty{
            color:var(--va-light-gray);
            background:rgba(255,255,255,.60);
        }

        .va-location span:last-child{
            min-width:0;
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
        }

        .va-date{
            display:block;
            color:var(--va-gray);
            font-size:12px;
            font-weight:700;
            line-height:1.35;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .va-preview{
            display:block;
            margin-top:8px;
            color:var(--va-navy);
            font-size:13px;
            font-weight:650;
            line-height:1.45;
            word-break:break-word;
        }

        @media (max-width:360px){
            .va-header{
                padding:16px 14px;
            }

            .va-list{
                padding:16px 14px 26px;
            }

            .va-title{
                font-size:18px;
            }

            .va-back,
            .va-space{
                width:38px;
                height:38px;
                flex-basis:38px;
            }
        }
    </style>
</head>
<body data-herelog-view-all-version="restore-plus-isolated-v1">
    <div class="va-page">
        <header class="va-header">
            <button class="va-back" type="button" onclick="location.href='./room.php?code=<?php echo urlencode($roomcode); ?>'">
                <img src="./src/image/back.png" alt="뒤로가기">
            </button>
            <div class="va-title">
                전체 기록
                <span><?php echo e($room['roomname']); ?></span>
            </div>
            <div class="va-space"></div>
        </header>

        <main class="va-list">
<?php if (count($posts) === 0) { ?>
            <div class="va-empty">아직 저장된 기록이 없습니다.</div>
<?php } ?>

<?php foreach ($posts as $post) { ?>
<?php
    $postNo = (int)($post['no'] ?? 0);
    $displayName = trim((string)($post['nickname'] ?? '')) !== '' ? $post['nickname'] : ($post['user_id'] ?? '알 수 없음');
    $profileImg = profile_img_or_default($post['profile_img'] ?? null);
    $preview = trim(text_preview($post['content'] ?? '', 60));
    $address = trim((string)($post['address'] ?? ''));
    $createdAt = trim((string)($post['created_at'] ?? ''));
    $likeCount = (int)($post['like_count'] ?? 0);
    $commentCount = (int)($post['comment_count'] ?? 0);
    $hasLocation = $address !== '';
?>
            <article
                class="va-card"
                role="link"
                tabindex="0"
                onclick="location.href='./view.php?post_no=<?php echo $postNo; ?>'"
                onkeydown="if(event.key==='Enter'){location.href='./view.php?post_no=<?php echo $postNo; ?>'}"
            >
<?php if (!empty($post['imgpath'])) { ?>
                <img class="va-thumb" src="./board/<?php echo e($post['imgpath']); ?>" alt="기록 이미지">
<?php } else { ?>
                <div class="va-no-thumb">이미지 없는 기록</div>
<?php } ?>
                <div class="va-info">
                    <div class="va-top">
                        <div class="va-author">
                            <img
                                src="./board/<?php echo e($profileImg); ?>"
                                alt="<?php echo e($displayName); ?>"
                                onerror="this.src='./board/uploads/profile/default.png'">
                            <strong><?php echo e($displayName); ?></strong>
                        </div>
                        <div class="va-stats" aria-label="게시글 반응">
                            <span>♡ <?php echo $likeCount; ?></span>
                            <span>댓글 <?php echo $commentCount; ?></span>
                        </div>
                    </div>

                    <div class="va-location<?php echo $hasLocation ? '' : ' is-empty'; ?>">
                        <span>📍</span>
                        <span><?php echo e($hasLocation ? $address : '위치 정보 없음'); ?></span>
                    </div>

                    <time class="va-date"><?php echo e($createdAt); ?></time>
<?php if ($preview !== '') { ?>
                    <p class="va-preview"><?php echo e($preview); ?></p>
<?php } ?>
                </div>
            </article>
<?php } ?>
        </main>
    </div>
</body>
</html>
<?php
mysqli_close($db);
?>
