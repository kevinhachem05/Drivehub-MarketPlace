<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$user_id   = (int)$_SESSION['user_id'];
$is_admin  = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

/* ── Ensure messages table with ALL car fields ── */
$conn->query("CREATE TABLE IF NOT EXISTS messages (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    car_id           INT DEFAULT NULL,
    car_name         VARCHAR(200) DEFAULT '',
    car_brand        VARCHAR(100) DEFAULT '',
    car_category     VARCHAR(80)  DEFAULT '',
    car_year         VARCHAR(10)  DEFAULT '',
    car_price        VARCHAR(50)  DEFAULT '',
    car_kms          INT          DEFAULT 0,
    car_engine       VARCHAR(80)  DEFAULT '',
    car_power        VARCHAR(50)  DEFAULT '',
    car_drive        VARCHAR(30)  DEFAULT '',
    car_transmission VARCHAR(50)  DEFAULT '',
    car_location     VARCHAR(200) DEFAULT '',
    car_image        VARCHAR(300) DEFAULT '',
    car_description  TEXT,
    sender           ENUM('user','admin') NOT NULL DEFAULT 'user',
    body             TEXT NOT NULL,
    is_read          TINYINT(1) DEFAULT 0,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_car_id  (car_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* Add columns for older installs */
$extra_cols = [
    "car_brand VARCHAR(100) DEFAULT ''",
    "car_category VARCHAR(80) DEFAULT ''",
    "car_year VARCHAR(10) DEFAULT ''",
    "car_price VARCHAR(50) DEFAULT ''",
    "car_kms INT DEFAULT 0",
    "car_engine VARCHAR(80) DEFAULT ''",
    "car_power VARCHAR(50) DEFAULT ''",
    "car_drive VARCHAR(30) DEFAULT ''",
    "car_transmission VARCHAR(50) DEFAULT ''",
    "car_location VARCHAR(200) DEFAULT ''",
    "car_image VARCHAR(300) DEFAULT ''",
    "car_description TEXT",
];
foreach ($extra_cols as $col_def) {
    $col_name = explode(' ', $col_def)[0];
    @$conn->query("ALTER TABLE messages ADD COLUMN IF NOT EXISTS $col_name " . implode(' ', array_slice(explode(' ', $col_def), 1)));
}

/* ── AJAX: send message ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'send_message') {
        $body             = trim($_POST['body'] ?? '');
        $car_id           = isset($_POST['car_id']) ? (int)$_POST['car_id'] : null;
        $car_name         = trim($_POST['car_name'] ?? '');
        $car_brand        = trim($_POST['car_brand'] ?? '');
        $car_category     = trim($_POST['car_category'] ?? '');
        $car_year         = trim($_POST['car_year'] ?? '');
        $car_price        = trim($_POST['car_price'] ?? '');
        $car_kms          = (int)($_POST['car_kms'] ?? 0);
        $car_engine       = trim($_POST['car_engine'] ?? '');
        $car_power        = trim($_POST['car_power'] ?? '');
        $car_drive        = trim($_POST['car_drive'] ?? '');
        $car_transmission = trim($_POST['car_transmission'] ?? '');
        $car_location     = trim($_POST['car_location'] ?? '');
        $car_image        = trim($_POST['car_image'] ?? '');
        $car_description  = trim($_POST['car_description'] ?? '');

        $target_user_id   = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : $user_id;
        $sender           = $is_admin ? 'admin' : 'user';
        $msg_user_id      = $is_admin ? $target_user_id : $user_id;

        if (!$body) {
            echo json_encode(['success' => false, 'message' => 'Message cannot be empty.']);
            exit();
        }

        $ins = $conn->prepare("INSERT INTO messages
            (user_id, car_id, car_name, car_brand, car_category, car_year, car_price, car_kms,
             car_engine, car_power, car_drive, car_transmission, car_location, car_image, car_description, sender, body)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        $ins->bind_param(
            "iisssssississssss",
            $msg_user_id,
            $car_id,
            $car_name,
            $car_brand,
            $car_category,
            $car_year,
            $car_price,
            $car_kms,
            $car_engine,
            $car_power,
            $car_drive,
            $car_transmission,
            $car_location,
            $car_image,
            $car_description,
            $sender,
            $body
        );

        $success   = $ins->execute();
        $insert_id = $ins->insert_id;
        $ins->close();

        if ($is_admin && $success) {
            $conn->query("UPDATE messages SET is_read=1 WHERE user_id=$msg_user_id AND sender='user'");
        }

        echo json_encode([
            'success'    => (bool)$success,
            'message_id' => $insert_id,
            'sender'     => $sender,
            'body'       => htmlspecialchars($body),
            'time'       => date('g:i A')
        ]);
        exit();
    }

    if ($_POST['action'] === 'mark_read') {
        $target = (int)($_POST['target_user_id'] ?? $user_id);
        $role   = $is_admin ? 'user' : 'admin';
        $conn->query("UPDATE messages SET is_read=1 WHERE user_id=$target AND sender='$role'");
        echo json_encode(['success' => true]);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit();
}

/* ── AJAX: fetch messages ── */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch_messages') {
    header('Content-Type: application/json');
    $target = $is_admin ? (int)($_GET['user_id'] ?? 0) : $user_id;
    if (!$target) {
        echo json_encode(['messages' => []]);
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM messages WHERE user_id=? ORDER BY created_at ASC, id ASC");
    $stmt->bind_param("i", $target);
    $stmt->execute();
    $res  = $stmt->get_result();
    $msgs = [];
    while ($r = $res->fetch_assoc()) $msgs[] = $r;
    $stmt->close();

    echo json_encode(['messages' => $msgs]);
    exit();
}

/* ── Page render data ── */
if ($is_admin) {
    $threads_res = $conn->query("
        SELECT u.id, u.first_name, u.last_name, u.email,
               COUNT(CASE WHEN m.sender='user' AND m.is_read=0 THEN 1 END) as unread,
               MAX(m.created_at) as last_msg,
               (SELECT body FROM messages WHERE user_id=u.id ORDER BY created_at DESC, id DESC LIMIT 1) as last_body
        FROM messages m
        JOIN users u ON u.id = m.user_id
        GROUP BY u.id
        ORDER BY last_msg DESC
    ");
    $threads = [];
    while ($t = $threads_res->fetch_assoc()) $threads[] = $t;
} else {
    $my_msgs_stmt = $conn->prepare("SELECT * FROM messages WHERE user_id=? ORDER BY created_at ASC, id ASC");
    $my_msgs_stmt->bind_param("i", $user_id);
    $my_msgs_stmt->execute();
    $my_msgs_result = $my_msgs_stmt->get_result();
    $my_msgs = [];
    while ($r = $my_msgs_result->fetch_assoc()) $my_msgs[] = $r;
    $my_msgs_stmt->close();

    $conn->query("UPDATE messages SET is_read=1 WHERE user_id=$user_id AND sender='admin'");
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>DriveHub — Messages</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --black:    #0a0a0a;
      --off-black:#111111;
      --panel:    #161616;
      --card:     #1a1a1a;
      --border:   rgba(255,255,255,0.08);
      --border-h: rgba(255,255,255,0.16);
      --muted:    #666;
      --text:     #e8e8e8;
      --white:    #ffffff;
      --red:      #e8341a;
      --red-dark: #c0290e;
      --input-bg: #1c1c1c;
      --sidebar:  300px;
    }
    html { height: 100%; }
    body { background: var(--black); color: var(--text); font-family: 'DM Sans', sans-serif; height: 100vh; overflow: hidden; display: flex; flex-direction: column; }
    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #2a2a2a; border-radius: 2px; }

    nav {
      flex-shrink: 0;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 40px; height: 64px;
      background: rgba(10,10,10,0.95);
      border-bottom: 1px solid var(--border);
      z-index: 10;
    }
    .nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
    .logo-mark { width: 30px; height: 30px; background: var(--red); clip-path: polygon(0 0,100% 0,100% 65%,50% 100%,0 65%); display: flex; align-items: center; justify-content: center; }
    .logo-mark span { font-size: 12px; color:#fff; font-weight:700; margin-bottom:4px; }
    .logo-name { font-family: 'Bebas Neue', sans-serif; font-size: 19px; letter-spacing: 0.1em; color: var(--white); }
    .nav-right { display: flex; align-items: center; gap: 16px; }
    .nav-back { font-size: 12px; font-weight: 500; letter-spacing: 0.1em; text-transform: uppercase; color: var(--muted); text-decoration: none; display: flex; align-items: center; gap: 6px; transition: color 0.2s; }
    .nav-back:hover { color: var(--red); }
    .nav-page-title { font-size: 11px; font-weight: 500; letter-spacing: 0.2em; text-transform: uppercase; color: var(--red); }

    .chat-layout { display: flex; flex: 1; overflow: hidden; }

    .thread-sidebar { width: var(--sidebar); min-width: var(--sidebar); background: var(--panel); border-right: 1px solid var(--border); display: flex; flex-direction: column; overflow: hidden; }
    .sidebar-header { padding: 20px 20px 14px; border-bottom: 1px solid var(--border); flex-shrink: 0; }
    .sidebar-header-title { font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 0.08em; color: var(--white); }
    .sidebar-header-sub { font-size: 11px; color: var(--muted); margin-top: 3px; }
    .sidebar-search { padding: 12px 16px; border-bottom: 1px solid var(--border); flex-shrink: 0; }
    .sidebar-search input { width: 100%; padding: 8px 12px; background: var(--input-bg); border: 1px solid var(--border); border-radius: 6px; color: var(--white); font-family: 'DM Sans', sans-serif; font-size: 12px; outline: none; transition: border-color 0.2s; }
    .sidebar-search input:focus { border-color: rgba(232,52,26,0.45); }
    .sidebar-search input::placeholder { color: #3a3a3a; }
    .threads-list { flex: 1; overflow-y: auto; }
    .thread-item { padding: 14px 18px; border-bottom: 1px solid rgba(255,255,255,0.04); cursor: pointer; transition: background 0.15s; display: flex; align-items: center; gap: 12px; position: relative; }
    .thread-item:hover { background: rgba(255,255,255,0.03); }
    .thread-item.active { background: rgba(232,52,26,0.08); border-right: 2px solid var(--red); }
    .thread-avatar { width: 38px; height: 38px; border-radius: 50%; background: var(--red); display: flex; align-items: center; justify-content: center; font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 500; color: #fff; flex-shrink: 0; }
    .thread-info { flex: 1; min-width: 0; }
    .thread-name { font-size: 13px; font-weight: 500; color: var(--white); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .thread-preview { font-size: 11px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
    .thread-time { font-size: 10px; color: var(--muted); white-space: nowrap; }
    .unread-dot { position: absolute; top: 14px; right: 14px; min-width: 18px; height: 18px; background: var(--red); border-radius: 999px; font-size: 10px; font-weight: 700; color: #fff; display: flex; align-items: center; justify-content: center; padding: 0 5px; }
    .threads-empty { padding: 40px 20px; text-align: center; color: var(--muted); font-size: 13px; }

    .chat-area { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
    .chat-topbar { flex-shrink: 0; padding: 0 28px; height: 60px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); background: rgba(10,10,10,0.6); backdrop-filter: blur(8px); }
    .chat-topbar-left { display: flex; align-items: center; gap: 12px; }
    .chat-user-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--red); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 500; color: #fff; }
    .chat-user-name { font-size: 14px; font-weight: 500; color: var(--white); }
    .chat-user-sub { font-size: 11px; color: var(--muted); }
    .online-dot { width: 8px; height: 8px; background: #2a9d5c; border-radius: 50%; }
    .chat-topbar-right { display: flex; align-items: center; gap: 10px; }
    .chip { padding: 4px 12px; background: rgba(255,255,255,0.04); border: 1px solid var(--border); border-radius: 20px; font-size: 11px; color: var(--muted); }
    .chip.car { border-color: rgba(232,52,26,0.2); color: var(--red); background: rgba(232,52,26,0.06); }

    .messages-scroll { flex: 1; overflow-y: auto; padding: 24px 32px; display: flex; flex-direction: column; gap: 10px; }

    .msg-group { display: flex; flex-direction: column; gap: 6px; }
    .msg-row { display: flex; align-items: flex-end; gap: 10px; animation: msgIn 0.22s ease both; }
    .msg-row.from-me { flex-direction: row-reverse; }
    @keyframes msgIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
    .msg-avatar { width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 500; color: #fff; }
    .msg-avatar.admin-av { background: var(--red); }
    .msg-avatar.user-av  { background: #333; }
    .msg-bubble { max-width: 65%; padding: 12px 16px; border-radius: 18px; font-size: 13.5px; line-height: 1.65; word-wrap: break-word; }
    .msg-bubble.from-them { background: var(--card); border: 1px solid var(--border); border-bottom-left-radius: 4px; color: var(--text); }
    .msg-bubble.from-me { background: var(--red); color: #fff; border-bottom-right-radius: 4px; }
    .msg-time { font-size: 10px; color: var(--muted); padding: 0 4px; white-space: nowrap; }

    .car-inline-card {
      width: 100%;
      max-width: 540px;
      margin: 8px 0 6px;
      background: #111;
      border: 1px solid rgba(232,52,26,0.22);
      border-radius: 14px;
      overflow: hidden;
      animation: msgIn 0.3s ease both;
    }
    .car-inline-card.from-me-card {
      margin-left: auto;
    }
    .car-inline-card.from-them-card {
      margin-left: 40px;
    }
    .cic-header {
      display: flex; align-items: center; gap: 8px;
      padding: 9px 14px;
      background: rgba(232,52,26,0.1);
      border-bottom: 1px solid rgba(232,52,26,0.15);
    }
    .cic-header-dot {
      width: 7px; height: 7px; border-radius: 50%; background: var(--red); flex-shrink: 0;
    }
    .cic-header-label {
      font-size: 10px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--red); font-weight: 500;
    }
    .cic-body { display: flex; }
    .cic-image {
      width: 140px; min-width: 140px; height: 108px;
      object-fit: cover; display: block;
      border-right: 1px solid rgba(255,255,255,0.06);
    }
    .cic-image-placeholder {
      width: 140px; min-width: 140px; height: 108px;
      background: #0d0d0d;
      display: flex; align-items: center; justify-content: center;
      border-right: 1px solid rgba(255,255,255,0.06);
    }
    .cic-details { flex: 1; padding: 13px 16px; min-width: 0; }
    .cic-brand { font-size: 9px; letter-spacing: 0.22em; text-transform: uppercase; color: var(--red); margin-bottom: 3px; }
    .cic-name { font-family: 'Bebas Neue', sans-serif; font-size: 22px; letter-spacing: 0.04em; color: var(--white); line-height: 1; margin-bottom: 10px; }
    .cic-specs { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
    .cic-spec-pill {
      display: flex; flex-direction: column;
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.07);
      border-radius: 7px;
      padding: 5px 9px;
      min-width: 54px;
    }
    .cic-spec-val { font-family: 'Bebas Neue', sans-serif; font-size: 14px; color: var(--white); letter-spacing: 0.04em; line-height: 1.1; }
    .cic-spec-key { font-size: 9px; color: var(--muted); letter-spacing: 0.12em; text-transform: uppercase; margin-top: 2px; }
    .cic-bottom { display: flex; align-items: center; justify-content: space-between; margin-top: 2px; gap: 8px; }
    .cic-price { font-family: 'Bebas Neue', sans-serif; font-size: 20px; color: var(--white); letter-spacing: 0.04em; }
    .cic-location { font-size: 10px; color: var(--muted); display: flex; align-items: center; gap: 4px; }
    .cic-desc-row {
      padding: 9px 16px 11px;
      border-top: 1px solid rgba(255,255,255,0.05);
      font-size: 11px; color: #aaa; line-height: 1.6;
    }

    .compose-bar { flex-shrink: 0; padding: 16px 24px; border-top: 1px solid var(--border); background: var(--panel); display: flex; align-items: flex-end; gap: 12px; }
    .compose-input-wrap { flex: 1; background: var(--input-bg); border: 1px solid var(--border); border-radius: 14px; display: flex; align-items: flex-end; gap: 8px; padding: 10px 14px; transition: border-color 0.2s; }
    .compose-input-wrap:focus-within { border-color: rgba(232,52,26,0.45); }
    .compose-input { flex: 1; background: transparent; border: none; outline: none; color: var(--white); font-family: 'DM Sans', sans-serif; font-size: 14px; resize: none; max-height: 120px; line-height: 1.5; }
    .compose-input::placeholder { color: #3a3a3a; }
    .send-btn { width: 44px; height: 44px; background: var(--red); border: none; border-radius: 12px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s, transform 0.1s; flex-shrink: 0; color: #fff; }
    .send-btn:hover { background: var(--red-dark); }
    .send-btn:active { transform: scale(0.93); }
    .send-btn:disabled { background: #2a2a2a; cursor: not-allowed; }

    .empty-chat { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 16px; color: var(--muted); text-align: center; padding: 40px; }
    .empty-chat-icon { opacity: 0.12; }
    .empty-chat-title { font-family: 'Bebas Neue', sans-serif; font-size: 28px; letter-spacing: 0.06em; color: #2a2a2a; }
    .empty-chat-sub { font-size: 13px; color: #2a2a2a; max-width: 280px; line-height: 1.7; }
    .select-thread-state { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 16px; text-align: center; padding: 40px; }

    @media (max-width: 640px) {
      .thread-sidebar { display: none; }
      .messages-scroll { padding: 18px 16px; }
      .compose-bar { padding: 12px 14px; }
      .cic-image, .cic-image-placeholder { width: 100px; min-width: 100px; }
    }
  </style>
</head>
<body>

<nav>
  <a href="<?php echo $is_admin ? 'admin.php' : 'home.php'; ?>" class="nav-logo">
    <div class="logo-mark"><span>⬡</span></div>
    <span class="logo-name">DriveHub</span>
  </a>
  <span class="nav-page-title">Messages</span>
  <div class="nav-right">
    <a href="<?php echo $is_admin ? 'admin.php' : 'home.php'; ?>" class="nav-back">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      <?php echo $is_admin ? 'Admin Panel' : 'Back to listings'; ?>
    </a>
  </div>
</nav>

<div class="chat-layout">

<?php if ($is_admin): ?>
  <aside class="thread-sidebar">
    <div class="sidebar-header">
      <div class="sidebar-header-title">INBOX</div>
      <div class="sidebar-header-sub"><?php echo count($threads); ?> conversation<?php echo count($threads)!=1?'s':''; ?></div>
    </div>
    <div class="sidebar-search">
      <input type="text" placeholder="Search users…" id="threadSearch" oninput="filterThreads(this.value)"/>
    </div>
    <div class="threads-list" id="threadsList">
      <?php if (empty($threads)): ?>
        <div class="threads-empty">No messages yet.<br>Users will appear here when they contact you.</div>
      <?php else: ?>
        <?php foreach ($threads as $t): ?>
        <div class="thread-item" data-uid="<?php echo $t['id']; ?>"
             data-name="<?php echo htmlspecialchars($t['first_name'].' '.$t['last_name']); ?>"
             data-email="<?php echo htmlspecialchars($t['email']); ?>"
             onclick="selectThread(this)">
          <div class="thread-avatar"><?php echo strtoupper(mb_substr($t['first_name'],0,1)); ?></div>
          <div class="thread-info">
            <div class="thread-name"><?php echo htmlspecialchars($t['first_name'].' '.$t['last_name']); ?></div>
            <div class="thread-preview"><?php echo htmlspecialchars(mb_strimwidth($t['last_body'],0,38,'…')); ?></div>
          </div>
          <div class="thread-time"><?php echo date('M j', strtotime($t['last_msg'])); ?></div>
          <?php if ($t['unread'] > 0): ?>
          <div class="unread-dot" id="unread-<?php echo $t['id']; ?>"><?php echo $t['unread']; ?></div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </aside>

  <div class="chat-area" id="adminChatArea">
    <div class="select-thread-state" id="selectThreadState">
      <svg class="empty-chat-icon" width="72" height="72" fill="none" stroke="white" stroke-width="1.2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      <div class="empty-chat-title">SELECT A CONVERSATION</div>
      <div class="empty-chat-sub">Pick a user from the left to view and reply to their messages.</div>
    </div>

    <div id="activeChatWrapper" style="display:none;flex-direction:column;flex:1;overflow:hidden;">
      <div class="chat-topbar">
        <div class="chat-topbar-left">
          <div class="chat-user-avatar" id="chatHeaderAvatar">?</div>
          <div>
            <div class="chat-user-name" id="chatHeaderName">User</div>
            <div class="chat-user-sub" id="chatHeaderSub">—</div>
          </div>
          <div class="online-dot" style="margin-left:4px"></div>
        </div>
        <div class="chat-topbar-right">
          <span class="chip car" id="chatCarChip" style="display:none"></span>
          <span class="chip" id="chatHeaderChip">Loading…</span>
        </div>
      </div>

      <div class="messages-scroll" id="messagesScroll"></div>

      <div class="compose-bar">
        <div class="compose-input-wrap">
          <textarea class="compose-input" id="adminCompose" rows="1" placeholder="Reply to user…" onkeydown="handleEnter(event,'admin')"></textarea>
        </div>
        <button class="send-btn" onclick="adminSend()">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        </button>
      </div>
    </div>
  </div>

<?php else: ?>
  <div class="chat-area">
    <div class="chat-topbar">
      <div class="chat-topbar-left">
        <div class="chat-user-avatar" style="background:var(--red);font-family:'Bebas Neue',sans-serif;font-size:16px;letter-spacing:0.06em;">DH</div>
        <div>
          <div class="chat-user-name">DriveHub Support</div>
          <div class="chat-user-sub">Typically replies in a few hours</div>
        </div>
        <div class="online-dot"></div>
      </div>
      <div class="chat-topbar-right">
        <span class="chip car" id="userCarChip" style="display:none"></span>
      </div>
    </div>

    <div class="messages-scroll" id="userMessagesScroll">
      <?php if (empty($my_msgs)): ?>
      <div class="empty-chat" id="userEmptyState">
        <svg class="empty-chat-icon" width="64" height="64" fill="none" stroke="white" stroke-width="1.2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <div class="empty-chat-title">START THE CONVERSATION</div>
        <div class="empty-chat-sub">Ask about any car, pricing, availability, test drives, or anything else.</div>
      </div>
      <?php else: ?>
        <?php
          $rendered_car_key = '';
          foreach ($my_msgs as $m):
            $isMe        = ($m['sender'] === 'user');
            $bubbleClass = $isMe ? 'from-me' : 'from-them';
            $rowClass    = $isMe ? 'from-me' : '';
            $avatarClass = $isMe ? 'user-av' : 'admin-av';
            $avatarLetter= $isMe
              ? strtoupper(mb_substr($_SESSION['first_name'],0,1))
              : '<span style="font-family:Bebas Neue,sans-serif;font-size:13px;letter-spacing:.04em">DH</span>';

            $car_key = $m['car_name'] ? $m['car_id'] . '_' . $m['car_name'] : '';
            $showCarCard = ($car_key && $car_key !== $rendered_car_key);
            if ($showCarCard) $rendered_car_key = $car_key;
        ?>
        <div class="msg-group">

          <?php if ($showCarCard && !empty($m['car_name'])): ?>
          <div class="car-inline-card <?php echo $isMe ? 'from-me-card' : 'from-them-card'; ?>">
            <div class="cic-header">
              <div class="cic-header-dot"></div>
              <span class="cic-header-label">
                <?php echo $isMe ? 'Inquiring about this car' : 'Car being discussed'; ?>
              </span>
            </div>
            <div class="cic-body">
              <?php if (!empty($m['car_image'])): ?>
                <img src="<?php echo htmlspecialchars($m['car_image']); ?>"
                     alt="<?php echo htmlspecialchars($m['car_name']); ?>"
                     class="cic-image"
                     onerror="this.outerHTML='<div class=&quot;cic-image-placeholder&quot;><svg width=&quot;40&quot; height=&quot;26&quot; fill=&quot;none&quot; stroke=&quot;#333&quot; stroke-width=&quot;1.5&quot; viewBox=&quot;0 0 800 400&quot;><path d=&quot;M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z&quot;/><circle cx=&quot;200&quot; cy=&quot;310&quot; r=&quot;38&quot;/><circle cx=&quot;620&quot; cy=&quot;310&quot; r=&quot;38&quot;/><circle cx=&quot;200&quot; cy=&quot;310&quot; r=&quot;22&quot; fill=&quot;#0d0d0d&quot;/><circle cx=&quot;620&quot; cy=&quot;310&quot; r=&quot;22&quot; fill=&quot;#0d0d0d&quot;/></svg></div>'"/>
              <?php else: ?>
                <div class="cic-image-placeholder">
                  <svg width="40" height="26" fill="none" stroke="#333" stroke-width="1.5" viewBox="0 0 800 400">
                    <path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"/>
                    <circle cx="200" cy="310" r="38"/><circle cx="620" cy="310" r="38"/>
                    <circle cx="200" cy="310" r="22" fill="#0d0d0d"/><circle cx="620" cy="310" r="22" fill="#0d0d0d"/>
                  </svg>
                </div>
              <?php endif; ?>
              <div class="cic-details">
                <?php if (!empty($m['car_brand'])): ?>
                  <div class="cic-brand"><?php echo htmlspecialchars($m['car_brand']); ?></div>
                <?php endif; ?>
                <div class="cic-name"><?php echo htmlspecialchars($m['car_name']); ?></div>
                <div class="cic-specs">
                  <?php if (!empty($m['car_year'])): ?>
                    <div class="cic-spec-pill"><span class="cic-spec-val"><?php echo htmlspecialchars($m['car_year']); ?></span><span class="cic-spec-key">Year</span></div>
                  <?php endif; ?>
                  <?php if (!empty($m['car_kms'])): ?>
                    <div class="cic-spec-pill"><span class="cic-spec-val"><?php echo number_format((int)$m['car_kms']); ?></span><span class="cic-spec-key">KMs</span></div>
                  <?php endif; ?>
                  <?php if (!empty($m['car_engine'])): ?>
                    <div class="cic-spec-pill"><span class="cic-spec-val"><?php echo htmlspecialchars($m['car_engine']); ?></span><span class="cic-spec-key">Engine</span></div>
                  <?php endif; ?>
                  <?php if (!empty($m['car_power'])): ?>
                    <div class="cic-spec-pill"><span class="cic-spec-val"><?php echo htmlspecialchars($m['car_power']); ?></span><span class="cic-spec-key">Power</span></div>
                  <?php endif; ?>
                  <?php if (!empty($m['car_drive'])): ?>
                    <div class="cic-spec-pill"><span class="cic-spec-val"><?php echo htmlspecialchars($m['car_drive']); ?></span><span class="cic-spec-key">Drive</span></div>
                  <?php endif; ?>
                  <?php if (!empty($m['car_transmission'])): ?>
                    <div class="cic-spec-pill"><span class="cic-spec-val"><?php echo htmlspecialchars($m['car_transmission']); ?></span><span class="cic-spec-key">Trans.</span></div>
                  <?php endif; ?>
                  <?php if (!empty($m['car_category'])): ?>
                    <div class="cic-spec-pill"><span class="cic-spec-val"><?php echo htmlspecialchars($m['car_category']); ?></span><span class="cic-spec-key">Type</span></div>
                  <?php endif; ?>
                </div>
                <div class="cic-bottom">
                  <?php if (!empty($m['car_price'])): ?>
                    <div class="cic-price"><?php echo htmlspecialchars($m['car_price']); ?></div>
                  <?php endif; ?>
                  <?php if (!empty($m['car_location'])): ?>
                    <div class="cic-location">
                      <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                      <?php echo htmlspecialchars($m['car_location']); ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php if (!empty($m['car_description'])): ?>
              <div class="cic-desc-row">
                <?php echo htmlspecialchars(mb_strimwidth($m['car_description'], 0, 160, '…')); ?>
              </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <div class="msg-row <?php echo $rowClass; ?>">
            <div class="msg-avatar <?php echo $avatarClass; ?>"><?php echo $avatarLetter; ?></div>
            <div class="msg-bubble <?php echo $bubbleClass; ?>"><?php echo nl2br(htmlspecialchars($m['body'])); ?></div>
            <div class="msg-time"><?php echo date('g:i A', strtotime($m['created_at'])); ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="compose-bar">
      <div class="compose-input-wrap">
        <textarea class="compose-input" id="userCompose" rows="1" placeholder="Type your message…" onkeydown="handleEnter(event,'user')"></textarea>
      </div>
      <button class="send-btn" onclick="userSend()">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
      </button>
    </div>
  </div>
<?php endif; ?>

</div>

<script>
const IS_ADMIN  = <?php echo $is_admin ? 'true' : 'false'; ?>;
const USER_FIRST = <?php echo json_encode($_SESSION['first_name']); ?>;

function esc(str) {
  return String(str || '').replace(/[&<>"']/g, m => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
  }[m]));
}
function scrollBottom(el) { if (el) el.scrollTop = el.scrollHeight; }
function autoGrow(el) { el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, 120) + 'px'; }
function handleEnter(e, mode) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    if (mode === 'admin') adminSend();
    else userSend();
  }
}
function fmtKms(k) {
  return k ? Number(k).toLocaleString() : '—';
}

function buildCarCard(msg, fromMe) {
  if (!msg.car_name) return '';

  const align = fromMe ? 'from-me-card' : 'from-them-card';
  const label = fromMe ? 'Inquiring about this car' : 'Car being discussed';

  let imgHtml;
  if (msg.car_image) {
    imgHtml = `<img src="${esc(msg.car_image)}" alt="${esc(msg.car_name)}" class="cic-image"
      onerror="this.outerHTML='<div class=&quot;cic-image-placeholder&quot;><svg width=&quot;40&quot; height=&quot;26&quot; fill=&quot;none&quot; stroke=&quot;#333&quot; stroke-width=&quot;1.5&quot; viewBox=&quot;0 0 800 400&quot;><path d=&quot;M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z&quot;/><circle cx=&quot;200&quot; cy=&quot;310&quot; r=&quot;38&quot;/><circle cx=&quot;620&quot; cy=&quot;310&quot; r=&quot;38&quot;/><circle cx=&quot;200&quot; cy=&quot;310&quot; r=&quot;22&quot; fill=&quot;#0d0d0d&quot;/><circle cx=&quot;620&quot; cy=&quot;310&quot; r=&quot;22&quot; fill=&quot;#0d0d0d&quot;/></svg></div>'"/>`;
  } else {
    imgHtml = `<div class="cic-image-placeholder">
      <svg width="40" height="26" fill="none" stroke="#333" stroke-width="1.5" viewBox="0 0 800 400">
        <path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"/>
        <circle cx="200" cy="310" r="38"></circle><circle cx="620" cy="310" r="38"></circle>
        <circle cx="200" cy="310" r="22" fill="#0d0d0d"></circle><circle cx="620" cy="310" r="22" fill="#0d0d0d"></circle>
      </svg>
    </div>`;
  }

  const specs = [
    msg.car_year         ? {val: msg.car_year, key:'Year'} : null,
    msg.car_kms          ? {val: fmtKms(msg.car_kms), key:'KMs'} : null,
    msg.car_engine       ? {val: msg.car_engine, key:'Engine'} : null,
    msg.car_power        ? {val: msg.car_power, key:'Power'} : null,
    msg.car_drive        ? {val: msg.car_drive, key:'Drive'} : null,
    msg.car_transmission ? {val: msg.car_transmission, key:'Trans.'} : null,
    msg.car_category     ? {val: msg.car_category, key:'Type'} : null
  ].filter(Boolean);

  const specsHtml = specs.map(s =>
    `<div class="cic-spec-pill"><span class="cic-spec-val">${esc(s.val)}</span><span class="cic-spec-key">${esc(s.key)}</span></div>`
  ).join('');

  const priceHtml = msg.car_price ? `<div class="cic-price">${esc(msg.car_price)}</div>` : '';
  const locHtml = msg.car_location ? `<div class="cic-location"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>${esc(msg.car_location)}</div>` : '';
  const brandHtml = msg.car_brand ? `<div class="cic-brand">${esc(msg.car_brand)}</div>` : '';
  const descText = msg.car_description || '';
  const descHtml = descText ? `<div class="cic-desc-row">${esc(descText.length > 160 ? descText.substring(0,160) + '…' : descText)}</div>` : '';

  return `<div class="car-inline-card ${align}">
    <div class="cic-header">
      <div class="cic-header-dot"></div>
      <span class="cic-header-label">${label}</span>
    </div>
    <div class="cic-body">
      ${imgHtml}
      <div class="cic-details">
        ${brandHtml}
        <div class="cic-name">${esc(msg.car_name)}</div>
        <div class="cic-specs">${specsHtml}</div>
        <div class="cic-bottom">${priceHtml}${locHtml}</div>
      </div>
    </div>
    ${descHtml}
  </div>`;
}

function renderMessages(msgs, perspective, activeUserNameLocal) {
  if (!msgs || !msgs.length) {
    return `<div class="empty-chat">
      <svg class="empty-chat-icon" width="56" height="56" fill="none" stroke="white" stroke-width="1.2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      <div class="empty-chat-title">NO MESSAGES YET</div>
      <div class="empty-chat-sub">This conversation is empty.</div>
    </div>`;
  }

  let html = '';
  let lastCarKey = '';

  msgs.forEach(m => {
    const isMe = perspective === 'admin' ? m.sender === 'admin' : m.sender === 'user';
    const bubClass = isMe ? 'from-me' : 'from-them';
    const rowClass = isMe ? 'from-me' : '';
    const avClass = m.sender === 'admin' ? 'admin-av' : 'user-av';

    let avLetter;
    if (m.sender === 'admin') {
      avLetter = '<span style="font-family:\'Bebas Neue\',sans-serif;font-size:13px;letter-spacing:.04em">DH</span>';
    } else {
      const initials = perspective === 'admin'
        ? esc((activeUserNameLocal || 'U').charAt(0).toUpperCase())
        : esc(USER_FIRST.charAt(0).toUpperCase());
      avLetter = initials;
    }

    const time = new Date(m.created_at).toLocaleTimeString([], {hour:'numeric', minute:'2-digit'});

    const carKey = m.car_name ? (m.car_id || '0') + '_' + m.car_name : '';
    const showCard = carKey && carKey !== lastCarKey;
    if (showCard) lastCarKey = carKey;

    html += `<div class="msg-group">
      ${showCard ? buildCarCard(m, isMe) : ''}
      <div class="msg-row ${rowClass}">
        <div class="msg-avatar ${avClass}">${avLetter}</div>
        <div class="msg-bubble ${bubClass}">${esc(m.body).replace(/\n/g,'<br>')}</div>
        <div class="msg-time">${time}</div>
      </div>
    </div>`;
  });

  return html;
}

/* ADMIN */
let activeUserId = null;
let activeUserName = '';
let pollInterval = null;

function filterThreads(q) {
  q = q.toLowerCase();
  document.querySelectorAll('.thread-item').forEach(t => {
    t.style.display = (t.dataset.name || '').toLowerCase().includes(q) ? '' : 'none';
  });
}

function selectThread(el) {
  document.querySelectorAll('.thread-item').forEach(t => t.classList.remove('active'));
  el.classList.add('active');

  activeUserId = parseInt(el.dataset.uid);
  activeUserName = el.dataset.name;
  const email = el.dataset.email || '';

  document.getElementById('selectThreadState').style.display = 'none';
  const wrap = document.getElementById('activeChatWrapper');
  wrap.style.display = 'flex';

  document.getElementById('chatHeaderAvatar').textContent = activeUserName.charAt(0).toUpperCase();
  document.getElementById('chatHeaderName').textContent = activeUserName;
  document.getElementById('chatHeaderSub').textContent = email;
  document.getElementById('chatHeaderChip').textContent = 'User #' + activeUserId;

  const badge = document.getElementById('unread-' + activeUserId);
  if (badge) badge.remove();

  loadAdminMessages();
  clearInterval(pollInterval);
  pollInterval = setInterval(loadAdminMessages, 5000);

  fetch('messages.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'action=mark_read&target_user_id=' + activeUserId
  });
}

async function loadAdminMessages() {
  if (!activeUserId) return;

  const res = await fetch('messages.php?action=fetch_messages&user_id=' + activeUserId);
  const data = await res.json();
  const scroll = document.getElementById('messagesScroll');
  const atBottom = scroll.scrollHeight - scroll.clientHeight - scroll.scrollTop < 80;

  const carMsg = data.messages ? data.messages.slice().reverse().find(m => m.car_name) : null;
  const chip = document.getElementById('chatCarChip');
  if (carMsg) {
    chip.textContent = '🚗 ' + carMsg.car_name;
    chip.style.display = 'inline-flex';
  } else {
    chip.style.display = 'none';
  }

  scroll.innerHTML = renderMessages(data.messages, 'admin', activeUserName);
  if (atBottom) scrollBottom(scroll);
}

async function adminSend() {
  if (!activeUserId) return;

  const textarea = document.getElementById('adminCompose');
  const body = textarea.value.trim();
  if (!body) return;

  textarea.value = '';
  textarea.style.height = 'auto';

  const res1 = await fetch('messages.php?action=fetch_messages&user_id=' + activeUserId);
  const data1 = await res1.json();

  let lastCar = null;
  if (data1.messages && data1.messages.length) {
    for (let i = data1.messages.length - 1; i >= 0; i--) {
      if (data1.messages[i].car_name) {
        lastCar = data1.messages[i];
        break;
      }
    }
  }

  const formData = new URLSearchParams();
  formData.append('action', 'send_message');
  formData.append('body', body);
  formData.append('target_user_id', activeUserId);

  if (lastCar) {
    if (lastCar.car_id)           formData.append('car_id', lastCar.car_id);
    if (lastCar.car_name)         formData.append('car_name', lastCar.car_name);
    if (lastCar.car_brand)        formData.append('car_brand', lastCar.car_brand);
    if (lastCar.car_category)     formData.append('car_category', lastCar.car_category);
    if (lastCar.car_year)         formData.append('car_year', lastCar.car_year);
    if (lastCar.car_price)        formData.append('car_price', lastCar.car_price);
    if (lastCar.car_kms)          formData.append('car_kms', lastCar.car_kms);
    if (lastCar.car_engine)       formData.append('car_engine', lastCar.car_engine);
    if (lastCar.car_power)        formData.append('car_power', lastCar.car_power);
    if (lastCar.car_drive)        formData.append('car_drive', lastCar.car_drive);
    if (lastCar.car_transmission) formData.append('car_transmission', lastCar.car_transmission);
    if (lastCar.car_location)     formData.append('car_location', lastCar.car_location);
    if (lastCar.car_image)        formData.append('car_image', lastCar.car_image);
    if (lastCar.car_description)  formData.append('car_description', lastCar.car_description);
  }

  await fetch('messages.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: formData.toString()
  });

  loadAdminMessages();
}

/* USER */
<?php if (!$is_admin): ?>
const ctxRaw = sessionStorage.getItem('drivehub_contact_ctx');
let contactCtx = null;
try { contactCtx = ctxRaw ? JSON.parse(ctxRaw) : null; } catch(e) {}

if (contactCtx) {
  sessionStorage.removeItem('drivehub_contact_ctx');

  const chip = document.getElementById('userCarChip');
  if (chip && contactCtx.carName) {
    chip.textContent = '🚗 ' + contactCtx.carName;
    chip.style.display = 'inline-flex';
  }

  const comp = document.getElementById('userCompose');
  if (comp) {
    comp.value = `Hi, I'm interested in the ${contactCtx.carName}. Is it still available?`;
    autoGrow(comp);
  }
}

<?php
$last_car_msg = null;
foreach (array_reverse($my_msgs) as $m) {
  if ($m['car_name']) { $last_car_msg = $m; break; }
}
if ($last_car_msg):
?>
if (!contactCtx) {
  const chip = document.getElementById('userCarChip');
  if (chip) {
    chip.textContent = '🚗 ' + <?php echo json_encode($last_car_msg['car_name']); ?>;
    chip.style.display = 'inline-flex';
  }
}
<?php endif; ?>

const userScroll = document.getElementById('userMessagesScroll');
scrollBottom(userScroll);

setInterval(async () => {
  const res = await fetch('messages.php?action=fetch_messages');
  const data = await res.json();
  if (!data.messages) return;

  const atBottom = userScroll.scrollHeight - userScroll.clientHeight - userScroll.scrollTop < 80;
  userScroll.innerHTML = renderMessages(data.messages, 'user', null);
  if (atBottom) scrollBottom(userScroll);

  fetch('messages.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'action=mark_read'
  });
}, 6000);

async function userSend() {
  const textarea = document.getElementById('userCompose');
  const body = textarea.value.trim();
  if (!body) return;

  const currentCtx = contactCtx ? { ...contactCtx } : null;

  textarea.value = '';
  textarea.style.height = 'auto';

  const emptyState = document.getElementById('userEmptyState');
  if (emptyState) emptyState.remove();

  const scroll = document.getElementById('userMessagesScroll');
  const msgGroup = document.createElement('div');
  msgGroup.className = 'msg-group';

  let html = '';

  if (currentCtx && currentCtx.carName) {
    html += buildCarCard({
      car_id:           currentCtx.carId || '',
      car_name:         currentCtx.carName || '',
      car_brand:        currentCtx.carBrand || '',
      car_category:     currentCtx.carCategory || '',
      car_year:         currentCtx.carYear || '',
      car_price:        currentCtx.carPrice || '',
      car_kms:          currentCtx.carKms || 0,
      car_engine:       currentCtx.carEngine || '',
      car_power:        currentCtx.carPower || '',
      car_drive:        currentCtx.carDrive || '',
      car_transmission: currentCtx.carTransmission || '',
      car_location:     currentCtx.carLocation || '',
      car_image:        currentCtx.carImage || '',
      car_description:  currentCtx.carDesc || ''
    }, true);
  }

  html += `<div class="msg-row from-me">
    <div class="msg-avatar user-av">${esc(USER_FIRST.charAt(0).toUpperCase())}</div>
    <div class="msg-bubble from-me">${esc(body).replace(/\n/g,'<br>')}</div>
    <div class="msg-time">Now</div>
  </div>`;

  msgGroup.innerHTML = html;
  scroll.appendChild(msgGroup);
  scrollBottom(scroll);

  const formData = new URLSearchParams();
  formData.append('action', 'send_message');
  formData.append('body', body);

  if (currentCtx) {
    if (currentCtx.carId)           formData.append('car_id', currentCtx.carId);
    if (currentCtx.carName)         formData.append('car_name', currentCtx.carName);
    if (currentCtx.carBrand)        formData.append('car_brand', currentCtx.carBrand);
    if (currentCtx.carCategory)     formData.append('car_category', currentCtx.carCategory);
    if (currentCtx.carYear)         formData.append('car_year', currentCtx.carYear);
    if (currentCtx.carPrice)        formData.append('car_price', currentCtx.carPrice);
    if (currentCtx.carKms)          formData.append('car_kms', currentCtx.carKms);
    if (currentCtx.carEngine)       formData.append('car_engine', currentCtx.carEngine);
    if (currentCtx.carPower)        formData.append('car_power', currentCtx.carPower);
    if (currentCtx.carDrive)        formData.append('car_drive', currentCtx.carDrive);
    if (currentCtx.carTransmission) formData.append('car_transmission', currentCtx.carTransmission);
    if (currentCtx.carLocation)     formData.append('car_location', currentCtx.carLocation);
    if (currentCtx.carImage)        formData.append('car_image', currentCtx.carImage);
    if (currentCtx.carDesc)         formData.append('car_description', currentCtx.carDesc);
  }

  await fetch('messages.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: formData.toString()
  });

  if (currentCtx) {
    contactCtx = null;
  }

  const res = await fetch('messages.php?action=fetch_messages');
  const data = await res.json();
  userScroll.innerHTML = renderMessages(data.messages, 'user', null);
  scrollBottom(userScroll);
}
<?php endif; ?>

document.querySelectorAll('.compose-input').forEach(ta => {
  ta.addEventListener('input', () => autoGrow(ta));
});
</script>
</body>
</html>