<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

include 'db.php';

$success_msg = '';
$error_msg   = '';

/* ── DELETE CAR ── */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("SELECT image_path FROM cars WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($r && $r['image_path'] && file_exists($r['image_path'])) unlink($r['image_path']);
    $stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin.php?deleted=1");
    exit();
}

if (isset($_GET['deleted'])) $success_msg = 'Car removed successfully.';

/* ── TOGGLE FEATURED ── */
if (isset($_GET['feature']) && is_numeric($_GET['feature'])) {
    $fid = (int)$_GET['feature'];

    // Count how many are currently featured
    $count_res = $conn->query("SELECT COUNT(*) as cnt FROM cars WHERE featured = 1");
    $count_row = $count_res->fetch_assoc();
    $featured_count = (int)$count_row['cnt'];

    // Check if this car is already featured
    $chk = $conn->prepare("SELECT featured FROM cars WHERE id = ?");
    $chk->bind_param("i", $fid);
    $chk->execute();
    $chk_row = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($chk_row['featured'] == 1) {
        // Unfeature it
        $stmt = $conn->prepare("UPDATE cars SET featured = 0 WHERE id = ?");
        $stmt->bind_param("i", $fid);
        $stmt->execute();
        $stmt->close();
        header("Location: admin.php?msg=unfeatured");
    } elseif ($featured_count >= 3) {
        // Already 3 featured — block
        header("Location: admin.php?msg=maxfeatured");
    } else {
        // Feature it
        $stmt = $conn->prepare("UPDATE cars SET featured = 1 WHERE id = ?");
        $stmt->bind_param("i", $fid);
        $stmt->execute();
        $stmt->close();
        header("Location: admin.php?msg=featured");
    }
    exit();
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'featured')    $success_msg = 'Car added to Featured This Week.';
    if ($_GET['msg'] === 'unfeatured')  $success_msg = 'Car removed from Featured This Week.';
    if ($_GET['msg'] === 'maxfeatured') $error_msg   = 'You already have 3 featured cars. Remove one before adding another.';
}

/* ── ADD CAR ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_car') {

    $car_name     = trim($_POST['car_name']);
    $brand        = trim($_POST['brand']);
    $category     = trim($_POST['category']);
    $year         = (int)$_POST['year'];
    $price        = trim($_POST['price']);
    $kms          = (int)$_POST['kms'];
    $transmission = trim($_POST['transmission']);
    $engine       = trim($_POST['engine']);
    $power        = trim($_POST['power']);
    $drive        = trim($_POST['drive']);
    $seats        = (int)$_POST['seats'];
    $description  = trim($_POST['description']);
    $location     = trim($_POST['location']);
    $badge        = trim($_POST['badge']);

    $image_path = '';
    if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/cars/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext     = strtolower(pathinfo($_FILES['car_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if (in_array($ext, $allowed)) {
            $filename   = uniqid('car_', true) . '.' . $ext;
            $image_path = $upload_dir . $filename;
            move_uploaded_file($_FILES['car_image']['tmp_name'], $image_path);
        } else {
            $error_msg = 'Invalid image format. Use JPG, PNG, or WEBP.';
        }
    }

    if (!$error_msg) {
        $conn->query("CREATE TABLE IF NOT EXISTS cars (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            car_name     VARCHAR(150) NOT NULL,
            brand        VARCHAR(100) NOT NULL,
            category     VARCHAR(80)  NOT NULL,
            year         YEAR         NOT NULL,
            price        VARCHAR(50)  NOT NULL,
            kms          INT          DEFAULT 0,
            transmission VARCHAR(50)  NOT NULL,
            engine       VARCHAR(80)  DEFAULT '',
            power        VARCHAR(50)  DEFAULT '',
            drive        VARCHAR(30)  DEFAULT '',
            seats        INT          DEFAULT 5,
            description  TEXT,
            location     VARCHAR(200) DEFAULT '',
            badge        VARCHAR(20)  DEFAULT '',
            image_path   VARCHAR(300) DEFAULT '',
            featured     TINYINT(1)   DEFAULT 0,
            created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
        )");

        $sql2 = "INSERT INTO cars (car_name,brand,category,year,price,kms,transmission,engine,power,drive,seats,description,location,badge,image_path)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("ssssssssssissss",
            $car_name,$brand,$category,$year,$price,$kms,
            $transmission,$engine,$power,$drive,$seats,
            $description,$location,$badge,$image_path
        );
        if ($stmt2->execute()) {
            $success_msg = 'Car listed successfully!';
        } else {
            $error_msg = 'Database error: ' . $stmt2->error;
        }
        $stmt2->close();
    }
}

/* ── ENSURE featured COLUMN EXISTS ── */
$conn->query("ALTER TABLE cars ADD COLUMN IF NOT EXISTS featured TINYINT(1) DEFAULT 0");

/* ── FETCH ALL CARS ── */
$cars_result = $conn->query("SELECT * FROM cars ORDER BY created_at DESC");
$all_cars    = [];
while ($row = $cars_result->fetch_assoc()) $all_cars[] = $row;

$total_cars     = count($all_cars);
$featured_count = count(array_filter($all_cars, fn($c) => $c['featured'] == 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>DriveHub — Admin Panel</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --black:    #0a0a0a;
      --off-black:#111111;
      --panel:    #161616;
      --card:     #1a1a1a;
      --border:   rgba(255,255,255,0.08);
      --border-h: rgba(255,255,255,0.18);
      --muted:    #666;
      --text:     #e8e8e8;
      --white:    #ffffff;
      --red:      #e8341a;
      --red-dark: #c0290e;
      --green:    #2a9d5c;
      --orange:   #e8891a;
      --input-bg: #1c1c1c;
      --sidebar:  140px;
    }
    html { scroll-behavior: smooth; }
    body { background: var(--black); color: var(--text); font-family: 'DM Sans', sans-serif; overflow-x: hidden; display: flex; }
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: var(--off-black); }
    ::-webkit-scrollbar-thumb { background: #2a2a2a; border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--red); }

    /* ── SIDEBAR ── */
    .sidebar { position: fixed; top: 0; left: 0; bottom: 0; width: var(--sidebar); background: var(--panel); border-right: 1px solid var(--border); display: flex; flex-direction: column; align-items: center; padding: 0 0 24px; z-index: 100; }
    .sidebar-logo { width: 100%; padding: 22px 0 20px; display: flex; flex-direction: column; align-items: center; gap: 6px; border-bottom: 1px solid var(--border); margin-bottom: 20px; }
    .logo-mark { width: 36px; height: 36px; background: var(--red); clip-path: polygon(0 0,100% 0,100% 65%,50% 100%,0 65%); display: flex; align-items: center; justify-content: center; }
    .logo-mark span { font-size: 14px; color:#fff; font-weight:700; margin-bottom:5px; }
    .logo-name { font-family: 'Bebas Neue', sans-serif; font-size: 16px; letter-spacing: 0.12em; color: var(--white); }
    .logo-badge { font-size: 8px; letter-spacing: 0.25em; text-transform: uppercase; color: var(--red); background: rgba(232,52,26,0.12); padding: 2px 8px; border-radius: 10px; }
    .sidebar-nav { width: 100%; flex: 1; display: flex; flex-direction: column; gap: 4px; padding: 0 10px; }
    .nav-item { display: flex; flex-direction: column; align-items: center; gap: 5px; padding: 12px 8px; border-radius: 8px; color: var(--muted); cursor: pointer; transition: all 0.2s; font-size: 9px; letter-spacing: 0.12em; text-transform: uppercase; border: 1px solid transparent; text-decoration: none; }
    .nav-item:hover { background: #1e1e1e; color: var(--text); border-color: var(--border); }
    .nav-item.active { background: rgba(232,52,26,0.12); color: var(--red); border-color: rgba(232,52,26,0.25); }
    .sidebar-bottom { width: 100%; padding: 0 10px; display: flex; flex-direction: column; gap: 4px; }
    .logout-btn { display: flex; flex-direction: column; align-items: center; gap: 5px; padding: 12px 8px; border-radius: 8px; color: var(--muted); cursor: pointer; font-size: 9px; letter-spacing: 0.12em; text-transform: uppercase; background: transparent; border: 1px solid transparent; font-family: 'DM Sans', sans-serif; transition: all 0.2s; width: 100%; text-decoration: none; }
    .logout-btn:hover { background: rgba(232,52,26,0.08); color: var(--red); border-color: rgba(232,52,26,0.2); }

    /* ── MAIN ── */
    .main { margin-left: var(--sidebar); flex: 1; min-height: 100vh; display: flex; flex-direction: column; }
    .topbar { position: sticky; top: 0; z-index: 50; height: 64px; background: rgba(10,10,10,0.92); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 40px; }
    .topbar-title { font-family: 'Bebas Neue', sans-serif; font-size: 22px; letter-spacing: 0.08em; color: var(--white); }
    .topbar-title span { color: var(--red); }
    .topbar-right { display: flex; align-items: center; gap: 16px; }
    .admin-pill { display: flex; align-items: center; gap: 8px; padding: 6px 14px 6px 8px; background: var(--card); border: 1px solid var(--border); border-radius: 24px; }
    .admin-avatar { width: 28px; height: 28px; background: var(--red); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: #fff; }
    .admin-name { font-size: 13px; font-weight: 500; color: var(--text); }

    /* ── STATS ── */
    .page-content { padding: 36px 40px; }
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 36px; }
    .stat-card { background: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 24px; position: relative; overflow: hidden; transition: border-color 0.25s, transform 0.2s; }
    .stat-card:hover { border-color: var(--border-h); transform: translateY(-2px); }
    .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--accent, var(--red)); }
    .stat-icon { width: 40px; height: 40px; border-radius: 8px; background: rgba(255,255,255,0.04); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--accent, var(--red)); margin-bottom: 16px; }
    .stat-num { font-family: 'Bebas Neue', sans-serif; font-size: 36px; letter-spacing: 0.04em; color: var(--white); line-height: 1; }
    .stat-label { font-size: 11px; font-weight: 500; letter-spacing: 0.14em; text-transform: uppercase; color: var(--muted); margin-top: 4px; }

    /* ── SECTION TITLES ── */
    .section-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
    .section-title { font-family: 'Bebas Neue', sans-serif; font-size: 26px; letter-spacing: 0.06em; color: var(--white); }
    .section-sub { font-size: 11px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--red); margin-bottom: 4px; }

    /* ── FORM ── */
    .form-panel { background: var(--card); border: 1px solid var(--border); border-radius: 10px; overflow: hidden; margin-bottom: 40px; }
    .form-header { padding: 22px 28px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; background: var(--panel); }
    .form-header-title { font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 0.08em; color: var(--white); }
    .form-header-sub { font-size: 12px; color: var(--muted); margin-top: 2px; }
    .form-body { padding: 28px; }
    .form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 16px; }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .form-full { margin-bottom: 16px; }
    .field label { display: block; font-size: 10px; font-weight: 500; letter-spacing: 0.18em; text-transform: uppercase; color: var(--muted); margin-bottom: 7px; }
    .input-wrap { position: relative; display: flex; align-items: center; }
    .input-wrap svg { position: absolute; left: 13px; color: #444; pointer-events: none; flex-shrink: 0; }
    .field input, .field select, .field textarea { width: 100%; padding: 11px 14px 11px 38px; background: var(--input-bg); border: 1px solid var(--border); border-radius: 6px; color: var(--white); font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; transition: border-color 0.2s, box-shadow 0.2s; }
    .field select { padding-left: 38px; cursor: pointer; }
    .field textarea { padding-left: 14px; resize: vertical; min-height: 90px; line-height: 1.6; }
    .field input:focus, .field select:focus, .field textarea:focus { border-color: var(--red); box-shadow: 0 0 0 3px rgba(232,52,26,0.1); }
    .field input::placeholder, .field textarea::placeholder { color: #3a3a3a; }
    .upload-zone { border: 2px dashed var(--border); border-radius: 8px; padding: 36px 24px; text-align: center; cursor: pointer; transition: border-color 0.2s, background 0.2s; position: relative; overflow: hidden; }
    .upload-zone:hover { border-color: var(--red); background: rgba(232,52,26,0.04); }
    .upload-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; padding: 0; border: none; background: transparent; }
    .upload-icon { color: #333; margin-bottom: 12px; }
    .upload-label { font-size: 13px; font-weight: 500; color: var(--text); margin-bottom: 4px; }
    .upload-sub { font-size: 11px; color: var(--muted); }
    .upload-preview { width: 100%; max-height: 180px; object-fit: cover; border-radius: 6px; display: none; margin-top: 12px; }
    .form-actions { display: flex; align-items: center; gap: 12px; padding-top: 20px; border-top: 1px solid var(--border); margin-top: 20px; }
    .btn-primary { padding: 13px 32px; background: var(--red); border: none; border-radius: 6px; color: #fff; font-family: 'Bebas Neue', sans-serif; font-size: 16px; letter-spacing: 0.1em; cursor: pointer; transition: background 0.2s, transform 0.1s; }
    .btn-primary:hover { background: var(--red-dark); }
    .btn-primary:active { transform: scale(0.98); }
    .btn-ghost { padding: 13px 24px; background: transparent; border: 1px solid var(--border); border-radius: 6px; color: var(--muted); font-family: 'DM Sans', sans-serif; font-size: 13px; cursor: pointer; transition: all 0.2s; }
    .btn-ghost:hover { border-color: var(--border-h); color: var(--text); background: #1e1e1e; }

    /* ── ALERTS ── */
    .alert { padding: 14px 20px; border-radius: 6px; font-size: 13px; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; }
    .alert-success { background: rgba(42,157,92,0.1); border: 1px solid rgba(42,157,92,0.3); color: #4ade80; }
    .alert-error   { background: rgba(232,52,26,0.1); border: 1px solid rgba(232,52,26,0.3); color: #f87171; }

    /* ── TABLE ── */
    .table-panel { background: var(--card); border: 1px solid var(--border); border-radius: 10px; overflow: hidden; }
    .table-header { padding: 20px 28px; border-bottom: 1px solid var(--border); background: var(--panel); display: flex; align-items: center; justify-content: space-between; }
    .search-wrap { position: relative; }
    .search-wrap svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #444; }
    .search-input { padding: 8px 14px 8px 36px; background: var(--input-bg); border: 1px solid var(--border); border-radius: 6px; color: var(--white); font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; transition: border-color 0.2s; width: 220px; }
    .search-input:focus { border-color: var(--red); }
    .search-input::placeholder { color: #3a3a3a; }

    /* ── FEATURED SLOT INDICATOR ── */
    .featured-slots {
      display: flex; align-items: center; gap: 8px;
      font-size: 12px; color: var(--muted);
    }
    .slot-pip {
      width: 10px; height: 10px; border-radius: 50%;
      background: var(--border);
      border: 1px solid rgba(255,255,255,0.1);
      transition: background 0.2s;
    }
    .slot-pip.filled { background: var(--red); border-color: var(--red); }

    table { width: 100%; border-collapse: collapse; }
    thead { background: rgba(255,255,255,0.02); }
    th { padding: 12px 20px; font-size: 9px; font-weight: 500; letter-spacing: 0.2em; text-transform: uppercase; color: var(--muted); border-bottom: 1px solid var(--border); text-align: left; }
    td { padding: 14px 20px; font-size: 13px; color: var(--text); border-bottom: 1px solid rgba(255,255,255,0.04); vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: rgba(255,255,255,0.02); }
    tr.is-featured td { background: rgba(232,52,26,0.04); }

    .td-img { width: 52px; height: 34px; object-fit: cover; border-radius: 4px; background: #111; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .td-img img { width: 100%; height: 100%; object-fit: cover; }
    .td-img-placeholder { width: 52px; height: 34px; background: #1a1a1a; border: 1px solid var(--border); border-radius: 4px; display: flex; align-items: center; justify-content: center; }
    .car-name-cell { font-weight: 500; color: var(--white); }
    .car-brand-cell { font-size: 11px; color: var(--muted); }

    .badge { display: inline-block; padding: 3px 9px; font-size: 9px; font-weight: 500; letter-spacing: 0.12em; text-transform: uppercase; border-radius: 10px; }
    .badge-hot  { background: rgba(232,137,26,0.15); color: var(--orange); border: 1px solid rgba(232,137,26,0.3); }
    .badge-new  { background: rgba(232,52,26,0.12);  color: var(--red);    border: 1px solid rgba(232,52,26,0.25); }
    .badge-sale { background: rgba(42,157,92,0.12);  color: var(--green);  border: 1px solid rgba(42,157,92,0.25); }
    .badge-none { color: var(--muted); font-size: 11px; }

    .cat-tag { display: inline-block; padding: 3px 9px; background: rgba(255,255,255,0.04); border: 1px solid var(--border); border-radius: 4px; font-size: 10px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--muted); }

    /* ── FEATURE BUTTON ── */
    .feat-btn {
      padding: 7px 14px;
      background: transparent;
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 5px;
      color: var(--muted);
      font-size: 11px; font-weight: 500;
      letter-spacing: 0.08em; text-transform: uppercase;
      cursor: pointer; transition: all 0.2s;
      font-family: 'DM Sans', sans-serif;
      text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    }
    .feat-btn:hover { background: rgba(232,52,26,0.1); border-color: rgba(232,52,26,0.4); color: var(--red); }
    .feat-btn.active {
      background: rgba(232,52,26,0.15);
      border-color: var(--red);
      color: var(--red);
    }
    .feat-btn.active:hover { background: rgba(232,52,26,0.25); }

    .del-btn { padding: 7px 14px; background: transparent; border: 1px solid rgba(232,52,26,0.25); border-radius: 5px; color: #f87171; font-size: 11px; font-weight: 500; letter-spacing: 0.08em; text-transform: uppercase; cursor: pointer; transition: all 0.2s; font-family: 'DM Sans', sans-serif; }
    .del-btn:hover { background: rgba(232,52,26,0.12); border-color: var(--red); color: var(--white); }

    .actions-cell { display: flex; gap: 6px; align-items: center; }

    .empty-state { padding: 80px 40px; text-align: center; }
    .empty-icon { color: #222; margin: 0 auto 16px; }
    .empty-title { font-family: 'Bebas Neue', sans-serif; font-size: 24px; letter-spacing: 0.06em; color: #2a2a2a; margin-bottom: 8px; }
    .empty-sub { font-size: 13px; color: #333; }

    @media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } .form-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 768px) {
      :root { --sidebar: 64px; }
      .logo-name, .logo-badge, .nav-item span, .logout-btn span { display: none; }
      .sidebar-logo { padding: 16px 0; }
      .nav-item { padding: 14px 8px; }
      .page-content { padding: 24px 16px; }
      .topbar { padding: 0 20px; }
      .form-grid { grid-template-columns: 1fr; }
      .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark"><span>⬡</span></div>
    <span class="logo-name">DriveHub</span>
    <span class="logo-badge">Admin</span>
  </div>
  <nav class="sidebar-nav">
    <a class="nav-item active" href="admin.php">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
      <span>Dashboard</span>
    </a>
    <a class="nav-item" href="#add-car" onclick="document.getElementById('add-car').scrollIntoView({behavior:'smooth'})">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
      <span>Add Car</span>
    </a>
    <a class="nav-item" href="#listings" onclick="document.getElementById('listings').scrollIntoView({behavior:'smooth'})">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M1 10h22M5 6l2-4h10l2 4M1 14l4-4h14l4 4"/></svg>
      <span>Listings</span>
    </a>
    <a class="nav-item" href="home.php">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      <span>View Site</span>
    </a>
  </nav>
  <div class="sidebar-bottom">
    <a class="logout-btn" href="logout.php">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      <span>Logout</span>
    </a>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
  <header class="topbar">
    <div><div class="topbar-title">Admin <span>Panel</span></div></div>
    <div class="topbar-right">
      <div class="admin-pill">
        <div class="admin-avatar"><?php echo strtoupper(substr($_SESSION['first_name'],0,1)); ?></div>
        <span class="admin-name"><?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
      </div>
    </div>
  </header>

  <div class="page-content">

    <!-- ALERTS -->
    <?php if ($success_msg): ?>
    <div class="alert alert-success">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
      <?php echo htmlspecialchars($success_msg); ?>
    </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
    <div class="alert alert-error">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      <?php echo htmlspecialchars($error_msg); ?>
    </div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="stats-grid">
      <div class="stat-card" style="--accent: var(--red)">
        <div class="stat-icon"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M1 10h22M5 6l2-4h10l2 4M1 14l4-4h14l4 4"/></svg></div>
        <div class="stat-num"><?php echo $total_cars; ?></div>
        <div class="stat-label">Total Cars Listed</div>
      </div>
      <div class="stat-card" style="--accent: var(--green)">
        <div class="stat-icon" style="color:var(--green)"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div class="stat-num"><?php echo array_reduce($all_cars, fn($c,$r) => $c + ($r['badge']==='new'?1:0), 0); ?></div>
        <div class="stat-label">New Arrivals</div>
      </div>
      <div class="stat-card" style="--accent: var(--orange)">
        <div class="stat-icon" style="color:var(--orange)"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9z"/></svg></div>
        <div class="stat-num"><?php echo count(array_unique(array_column($all_cars,'category'))); ?></div>
        <div class="stat-label">Categories Active</div>
      </div>
      <div class="stat-card" style="--accent: #e8341a">
        <div class="stat-icon" style="color:#e8341a"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
        <div class="stat-num"><?php echo $featured_count; ?>/3</div>
        <div class="stat-label">Featured This Week</div>
      </div>
    </div>

    <!-- ADD CAR FORM -->
    <div id="add-car">
      <div class="section-head" style="margin-bottom:16px">
        <div><div class="section-sub">New Listing</div><div class="section-title">ADD A CAR</div></div>
      </div>
    </div>

    <div class="form-panel">
      <div class="form-header">
        <div>
          <div class="form-header-title">Car Details</div>
          <div class="form-header-sub">Fill in all fields. Image is optional but recommended.</div>
        </div>
        <svg width="24" height="24" fill="none" stroke="var(--muted)" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M1 10h22M5 6l2-4h10l2 4M1 14l4-4h14l4 4"/></svg>
      </div>
      <div class="form-body">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="add_car"/>
          <div class="form-grid">
            <div class="field"><label>Car Name</label><div class="input-wrap"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 0 1 0 2.828l-7 7a2 2 0 0 1-2.828 0l-7-7A2 2 0 0 1 3 12V7a4 4 0 0 1 4-4z"/></svg><input type="text" name="car_name" placeholder="e.g. Land Cruiser 300" required/></div></div>
            <div class="field"><label>Brand</label><div class="input-wrap"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg><input type="text" name="brand" placeholder="e.g. Toyota" required/></div></div>
            <div class="field"><label>Category</label><div class="input-wrap"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg><select name="category" required><option value="">Select Category</option><option value="4X4 / SUV">4X4 / SUV</option><option value="2 Doors">2 Doors</option><option value="Sedan">Sedan</option><option value="Bus / Van">Bus / Van</option><option value="Moto">Moto</option><option value="Electric">Electric</option><option value="Truck">Truck</option><option value="Luxury">Luxury</option></select></div></div>
          </div>
          <div class="form-grid">
            <div class="field"><label>Year</label><div class="input-wrap"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><input type="number" name="year" min="1990" max="2026" placeholder="2024" required/></div></div>
            <div class="field"><label>Price</label><div class="input-wrap"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 1 0 0 7h5a3.5 3.5 0 1 1 0 7H6"/></svg><input type="text" name="price" placeholder="e.g. $45,000" required/></div></div>
            <div class="field"><label>Kilometers Driven</label><div class="input-wrap"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg><input type="number" name="kms" min="0" placeholder="e.g. 35000" required/></div></div>
          </div>
          <div class="form-grid">
            <div class="field"><label>Transmission</label><div class="input-wrap"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg><select name="transmission" required><option value="">Select</option><option value="Automatic">Automatic</option><option value="Manual">Manual</option><option value="CVT">CVT</option><option value="DCT">DCT (Dual-Clutch)</option><option value="PDK">PDK</option></select></div></div>
            <div class="field"><label>Engine</label><div class="input-wrap"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg><input type="text" name="engine" placeholder="e.g. V8 4.5L"/></div></div>
            <div class="field"><label>Power (HP)</label><div class="input-wrap"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9z"/></svg><input type="text" name="power" placeholder="e.g. 285HP"/></div></div>
          </div>
          <div class="form-grid-2">
            <div class="field"><label>Drive Type</label><div class="input-wrap"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M7 16V6M17 16V6M7 6h10"/></svg><select name="drive"><option value="">Select</option><option value="AWD">AWD</option><option value="4WD">4WD</option><option value="RWD">RWD</option><option value="FWD">FWD</option></select></div></div>
            <div class="field"><label>Seats</label><div class="input-wrap"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg><input type="number" name="seats" min="1" max="60" placeholder="5"/></div></div>
          </div>
          <div class="form-grid-2">
            <div class="field"><label>Badge / Tag</label><div class="input-wrap"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg><select name="badge"><option value="">No Badge</option><option value="new">NEW</option><option value="hot">HOT</option><option value="sale">DEAL</option></select></div></div>
            <div class="field"><label>Location</label><div class="input-wrap"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg><input type="text" name="location" placeholder="e.g. Beirut, Lebanon"/></div></div>
          </div>
          <div class="form-full field"><label>Description</label><textarea name="description" placeholder="Write a compelling description of the vehicle — condition, history, features, seller notes..."></textarea></div>
          <div class="form-full field">
            <label>Car Photo</label>
            <div class="upload-zone" id="uploadZone">
              <input type="file" name="car_image" accept="image/*" id="carImageInput" onchange="previewImage(this)"/>
              <div class="upload-icon"><svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>
              <div class="upload-label">Drop image here or click to upload</div>
              <div class="upload-sub">JPG, PNG, WEBP — max 10MB</div>
              <img id="imagePreview" class="upload-preview" src="" alt="Preview"/>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-primary">+ PUBLISH LISTING</button>
            <button type="reset" class="btn-ghost" onclick="document.getElementById('imagePreview').style.display='none'">Clear Form</button>
          </div>
        </form>
      </div>
    </div>

    <!-- LISTINGS TABLE -->
    <div id="listings">
      <div class="section-head" style="margin-bottom:16px">
        <div><div class="section-sub">Manage</div><div class="section-title">ALL LISTINGS</div></div>
        <span style="font-size:12px;color:var(--muted)"><?php echo $total_cars; ?> vehicle<?php echo $total_cars!=1?'s':''; ?></span>
      </div>
    </div>

    <div class="table-panel">
      <div class="table-header">
        <div class="search-wrap">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input class="search-input" type="text" placeholder="Search listings..." id="tableSearch" oninput="filterTable(this.value)"/>
        </div>
        <!-- Featured slot indicator -->
        <div class="featured-slots">
          <span style="font-size:11px;letter-spacing:0.1em;text-transform:uppercase;">Featured slots:</span>
          <?php for ($i = 0; $i < 3; $i++): ?>
            <div class="slot-pip <?php echo $i < $featured_count ? 'filled' : ''; ?>"></div>
          <?php endfor; ?>
          <span style="color:var(--red);font-weight:500;"><?php echo $featured_count; ?>/3</span>
        </div>
      </div>

      <?php if (empty($all_cars)): ?>
      <div class="empty-state">
        <div class="empty-icon"><svg width="64" height="64" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M1 10h22M5 6l2-4h10l2 4M1 14l4-4h14l4 4"/></svg></div>
        <div class="empty-title">No Cars Listed Yet</div>
        <div class="empty-sub">Use the form above to add your first listing.</div>
      </div>
      <?php else: ?>
      <table id="carsTable">
        <thead>
          <tr>
            <th>Photo</th>
            <th>Vehicle</th>
            <th>Category</th>
            <th>Year</th>
            <th>KMs</th>
            <th>Price</th>
            <th>Badge</th>
            <th>Location</th>
            <th>Featured</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($all_cars as $car): ?>
          <tr class="<?php echo $car['featured'] ? 'is-featured' : ''; ?>">
            <td>
              <?php if ($car['image_path'] && file_exists($car['image_path'])): ?>
                <div class="td-img"><img src="<?php echo htmlspecialchars($car['image_path']); ?>" alt=""/></div>
              <?php else: ?>
                <div class="td-img-placeholder"><svg width="18" height="12" fill="none" stroke="#333" stroke-width="1.5" viewBox="0 0 24 16"><rect x="1" y="1" width="22" height="14" rx="2"/><circle cx="8" cy="7" r="2"/><path d="M21 15l-5-5-4 4-2-2-4 4"/></svg></div>
              <?php endif; ?>
            </td>
            <td>
              <div class="car-name-cell"><?php echo htmlspecialchars($car['car_name']); ?></div>
              <div class="car-brand-cell"><?php echo htmlspecialchars($car['brand']); ?></div>
            </td>
            <td><span class="cat-tag"><?php echo htmlspecialchars($car['category']); ?></span></td>
            <td><?php echo htmlspecialchars($car['year']); ?></td>
            <td><?php echo number_format($car['kms']); ?> km</td>
            <td style="font-family:'Bebas Neue',sans-serif;font-size:16px;letter-spacing:.04em;color:var(--white)"><?php echo htmlspecialchars($car['price']); ?></td>
            <td>
              <?php if ($car['badge']): ?>
                <span class="badge badge-<?php echo $car['badge']; ?>"><?php echo strtoupper($car['badge']); ?></span>
              <?php else: ?>
                <span class="badge-none">—</span>
              <?php endif; ?>
            </td>
            <td style="font-size:12px;color:var(--muted)"><?php echo htmlspecialchars($car['location'] ?: '—'); ?></td>
            <td>
              <a href="admin.php?feature=<?php echo $car['id']; ?>"
                 class="feat-btn <?php echo $car['featured'] ? 'active' : ''; ?>"
                 onclick="return <?php echo ($car['featured'] == 0 && $featured_count >= 3) ? 'confirm(\'3 cars are already featured. Remove one first.\')' : 'true'; ?>">
                <svg width="13" height="13" fill="<?php echo $car['featured'] ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <?php echo $car['featured'] ? 'Featured' : 'Feature'; ?>
              </a>
            </td>
            <td>
              <div class="actions-cell">
                <button class="del-btn" onclick="confirmDelete(<?php echo $car['id']; ?>, '<?php echo addslashes($car['car_name']); ?>')">Remove</button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

  </div>
</div>

<!-- DELETE MODAL -->
<div id="deleteModal" style="position:fixed;inset:0;z-index:300;background:rgba(0,0,0,0.85);backdrop-filter:blur(8px);display:none;align-items:center;justify-content:center;">
  <div style="background:var(--panel);border:1px solid var(--border);border-radius:10px;padding:40px;max-width:440px;width:90%;position:relative;">
    <div style="width:52px;height:52px;background:rgba(232,52,26,0.1);border:1px solid rgba(232,52,26,0.2);border-radius:10px;margin:0 auto 20px;display:flex;align-items:center;justify-content:center;color:var(--red);">
      <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6M9 6V4h6v2"/></svg>
    </div>
    <div style="font-family:'Bebas Neue',sans-serif;font-size:24px;letter-spacing:.06em;color:var(--white);text-align:center;margin-bottom:8px">Remove Listing?</div>
    <div style="font-size:13px;color:var(--muted);text-align:center;line-height:1.7;margin-bottom:28px" id="deleteModalMsg">This will permanently remove the car from DriveHub.</div>
    <div style="display:flex;gap:10px;justify-content:center;">
      <button onclick="document.getElementById('deleteModal').style.display='none'" style="padding:12px 24px;background:transparent;border:1px solid var(--border);border-radius:6px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:13px;cursor:pointer;">Cancel</button>
      <a id="deleteConfirmLink" href="#" style="padding:12px 28px;background:var(--red);border:none;border-radius:6px;color:#fff;font-family:'Bebas Neue',sans-serif;font-size:16px;letter-spacing:.1em;cursor:pointer;text-decoration:none;display:inline-block;text-align:center;">DELETE</a>
    </div>
  </div>
</div>

<script>
  function previewImage(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.getElementById('imagePreview');
      img.src = e.target.result;
      img.style.display = 'block';
    };
    reader.readAsDataURL(file);
  }

  const zone = document.getElementById('uploadZone');
  if (zone) {
    zone.addEventListener('dragover', () => zone.classList.add('drag-over'));
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', () => zone.classList.remove('drag-over'));
  }

  function confirmDelete(id, name) {
    document.getElementById('deleteModalMsg').textContent = `"${name}" will be permanently removed from all listings.`;
    document.getElementById('deleteConfirmLink').href = `admin.php?delete=${id}`;
    document.getElementById('deleteModal').style.display = 'flex';
  }

  function filterTable(q) {
    const rows = document.querySelectorAll('#carsTable tbody tr');
    q = q.toLowerCase();
    rows.forEach(r => { r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none'; });
  }

  setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => {
      a.style.transition = 'opacity .5s';
      a.style.opacity = '0';
      setTimeout(() => a.remove(), 500);
    });
  }, 4000);
</script>
</body>
</html>