<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

include 'db.php';

/* ── Fetch admin-added cars from DB ── */
$db_cars = [];
$result  = $conn->query("SELECT * FROM cars ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) $db_cars[] = $row;
}

/* ── Fetch 3 featured cars (admin-selected) ── */
$featured_cars = [];
$feat_result = $conn->query("SELECT * FROM cars WHERE featured = 1 ORDER BY created_at DESC LIMIT 3");
if ($feat_result) {
    while ($row = $feat_result->fetch_assoc()) $featured_cars[] = $row;
}
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>DriveHub — Find Your Drive</title>
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
    }
    html { scroll-behavior: smooth; }
    body { background: var(--black); color: var(--text); font-family: 'DM Sans', sans-serif; overflow-x: hidden; }
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: var(--off-black); }
    ::-webkit-scrollbar-thumb { background: #333; border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--red); }

    /* ── NAV ── */
    nav {
      position: fixed; top: 0; left: 0; right: 0; z-index: 100;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 56px; height: 68px;
      background: rgba(10,10,10,0.88);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border);
    }
    .nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
    .logo-mark { width: 32px; height: 32px; background: var(--red); clip-path: polygon(0 0, 100% 0, 100% 65%, 50% 100%, 0 65%); display: flex; align-items: center; justify-content: center; }
    .logo-mark span { font-size: 13px; color:#fff; font-weight:700; margin-bottom:5px; }
    .logo-name { font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 0.1em; color: var(--white); }
    .nav-links { display: flex; align-items: center; gap: 32px; list-style: none; }
    .nav-links a { font-size: 12px; font-weight: 500; letter-spacing: 0.12em; text-transform: uppercase; color: var(--muted); text-decoration: none; transition: color 0.2s; }
    .nav-links a:hover { color: var(--white); }
    .nav-links a.active { color: var(--red); }
    .nav-ai-dot { width: 5px; height: 5px; background: var(--red); border-radius: 50%; animation: pulse 2s ease-in-out infinite; }
    @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.4;transform:scale(0.7)} }
    .nav-cta { display: flex; align-items: center; gap: 12px; }
    .welcome { font-size: 13px; color: var(--muted); }

    /* ── LOGOUT BUTTON ── */
    .nav-logout-btn {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 8px 16px;
      background: transparent;
      border: 1px solid rgba(232,52,26,0.25);
      border-radius: 6px;
      color: #f87171;
      font-family: 'DM Sans', sans-serif;
      font-size: 12px; font-weight: 500;
      letter-spacing: 0.08em; text-transform: uppercase;
      text-decoration: none;
      transition: all 0.2s;
    }
    .nav-logout-btn:hover {
      background: rgba(232,52,26,0.1);
      border-color: var(--red);
      color: var(--white);
    }

    .nav-profile { display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 5px 10px; border-radius: 6px; transition: background 0.2s; text-decoration: none; }
    .nav-profile:hover { background: rgba(255,255,255,0.06); }
    .nav-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--red); display: flex; align-items: center; justify-content: center; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 500; color: #fff; flex-shrink: 0; }
    .nav-profile-name { font-size: 13px; font-weight: 500; color: var(--text); }

    /* ── HERO ── */
    .hero { position: relative; height: 100vh; min-height: 600px; display: flex; align-items: flex-start; overflow: hidden; }
    .hero-bg { position: absolute; inset: 0; background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 40%, #2a1510 100%); }
    .hero-car { position: absolute; right: -40px; bottom: 0; width: 65%; opacity: 0.07; pointer-events: none; }
    .hero-lines { position: absolute; inset: 0; background-image: repeating-linear-gradient(-60deg, transparent, transparent 40px, rgba(255,255,255,0.012) 40px, rgba(255,255,255,0.012) 41px); }
    .hero::after { content:''; position:absolute; bottom:0; left:0; right:0; height:200px; background: linear-gradient(to top, var(--black), transparent); }
    .hero-content { position: relative; z-index: 2; padding: 140px 80px 0; animation: slideUp 0.9s cubic-bezier(.22,1,.36,1) 0.1s both; }
    @keyframes slideUp { from { opacity:0; transform:translateY(40px); } to { opacity:1; transform:translateY(0); } }
    .hero-eyebrow { font-size: 11px; font-weight: 500; letter-spacing: 0.35em; text-transform: uppercase; color: var(--red); margin-bottom: 16px; }
    .hero-title { font-family: 'Bebas Neue', sans-serif; font-size: clamp(64px, 9vw, 130px); line-height: 0.9; color: var(--white); letter-spacing: 0.02em; }
    .hero-title span { color: var(--red); }
    .hero-desc { margin-top: 24px; font-size: 15px; font-weight: 300; color: rgba(255,255,255,0.45); max-width: 400px; line-height: 1.7; }
    .hero-actions { display: flex; align-items: center; gap: 16px; margin-top: 36px; }
    .hero-btn { padding: 16px 36px; background: var(--red); border: none; border-radius: 6px; color: #fff; font-family: 'Bebas Neue', sans-serif; font-size: 18px; letter-spacing: 0.12em; cursor: pointer; position: relative; overflow: hidden; transition: background 0.2s, transform 0.1s; }
    .hero-btn::after { content:''; position:absolute; inset:0; background: linear-gradient(to right, transparent, rgba(255,255,255,0.12), transparent); transform: translateX(-100%); transition: transform 0.5s; }
    .hero-btn:hover { background: var(--red-dark); }
    .hero-btn:hover::after { transform: translateX(100%); }
    .hero-btn:active { transform: scale(0.98); }
    .hero-btn-ghost { padding: 16px 28px; background: transparent; border: 1px solid var(--border-hover); border-radius: 6px; color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 14px; cursor: pointer; transition: border-color 0.2s, background 0.2s; }
    .hero-btn-ghost:hover { background: #1c1c1c; border-color: rgba(255,255,255,0.3); }
    .hero-stats { position: absolute; right: 80px; top: 140px; z-index: 2; display: flex; flex-direction: column; gap: 28px; animation: slideUp 0.9s cubic-bezier(.22,1,.36,1) 0.3s both; }
    .hero-stat { text-align: right; }
    .hero-stat-num { font-family: 'Bebas Neue', sans-serif; font-size: 36px; color: var(--white); letter-spacing: 0.05em; }
    .hero-stat-label { font-size: 10px; color: var(--muted); letter-spacing: 0.2em; text-transform: uppercase; }
    .hero-stat-divider { width: 40px; height: 1px; background: var(--red); margin-left: auto; margin-top: 6px; }

    /* ── SECTIONS ── */
    section { padding: 100px 80px; }
    .section-eyebrow { font-size: 11px; font-weight: 500; letter-spacing: 0.3em; text-transform: uppercase; color: var(--red); margin-bottom: 10px; }
    .section-title { font-family: 'Bebas Neue', sans-serif; font-size: clamp(36px, 4vw, 56px); color: var(--white); letter-spacing: 0.03em; line-height: 1; }
    .section-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 48px; }
    .view-all { font-size: 12px; font-weight: 500; letter-spacing: 0.12em; text-transform: uppercase; color: var(--muted); text-decoration: none; border-bottom: 1px solid var(--border); padding-bottom: 2px; transition: color 0.2s, border-color 0.2s; white-space: nowrap; }
    .view-all:hover { color: var(--red); border-color: var(--red); }

    /* ── CATEGORIES ── */
    #categories { background: var(--off-black); padding: 80px; }

    /* ── CAR GRID ── */
    .car-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2px; }
    .car-card { background: var(--card); border: 1px solid var(--border); overflow: hidden; cursor: pointer; transition: border-color 0.25s, transform 0.25s; position: relative; }
    .car-card:hover { border-color: var(--red); transform: translateY(-3px); z-index: 2; }
    .car-badge { position: absolute; top: 14px; left: 14px; z-index: 3; padding: 4px 10px; font-size: 10px; font-weight: 500; letter-spacing: 0.15em; text-transform: uppercase; border-radius: 3px; }
    .badge-new { background: var(--red); color: #fff; }
    .badge-hot { background: #e8891a; color: #fff; }
    .badge-sale { background: #2a9d5c; color: #fff; }
    .car-img { width: 100%; aspect-ratio: 16/10; background: #111; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; border-bottom: 1px solid var(--border); position: relative; overflow: hidden; }
    .car-img img { width: 100%; height: 100%; object-fit: cover; }
    .car-img::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to bottom, transparent 60%, rgba(0,0,0,0.4) 100%); }
    .car-img-icon { opacity: 0.12; }
    .car-img-label { font-size: 10px; letter-spacing: 0.2em; text-transform: uppercase; color: #333; }
    .car-body { padding: 20px 22px 24px; }
    .car-brand { font-size: 10px; font-weight: 500; letter-spacing: 0.2em; text-transform: uppercase; color: var(--red); margin-bottom: 4px; }
    .car-name { font-family: 'Bebas Neue', sans-serif; font-size: 22px; letter-spacing: 0.04em; color: var(--white); margin-bottom: 8px; }
    .car-desc { font-size: 13px; font-weight: 300; color: var(--muted); line-height: 1.6; margin-bottom: 16px; }
    .car-specs { display: flex; gap: 16px; padding: 12px 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); margin-bottom: 16px; }
    .car-spec { display: flex; flex-direction: column; gap: 2px; }
    .car-spec-val { font-family: 'Bebas Neue', sans-serif; font-size: 16px; color: var(--white); letter-spacing: 0.05em; }
    .car-spec-key { font-size: 9px; color: var(--muted); letter-spacing: 0.15em; text-transform: uppercase; }
    .car-footer { display: flex; align-items: center; justify-content: space-between; }
    .car-price { font-family: 'Bebas Neue', sans-serif; font-size: 26px; color: var(--white); letter-spacing: 0.04em; }
    .car-price span { font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 300; color: var(--muted); letter-spacing: 0; }
    .car-btn { padding: 9px 18px; background: transparent; border: 1px solid var(--border); border-radius: 4px; color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 12px; cursor: pointer; transition: all 0.2s; }
    .car-btn:hover { background: var(--red); border-color: var(--red); color: #fff; }
    .car-location { font-size: 11px; color: var(--muted); margin-top: 10px; display: flex; align-items: center; gap: 5px; }

    /* ── EMPTY STATE ── */
    .empty-state { grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 80px 40px; border: 1px dashed rgba(255,255,255,0.08); text-align: center; }
    .empty-state-icon { opacity: 0.15; margin-bottom: 20px; }
    .empty-state-title { font-family: 'Bebas Neue', sans-serif; font-size: 24px; letter-spacing: 0.1em; color: var(--muted); margin-bottom: 8px; }
    .empty-state-desc { font-size: 13px; color: #333; font-weight: 300; }

    /* ── FEATURED ── */
    #featured { background: var(--black); }
    .featured-grid { display: grid; grid-template-columns: 1.6fr 1fr; grid-template-rows: auto auto; gap: 2px; }
    .featured-main { grid-row: 1 / 3; background: var(--card); border: 1px solid var(--border); overflow: hidden; cursor: pointer; transition: border-color 0.25s; position: relative; min-height: 100%; display: flex; flex-direction: column; }
    .featured-main:hover { border-color: var(--red); }
    .featured-main .car-img { aspect-ratio: 4/3; }
    .featured-tag { position: absolute; top: 0; left: 0; background: var(--red); padding: 8px 18px; font-family: 'Bebas Neue', sans-serif; font-size: 13px; letter-spacing: 0.15em; color: #fff; }
    .featured-main .car-name { font-size: 32px; }
    .featured-main .car-price { font-size: 34px; }
    .featured-side { background: var(--card); border: 1px solid var(--border); overflow: hidden; cursor: pointer; transition: border-color 0.25s; }
    .featured-side:hover { border-color: var(--red); }
    .featured-side .car-img { aspect-ratio: 16/9; }

    /* ── BRANDS ── */
    .brands-strip { background: var(--panel); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 32px 80px; overflow: hidden; }
    .brands-label { font-size: 10px; letter-spacing: 0.25em; text-transform: uppercase; color: #333; margin-bottom: 20px; }
    .brands-scroll { display: flex; gap: 48px; align-items: center; animation: scrollBrands 28s linear infinite; width: max-content; }
    @keyframes scrollBrands { from { transform: translateX(0); } to { transform: translateX(-50%); } }
    .brand-name { font-family: 'Bebas Neue', sans-serif; font-size: 22px; letter-spacing: 0.1em; color: #2a2a2a; white-space: nowrap; transition: color 0.2s; }
    .brand-name:hover { color: var(--red); }

    /* ── AI PREDICTOR SECTION ── */
    #ai-predictor {
      background: var(--black);
      padding: 100px 80px;
      position: relative;
      overflow: hidden;
      border-top: 1px solid var(--border);
    }
    #ai-predictor::before {
      content: '';
      position: absolute; top: 0; right: 0;
      width: 55%; height: 100%;
      background: radial-gradient(ellipse at 80% 50%, rgba(232,52,26,0.065) 0%, transparent 65%);
      pointer-events: none;
    }
    #ai-predictor::after {
      content: '';
      position: absolute; inset: 0;
      background-image: repeating-linear-gradient(-60deg, transparent, transparent 40px, rgba(255,255,255,0.008) 40px, rgba(255,255,255,0.008) 41px);
      pointer-events: none;
    }
    .ai-inner { display: grid; grid-template-columns: 1fr 1.08fr; gap: 72px; align-items: start; }
    .ai-left { min-height: 100%; display: flex; flex-direction: column; }
    .ai-right { min-height: 100%; display: flex; flex-direction: column; gap: 2px; }
    .ai-model-stat { display: flex; gap: 32px; margin-top: 32px; padding-top: 28px; border-top: 1px solid var(--border); }
    .ai-stat-num { font-family: 'Bebas Neue', sans-serif; font-size: 30px; color: var(--white); letter-spacing: 0.04em; }
    .ai-stat-num span { color: var(--red); }
    .ai-stat-label { font-size: 10px; color: var(--muted); letter-spacing: 0.15em; text-transform: uppercase; margin-top: 3px; }
    .ai-cta-block { display: flex; align-items: center; gap: 20px; margin-top: 40px; }
    .ai-cta-btn { display: inline-flex; align-items: center; gap: 10px; padding: 16px 36px; background: var(--red); border: none; border-radius: 6px; color: #fff; font-family: 'Bebas Neue', sans-serif; font-size: 18px; letter-spacing: 0.12em; cursor: pointer; text-decoration: none; position: relative; overflow: hidden; transition: background 0.2s, transform 0.1s; }
    .ai-cta-btn::after { content:''; position:absolute; inset:0; background: linear-gradient(to right, transparent, rgba(255,255,255,0.12), transparent); transform: translateX(-100%); transition: transform 0.5s; }
    .ai-cta-btn:hover { background: var(--red-dark); }
    .ai-cta-btn:hover::after { transform: translateX(100%); }
    .ai-cta-btn:active { transform: scale(0.98); }
    .ai-cta-note { font-size: 12px; font-weight: 300; color: var(--muted); line-height: 1.65; }
    .ai-feature-card { background: var(--panel); border: 1px solid var(--border); padding: 30px 30px; min-height: 125px; display: flex; align-items: flex-start; gap: 16px; transition: border-color 0.25s, background 0.25s; }
    .ai-feature-card:hover { border-color: rgba(232,52,26,0.3); background: #181818; }
    .ai-feature-icon { width: 38px; height: 38px; flex-shrink: 0; background: rgba(232,52,26,0.1); border: 1px solid rgba(232,52,26,0.2); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: var(--red); }
    .ai-feature-title { font-family: 'Bebas Neue', sans-serif; font-size: 16px; letter-spacing: 0.06em; color: var(--white); margin-bottom: 4px; }
    .ai-feature-desc { font-size: 12px; font-weight: 300; color: var(--muted); line-height: 1.6; }

    /* ── WHY US ── */
    #why { background: var(--off-black); }
    .why-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; border: 1px solid var(--border); }
    .why-card { padding: 40px 32px; background: var(--panel); border-right: 1px solid var(--border); transition: background 0.2s; }
    .why-card:last-child { border-right: none; }
    .why-card:hover { background: #1e1e1e; }
    .why-icon { width: 44px; height: 44px; background: rgba(232,52,26,0.1); border: 1px solid rgba(232,52,26,0.2); border-radius: 6px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; color: var(--red); }
    .why-title { font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 0.06em; color: var(--white); margin-bottom: 10px; }
    .why-desc { font-size: 13px; font-weight: 300; color: var(--muted); line-height: 1.7; }

    /* ── NEWSLETTER ── */
    .newsletter { background: var(--black); padding: 80px; border-top: 1px solid var(--border); }
    .newsletter-inner { max-width: 600px; margin: 0 auto; text-align: center; }
    .newsletter .section-eyebrow { display: block; margin-bottom: 10px; }
    .newsletter .section-title { margin-bottom: 16px; }
    .newsletter-sub { font-size: 14px; font-weight: 300; color: var(--muted); line-height: 1.7; margin-bottom: 32px; }
    .newsletter-form { display: flex; gap: 0; }
    .newsletter-input { flex: 1; padding: 14px 18px; background: var(--input-bg); border: 1px solid var(--border); border-right: none; border-radius: 6px 0 0 6px; color: var(--white); font-family: 'DM Sans', sans-serif; font-size: 14px; outline: none; transition: border-color 0.2s; }
    .newsletter-input::placeholder { color: #444; }
    .newsletter-input:focus { border-color: var(--red); }
    .newsletter-btn { padding: 14px 28px; background: var(--red); border: none; border-radius: 0 6px 6px 0; color: #fff; font-family: 'Bebas Neue', sans-serif; font-size: 16px; letter-spacing: 0.12em; cursor: pointer; transition: background 0.2s; }
    .newsletter-btn:hover { background: var(--red-dark); }

    /* ── FOOTER ── */
    footer { background: var(--panel); border-top: 1px solid var(--border); padding: 56px 80px 32px; }
    .footer-top { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: 60px; margin-bottom: 48px; }
    .footer-brand p { font-size: 13px; font-weight: 300; color: var(--muted); line-height: 1.7; margin-top: 14px; max-width: 240px; }
    .footer-col-title { font-family: 'Bebas Neue', sans-serif; font-size: 14px; letter-spacing: 0.15em; color: var(--white); margin-bottom: 16px; }
    .footer-links { list-style: none; display: flex; flex-direction: column; gap: 10px; }
    .footer-links a { font-size: 13px; font-weight: 300; color: var(--muted); text-decoration: none; transition: color 0.2s; }
    .footer-links a:hover { color: var(--red); }
    .footer-bottom { display: flex; align-items: center; justify-content: space-between; padding-top: 24px; border-top: 1px solid var(--border); }
    .footer-copy { font-size: 12px; color: #333; }
    .footer-copy span { color: var(--red); }
    .footer-legal { display: flex; gap: 20px; }
    .footer-legal a { font-size: 12px; color: #333; text-decoration: none; transition: color 0.2s; }
    .footer-legal a:hover { color: var(--muted); }

    /* ── MODAL ── */
    .modal-overlay { position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.3s; }
    .modal-overlay.open { opacity: 1; pointer-events: all; }
    .modal { background: var(--panel); border: 1px solid var(--border); width: 90%; max-width: 760px; max-height: 90vh; overflow-y: auto; border-radius: 4px; transform: translateY(20px); transition: transform 0.3s; position: relative; }
    .modal-overlay.open .modal { transform: translateY(0); }
    .modal-close { position: absolute; top: 16px; right: 16px; background: #1c1c1c; border: 1px solid var(--border); color: var(--muted); cursor: pointer; width: 32px; height: 32px; border-radius: 4px; font-size: 18px; display: flex; align-items: center; justify-content: center; transition: color 0.2s, border-color 0.2s; }
    .modal-close:hover { color: var(--white); border-color: var(--border-hover); }
    .modal-img { width: 100%; aspect-ratio: 16/9; background: #111; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 10px; border-bottom: 1px solid var(--border); overflow: hidden; }
    .modal-img img { width: 100%; height: 100%; object-fit: cover; }
    .modal-body { padding: 32px 36px 36px; }
    .modal-brand { font-size: 11px; font-weight: 500; letter-spacing: 0.2em; text-transform: uppercase; color: var(--red); margin-bottom: 4px; }
    .modal-name { font-family: 'Bebas Neue', sans-serif; font-size: 36px; color: var(--white); letter-spacing: 0.04em; margin-bottom: 12px; }
    .modal-desc { font-size: 14px; font-weight: 300; color: var(--muted); line-height: 1.8; margin-bottom: 24px; }
    .modal-specs { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: var(--border); border: 1px solid var(--border); margin-bottom: 28px; }
    .modal-spec { background: var(--card); padding: 16px 18px; }
    .modal-spec-val { font-family: 'Bebas Neue', sans-serif; font-size: 22px; color: var(--white); letter-spacing: 0.04em; }
    .modal-spec-key { font-size: 10px; color: var(--muted); letter-spacing: 0.15em; text-transform: uppercase; }
    .modal-footer { display: flex; align-items: center; justify-content: space-between; }
    .modal-price { font-family: 'Bebas Neue', sans-serif; font-size: 36px; color: var(--white); letter-spacing: 0.04em; }
    .modal-price span { font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 300; color: var(--muted); }
    .modal-actions { display: flex; gap: 10px; }
    .modal-btn-primary { padding: 13px 28px; background: var(--red); border: none; border-radius: 5px; color: #fff; font-family: 'Bebas Neue', sans-serif; font-size: 16px; letter-spacing: 0.1em; cursor: pointer; transition: background 0.2s; }
    .modal-btn-primary:hover { background: var(--red-dark); }
    .modal-btn-sec { padding: 13px 20px; background: transparent; border: 1px solid var(--border); border-radius: 5px; color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 13px; cursor: pointer; transition: all 0.2s; }
    .modal-btn-sec:hover { border-color: var(--border-hover); background: #1c1c1c; }

    /* ── RESPONSIVE ── */
    @media (max-width: 1100px) {
      .featured-grid { grid-template-columns: 1fr 1fr; }
      .why-grid { grid-template-columns: repeat(2,1fr); }
      .footer-top { grid-template-columns: 1fr 1fr; gap: 40px; }
      .ai-inner { grid-template-columns: 1fr; gap: 48px; }
    }
    @media (max-width: 768px) {
      nav { padding: 0 24px; }
      .nav-links { display: none; }
      section, #categories, .newsletter, footer { padding: 60px 24px; }
      .hero-content { padding: 120px 24px 0; }
      .hero-stats { display: none; }
      .featured-grid { grid-template-columns: 1fr; }
      .why-grid { grid-template-columns: 1fr; }
      .footer-top { grid-template-columns: 1fr; }
      .modal-specs { grid-template-columns: repeat(2,1fr); }
      .brands-strip { padding: 24px; }
      #ai-predictor { padding: 60px 24px; }
      .ai-model-stat { gap: 20px; }
      .ai-cta-block { flex-direction: column; align-items: flex-start; }
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
  <ul class="nav-links">
    <li><a href="#" class="active">Home</a></li>
    <li><a href="#categories">Categories</a></li>
    <li><a href="#featured">Featured</a></li>
    <li><a href="#why">Why Us</a></li>
    <li><a href="predictor.html" style="color: var(--red) !important;">AI Predictor ✦</a></li>
    <li><a href="#">Contact</a></li>
  </ul>
  <div class="nav-cta">
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

<!-- HERO -->
<section class="hero">
  <div class="hero-bg"></div>
  <div class="hero-lines"></div>
  <svg class="hero-car" viewBox="0 0 900 420" xmlns="http://www.w3.org/2000/svg">
    <path d="M40 310 Q70 200 180 175 L330 130 Q450 85 590 118 L770 158 Q860 182 880 235 L900 285 Q900 315 820 320 Q795 265 720 265 Q645 265 620 320 L300 320 Q275 265 200 265 Q125 265 100 320 Z" fill="white"/>
    <circle cx="200" cy="340" r="54" fill="white"/><circle cx="720" cy="340" r="54" fill="white"/>
    <circle cx="200" cy="340" r="34" fill="#0a0a0a"/><circle cx="720" cy="340" r="34" fill="#0a0a0a"/>
    <circle cx="200" cy="340" r="14" fill="white"/><circle cx="720" cy="340" r="14" fill="white"/>
    <path d="M340 135 L365 185 L555 185 L580 135 Z" fill="#0a0a0a" opacity="0.5"/>
    <path d="M180 185 L205 135 L335 135 L360 185 Z" fill="#0a0a0a" opacity="0.5"/>
  </svg>
  <div class="hero-content">
    <p class="hero-eyebrow">Lebanon's #1 Car Marketplace</p>
    <h1 class="hero-title">YOUR NEXT<br>CAR<br><span>AWAITS.</span></h1>
    <p class="hero-desc">Browse thousands of verified vehicles across every category — from rugged 4x4s to sleek city cars, bikes, and more.</p>
    <div class="hero-actions">
      <button class="hero-btn" onclick="document.getElementById('categories').scrollIntoView({behavior:'smooth'})">BROWSE ALL CARS</button>
      <button class="hero-btn-ghost">How It Works</button>
    </div>
  </div>
  <div class="hero-stats">
    <div class="hero-stat"><div class="hero-stat-num">48K+</div><div class="hero-stat-label">Active Listings</div><div class="hero-stat-divider"></div></div>
    <div class="hero-stat"><div class="hero-stat-num">120+</div><div class="hero-stat-label">Brands</div><div class="hero-stat-divider"></div></div>
    <div class="hero-stat"><div class="hero-stat-num">4.9★</div><div class="hero-stat-label">Avg Rating</div><div class="hero-stat-divider"></div></div>
  </div>
</section>

<!-- BRANDS STRIP -->
<div class="brands-strip">
  <div class="brands-label">Trusted brands on DriveHub</div>
  <div class="brands-scroll">
    <span class="brand-name">Toyota</span><span class="brand-name">BMW</span><span class="brand-name">Mercedes</span><span class="brand-name">Jeep</span><span class="brand-name">Land Rover</span><span class="brand-name">Harley-Davidson</span><span class="brand-name">Volkswagen</span><span class="brand-name">Ford</span><span class="brand-name">Audi</span><span class="brand-name">Porsche</span><span class="brand-name">Honda</span><span class="brand-name">Nissan</span>
    <span class="brand-name">Toyota</span><span class="brand-name">BMW</span><span class="brand-name">Mercedes</span><span class="brand-name">Jeep</span><span class="brand-name">Land Rover</span><span class="brand-name">Harley-Davidson</span><span class="brand-name">Volkswagen</span><span class="brand-name">Ford</span><span class="brand-name">Audi</span><span class="brand-name">Porsche</span><span class="brand-name">Honda</span><span class="brand-name">Nissan</span>
  </div>
</div>

<!-- CATEGORIES -->
<section id="categories">
  <div class="section-header">
    <div>
      <p class="section-eyebrow">Shop by Type</p>
      <h2 class="section-title">BROWSE CATEGORIES</h2>
    </div>
    <a href="#" class="view-all">View All Listings →</a>
  </div>
  <div class="car-grid">
    <?php if (!empty($db_cars)): ?>
      <?php foreach ($db_cars as $car):
        $bc = ''; $bl = '';
        if ($car['badge']==='new')  { $bc='badge-new';  $bl='NEW';  }
        if ($car['badge']==='hot')  { $bc='badge-hot';  $bl='HOT';  }
        if ($car['badge']==='sale') { $bc='badge-sale'; $bl='DEAL'; }
        $en = addslashes(htmlspecialchars($car['car_name']));
        $eb = addslashes(htmlspecialchars($car['brand']));
        $ed = addslashes(htmlspecialchars($car['description']));
        $ec = addslashes(htmlspecialchars($car['category']));
        $ep = addslashes(htmlspecialchars($car['price']));
        $ee = addslashes(htmlspecialchars($car['engine'] ?: '–'));
        $ew = addslashes(htmlspecialchars($car['power']  ?: '–'));
        $ev = addslashes(htmlspecialchars($car['drive']  ?: '–'));
      ?>
      <div class="car-card" onclick="openModal('<?php echo $en;?>','<?php echo $eb;?>','<?php echo $ed;?>','<?php echo $ec;?>','<?php echo $ep;?>','<?php echo $ee;?>','<?php echo $ew;?>','<?php echo $ev;?>','<?php echo $car['year'];?> | <?php echo number_format($car['kms']);?> km','<?php echo $bc;?>')">
        <?php if ($bl): ?><span class="car-badge <?php echo $bc;?>"><?php echo $bl;?></span><?php endif; ?>
        <div class="car-img">
          <?php if ($car['image_path'] && file_exists($car['image_path'])): ?>
            <img src="<?php echo htmlspecialchars($car['image_path']);?>" alt=""/>
          <?php else: ?>
            <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"/><circle cx="200" cy="300" r="44"/><circle cx="620" cy="300" r="44"/><circle cx="200" cy="300" r="28" fill="#111"/><circle cx="620" cy="300" r="28" fill="#111"/></svg>
            <span class="car-img-label">No Image</span>
          <?php endif; ?>
        </div>
        <div class="car-body">
          <div class="car-brand"><?php echo htmlspecialchars($car['brand']);?></div>
          <div class="car-name"><?php echo htmlspecialchars($car['car_name']);?></div>
          <div class="car-desc"><?php echo htmlspecialchars(mb_strimwidth($car['description'],0,100,'…'));?></div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val"><?php echo htmlspecialchars($car['engine']?:'–');?></div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val"><?php echo htmlspecialchars($car['power']?:'–');?></div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val"><?php echo number_format($car['kms']);?></div><div class="car-spec-key">KM</div></div>
            <div class="car-spec"><div class="car-spec-val"><?php echo $car['year'];?></div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price"><?php echo htmlspecialchars($car['price']);?> <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
          <?php if ($car['location']): ?>
          <div class="car-location">
            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <?php echo htmlspecialchars($car['location']);?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state">
        <svg class="empty-state-icon" width="80" height="80" fill="none" stroke="white" stroke-width="1.5" viewBox="0 0 24 24"><path d="M19 17H5c-1 0-2-.9-2-2V7c0-1.1.9-2 2-2h14c1.1 0 2 .9 2 2v8c0 1.1-.9 2-2 2z"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/><path d="M3 11h18"/></svg>
        <div class="empty-state-title">No Listings Yet</div>
        <div class="empty-state-desc">Cars added from the admin panel will appear here.</div>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- FEATURED -->
<section id="featured">
  <div class="section-header">
    <div><p class="section-eyebrow">Editor's Picks</p><h2 class="section-title">FEATURED THIS WEEK</h2></div>
    <a href="#" class="view-all">See All Featured →</a>
  </div>

  <?php if (!empty($featured_cars)): ?>
  <div class="featured-grid">

    <?php
    $f  = $featured_cars[0];
    $bc = $f['badge']==='new' ? 'badge-new' : ($f['badge']==='hot' ? 'badge-hot' : ($f['badge']==='sale' ? 'badge-sale' : ''));
    ?>
    <div class="featured-main car-card" onclick="openModal(
      '<?php echo addslashes(htmlspecialchars($f['car_name']));?>',
      '<?php echo addslashes(htmlspecialchars($f['brand']));?>',
      '<?php echo addslashes(htmlspecialchars($f['description']));?>',
      '<?php echo addslashes(htmlspecialchars($f['category']));?>',
      '<?php echo addslashes(htmlspecialchars($f['price']));?>',
      '<?php echo addslashes(htmlspecialchars($f['engine']?:'–'));?>',
      '<?php echo addslashes(htmlspecialchars($f['power']?:'–'));?>',
      '<?php echo addslashes(htmlspecialchars($f['drive']?:'–'));?>',
      '<?php echo $f['year'];?> | <?php echo number_format($f['kms']);?> km',
      '<?php echo $bc;?>')">
      <span class="featured-tag">FEATURED</span>
      <div class="car-img">
        <?php if ($f['image_path'] && file_exists($f['image_path'])): ?>
          <img src="<?php echo htmlspecialchars($f['image_path']);?>" alt=""/>
        <?php else: ?>
          <svg class="car-img-icon" width="120" height="75" viewBox="0 0 800 400" fill="white"><path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"/><circle cx="200" cy="300" r="44"/><circle cx="620" cy="300" r="44"/><circle cx="200" cy="300" r="28" fill="#111"/><circle cx="620" cy="300" r="28" fill="#111"/></svg>
          <span class="car-img-label">No Image</span>
        <?php endif; ?>
      </div>
      <div class="car-body">
        <div class="car-brand"><?php echo htmlspecialchars($f['brand']);?></div>
        <div class="car-name"><?php echo htmlspecialchars($f['car_name']);?></div>
        <div class="car-desc"><?php echo htmlspecialchars(mb_strimwidth($f['description'],0,120,'…'));?></div>
        <div class="car-specs">
          <div class="car-spec"><div class="car-spec-val"><?php echo htmlspecialchars($f['engine']?:'–');?></div><div class="car-spec-key">Engine</div></div>
          <div class="car-spec"><div class="car-spec-val"><?php echo htmlspecialchars($f['power']?:'–');?></div><div class="car-spec-key">Power</div></div>
          <div class="car-spec"><div class="car-spec-val"><?php echo htmlspecialchars($f['drive']?:'–');?></div><div class="car-spec-key">Drive</div></div>
          <div class="car-spec"><div class="car-spec-val"><?php echo $f['year'];?></div><div class="car-spec-key">Year</div></div>
        </div>
        <div class="car-footer">
          <div class="car-price"><?php echo htmlspecialchars($f['price']);?> <span>/ neg.</span></div>
          <button class="car-btn">View Details →</button>
        </div>
      </div>
    </div>

    <?php for ($i = 1; $i <= 2; $i++):
      if (!isset($featured_cars[$i])) continue;
      $s  = $featured_cars[$i];
      $sbc = $s['badge']==='new' ? 'badge-new' : ($s['badge']==='hot' ? 'badge-hot' : ($s['badge']==='sale' ? 'badge-sale' : ''));
    ?>
    <div class="featured-side car-card" onclick="openModal(
      '<?php echo addslashes(htmlspecialchars($s['car_name']));?>',
      '<?php echo addslashes(htmlspecialchars($s['brand']));?>',
      '<?php echo addslashes(htmlspecialchars($s['description']));?>',
      '<?php echo addslashes(htmlspecialchars($s['category']));?>',
      '<?php echo addslashes(htmlspecialchars($s['price']));?>',
      '<?php echo addslashes(htmlspecialchars($s['engine']?:'–'));?>',
      '<?php echo addslashes(htmlspecialchars($s['power']?:'–'));?>',
      '<?php echo addslashes(htmlspecialchars($s['drive']?:'–'));?>',
      '<?php echo $s['year'];?> | <?php echo number_format($s['kms']);?> km',
      '<?php echo $sbc;?>')">
      <div class="car-img">
        <?php if ($s['image_path'] && file_exists($s['image_path'])): ?>
          <img src="<?php echo htmlspecialchars($s['image_path']);?>" alt=""/>
        <?php else: ?>
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"/><circle cx="200" cy="300" r="44"/><circle cx="620" cy="300" r="44"/><circle cx="200" cy="300" r="28" fill="#111"/><circle cx="620" cy="300" r="28" fill="#111"/></svg>
          <span class="car-img-label">No Image</span>
        <?php endif; ?>
      </div>
      <div class="car-body">
        <div class="car-brand"><?php echo htmlspecialchars($s['brand']);?></div>
        <div class="car-name"><?php echo htmlspecialchars($s['car_name']);?></div>
        <div class="car-desc"><?php echo htmlspecialchars(mb_strimwidth($s['description'],0,80,'…'));?></div>
        <div class="car-footer">
          <div class="car-price"><?php echo htmlspecialchars($s['price']);?> <span>/ neg.</span></div>
          <button class="car-btn">View →</button>
        </div>
      </div>
    </div>
    <?php endfor; ?>

  </div>
  <?php else: ?>
  <div style="padding:60px 0;text-align:center;border:1px dashed rgba(255,255,255,0.08);color:#333;font-size:13px;letter-spacing:0.1em;text-transform:uppercase;">
    No featured cars yet — add listings from the admin panel.
  </div>
  <?php endif; ?>
</section>

<!-- AI PREDICTOR -->
<section id="ai-predictor">
  <div class="ai-inner">
    <div class="ai-left">
      <p class="section-eyebrow">Machine Learning</p>
      <h2 class="section-title">KNOW YOUR<br>CAR'S <span style="color:var(--red)">TRUE WORTH</span></h2>
      <p style="font-size:15px;font-weight:300;color:rgba(255,255,255,0.45);line-height:1.7;margin-top:20px;max-width:440px;">
        Our AI model — trained on 300+ real vehicle transactions using XGBoost regression — delivers instant, data-driven price estimates. Enter any car's specs and get a market value in seconds.
      </p>
      <div class="ai-model-stat">
        <div><div class="ai-stat-num">96<span>%</span></div><div class="ai-stat-label">R² Accuracy</div></div>
        <div><div class="ai-stat-num">301<span>+</span></div><div class="ai-stat-label">Training Records</div></div>
        <div><div class="ai-stat-num">3</div><div class="ai-stat-label">Models Compared</div></div>
      </div>
      <div class="ai-cta-block">
        <a href="predictor.html" class="ai-cta-btn">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z"/><path d="M12 8v4l3 3"/></svg>
          TRY THE PREDICTOR
        </a>
        <div class="ai-cta-note">Free · No signup required<br>Instant estimate in seconds</div>
      </div>
    </div>
    <div class="ai-right">
      <div class="ai-feature-card">
        <div class="ai-feature-icon"><svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3h18v4H3zM3 10h18v4H3zM3 17h18v4H3z"/></svg></div>
        <div><div class="ai-feature-title">XGBoost Regressor</div><div class="ai-feature-desc">The winning model from a 3-way comparison — selected for highest R² and lowest mean absolute error on the test set.</div></div>
      </div>
      <div class="ai-feature-card">
        <div class="ai-feature-icon"><svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></div>
        <div><div class="ai-feature-title">8 Smart Inputs</div><div class="ai-feature-desc">Year, original price, mileage, fuel type, transmission, seller type, and ownership history — all weighted by real market patterns.</div></div>
      </div>
      <div class="ai-feature-card">
        <div class="ai-feature-icon"><svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
        <div><div class="ai-feature-title">Dual Prediction Mode</div><div class="ai-feature-desc">Get an exact USD price or a market tier (Budget → Luxury) — with a confidence bar and contextual AI insight on every result.</div></div>
      </div>
      <div class="ai-feature-card">
        <div class="ai-feature-icon"><svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div><div class="ai-feature-title">Built for DriveHub</div><div class="ai-feature-desc">Trained on Lebanese-market USD prices. Not a generic tool — a model calibrated to the vehicles and valuations you actually see here.</div></div>
      </div>
    </div>
  </div>
</section>

<!-- WHY US -->
<section id="why">
  <div class="section-header"><div><p class="section-eyebrow">Why DriveHub</p><h2 class="section-title">THE DRIVEHUB DIFFERENCE</h2></div></div>
  <div class="why-grid">
    <div class="why-card"><div class="why-icon"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4"/><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z"/></svg></div><div class="why-title">Verified Listings</div><div class="why-desc">Every vehicle goes through a 50-point inspection. No fakes, no surprises — just honest listings.</div></div>
    <div class="why-card"><div class="why-icon"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div><div class="why-title">Buyer Protection</div><div class="why-desc">Secure escrow payments, full refund guarantee within 7 days if the car isn't as described.</div></div>
    <div class="why-card"><div class="why-icon"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg></div><div class="why-title">Instant Response</div><div class="why-desc">Connect directly with sellers. Average response time under 2 hours on all premium listings.</div></div>
    <div class="why-card"><div class="why-icon"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div><div class="why-title">Nationwide Delivery</div><div class="why-desc">We partner with licensed transporters to deliver your car safely to any address in Lebanon.</div></div>
  </div>
</section>

<!-- NEWSLETTER -->
<div class="newsletter">
  <div class="newsletter-inner">
    <span class="section-eyebrow">Stay in the Loop</span>
    <h2 class="section-title">NEW ARRIVALS.<br>FIRST.</h2>
    <p class="newsletter-sub">Get notified the moment your dream car hits the market. Weekly digest, zero spam.</p>
    <div class="newsletter-form">
      <input class="newsletter-input" type="email" placeholder="your@email.com"/>
      <button class="newsletter-btn">SUBSCRIBE</button>
    </div>
  </div>
</div>

<!-- FOOTER -->
<footer>
  <div class="footer-top">
    <div class="footer-brand">
      <div class="nav-logo"><div class="logo-mark"><span>⬡</span></div><span class="logo-name">DriveHub</span></div>
      <p>Lebanon's premier automotive marketplace. Connecting buyers and sellers with verified listings since 2020.</p>
    </div>
    <div><div class="footer-col-title">Browse</div><ul class="footer-links"><li><a href="#">4X4 / SUV</a></li><li><a href="#">2 Doors</a></li><li><a href="#">Sedan</a></li><li><a href="#">Bus & Van</a></li><li><a href="#">Motorcycles</a></li><li><a href="#">Electric</a></li></ul></div>
    <div><div class="footer-col-title">Company</div><ul class="footer-links"><li><a href="About.html">About Us</a></li><li><a href="hiw.html">How It Works</a></li><li><a href="#">List Your Car</a></li><li><a href="Careers.html">Careers</a></li><li><a href="Press.html">Press</a></li></ul></div>
    <div><div class="footer-col-title">Support</div><ul class="footer-links"><li><a href="help.html">Help Center</a></li><li><a href="Contactus.html">Contact Us</a></li><li><a href="Support.html">Buyer Guide</a></li><li><a href="Support.html">Seller Guide</a></li><li><a href="Support.html">Report an Issue</a></li><li><a href="predictor.html">AI Predictor</a></li></ul></div>
  </div>
  <div class="footer-bottom">
    <div class="footer-copy">© 2026 <span>DriveHub</span>. All rights reserved.</div>
    <div class="footer-legal"><a href="#">Privacy Policy</a><a href="#">Terms of Service</a><a href="#">Cookie Policy</a></div>
  </div>
</footer>

<!-- MODAL -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModalOutside(event)">
  <div class="modal" id="modal">
    <button class="modal-close" onclick="closeModal()">✕</button>
    <div class="modal-img" id="modalImg">
      <svg class="car-img-icon" width="100" height="62" viewBox="0 0 800 400" fill="white"><path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"/><circle cx="200" cy="300" r="44"/><circle cx="620" cy="300" r="44"/><circle cx="200" cy="300" r="28" fill="#111"/><circle cx="620" cy="300" r="28" fill="#111"/></svg>
      <span class="car-img-label">No Image</span>
    </div>
    <div class="modal-body">
      <div class="modal-brand" id="modalBrand">Brand</div>
      <div class="modal-name" id="modalName">Car Name</div>
      <div class="modal-desc" id="modalDesc">Description</div>
      <div class="modal-specs" id="modalSpecs"></div>
      <div class="modal-footer">
        <div>
          <div class="modal-price" id="modalPrice">$0 <span>/ negotiable</span></div>
          <div style="font-size:11px;color:var(--muted);margin-top:4px;" id="modalCat">Category</div>
        </div>
        <div class="modal-actions">
          <button class="modal-btn-sec">Save ♡</button>
          <button class="modal-btn-primary">Contact Seller</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function openModal(name, brand, desc, cat, price, spec1, spec2, spec3, spec4, badgeClass) {
    document.getElementById('modalName').textContent  = name;
    document.getElementById('modalBrand').textContent = brand;
    document.getElementById('modalDesc').textContent  = desc;
    document.getElementById('modalCat').textContent   = cat;
    document.getElementById('modalPrice').innerHTML   = price + ' <span>/ negotiable</span>';
    const specLabels = ['Engine','Power','Drive','Year'];
    const specVals   = [spec1, spec2, spec3, spec4];
    document.getElementById('modalSpecs').innerHTML = specVals.map((v,i) => `
      <div class="modal-spec">
        <div class="modal-spec-val">${v}</div>
        <div class="modal-spec-key">${specLabels[i]}</div>
      </div>`).join('');
    document.getElementById('modalOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeModal() {
    document.getElementById('modalOverlay').classList.remove('open');
    document.body.style.overflow = '';
  }
  function closeModalOutside(e) {
    if (e.target === document.getElementById('modalOverlay')) closeModal();
  }
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
  const sections = document.querySelectorAll('section[id], div[id]');
  window.addEventListener('scroll', () => {
    let current = '';
    sections.forEach(s => { if (window.scrollY >= s.offsetTop - 100) current = s.id; });
    document.querySelectorAll('.nav-links a').forEach(a => {
      a.classList.remove('active');
      if (a.getAttribute('href') === '#' + current) a.classList.add('active');
    });
  });
</script>
</body>
</html>