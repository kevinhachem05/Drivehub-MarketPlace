<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: login.html");
    exit();
}

include 'db.php';

$success = '';
$error   = '';

/* ── DELETE USER ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $del_id = (int) $_POST['delete_user_id'];
    // Prevent superadmin from deleting themselves
    if ($del_id === (int) $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) {
            $success = "User removed successfully.";
        } else {
            $error = "Failed to remove user: " . $conn->error;
        }
        $stmt->close();
    }
}

/* ── SEARCH ── */
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $like = '%' . $conn->real_escape_string($search) . '%';
    $result = $conn->query("SELECT id, first_name, last_name, email, created_at FROM users WHERE first_name LIKE '$like' OR last_name LIKE '$like' OR email LIKE '$like' ORDER BY created_at DESC");
} else {
    $result = $conn->query("SELECT id, first_name, last_name, email, created_at FROM users ORDER BY created_at DESC");
}

$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) $users[] = $row;
}

$total_users = count($users);
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>DriveHub — Super Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
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
    .btn-outline { padding: 9px 20px; background: transparent; border: 1px solid var(--border); border-radius: 5px; color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 13px; cursor: pointer; transition: border-color 0.2s, background 0.2s; text-decoration: none; }
    .btn-outline:hover { border-color: var(--border-hover); background: #1c1c1c; }
    .nav-profile { display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 5px 10px; border-radius: 6px; transition: background 0.2s; text-decoration: none; }
    .nav-profile:hover { background: rgba(255,255,255,0.06); }
    .nav-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--red); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 500; color: #fff; flex-shrink: 0; }
    .nav-profile-name { font-size: 13px; font-weight: 500; color: var(--text); }

    /* MAIN */
    .main { padding: 100px 56px 60px; }

    /* ALERT */
    .alert { padding: 13px 18px; border-radius: 5px; font-size: 13px; margin-bottom: 24px; border: 1px solid; }
    .alert-success { background: rgba(42,157,92,0.1); border-color: rgba(42,157,92,0.3); color: #4eca8b; }
    .alert-error   { background: rgba(232,52,26,0.1); border-color: rgba(232,52,26,0.3); color: #f07060; }

    /* PAGE HEADER */
    .page-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 36px; }
    .page-eyebrow { font-size: 11px; font-weight: 500; letter-spacing: 0.3em; text-transform: uppercase; color: var(--red); margin-bottom: 8px; }
    .page-title { font-family: 'Bebas Neue', sans-serif; font-size: 48px; color: var(--white); letter-spacing: 0.03em; line-height: 1; }

    /* STATS ROW */
    .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2px; margin-bottom: 36px; }
    .stat-card { background: var(--card); border: 1px solid var(--border); padding: 28px 32px; }
    .stat-num { font-family: 'Bebas Neue', sans-serif; font-size: 44px; color: var(--white); letter-spacing: 0.04em; line-height: 1; }
    .stat-num.red { color: var(--red); }
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
    .user-name { font-weight: 500; color: var(--white); }
    .user-id-sub { font-size: 11px; color: var(--muted); margin-top: 2px; }
    .td-email { color: var(--muted); }
    .td-date { color: var(--muted); font-size: 12px; white-space: nowrap; }
    .role-badge { display: inline-block; padding: 3px 10px; border-radius: 3px; font-size: 10px; font-weight: 500; letter-spacing: 0.12em; text-transform: uppercase; }
    .role-user  { background: rgba(255,255,255,0.06); color: #aaa; border: 1px solid rgba(255,255,255,0.1); }
    .role-admin { background: rgba(232,52,26,0.12); color: #e8341a; border: 1px solid rgba(232,52,26,0.25); }
    .role-super { background: rgba(42,157,92,0.12); color: #4eca8b; border: 1px solid rgba(42,157,92,0.25); }
    .delete-btn { padding: 7px 16px; background: transparent; border: 1px solid rgba(232,52,26,0.25); border-radius: 4px; color: rgba(232,52,26,0.7); font-family: 'DM Sans', sans-serif; font-size: 12px; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
    .delete-btn:hover { background: rgba(232,52,26,0.1); border-color: var(--red); color: var(--red); }
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
    .modal-icon { width: 48px; height: 48px; background: rgba(232,52,26,0.1); border: 1px solid rgba(232,52,26,0.2); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; }
    .modal-title { font-family: 'Bebas Neue', sans-serif; font-size: 24px; letter-spacing: 0.06em; color: var(--white); margin-bottom: 8px; }
    .modal-desc { font-size: 13px; font-weight: 300; color: var(--muted); line-height: 1.7; margin-bottom: 28px; }
    .modal-desc strong { color: var(--text); font-weight: 500; }
    .modal-actions { display: flex; gap: 10px; }
    .modal-cancel { flex: 1; padding: 12px; background: transparent; border: 1px solid var(--border); border-radius: 5px; color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 13px; cursor: pointer; transition: all 0.2s; }
    .modal-cancel:hover { border-color: var(--border-hover); background: #1c1c1c; }
    .modal-confirm { flex: 1; padding: 12px; background: var(--red); border: none; border-radius: 5px; color: #fff; font-family: 'Bebas Neue', sans-serif; font-size: 15px; letter-spacing: 0.1em; cursor: pointer; transition: background 0.2s; }
    .modal-confirm:hover { background: var(--red-dark); }

    @media (max-width: 768px) {
      nav { padding: 0 20px; }
      .main { padding: 88px 20px 40px; }
      .stats-row { grid-template-columns: 1fr; }
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
    <span class="nav-badge">User Management</span>
  </div>
  <div class="nav-right">
    <a href="logout.php" class="btn-outline">Sign Out</a>
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
      <h1 class="page-title">USER MANAGEMENT</h1>
    </div>
  </div>

  <!-- STATS -->
  <?php
    $total_res = $conn->query("SELECT COUNT(*) as c FROM users");
    $total_all = $total_res ? $total_res->fetch_assoc()['c'] : 0;

    $superadmin_emails_list = "'superadmin@drivehub.lb'";
    $admin_emails_list      = "'admin@drivehub.lb','admin2@drivehub.lb'";
    $reg_res = $conn->query("SELECT COUNT(*) as c FROM users WHERE email NOT IN ($superadmin_emails_list, $admin_emails_list)");
    $total_reg = $reg_res ? $reg_res->fetch_assoc()['c'] : 0;
  ?>
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
      <div class="stat-num"><?php echo $total_all - $total_reg; ?></div>
      <div class="stat-label">Admin Accounts</div>
    </div>
  </div>

  <!-- TOOLBAR -->
  <form method="GET" action="">
    <div class="toolbar">
      <div class="search-wrap">
        <svg class="search-icon" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input class="search-input" type="text" name="search" placeholder="Search by name or email…" value="<?php echo htmlspecialchars($search); ?>"/>
      </div>
      <button class="search-btn" type="submit">SEARCH</button>
      <?php if ($search): ?>
        <a href="superadmin.php" class="clear-btn">Clear</a>
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
          <th>Joined</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr class="empty-row">
            <td colspan="6">
              <div class="empty-icon">
                <svg width="48" height="48" fill="none" stroke="white" stroke-width="1.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              </div>
              <div class="empty-text">No Users Found</div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($users as $u):
            $initials = strtoupper(mb_substr($u['first_name'], 0, 1) . mb_substr($u['last_name'], 0, 1));
            $emailL   = strtolower($u['email']);
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
                <div class="user-initials"><?php echo htmlspecialchars($initials); ?></div>
                <div>
                  <div class="user-name"><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></div>
                  <div class="user-id-sub">ID #<?php echo $u['id']; ?></div>
                </div>
              </div>
            </td>
            <td class="td-email"><?php echo htmlspecialchars($u['email']); ?></td>
            <td><span class="role-badge <?php echo $role_class; ?>"><?php echo $role_label; ?></span></td>
            <td class="td-date"><?php echo $joined; ?></td>
            <td>
              <?php if ($is_self): ?>
                <span class="self-badge">You</span>
              <?php else: ?>
                <button class="delete-btn" onclick="confirmDelete(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['first_name'] . ' ' . $u['last_name'])); ?>')">Remove</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div><!-- /main -->

<!-- CONFIRM DELETE MODAL -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <div class="modal-icon">
      <svg width="22" height="22" fill="none" stroke="#e8341a" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
    </div>
    <div class="modal-title">REMOVE USER</div>
    <div class="modal-desc">You are about to permanently remove <strong id="modal-user-name"></strong> from the database. This action cannot be undone.</div>
    <div class="modal-actions">
      <button class="modal-cancel" onclick="closeModal()">Cancel</button>
      <form method="POST" style="flex:1">
        <input type="hidden" name="delete_user_id" id="modal-user-id"/>
        <button class="modal-confirm" type="submit" style="width:100%">REMOVE</button>
      </form>
    </div>
  </div>
</div>

<script>
  function confirmDelete(id, name) {
    document.getElementById('modal-user-id').value   = id;
    document.getElementById('modal-user-name').textContent = name;
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
</script>
</body>
</html>