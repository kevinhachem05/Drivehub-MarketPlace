<?php
ob_start();
session_start();
include 'db.php';

$admin_emails = [
    'admin@drivehub.lb',
    'admin2@drivehub.lb'
];

$superadmin_email = ['superadmin@drivehub.lb'];

$email_error    = '';
$password_error = '';
$email_value    = '';

// ── Signup error variables ──
$su_first_name_error    = '';
$su_last_name_error     = '';
$su_email_error         = '';
$su_password_error      = '';
$su_confirm_error       = '';
$su_first_name_value    = '';
$su_last_name_value     = '';
$su_email_value         = '';
$show_signup            = false;

// ── LOGIN ──
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'login') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $email_value = htmlspecialchars($email);

    if (empty($email) && empty($password)) {
        $email_error    = 'Please enter your email address.';
        $password_error = 'Please enter your password.';
    } elseif (empty($email)) {
        $email_error = 'Please enter your email address.';
    } elseif (empty($password)) {
        $password_error = 'Please enter your password.';
    } else {
        $sql  = "SELECT id, first_name, last_name, email, password FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            ob_end_clean();
            die("Database prepare error: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['email']      = $user['email'];

                if (in_array(strtolower(trim($email)), array_map('strtolower', $superadmin_email))) {
                    $_SESSION['role'] = 'superadmin';
                    ob_end_clean();
                    header("Location: superadmin.php");
                    exit();
                } elseif (in_array(strtolower(trim($email)), array_map('strtolower', $admin_emails))) {
                    $_SESSION['role'] = 'admin';
                    ob_end_clean();
                    header("Location: admin.php");
                    exit();
                } else {
                    $_SESSION['role'] = 'user';
                    ob_end_clean();
                    header("Location: home.php");
                    exit();
                }
            } else {
                $password_error = 'Incorrect password. Please try again.';
            }
        } else {
            $email_error = 'No account found with that email address.';
        }

        $stmt->close();
        $conn->close();
    }
}

// ── SIGNUP ──
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'signup') {
    $show_signup = true;

    $first_name      = trim($_POST['first_name']);
    $last_name       = trim($_POST['last_name']);
    $email           = trim($_POST['email']);
    $password        = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Keep values to repopulate fields
    $su_first_name_value = htmlspecialchars($first_name);
    $su_last_name_value  = htmlspecialchars($last_name);
    $su_email_value      = htmlspecialchars($email);

    // Validate
    if (empty($first_name)) {
        $su_first_name_error = 'Please enter your first name.';
    }
    if (empty($last_name)) {
        $su_last_name_error = 'Please enter your last name.';
    }
    if (empty($email)) {
        $su_email_error = 'Please enter your email address.';
    }
    if (empty($password)) {
        $su_password_error = 'Please enter a password.';
    } elseif (strlen($password) < 8) {
        $su_password_error = 'Password must be at least 8 characters.';
    }
    if (empty($confirm_password)) {
        $su_confirm_error = 'Please confirm your password.';
    } elseif ($password !== $confirm_password) {
        $su_confirm_error = 'Passwords do not match. Please try again.';
    }

    // Only hit DB if no validation errors
    if (empty($su_first_name_error) && empty($su_last_name_error) && empty($su_email_error) && empty($su_password_error) && empty($su_confirm_error)) {
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $su_email_error = 'An account with this email already exists.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $first_name, $last_name, $email, $hashed_password);

            if ($stmt->execute()) {
                ob_end_clean();
                header("Location: login.php");
                exit();
            } else {
                $su_email_error = 'Something went wrong. Please try again.';
            }
        }

        $stmt->close();
        $conn->close();
    }
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>DriveHub — Sign In</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
<style>

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --black: #0a0a0a;
  --off-black: #111111;
  --panel: #161616;
  --border: rgba(255,255,255,0.08);
  --muted: #666;
  --text: #e8e8e8;
  --white: #ffffff;
  --red: #e8341a;
  --red-dark: #c0290e;
  --input-bg: #1c1c1c;
  --error: #e8341a;
  --error-bg: rgba(232, 52, 26, 0.08);
  --error-border: rgba(232, 52, 26, 0.5);
}

html, body {
  height: 100%;
  background: var(--black);
  color: var(--text);
  font-family: 'DM Sans', sans-serif;
  overflow: hidden;
}

/* ─── Layout ─── */
.page {
  display: grid;
  grid-template-columns: 1fr 480px;
  height: 100vh;
}

/* ─── Left hero panel ─── */
.hero {
  position: relative;
  overflow: hidden;
  background: #000;
}

.hero-img {
  width: 100%; height: 100%;
  object-fit: cover;
  opacity: 0.55;
  transform: scale(1.05);
  animation: zoomOut 12s ease forwards;
  display: block;
}

@keyframes zoomOut {
  from { transform: scale(1.05); }
  to   { transform: scale(1.0); }
}

.hero::after {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(to right, transparent 50%, var(--black) 100%);
}

.hero-lines {
  position: absolute; inset: 0;
  background-image: repeating-linear-gradient(
    -60deg,
    transparent,
    transparent 40px,
    rgba(255,255,255,0.015) 40px,
    rgba(255,255,255,0.015) 41px
  );
  pointer-events: none;
}

.hero-content {
  position: absolute;
  bottom: 64px; left: 56px;
  z-index: 2;
  animation: slideUp 0.9s cubic-bezier(.22,1,.36,1) 0.2s both;
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(30px); }
  to   { opacity: 1; transform: translateY(0); }
}

.hero-eyebrow {
  font-family: 'DM Sans', sans-serif;
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: var(--red);
  margin-bottom: 12px;
}

.hero-title {
  font-family: 'Bebas Neue', sans-serif;
  font-size: clamp(52px, 6vw, 88px);
  line-height: 0.92;
  color: var(--white);
  letter-spacing: 0.02em;
}

.hero-title span { color: var(--red); }

.hero-sub {
  margin-top: 18px;
  font-size: 14px;
  font-weight: 300;
  color: rgba(255,255,255,0.5);
  max-width: 320px;
  line-height: 1.6;
}

.stat-row {
  display: flex; gap: 40px;
  margin-top: 40px;
}

.stat-num {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 28px;
  color: var(--white);
  letter-spacing: 0.05em;
}
.stat-label {
  font-size: 11px;
  color: var(--muted);
  letter-spacing: 0.15em;
  text-transform: uppercase;
}

/* ─── Right form panel ─── */
.panel {
  background: var(--panel);
  border-left: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 60px 52px;
  position: relative;
  overflow: hidden;
  animation: fadeSlide 0.7s cubic-bezier(.22,1,.36,1) 0.1s both;
}

@keyframes fadeSlide {
  from { opacity: 0; transform: translateX(24px); }
  to   { opacity: 1; transform: translateX(0); }
}

.panel::before {
  content: '';
  position: absolute;
  top: -1px; right: -1px;
  width: 120px; height: 120px;
  background: conic-gradient(from 180deg at 100% 0%, var(--red) 0deg, transparent 90deg);
  opacity: 0.25;
}

/* ─── Logo ─── */
.logo {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 52px;
}

.logo-mark {
  width: 36px; height: 36px;
  background: var(--red);
  clip-path: polygon(0 0, 100% 0, 100% 65%, 50% 100%, 0 65%);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

.logo-icon {
  font-size: 15px;
  color: #fff;
  font-weight: 700;
  line-height: 1;
  margin-bottom: 6px;
}

.logo-name {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 22px;
  letter-spacing: 0.1em;
  color: var(--white);
}

/* ─── Headings ─── */
.panel-title {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 42px;
  color: var(--white);
  letter-spacing: 0.04em;
  line-height: 1;
  margin-bottom: 6px;
}

.panel-sub {
  font-size: 13px;
  font-weight: 300;
  color: var(--muted);
  margin-bottom: 36px;
}

.panel-sub a {
  color: var(--red);
  text-decoration: none;
  font-weight: 500;
  transition: opacity 0.2s;
}
.panel-sub a:hover { opacity: 0.8; }

/* ─── Social login ─── */
.social-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: 10px;
  margin-bottom: 28px;
}

.social-btn {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  padding: 11px 16px;
  background: var(--input-bg);
  border: 1px solid var(--border);
  border-radius: 6px;
  color: var(--text);
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s;
}

.social-btn:hover {
  border-color: rgba(255,255,255,0.2);
  background: #222;
}

.social-btn svg { flex-shrink: 0; }

/* ─── Divider ─── */
.divider {
  display: flex; align-items: center; gap: 14px;
  margin-bottom: 28px;
}

.divider-line {
  flex: 1;
  height: 1px;
  background: var(--border);
}

.divider-text {
  font-size: 11px;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: var(--muted);
}

/* ─── Form ─── */
.form { display: flex; flex-direction: column; gap: 16px; }

.field { display: flex; flex-direction: column; gap: 6px; }

.field label {
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.45);
}

.input-wrap { position: relative; }

.input-wrap svg:first-child {
  position: absolute;
  left: 14px;
  top: 50%; transform: translateY(-50%);
  color: var(--muted);
  pointer-events: none;
  transition: color 0.2s;
}

.field input {
  width: 100%;
  padding: 13px 14px 13px 42px;
  background: var(--input-bg);
  border: 1px solid var(--border);
  border-radius: 6px;
  color: var(--white);
  font-family: 'DM Sans', sans-serif;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}

.field input::placeholder { color: #444; }

.field input:focus {
  border-color: var(--red);
  box-shadow: 0 0 0 3px rgba(232,52,26,0.12);
}

/* ─── Error states ─── */
.input-wrap.has-error svg:first-child {
  color: var(--error);
}

.field input.input-error {
  border-color: var(--error-border);
  background: var(--error-bg);
  box-shadow: 0 0 0 3px rgba(232, 52, 26, 0.08);
}

.field input.input-error:focus {
  border-color: var(--error);
  box-shadow: 0 0 0 3px rgba(232, 52, 26, 0.18);
}

.field-error {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 7px 10px;
  margin-top: 4px;
  background: var(--error-bg);
  border: 1px solid var(--error-border);
  border-radius: 5px;
  color: var(--error);
  font-size: 12px;
  font-weight: 400;
  letter-spacing: 0.01em;
  line-height: 1.4;
  animation: errorSlide 0.22s cubic-bezier(.22,1,.36,1) both;
}

.field-error svg {
  flex-shrink: 0;
  opacity: 0.9;
}

@keyframes errorSlide {
  from { opacity: 0; transform: translateY(-4px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ─── Eye button ─── */
.eye-btn {
  position: absolute;
  right: 14px; top: 50%; transform: translateY(-50%);
  background: none; border: none;
  color: var(--muted); cursor: pointer;
  padding: 0; line-height: 1;
  transition: color 0.2s;
}
.eye-btn:hover { color: var(--text); }

/* ─── Forgot ─── */
.meta-row {
  display: flex; justify-content: flex-end;
  margin-top: -6px;
}

.forgot {
  font-size: 12px;
  color: var(--muted);
  text-decoration: none;
  transition: color 0.2s;
}
.forgot:hover { color: var(--red); }

/* ─── Submit ─── */
.submit-btn {
  margin-top: 4px;
  width: 100%;
  padding: 15px;
  background: var(--red);
  border: none;
  border-radius: 6px;
  color: #fff;
  font-family: 'Bebas Neue', sans-serif;
  font-size: 18px;
  letter-spacing: 0.12em;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition: background 0.2s, transform 0.1s;
}

.submit-btn::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(to right, transparent 0%, rgba(255,255,255,0.12) 50%, transparent 100%);
  transform: translateX(-100%);
  transition: transform 0.5s;
}

.submit-btn:hover { background: #d42f17; }
.submit-btn:hover::after { transform: translateX(100%); }
.submit-btn:active { transform: scale(0.98); }

/* ─── Footer note ─── */
.terms {
  margin-top: 20px;
  font-size: 11px;
  color: #444;
  text-align: center;
  line-height: 1.6;
}
.terms a { color: var(--muted); text-decoration: none; }
.terms a:hover { color: var(--red); }

@keyframes fadeOut {
  from { opacity: 1; transform: translateX(0); }
  to   { opacity: 0; transform: translateX(-20px); }
}

.signup-panel {
  overflow-y: auto;
}

@media (max-width: 860px) {
  .page { grid-template-columns: 1fr; overflow: auto; }
  .hero { display: none; }
  .panel { padding: 48px 32px; justify-content: flex-start; }
  html, body { overflow: auto; }
}
</style>
</head>
<body>

<div class="page">

  <!-- ── LEFT HERO ── -->
  <div class="hero">
    <div style="width:100%;height:100%;background:linear-gradient(135deg,#0a0a0a 0%,#1a1a1a 40%,#2a1510 100%);position:absolute;inset:0;"></div>

    <svg viewBox="0 0 800 400" xmlns="http://www.w3.org/2000/svg"
      style="position:absolute;inset:0;width:100%;height:100%;opacity:0.12;">
      <path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"
        fill="white"/>
      <circle cx="200" cy="300" r="44" fill="white"/>
      <circle cx="620" cy="300" r="44" fill="white"/>
      <circle cx="200" cy="300" r="28" fill="#0a0a0a"/>
      <circle cx="620" cy="300" r="28" fill="#0a0a0a"/>
      <circle cx="200" cy="300" r="12" fill="white"/>
      <circle cx="620" cy="300" r="12" fill="white"/>
      <path d="M290 145 L310 185 L480 185 L500 145 Z" fill="#0a0a0a" opacity="0.6"/>
      <path d="M160 185 L180 145 L285 145 L305 185 Z" fill="#0a0a0a" opacity="0.6"/>
    </svg>

    <div class="hero-lines"></div>

    <div class="hero-content">
      <p class="hero-eyebrow">The Premium Marketplace</p>
      <h1 class="hero-title">FIND YOUR<br>PERFECT<br><span>DRIVE.</span></h1>
      <p class="hero-sub">Thousands of verified listings. Zero compromise. Your next car is one search away.</p>
      <div class="stat-row">
        <div class="stat">
          <div class="stat-num">48K+</div>
          <div class="stat-label">Listings</div>
        </div>
        <div class="stat">
          <div class="stat-num">120+</div>
          <div class="stat-label">Brands</div>
        </div>
        <div class="stat">
          <div class="stat-num">4.9★</div>
          <div class="stat-label">Avg Rating</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── RIGHT PANEL (LOGIN) ── -->
  <div class="panel" id="loginPanel" <?= $show_signup ? 'style="display:none;"' : '' ?>>

    <div class="logo">
      <div class="logo-mark">
        <span class="logo-icon">⬡</span>
      </div>
      <span class="logo-name">DriveHub</span>
    </div>

    <h2 class="panel-title">WELCOME<br>BACK</h2>
    <p class="panel-sub">No account? <a href="#" onclick="showSignup(event)">Create one free →</a></p>

    <div class="social-row">
      <button class="social-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
          <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
          <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
          <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
          <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        Continue with Google
      </button>
      <button class="social-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12.152 6.896c-.948 0-2.415-1.078-3.96-1.04-2.04.027-3.91 1.183-4.961 3.014-2.117 3.675-.546 9.103 1.519 12.09 1.013 1.454 2.208 3.09 3.792 3.039 1.52-.065 2.09-.987 3.935-.987 1.831 0 2.35.987 3.96.948 1.637-.026 2.676-1.48 3.676-2.948 1.156-1.688 1.636-3.325 1.662-3.415-.039-.013-3.182-1.221-3.22-4.857-.026-3.04 2.48-4.494 2.597-4.559-1.429-2.09-3.623-2.324-4.39-2.376-2-.156-3.675 1.09-4.61 1.09zM15.53 3.83c.843-1.012 1.4-2.427 1.245-3.83-1.207.052-2.662.805-3.532 1.818-.78.896-1.454 2.338-1.273 3.714 1.338.104 2.715-.688 3.559-1.701z"/>
        </svg>
        Continue with Apple
      </button>
    </div>

    <div class="divider">
      <div class="divider-line"></div>
      <span class="divider-text">or</span>
      <div class="divider-line"></div>
    </div>

    <form class="form" action="login.php" method="POST">
      <input type="hidden" name="form_type" value="login"/>
      <div class="field">
        <label>Email address</label>
        <div class="input-wrap <?= !empty($email_error) ? 'has-error' : '' ?>">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <rect x="2" y="4" width="20" height="16" rx="2"/>
            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
          </svg>
          <input type="email" name="email" placeholder="you@example.com" autocomplete="email"
            value="<?= $email_value ?>"
            class="<?= !empty($email_error) ? 'input-error' : '' ?>"/>
        </div>
        <?php if (!empty($email_error)): ?>
          <div class="field-error">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?= htmlspecialchars($email_error) ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="field">
        <label>Password</label>
        <div class="input-wrap <?= !empty($password_error) ? 'has-error' : '' ?>">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <rect x="3" y="11" width="18" height="11" rx="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          <input type="password" name="password" id="pwInput" placeholder="••••••••" autocomplete="current-password"
            class="<?= !empty($password_error) ? 'input-error' : '' ?>"/>
          <button class="eye-btn" onclick="togglePw()" type="button" aria-label="Toggle password">
            <svg id="eyeIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
        <?php if (!empty($password_error)): ?>
          <div class="field-error">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?= htmlspecialchars($password_error) ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="meta-row">
        <a href="#" class="forgot">Forgot password?</a>
      </div>

      <button class="submit-btn" type="submit">SIGN IN</button>
    </form>

    <p class="terms">
      By signing in you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
    </p>

  </div><!-- /.panel login -->

  <!-- ── SIGNUP PANEL ── -->
  <div class="panel signup-panel" id="signupPanel" <?= $show_signup ? '' : 'style="display:none;"' ?>>

    <div class="logo">
      <div class="logo-mark">
        <span class="logo-icon">⬡</span>
      </div>
      <span class="logo-name">DriveHub</span>
    </div>

    <h2 class="panel-title">CREATE<br>ACCOUNT</h2>
    <p class="panel-sub">Already have one? <a href="#" onclick="showLogin(event)">Sign in →</a></p>

    <form class="form" action="login.php" method="POST">
      <input type="hidden" name="form_type" value="signup"/>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="field">
          <label>First Name</label>
          <div class="input-wrap <?= !empty($su_first_name_error) ? 'has-error' : '' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
            </svg>
            <input type="text" name="first_name" placeholder="John" autocomplete="given-name"
              value="<?= $su_first_name_value ?>"
              class="<?= !empty($su_first_name_error) ? 'input-error' : '' ?>"/>
          </div>
          <?php if (!empty($su_first_name_error)): ?>
            <div class="field-error">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
              </svg>
              <?= htmlspecialchars($su_first_name_error) ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="field">
          <label>Last Name</label>
          <div class="input-wrap <?= !empty($su_last_name_error) ? 'has-error' : '' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
            </svg>
            <input type="text" name="last_name" placeholder="Doe" autocomplete="family-name"
              value="<?= $su_last_name_value ?>"
              class="<?= !empty($su_last_name_error) ? 'input-error' : '' ?>"/>
          </div>
          <?php if (!empty($su_last_name_error)): ?>
            <div class="field-error">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
              </svg>
              <?= htmlspecialchars($su_last_name_error) ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="field">
        <label>Email address</label>
        <div class="input-wrap <?= !empty($su_email_error) ? 'has-error' : '' ?>">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <rect x="2" y="4" width="20" height="16" rx="2"/>
            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
          </svg>
          <input type="email" name="email" placeholder="you@example.com" autocomplete="email"
            value="<?= $su_email_value ?>"
            class="<?= !empty($su_email_error) ? 'input-error' : '' ?>"/>
        </div>
        <?php if (!empty($su_email_error)): ?>
          <div class="field-error">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?= htmlspecialchars($su_email_error) ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="field">
        <label>Password</label>
        <div class="input-wrap <?= !empty($su_password_error) ? 'has-error' : '' ?>">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <rect x="3" y="11" width="18" height="11" rx="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          <input type="password" name="password" id="pwInputSu" placeholder="Min. 8 characters" autocomplete="new-password"
            class="<?= !empty($su_password_error) ? 'input-error' : '' ?>"/>
          <button class="eye-btn" onclick="togglePwSu()" type="button" aria-label="Toggle password">
            <svg id="eyeIconSu" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
        <?php if (!empty($su_password_error)): ?>
          <div class="field-error">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?= htmlspecialchars($su_password_error) ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="field">
        <label>Confirm Password</label>
        <div class="input-wrap <?= !empty($su_confirm_error) ? 'has-error' : '' ?>">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <rect x="3" y="11" width="18" height="11" rx="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          <input type="password" name="confirm_password" id="pwConfirm" placeholder="Repeat password" autocomplete="new-password"
            class="<?= !empty($su_confirm_error) ? 'input-error' : '' ?>"/>
        </div>
        <?php if (!empty($su_confirm_error)): ?>
          <div class="field-error">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?= htmlspecialchars($su_confirm_error) ?>
          </div>
        <?php endif; ?>
      </div>

      <button class="submit-btn" type="submit">CREATE ACCOUNT</button>
    </form>

    <p class="terms">
      By signing up you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
    </p>

  </div><!-- /.panel signup -->

</div>

<script src="login.js"></script>
</body>
</html>