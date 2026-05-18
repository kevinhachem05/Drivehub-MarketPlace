<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}

include 'db.php';

$success = '';
$error   = '';

/* ── ADD is_active COLUMN IF MISSING (run once) ── */
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1");

/* ── TOGGLE ACTIVE/INACTIVE ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_user_id'])) {
    $tog_id     = (int) $_POST['toggle_user_id'];
    $tog_status = (int) $_POST['toggle_status']; // new desired status
    if ($tog_id === (int) $_SESSION['user_id']) {
        $error = "You cannot change your own status.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $tog_status, $tog_id);
        if ($stmt->execute()) {
            $success = $tog_status === 1 ? "User activated successfully." : "User deactivated successfully.";
        } else {
            $error = "Failed to update user status: " . $conn->error;
        }
        $stmt->close();
    }
}

/* ── SEARCH ── */
$search = trim($_GET['search'] ?? '');
$tab    = $_GET['tab'] ?? 'users'; // 'users' or 'analytics'

if ($search !== '') {
    $like   = '%' . $conn->real_escape_string($search) . '%';
    $result = $conn->query("SELECT id, first_name, last_name, email, created_at, is_active FROM users WHERE first_name LIKE '$like' OR last_name LIKE '$like' OR email LIKE '$like' ORDER BY created_at DESC");
} else {
    $result = $conn->query("SELECT id, first_name, last_name, email, created_at, is_active FROM users ORDER BY created_at DESC");
}

$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) $users[] = $row;
}

$total_users = count($users);

/* ── STATS ── */
$total_res   = $conn->query("SELECT COUNT(*) as c FROM users");
$total_all   = $total_res ? $total_res->fetch_assoc()['c'] : 0;
$active_res  = $conn->query("SELECT COUNT(*) as c FROM users WHERE is_active = 1");
$total_active = $active_res ? $active_res->fetch_assoc()['c'] : 0;
$inactive_res = $conn->query("SELECT COUNT(*) as c FROM users WHERE is_active = 0");
$total_inactive = $inactive_res ? $inactive_res->fetch_assoc()['c'] : 0;

$superadmin_emails_list = "'superadmin@drivehub.lb'";
$admin_emails_list      = "'admin@drivehub.lb','admin2@drivehub.lb'";
$reg_res   = $conn->query("SELECT COUNT(*) as c FROM users WHERE email NOT IN ($superadmin_emails_list, $admin_emails_list)");
$total_reg = $reg_res ? $reg_res->fetch_assoc()['c'] : 0;

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>DriveHub — Super Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --black:    #0a0a0a;
      --off-black:#111111;
      --panel:    #161616;
      --card:     #1a1a1a;
      --border:   rgba(255,255,255,0.08);
      --border-hover: rgba(255,255,255,0.18);
      --muted:    #666;
      --text:     #e8e8e8;
      --white:    #ffffff;
      --red:      #e8341a;
      --red-dark: #c0290e;
      --input-bg: #1c1c1c;
      --green:    #2a9d5c;
      --green-dim: rgba(42,157,92,0.15);
      --amber:    #f59e0b;
    }
    html { scroll-behavior: smooth; }
    body { background: var(--black); color: var(--text); font-family: 'DM Sans', sans-serif; min-height: 100vh; }
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: var(--off-black); }
    ::-webkit-scrollbar-thumb { background: #333; border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--red); }

    /* NAV */
    nav {
      position: fixed; top: 0; left: 0; right: 0; z-index: 100;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 56px; height: 68px;
      background: rgba(10,10,10,0.95);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border);
    }
    .nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
    .logo-mark {
      width: 32px; height: 32px; background: var(--red);
      clip-path: polygon(0 0, 100% 0, 100% 65%, 50% 100%, 0 65%);
      display: flex; align-items: center; justify-content: center;
    }
    .logo-mark span { font-size: 13px; color:#fff; font-weight:700; margin-bottom:5px; }
    .logo-name { font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 0.1em; color: var(--white); }
    .nav-center { display: flex; align-items: center; gap: 8px; }
    .nav-title { font-family: 'Bebas Neue', sans-serif; font-size: 18px; letter-spacing: 0.15em; color: var(--white); }
    .nav-title span { color: var(--red); }
    .nav-badge { padding: 3px 10px; background: rgba(232,52,26,0.15); border: 1px solid rgba(232,52,26,0.3); border-radius: 3px; font-size: 10px; font-weight: 500; letter-spacing: 0.15em; text-transform: uppercase; color: var(--red); }
    .nav-right { display: flex; align-items: center; gap: 12px; }
    .nav-profile { display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 5px 10px; border-radius: 6px; transition: background 0.2s; text-decoration: none; }
    .nav-profile:hover { background: rgba(255,255,255,0.06); }
    .nav-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--red); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 500; color: #fff; flex-shrink: 0; }
    .nav-profile-name { font-size: 13px; font-weight: 500; color: var(--text); }
    .nav-logout-btn {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 8px 16px;
      background: transparent;
      border: 1px solid rgba(232,52,26,0.25);
      border-radius: 6px;
      color: #e8341a;
      font-family: 'DM Sans', sans-serif;
      font-size: 12px; font-weight: 500;
      letter-spacing: 0.08em; text-transform: uppercase;
      text-decoration: none;
      transition: all 0.2s;
    }
    .nav-logout-btn:hover { background: rgba(232,52,26,0.1); border-color: var(--red); color: var(--white); }

    /* MAIN */
    .main { padding: 100px 56px 60px; }

    /* ALERT */
    .alert { padding: 13px 18px; border-radius: 5px; font-size: 13px; margin-bottom: 24px; border: 1px solid; }
    .alert-success { background: rgba(42,157,92,0.1); border-color: rgba(42,157,92,0.3); color: #4eca8b; }
    .alert-error   { background: rgba(232,52,26,0.1); border-color: rgba(232,52,26,0.3); color: #f07060; }

    /* TABS */
    .tabs { display: flex; gap: 2px; margin-bottom: 36px; border-bottom: 1px solid var(--border); }
    .tab-btn {
      padding: 12px 28px; background: transparent; border: none;
      font-family: 'Bebas Neue', sans-serif; font-size: 16px; letter-spacing: 0.12em;
      color: var(--muted); cursor: pointer; transition: all 0.2s;
      border-bottom: 2px solid transparent; margin-bottom: -1px;
      text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
    }
    .tab-btn:hover { color: var(--text); }
    .tab-btn.active { color: var(--white); border-bottom-color: var(--red); }
    .tab-btn svg { opacity: 0.7; }
    .tab-btn.active svg { opacity: 1; }

    /* TAB PANELS */
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    /* PAGE HEADER */
    .page-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 36px; }
    .page-eyebrow { font-size: 11px; font-weight: 500; letter-spacing: 0.3em; text-transform: uppercase; color: var(--red); margin-bottom: 8px; }
    .page-title { font-family: 'Bebas Neue', sans-serif; font-size: 48px; color: var(--white); letter-spacing: 0.03em; line-height: 1; }

    /* STATS ROW */
    .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 2px; margin-bottom: 36px; }
    .stat-card { background: var(--card); border: 1px solid var(--border); padding: 28px 32px; }
    .stat-num { font-family: 'Bebas Neue', sans-serif; font-size: 44px; color: var(--white); letter-spacing: 0.04em; line-height: 1; }
    .stat-num.red    { color: var(--red); }
    .stat-num.green  { color: #4eca8b; }
    .stat-num.amber  { color: var(--amber); }
    .stat-label { font-size: 11px; font-weight: 500; letter-spacing: 0.2em; text-transform: uppercase; color: var(--muted); margin-top: 6px; }

    /* TOOLBAR */
    .toolbar { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
    .search-wrap { position: relative; flex: 1; max-width: 380px; }
    .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); pointer-events: none; }
    .search-input { width: 100%; padding: 11px 14px 11px 40px; background: var(--input-bg); border: 1px solid var(--border); border-radius: 5px; color: var(--white); font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; transition: border-color 0.2s; }
    .search-input::placeholder { color: #444; }
    .search-input:focus { border-color: rgba(255,255,255,0.25); }
    .search-btn { padding: 11px 22px; background: var(--red); border: none; border-radius: 5px; color: #fff; font-family: 'Bebas Neue', sans-serif; font-size: 14px; letter-spacing: 0.1em; cursor: pointer; transition: background 0.2s; }
    .search-btn:hover { background: var(--red-dark); }
    .clear-btn { padding: 11px 18px; background: transparent; border: 1px solid var(--border); border-radius: 5px; color: var(--muted); font-family: 'DM Sans', sans-serif; font-size: 13px; cursor: pointer; text-decoration: none; transition: all 0.2s; }
    .clear-btn:hover { border-color: var(--border-hover); color: var(--text); }
    .result-count { font-size: 12px; color: var(--muted); margin-left: auto; }

    /* TABLE */
    .table-wrap { background: var(--card); border: 1px solid var(--border); overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    thead tr { border-bottom: 1px solid var(--border); background: var(--panel); }
    thead th { padding: 14px 20px; text-align: left; font-size: 10px; font-weight: 500; letter-spacing: 0.2em; text-transform: uppercase; color: var(--muted); white-space: nowrap; }
    tbody tr { border-bottom: 1px solid var(--border); transition: background 0.15s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: rgba(255,255,255,0.02); }
    td { padding: 16px 20px; font-size: 13px; vertical-align: middle; }
    .td-id { font-family: 'Bebas Neue', sans-serif; font-size: 15px; color: var(--muted); letter-spacing: 0.05em; }
    .user-cell { display: flex; align-items: center; gap: 12px; }
    .user-initials { width: 36px; height: 36px; border-radius: 50%; background: rgba(232,52,26,0.15); border: 1px solid rgba(232,52,26,0.25); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 500; color: var(--red); flex-shrink: 0; }
    .user-initials.inactive-avatar { background: rgba(102,102,102,0.15); border-color: rgba(102,102,102,0.25); color: var(--muted); }
    .user-name { font-weight: 500; color: var(--white); }
    .user-name.inactive-name { color: var(--muted); text-decoration: line-through; text-decoration-color: rgba(102,102,102,0.5); }
    .user-id-sub { font-size: 11px; color: var(--muted); margin-top: 2px; }
    .td-email { color: var(--muted); }
    .td-date { color: var(--muted); font-size: 12px; white-space: nowrap; }
    .role-badge { display: inline-block; padding: 3px 10px; border-radius: 3px; font-size: 10px; font-weight: 500; letter-spacing: 0.12em; text-transform: uppercase; }
    .role-user  { background: rgba(255,255,255,0.06); color: #aaa; border: 1px solid rgba(255,255,255,0.1); }
    .role-admin { background: rgba(232,52,26,0.12); color: #e8341a; border: 1px solid rgba(232,52,26,0.25); }
    .role-super { background: rgba(42,157,92,0.12); color: #4eca8b; border: 1px solid rgba(42,157,92,0.25); }

    /* STATUS BADGE */
    .status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 500; letter-spacing: 0.1em; text-transform: uppercase; }
    .status-active   { background: rgba(42,157,92,0.12); color: #4eca8b; border: 1px solid rgba(42,157,92,0.25); }
    .status-inactive { background: rgba(102,102,102,0.12); color: #666; border: 1px solid rgba(102,102,102,0.25); }
    .status-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }

    /* TOGGLE BUTTON */
    .toggle-wrap { display: flex; align-items: center; gap: 8px; }
    .toggle-btn {
      padding: 7px 14px; border-radius: 4px;
      font-family: 'DM Sans', sans-serif; font-size: 11px; font-weight: 500;
      letter-spacing: 0.06em; text-transform: uppercase;
      cursor: pointer; transition: all 0.2s; border: 1px solid; white-space: nowrap;
    }
    .toggle-activate   { background: rgba(42,157,92,0.08); border-color: rgba(42,157,92,0.25); color: #4eca8b; }
    .toggle-activate:hover { background: rgba(42,157,92,0.18); border-color: #2a9d5c; }
    .toggle-deactivate { background: rgba(232,52,26,0.08); border-color: rgba(232,52,26,0.25); color: rgba(232,52,26,0.8); }
    .toggle-deactivate:hover { background: rgba(232,52,26,0.15); border-color: var(--red); color: var(--red); }
    .self-badge { font-size: 11px; color: var(--muted); font-style: italic; }

    /* EMPTY */
    .empty-row td { padding: 60px 20px; text-align: center; color: var(--muted); }
    .empty-icon { opacity: 0.15; margin-bottom: 12px; }
    .empty-text { font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 0.1em; color: var(--muted); }

    /* CONFIRM MODAL */
    .modal-overlay { position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.25s; }
    .modal-overlay.open { opacity: 1; pointer-events: all; }
    .modal { background: var(--panel); border: 1px solid var(--border); width: 90%; max-width: 420px; border-radius: 6px; padding: 36px; transform: translateY(16px); transition: transform 0.25s; }
    .modal-overlay.open .modal { transform: translateY(0); }
    .modal-icon { width: 48px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; }
    .modal-icon.deactivate { background: rgba(232,52,26,0.1); border: 1px solid rgba(232,52,26,0.2); }
    .modal-icon.activate   { background: rgba(42,157,92,0.1);  border: 1px solid rgba(42,157,92,0.2); }
    .modal-title { font-family: 'Bebas Neue', sans-serif; font-size: 24px; letter-spacing: 0.06em; color: var(--white); margin-bottom: 8px; }
    .modal-desc { font-size: 13px; font-weight: 300; color: var(--muted); line-height: 1.7; margin-bottom: 28px; }
    .modal-desc strong { color: var(--text); font-weight: 500; }
    .modal-actions { display: flex; gap: 10px; }
    .modal-cancel { flex: 1; padding: 12px; background: transparent; border: 1px solid var(--border); border-radius: 5px; color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 13px; cursor: pointer; transition: all 0.2s; }
    .modal-cancel:hover { border-color: var(--border-hover); background: #1c1c1c; }
    .modal-confirm { flex: 1; padding: 12px; border: none; border-radius: 5px; color: #fff; font-family: 'Bebas Neue', sans-serif; font-size: 15px; letter-spacing: 0.1em; cursor: pointer; transition: background 0.2s; }
    .modal-confirm.deactivate { background: var(--red); }
    .modal-confirm.deactivate:hover { background: var(--red-dark); }
    .modal-confirm.activate   { background: var(--green); }
    .modal-confirm.activate:hover   { background: #228a4e; }

    /* ── ANALYTICS ── */
    .analytics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 2px; margin-bottom: 36px; }
    .a-stat { background: var(--card); border: 1px solid var(--border); padding: 24px 28px; }
    .a-stat-num { font-family: 'Bebas Neue', sans-serif; font-size: 38px; letter-spacing: 0.04em; line-height: 1; }
    .a-stat-label { font-size: 11px; font-weight: 500; letter-spacing: 0.2em; text-transform: uppercase; color: var(--muted); margin-top: 5px; }
    .a-stat-change { font-size: 12px; margin-top: 6px; display: flex; align-items: center; gap: 4px; }
    .a-stat-change.up   { color: #4eca8b; }
    .a-stat-change.down { color: var(--red); }

    .charts-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 2px; margin-bottom: 2px; }
    .charts-grid-2 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2px; margin-bottom: 36px; }
    .chart-card { background: var(--card); border: 1px solid var(--border); padding: 28px; }
    .chart-title { font-family: 'Bebas Neue', sans-serif; font-size: 18px; letter-spacing: 0.08em; color: var(--white); margin-bottom: 4px; }
    .chart-sub { font-size: 12px; color: var(--muted); margin-bottom: 22px; }
    .chart-wrap { position: relative; width: 100%; }

    /* TOP BRANDS TABLE */
    .brand-row { display: flex; align-items: center; justify-content: space-between; padding: 11px 0; border-bottom: 1px solid var(--border); }
    .brand-row:last-child { border-bottom: none; }
    .brand-name { font-size: 13px; font-weight: 500; color: var(--text); }
    .brand-count { font-family: 'Bebas Neue', sans-serif; font-size: 18px; color: var(--white); letter-spacing: 0.05em; }
    .brand-bar-wrap { flex: 1; margin: 0 16px; height: 4px; background: rgba(255,255,255,0.06); border-radius: 2px; overflow: hidden; }
    .brand-bar { height: 100%; background: var(--red); border-radius: 2px; }
    .brand-pct { font-size: 11px; color: var(--muted); min-width: 36px; text-align: right; }

    /* RECENT ACTIVITY */
    .activity-item { display: flex; align-items: center; gap: 14px; padding: 12px 0; border-bottom: 1px solid var(--border); }
    .activity-item:last-child { border-bottom: none; }
    .activity-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .activity-dot.green { background: #4eca8b; }
    .activity-dot.red   { background: var(--red); }
    .activity-dot.amber { background: var(--amber); }
    .activity-text { font-size: 12px; color: var(--text); flex: 1; line-height: 1.4; }
    .activity-time { font-size: 11px; color: var(--muted); white-space: nowrap; }

    @media (max-width: 1024px) {
      .analytics-grid { grid-template-columns: repeat(2,1fr); }
      .charts-grid { grid-template-columns: 1fr; }
      .charts-grid-2 { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
      nav { padding: 0 20px; }
      .main { padding: 88px 20px 40px; }
      .stats-row { grid-template-columns: 1fr 1fr; }
      .analytics-grid { grid-template-columns: 1fr 1fr; }
      .page-header { flex-direction: column; align-items: flex-start; gap: 16px; }
      table { display: block; overflow-x: auto; }
    }
  </style>
</head>
<body>

<!-- NAV -->
<nav>
  <a href="#" class="nav-logo">
    <div class="logo-mark"><span>⬡</span></div>
    <span class="logo-name">DriveHub</span>
  </a>
  <div class="nav-center">
    <span class="nav-title"><span>SUPER</span> ADMIN</span>
    <span class="nav-badge">Control Panel</span>
  </div>
  <div class="nav-right">
    <a href="logout.php" class="nav-logout-btn">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      Logout
    </a>
    <a href="#" class="nav-profile">
      <div class="nav-avatar"><?php echo strtoupper(mb_substr($_SESSION['first_name'], 0, 1)); ?></div>
      <span class="nav-profile-name"><?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
    </a>
  </div>
</nav>

<!-- MAIN -->
<div class="main">

  <?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <!-- PAGE HEADER -->
  <div class="page-header">
    <div>
      <p class="page-eyebrow">Super Admin Panel</p>
      <h1 class="page-title">DRIVEHUB CONTROL CENTER</h1>
    </div>
  </div>

  <!-- TABS -->
  <div class="tabs">
    <a href="?tab=users<?php echo $search ? '&search='.urlencode($search) : ''; ?>"
       class="tab-btn <?php echo ($tab !== 'analytics') ? 'active' : ''; ?>">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
      </svg>
      User Management
    </a>
    <a href="?tab=analytics" class="tab-btn <?php echo ($tab === 'analytics') ? 'active' : ''; ?>">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/>
        <line x1="6" y1="20" x2="6" y2="14"/>
      </svg>
      Market Analytics
    </a>
  </div>

  <!-- ══════════ USER MANAGEMENT TAB ══════════ -->
  <div class="tab-panel <?php echo ($tab !== 'analytics') ? 'active' : ''; ?>">

    <!-- STATS -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-num"><?php echo $total_all; ?></div>
        <div class="stat-label">Total Users</div>
      </div>
      <div class="stat-card">
        <div class="stat-num red"><?php echo $total_reg; ?></div>
        <div class="stat-label">Registered Users</div>
      </div>
      <div class="stat-card">
        <div class="stat-num green"><?php echo $total_active; ?></div>
        <div class="stat-label">Active Accounts</div>
      </div>
      <div class="stat-card">
        <div class="stat-num amber"><?php echo $total_inactive; ?></div>
        <div class="stat-label">Inactive Accounts</div>
      </div>
    </div>

    <!-- TOOLBAR -->
    <form method="GET" action="">
      <input type="hidden" name="tab" value="users"/>
      <div class="toolbar">
        <div class="search-wrap">
          <svg class="search-icon" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input class="search-input" type="text" name="search" placeholder="Search by name or email…" value="<?php echo htmlspecialchars($search); ?>"/>
        </div>
        <button class="search-btn" type="submit">SEARCH</button>
        <?php if ($search): ?>
          <a href="superadmin.php?tab=users" class="clear-btn">Clear</a>
        <?php endif; ?>
        <span class="result-count"><?php echo $total_users; ?> user<?php echo $total_users !== 1 ? 's' : ''; ?> found</span>
      </div>
    </form>

    <!-- TABLE -->
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>User</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Joined</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
            <tr class="empty-row">
              <td colspan="7">
                <div class="empty-icon">
                  <svg width="48" height="48" fill="none" stroke="white" stroke-width="1.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="empty-text">No Users Found</div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($users as $u):
              $initials  = strtoupper(mb_substr($u['first_name'], 0, 1) . mb_substr($u['last_name'], 0, 1));
              $emailL    = strtolower($u['email']);
              $is_active = (int)$u['is_active'];
              if ($emailL === 'superadmin@drivehub.lb') {
                  $role_class = 'role-super'; $role_label = 'Superadmin';
              } elseif (in_array($emailL, ['admin@drivehub.lb','admin2@drivehub.lb'])) {
                  $role_class = 'role-admin'; $role_label = 'Admin';
              } else {
                  $role_class = 'role-user'; $role_label = 'User';
              }
              $is_self = ($u['id'] === (int)$_SESSION['user_id']);
              $joined  = date('M j, Y', strtotime($u['created_at']));
            ?>
            <tr>
              <td class="td-id"><?php echo $u['id']; ?></td>
              <td>
                <div class="user-cell">
                  <div class="user-initials <?php echo !$is_active ? 'inactive-avatar' : ''; ?>"><?php echo htmlspecialchars($initials); ?></div>
                  <div>
                    <div class="user-name <?php echo !$is_active ? 'inactive-name' : ''; ?>"><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></div>
                    <div class="user-id-sub">ID #<?php echo $u['id']; ?></div>
                  </div>
                </div>
              </td>
              <td class="td-email"><?php echo htmlspecialchars($u['email']); ?></td>
              <td><span class="role-badge <?php echo $role_class; ?>"><?php echo $role_label; ?></span></td>
              <td>
                <span class="status-badge <?php echo $is_active ? 'status-active' : 'status-inactive'; ?>">
                  <span class="status-dot"></span>
                  <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                </span>
              </td>
              <td class="td-date"><?php echo $joined; ?></td>
              <td>
                <?php if ($is_self): ?>
                  <span class="self-badge">You</span>
                <?php else: ?>
                  <div class="toggle-wrap">
                    <?php if ($is_active): ?>
                      <button class="toggle-btn toggle-deactivate"
                        onclick="confirmToggle(<?php echo $u['id']; ?>, 0, '<?php echo htmlspecialchars(addslashes($u['first_name'] . ' ' . $u['last_name'])); ?>')">
                        Deactivate
                      </button>
                    <?php else: ?>
                      <button class="toggle-btn toggle-activate"
                        onclick="confirmToggle(<?php echo $u['id']; ?>, 1, '<?php echo htmlspecialchars(addslashes($u['first_name'] . ' ' . $u['last_name'])); ?>')">
                        Activate
                      </button>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div><!-- /users tab -->

  <!-- ══════════ ANALYTICS TAB ══════════ -->
  <div class="tab-panel <?php echo ($tab === 'analytics') ? 'active' : ''; ?>">

    <div class="page-header" style="margin-bottom:28px;">
      <div>
        <p class="page-eyebrow">Live Market Intelligence</p>
        <h1 class="page-title" style="font-size:38px;">MARKETPLACE ANALYTICS</h1>
      </div>
      <div style="font-size:12px;color:var(--muted);">Last updated: <?php echo date('M j, Y — H:i'); ?></div>
    </div>

    <!-- KPI STATS -->
    <div class="analytics-grid">
      <div class="a-stat">
        <div class="a-stat-num" style="color:var(--white);">48,312</div>
        <div class="a-stat-label">Active Listings</div>
        <div class="a-stat-change up">▲ 6.4% vs last month</div>
      </div>
      <div class="a-stat">
        <div class="a-stat-num" style="color:var(--red);">$24,850</div>
        <div class="a-stat-label">Avg. Listing Price</div>
        <div class="a-stat-change up">▲ 2.1% vs last month</div>
      </div>
      <div class="a-stat">
        <div class="a-stat-num" style="color:#4eca8b;">3,741</div>
        <div class="a-stat-label">Sales This Month</div>
        <div class="a-stat-change up">▲ 11.8% vs last month</div>
      </div>
      <div class="a-stat">
        <div class="a-stat-num" style="color:var(--amber);">18.4</div>
        <div class="a-stat-label">Avg. Days to Sell</div>
        <div class="a-stat-change down">▼ 3.2 days faster</div>
      </div>
    </div>

    <!-- CHARTS ROW 1 -->
    <div class="charts-grid" style="margin-bottom:2px;">

      <!-- Revenue / Sales Line Chart -->
      <div class="chart-card">
        <div class="chart-title">Monthly Sales Volume & Revenue</div>
        <div class="chart-sub">Units sold and gross revenue — past 12 months</div>
        <div class="chart-wrap" style="height:260px;">
          <canvas id="salesChart"></canvas>
        </div>
      </div>

      <!-- Brand Donut -->
      <div class="chart-card">
        <div class="chart-title">Listings by Brand</div>
        <div class="chart-sub">Top 6 makes by listing share</div>
        <div class="chart-wrap" style="height:260px; display:flex; align-items:center; justify-content:center;">
          <canvas id="brandDonut"></canvas>
        </div>
      </div>

    </div>

    <!-- CHARTS ROW 2 -->
    <div class="charts-grid-2">

      <!-- Price Range Bar -->
      <div class="chart-card">
        <div class="chart-title">Price Range Distribution</div>
        <div class="chart-sub">Listings grouped by sale price bracket</div>
        <div class="chart-wrap" style="height:220px;">
          <canvas id="priceBar"></canvas>
        </div>
      </div>

      <!-- Body Type Bar -->
      <div class="chart-card">
        <div class="chart-title">Vehicle Body Types</div>
        <div class="chart-sub">Share of listings by category</div>
        <div class="chart-wrap" style="height:220px;">
          <canvas id="bodyBar"></canvas>
        </div>
      </div>

      <!-- User Signups Trend -->
      <div class="chart-card">
        <div class="chart-title">New User Registrations</div>
        <div class="chart-sub">Weekly signups — last 8 weeks</div>
        <div class="chart-wrap" style="height:220px;">
          <canvas id="signupChart"></canvas>
        </div>
      </div>

    </div>

    <!-- BOTTOM ROW -->
    <div class="charts-grid">

      <!-- Top Brands Table -->
      <div class="chart-card">
        <div class="chart-title">Top Performing Brands</div>
        <div class="chart-sub">Ranked by number of completed sales this month</div>
        <?php
          $brands = [
            ['Toyota',    847, 22.7],
            ['BMW',       631, 16.9],
            ['Mercedes',  589, 15.8],
            ['Kia',       412, 11.0],
            ['Hyundai',   378, 10.1],
            ['Ford',      341,  9.1],
            ['Honda',     288,  7.7],
            ['Nissan',    255,  6.8],
          ];
          $max_brand = $brands[0][1];
        ?>
        <?php foreach ($brands as $b): ?>
        <div class="brand-row">
          <span class="brand-name"><?php echo $b[0]; ?></span>
          <div class="brand-bar-wrap">
            <div class="brand-bar" style="width:<?php echo round($b[1]/$max_brand*100); ?>%"></div>
          </div>
          <span class="brand-count"><?php echo $b[1]; ?></span>
          <span class="brand-pct"><?php echo $b[2]; ?>%</span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Recent Activity Feed -->
      <div class="chart-card">
        <div class="chart-title">Recent Activity</div>
        <div class="chart-sub">Latest platform events</div>
        <?php
          $activities = [
            ['green', '2 min ago',  'New listing posted: 2022 BMW 5 Series — $38,500'],
            ['green', '7 min ago',  'User registration: Ahmad K. joined DriveHub'],
            ['red',   '14 min ago', 'Listing removed: flagged for misrepresentation'],
            ['amber', '21 min ago', 'Price update: Toyota Camry 2021 → $19,800'],
            ['green', '34 min ago', 'Sale completed: Mercedes C200 for $27,200'],
            ['green', '41 min ago', 'New listing: 2023 Kia Sportage — $24,999'],
            ['amber', '53 min ago', 'Inquiry submitted on 2020 Honda Civic'],
            ['red',   '1 hr ago',   'User account deactivated by admin'],
            ['green', '1 hr ago',   'Sale completed: Ford Mustang GT — $42,000'],
            ['amber', '2 hr ago',   'Listing boosted: Hyundai Tucson 2022'],
          ];
        ?>
        <?php foreach ($activities as $a): ?>
        <div class="activity-item">
          <span class="activity-dot <?php echo $a[0]; ?>"></span>
          <span class="activity-text"><?php echo $a[2]; ?></span>
          <span class="activity-time"><?php echo $a[1]; ?></span>
        </div>
        <?php endforeach; ?>
      </div>

    </div>

  </div><!-- /analytics tab -->

</div><!-- /main -->

<!-- CONFIRM TOGGLE MODAL -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <div class="modal-icon" id="modalIcon">
      <svg id="modalSvg" width="22" height="22" fill="none" stroke="#e8341a" stroke-width="2" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
    </div>
    <div class="modal-title" id="modalTitle">DEACTIVATE USER</div>
    <div class="modal-desc" id="modalDesc">You are about to deactivate <strong id="modal-user-name"></strong>. They will no longer be able to log in, but their account will remain in the database.</div>
    <div class="modal-actions">
      <button class="modal-cancel" onclick="closeModal()">Cancel</button>
      <form method="POST" style="flex:1">
        <input type="hidden" name="toggle_user_id" id="modal-user-id"/>
        <input type="hidden" name="toggle_status"  id="modal-status"/>
        <button class="modal-confirm" id="modalConfirmBtn" type="submit" style="width:100%">CONFIRM</button>
      </form>
    </div>
  </div>
</div>

<script>
  function confirmToggle(id, newStatus, name) {
    document.getElementById('modal-user-id').value = id;
    document.getElementById('modal-status').value  = newStatus;
    document.getElementById('modal-user-name').textContent = name;
    const icon = document.getElementById('modalIcon');
    const title = document.getElementById('modalTitle');
    const desc  = document.getElementById('modalDesc');
    const btn   = document.getElementById('modalConfirmBtn');
    const svg   = document.getElementById('modalSvg');
    if (newStatus === 0) {
      icon.className  = 'modal-icon deactivate';
      title.textContent = 'DEACTIVATE USER';
      desc.innerHTML  = `You are about to deactivate <strong>${name}</strong>. They will no longer be able to log in, but their account is kept in the database.`;
      btn.className   = 'modal-confirm deactivate';
      btn.textContent = 'DEACTIVATE';
      svg.setAttribute('stroke','#e8341a');
    } else {
      icon.className  = 'modal-icon activate';
      title.textContent = 'ACTIVATE USER';
      desc.innerHTML  = `You are about to re-activate <strong>${name}</strong>. They will be able to log in again immediately.`;
      btn.className   = 'modal-confirm activate';
      btn.textContent = 'ACTIVATE';
      svg.setAttribute('stroke','#4eca8b');
    }
    document.getElementById('modalOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeModal() {
    document.getElementById('modalOverlay').classList.remove('open');
    document.body.style.overflow = '';
  }
  document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

  /* ── CHARTS ── */
  const chartDefaults = {
    color: '#e8e8e8',
    borderColor: 'rgba(255,255,255,0.08)',
    font: { family: "'DM Sans', sans-serif" }
  };
  Chart.defaults.color = '#666';
  Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
  Chart.defaults.font.family = "'DM Sans', sans-serif";

  const months = ['May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar','Apr'];

  /* Sales Line Chart */
  new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
      labels: months,
      datasets: [
        {
          label: 'Units Sold',
          data: [2100,2340,2580,2750,3100,2980,3200,3410,3050,3280,3600,3741],
          borderColor: '#e8341a',
          backgroundColor: 'rgba(232,52,26,0.08)',
          fill: true,
          tension: 0.4,
          pointRadius: 3,
          pointBackgroundColor: '#e8341a',
          yAxisID: 'y'
        },
        {
          label: 'Revenue ($K)',
          data: [52,58,64,69,77,74,80,85,76,82,90,93],
          borderColor: '#4eca8b',
          backgroundColor: 'rgba(78,202,139,0.05)',
          fill: true,
          tension: 0.4,
          pointRadius: 3,
          pointBackgroundColor: '#4eca8b',
          yAxisID: 'y1'
        }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: { legend: { labels: { color: '#666', font: { size: 11 } } } },
      scales: {
        x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#555', font: { size: 11 } } },
        y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#555', font: { size: 11 } }, position: 'left' },
        y1: { grid: { display: false }, ticks: { color: '#555', font: { size: 11 } }, position: 'right' }
      }
    }
  });

  /* Brand Donut */
  new Chart(document.getElementById('brandDonut'), {
    type: 'doughnut',
    data: {
      labels: ['Toyota','BMW','Mercedes','Kia','Hyundai','Other'],
      datasets: [{
        data: [22.7,16.9,15.8,11.0,10.1,23.5],
        backgroundColor: ['#e8341a','#c0290e','#4eca8b','#f59e0b','#3b82f6','#333'],
        borderColor: '#1a1a1a',
        borderWidth: 3,
        hoverOffset: 6
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      cutout: '68%',
      plugins: {
        legend: { position: 'bottom', labels: { color: '#666', font: { size: 11 }, padding: 14, boxWidth: 12 } }
      }
    }
  });

  /* Price Range Bar */
  new Chart(document.getElementById('priceBar'), {
    type: 'bar',
    data: {
      labels: ['<$5K','$5-10K','$10-20K','$20-35K','$35-60K','$60K+'],
      datasets: [{
        label: 'Listings',
        data: [3200, 7800, 14200, 11600, 7400, 4112],
        backgroundColor: ['rgba(232,52,26,0.7)','rgba(232,52,26,0.6)','rgba(232,52,26,0.85)','rgba(232,52,26,0.6)','rgba(232,52,26,0.45)','rgba(232,52,26,0.3)'],
        borderRadius: 3,
        borderSkipped: false
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#555', font: { size: 10 } } },
        y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#555', font: { size: 10 } } }
      }
    }
  });

  /* Body Type Bar */
  new Chart(document.getElementById('bodyBar'), {
    type: 'bar',
    data: {
      labels: ['SUV','Sedan','Hatchback','Pickup','Coupe','Van'],
      datasets: [{
        label: 'Share %',
        data: [34, 27, 16, 11, 8, 4],
        backgroundColor: '#4eca8b',
        borderRadius: 3,
        borderSkipped: false
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      indexAxis: 'y',
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#555', font: { size: 10 }, callback: v => v+'%' } },
        y: { grid: { display: false }, ticks: { color: '#888', font: { size: 11 } } }
      }
    }
  });

  /* Signup Trend */
  new Chart(document.getElementById('signupChart'), {
    type: 'line',
    data: {
      labels: ['W1','W2','W3','W4','W5','W6','W7','W8'],
      datasets: [{
        label: 'New Users',
        data: [142, 168, 155, 201, 187, 234, 218, 261],
        borderColor: '#f59e0b',
        backgroundColor: 'rgba(245,158,11,0.08)',
        fill: true,
        tension: 0.45,
        pointRadius: 4,
        pointBackgroundColor: '#f59e0b'
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#555', font: { size: 10 } } },
        y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#555', font: { size: 10 } } }
      }
    }
  });
</script>
</body>
</html>