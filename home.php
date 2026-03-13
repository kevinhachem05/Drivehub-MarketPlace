<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}
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

    body {
      background: var(--black);
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      overflow-x: hidden;
    }

    /* ── SCROLLBAR ── */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: var(--off-black); }
    ::-webkit-scrollbar-thumb { background: #333; border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--red); }

    /* ══════════════════════════════════════
       NAV
    ══════════════════════════════════════ */
    nav {
      position: fixed; top: 0; left: 0; right: 0; z-index: 100;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 56px;
      height: 68px;
      background: rgba(10,10,10,0.88);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border);
    }

    .nav-logo {
      display: flex; align-items: center; gap: 10px;
      text-decoration: none;
    }

    .logo-mark {
      width: 32px; height: 32px;
      background: var(--red);
      clip-path: polygon(0 0, 100% 0, 100% 65%, 50% 100%, 0 65%);
      display: flex; align-items: center; justify-content: center;
    }
    .logo-mark span { font-size: 13px; color:#fff; font-weight:700; margin-bottom:5px; }

    .logo-name {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 20px; letter-spacing: 0.1em;
      color: var(--white);
    }

    .nav-links {
      display: flex; align-items: center; gap: 32px;
      list-style: none;
    }

    .nav-links a {
      font-size: 12px; font-weight: 500;
      letter-spacing: 0.12em; text-transform: uppercase;
      color: var(--muted); text-decoration: none;
      transition: color 0.2s;
    }
    .nav-links a:hover { color: var(--white); }
    .nav-links a.active { color: var(--red); }

    .nav-cta {
      display: flex; align-items: center; gap: 12px;
    }

    .btn-outline {
      padding: 9px 20px;
      background: transparent;
      border: 1px solid var(--border);
      border-radius: 5px;
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      font-size: 13px; cursor: pointer;
      transition: border-color 0.2s, background 0.2s;
      text-decoration: none;
    }
    .btn-outline:hover { border-color: var(--border-hover); background: #1c1c1c; }

    .btn-red {
      padding: 9px 22px;
      background: var(--red);
      border: none; border-radius: 5px;
      color: #fff;
      font-family: 'Bebas Neue', sans-serif;
      font-size: 15px; letter-spacing: 0.1em;
      cursor: pointer; text-decoration: none;
      transition: background 0.2s;
    }
    .btn-red:hover { background: var(--red-dark); }

    /* ══════════════════════════════════════
       HERO
    ══════════════════════════════════════ */
    .hero {
      position: relative;
      height: 100vh; min-height: 600px;
      display: flex; align-items: flex-end;
      overflow: hidden;
    }

    .hero-bg {
      position: absolute; inset: 0;
      background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 40%, #2a1510 100%);
    }

    /* big decorative car SVG */
    .hero-car {
      position: absolute; right: -40px; bottom: 0;
      width: 65%; opacity: 0.07;
      pointer-events: none;
    }

    /* speed lines */
    .hero-lines {
      position: absolute; inset: 0;
      background-image: repeating-linear-gradient(
        -60deg, transparent, transparent 40px,
        rgba(255,255,255,0.012) 40px, rgba(255,255,255,0.012) 41px
      );
    }

    /* bottom gradient fade */
    .hero::after {
      content:''; position:absolute; bottom:0; left:0; right:0; height:200px;
      background: linear-gradient(to top, var(--black), transparent);
    }

    .hero-content {
      position: relative; z-index: 2;
      padding: 0 80px 100px;
      animation: slideUp 0.9s cubic-bezier(.22,1,.36,1) 0.1s both;
    }

    @keyframes slideUp {
      from { opacity:0; transform:translateY(40px); }
      to   { opacity:1; transform:translateY(0); }
    }

    .hero-eyebrow {
      font-size: 11px; font-weight: 500;
      letter-spacing: 0.35em; text-transform: uppercase;
      color: var(--red); margin-bottom: 16px;
    }

    .hero-title {
      font-family: 'Bebas Neue', sans-serif;
      font-size: clamp(64px, 9vw, 130px);
      line-height: 0.9; color: var(--white);
      letter-spacing: 0.02em;
    }
    .hero-title span { color: var(--red); }

    .hero-desc {
      margin-top: 24px;
      font-size: 15px; font-weight: 300;
      color: rgba(255,255,255,0.45);
      max-width: 400px; line-height: 1.7;
    }

    .hero-actions {
      display: flex; align-items: center; gap: 16px;
      margin-top: 36px;
    }

    .hero-btn {
      padding: 16px 36px;
      background: var(--red); border: none; border-radius: 6px;
      color: #fff;
      font-family: 'Bebas Neue', sans-serif;
      font-size: 18px; letter-spacing: 0.12em;
      cursor: pointer;
      position: relative; overflow: hidden;
      transition: background 0.2s, transform 0.1s;
    }
    .hero-btn::after {
      content:''; position:absolute; inset:0;
      background: linear-gradient(to right, transparent, rgba(255,255,255,0.12), transparent);
      transform: translateX(-100%); transition: transform 0.5s;
    }
    .hero-btn:hover { background: var(--red-dark); }
    .hero-btn:hover::after { transform: translateX(100%); }
    .hero-btn:active { transform: scale(0.98); }

    .hero-btn-ghost {
      padding: 16px 28px;
      background: transparent;
      border: 1px solid var(--border-hover);
      border-radius: 6px; color: var(--text);
      font-family: 'DM Sans', sans-serif;
      font-size: 14px; cursor: pointer;
      transition: border-color 0.2s, background 0.2s;
    }
    .hero-btn-ghost:hover { background: #1c1c1c; border-color: rgba(255,255,255,0.3); }

    .hero-stats {
      position: absolute; right: 80px; bottom: 100px; z-index: 2;
      display: flex; flex-direction: column; gap: 28px;
      animation: slideUp 0.9s cubic-bezier(.22,1,.36,1) 0.3s both;
    }

    .hero-stat { text-align: right; }
    .hero-stat-num {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 36px; color: var(--white); letter-spacing: 0.05em;
    }
    .hero-stat-label {
      font-size: 10px; color: var(--muted);
      letter-spacing: 0.2em; text-transform: uppercase;
    }
    .hero-stat-divider {
      width: 40px; height: 1px;
      background: var(--red); margin-left: auto; margin-top: 6px;
    }

    /* ══════════════════════════════════════
       SECTION SHARED
    ══════════════════════════════════════ */
    section { padding: 100px 80px; }

    .section-eyebrow {
      font-size: 11px; font-weight: 500;
      letter-spacing: 0.3em; text-transform: uppercase;
      color: var(--red); margin-bottom: 10px;
    }

    .section-title {
      font-family: 'Bebas Neue', sans-serif;
      font-size: clamp(36px, 4vw, 56px);
      color: var(--white); letter-spacing: 0.03em;
      line-height: 1;
    }

    .section-header {
      display: flex; align-items: flex-end;
      justify-content: space-between;
      margin-bottom: 48px;
    }

    .view-all {
      font-size: 12px; font-weight: 500;
      letter-spacing: 0.12em; text-transform: uppercase;
      color: var(--muted); text-decoration: none;
      border-bottom: 1px solid var(--border);
      padding-bottom: 2px;
      transition: color 0.2s, border-color 0.2s;
      white-space: nowrap;
    }
    .view-all:hover { color: var(--red); border-color: var(--red); }

    /* ══════════════════════════════════════
       CATEGORY TABS
    ══════════════════════════════════════ */
    #categories { background: var(--off-black); padding: 80px; }

    .cat-tabs {
      display: flex; gap: 8px; flex-wrap: wrap;
      margin-bottom: 56px;
    }

    .cat-tab {
      display: flex; align-items: center; gap: 8px;
      padding: 10px 20px;
      background: transparent;
      border: 1px solid var(--border);
      border-radius: 4px;
      color: var(--muted);
      font-family: 'DM Sans', sans-serif;
      font-size: 13px; font-weight: 500;
      letter-spacing: 0.06em; text-transform: uppercase;
      cursor: pointer;
      transition: all 0.2s;
    }
    .cat-tab:hover { border-color: var(--border-hover); color: var(--text); background: #1e1e1e; }
    .cat-tab.active {
      background: var(--red); border-color: var(--red);
      color: #fff;
    }
    .cat-tab .cat-count {
      font-size: 10px; opacity: 0.7;
      background: rgba(255,255,255,0.15);
      padding: 1px 6px; border-radius: 10px;
    }

    /* category sections */
    .cat-section { display: none; }
    .cat-section.active { display: block; }

    /* ══════════════════════════════════════
       CAR GRID
    ══════════════════════════════════════ */
    .car-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 2px;
    }

    .car-card {
      background: var(--card);
      border: 1px solid var(--border);
      overflow: hidden;
      cursor: pointer;
      transition: border-color 0.25s, transform 0.25s;
      position: relative;
    }
    .car-card:hover {
      border-color: var(--red);
      transform: translateY(-3px);
      z-index: 2;
    }

    /* badge */
    .car-badge {
      position: absolute; top: 14px; left: 14px; z-index: 3;
      padding: 4px 10px;
      font-size: 10px; font-weight: 500;
      letter-spacing: 0.15em; text-transform: uppercase;
      border-radius: 3px;
    }
    .badge-new { background: var(--red); color: #fff; }
    .badge-hot { background: #e8891a; color: #fff; }
    .badge-sale { background: #2a9d5c; color: #fff; }

    /* image placeholder */
    .car-img {
      width: 100%; aspect-ratio: 16/10;
      background: #111;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      gap: 10px;
      border-bottom: 1px solid var(--border);
      position: relative; overflow: hidden;
    }

    .car-img::after {
      content: '';
      position: absolute; inset: 0;
      background: linear-gradient(to bottom, transparent 60%, rgba(0,0,0,0.4) 100%);
    }

    .car-img-icon {
      opacity: 0.12;
    }

    .car-img-label {
      font-size: 10px; letter-spacing: 0.2em;
      text-transform: uppercase; color: #333;
    }

    .car-body {
      padding: 20px 22px 24px;
    }

    .car-brand {
      font-size: 10px; font-weight: 500;
      letter-spacing: 0.2em; text-transform: uppercase;
      color: var(--red); margin-bottom: 4px;
    }

    .car-name {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 22px; letter-spacing: 0.04em;
      color: var(--white); margin-bottom: 8px;
    }

    .car-desc {
      font-size: 13px; font-weight: 300;
      color: var(--muted); line-height: 1.6;
      margin-bottom: 16px;
    }

    .car-specs {
      display: flex; gap: 16px;
      padding: 12px 0;
      border-top: 1px solid var(--border);
      border-bottom: 1px solid var(--border);
      margin-bottom: 16px;
    }

    .car-spec {
      display: flex; flex-direction: column; gap: 2px;
    }
    .car-spec-val {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 16px; color: var(--white); letter-spacing: 0.05em;
    }
    .car-spec-key {
      font-size: 9px; color: var(--muted);
      letter-spacing: 0.15em; text-transform: uppercase;
    }

    .car-footer {
      display: flex; align-items: center; justify-content: space-between;
    }

    .car-price {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 26px; color: var(--white);
      letter-spacing: 0.04em;
    }
    .car-price span {
      font-family: 'DM Sans', sans-serif;
      font-size: 12px; font-weight: 300;
      color: var(--muted); letter-spacing: 0;
    }

    .car-btn {
      padding: 9px 18px;
      background: transparent;
      border: 1px solid var(--border);
      border-radius: 4px;
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      font-size: 12px; cursor: pointer;
      transition: all 0.2s;
    }
    .car-btn:hover { background: var(--red); border-color: var(--red); color: #fff; }

    /* ══════════════════════════════════════
       FEATURED SECTION (big card)
    ══════════════════════════════════════ */
    #featured { background: var(--black); }

    .featured-grid {
      display: grid;
      grid-template-columns: 1.6fr 1fr;
      grid-template-rows: auto auto;
      gap: 2px;
    }

    .featured-main {
      grid-row: 1 / 3;
      background: var(--card);
      border: 1px solid var(--border);
      overflow: hidden;
      cursor: pointer;
      transition: border-color 0.25s;
      position: relative;
    }
    .featured-main:hover { border-color: var(--red); }

    .featured-main .car-img {
      aspect-ratio: 4/3;
    }

    .featured-tag {
      position: absolute; top: 0; left: 0;
      background: var(--red);
      padding: 8px 18px;
      font-family: 'Bebas Neue', sans-serif;
      font-size: 13px; letter-spacing: 0.15em;
      color: #fff;
    }

    .featured-main .car-name { font-size: 32px; }
    .featured-main .car-price { font-size: 34px; }

    .featured-side {
      background: var(--card);
      border: 1px solid var(--border);
      overflow: hidden;
      cursor: pointer;
      transition: border-color 0.25s;
    }
    .featured-side:hover { border-color: var(--red); }
    .featured-side .car-img { aspect-ratio: 16/9; }

    /* ══════════════════════════════════════
       BRANDS STRIP
    ══════════════════════════════════════ */
    .brands-strip {
      background: var(--panel);
      border-top: 1px solid var(--border);
      border-bottom: 1px solid var(--border);
      padding: 32px 80px;
      overflow: hidden;
    }

    .brands-label {
      font-size: 10px; letter-spacing: 0.25em;
      text-transform: uppercase; color: #333;
      margin-bottom: 20px;
    }

    .brands-scroll {
      display: flex; gap: 48px; align-items: center;
      animation: scrollBrands 28s linear infinite;
      width: max-content;
    }

    @keyframes scrollBrands {
      from { transform: translateX(0); }
      to   { transform: translateX(-50%); }
    }

    .brand-name {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 22px; letter-spacing: 0.1em;
      color: #2a2a2a; white-space: nowrap;
      transition: color 0.2s;
    }
    .brand-name:hover { color: var(--red); }

    /* ══════════════════════════════════════
       WHY US
    ══════════════════════════════════════ */
    #why { background: var(--off-black); }

    .why-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1px;
      border: 1px solid var(--border);
    }

    .why-card {
      padding: 40px 32px;
      background: var(--panel);
      border-right: 1px solid var(--border);
      transition: background 0.2s;
    }
    .why-card:last-child { border-right: none; }
    .why-card:hover { background: #1e1e1e; }

    .why-icon {
      width: 44px; height: 44px;
      background: rgba(232,52,26,0.1);
      border: 1px solid rgba(232,52,26,0.2);
      border-radius: 6px;
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 20px;
      color: var(--red);
    }

    .why-title {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 20px; letter-spacing: 0.06em;
      color: var(--white); margin-bottom: 10px;
    }

    .why-desc {
      font-size: 13px; font-weight: 300;
      color: var(--muted); line-height: 1.7;
    }

    /* ══════════════════════════════════════
       NEWSLETTER
    ══════════════════════════════════════ */
    .newsletter {
      background: var(--black);
      padding: 80px;
      border-top: 1px solid var(--border);
    }

    .newsletter-inner {
      max-width: 600px; margin: 0 auto; text-align: center;
    }

    .newsletter .section-eyebrow { display: block; margin-bottom: 10px; }
    .newsletter .section-title { margin-bottom: 16px; }

    .newsletter-sub {
      font-size: 14px; font-weight: 300;
      color: var(--muted); line-height: 1.7;
      margin-bottom: 32px;
    }

    .newsletter-form {
      display: flex; gap: 0;
    }

    .newsletter-input {
      flex: 1;
      padding: 14px 18px;
      background: var(--input-bg);
      border: 1px solid var(--border);
      border-right: none;
      border-radius: 6px 0 0 6px;
      color: var(--white);
      font-family: 'DM Sans', sans-serif;
      font-size: 14px; outline: none;
      transition: border-color 0.2s;
    }
    .newsletter-input::placeholder { color: #444; }
    .newsletter-input:focus { border-color: var(--red); }

    .newsletter-btn {
      padding: 14px 28px;
      background: var(--red); border: none;
      border-radius: 0 6px 6px 0;
      color: #fff;
      font-family: 'Bebas Neue', sans-serif;
      font-size: 16px; letter-spacing: 0.12em;
      cursor: pointer;
      transition: background 0.2s;
    }
    .newsletter-btn:hover { background: var(--red-dark); }

    /* ══════════════════════════════════════
       FOOTER
    ══════════════════════════════════════ */
    footer {
      background: var(--panel);
      border-top: 1px solid var(--border);
      padding: 56px 80px 32px;
    }

    .footer-top {
      display: grid;
      grid-template-columns: 1.5fr 1fr 1fr 1fr;
      gap: 60px;
      margin-bottom: 48px;
    }

    .footer-brand p {
      font-size: 13px; font-weight: 300;
      color: var(--muted); line-height: 1.7;
      margin-top: 14px; max-width: 240px;
    }

    .footer-col-title {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 14px; letter-spacing: 0.15em;
      color: var(--white); margin-bottom: 16px;
    }

    .footer-links { list-style: none; display: flex; flex-direction: column; gap: 10px; }
    .footer-links a {
      font-size: 13px; font-weight: 300;
      color: var(--muted); text-decoration: none;
      transition: color 0.2s;
    }
    .footer-links a:hover { color: var(--red); }

    .footer-bottom {
      display: flex; align-items: center; justify-content: space-between;
      padding-top: 24px;
      border-top: 1px solid var(--border);
    }

    .footer-copy {
      font-size: 12px; color: #333;
    }

    .footer-copy span { color: var(--red); }

    .footer-legal { display: flex; gap: 20px; }
    .footer-legal a { font-size: 12px; color: #333; text-decoration: none; transition: color 0.2s; }
    .footer-legal a:hover { color: var(--muted); }

    /* ══════════════════════════════════════
       MODAL
    ══════════════════════════════════════ */
    .modal-overlay {
      position: fixed; inset: 0; z-index: 200;
      background: rgba(0,0,0,0.85);
      backdrop-filter: blur(8px);
      display: flex; align-items: center; justify-content: center;
      opacity: 0; pointer-events: none;
      transition: opacity 0.3s;
    }
    .modal-overlay.open { opacity: 1; pointer-events: all; }

    .modal {
      background: var(--panel);
      border: 1px solid var(--border);
      width: 90%; max-width: 760px;
      max-height: 90vh; overflow-y: auto;
      border-radius: 4px;
      transform: translateY(20px);
      transition: transform 0.3s;
      position: relative;
    }
    .modal-overlay.open .modal { transform: translateY(0); }

    .modal-close {
      position: absolute; top: 16px; right: 16px;
      background: #1c1c1c; border: 1px solid var(--border);
      color: var(--muted); cursor: pointer;
      width: 32px; height: 32px; border-radius: 4px;
      font-size: 18px; display: flex; align-items: center; justify-content: center;
      transition: color 0.2s, border-color 0.2s;
    }
    .modal-close:hover { color: var(--white); border-color: var(--border-hover); }

    .modal-img {
      width: 100%; aspect-ratio: 16/9;
      background: #111;
      display: flex; align-items: center; justify-content: center;
      flex-direction: column; gap: 10px;
      border-bottom: 1px solid var(--border);
    }

    .modal-body { padding: 32px 36px 36px; }

    .modal-brand {
      font-size: 11px; font-weight: 500;
      letter-spacing: 0.2em; text-transform: uppercase;
      color: var(--red); margin-bottom: 4px;
    }

    .modal-name {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 36px; color: var(--white);
      letter-spacing: 0.04em; margin-bottom: 12px;
    }

    .modal-desc {
      font-size: 14px; font-weight: 300;
      color: var(--muted); line-height: 1.8;
      margin-bottom: 24px;
    }

    .modal-specs {
      display: grid; grid-template-columns: repeat(4, 1fr);
      gap: 1px; background: var(--border);
      border: 1px solid var(--border);
      margin-bottom: 28px;
    }

    .modal-spec {
      background: var(--card);
      padding: 16px 18px;
    }
    .modal-spec-val {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 22px; color: var(--white); letter-spacing: 0.04em;
    }
    .modal-spec-key {
      font-size: 10px; color: var(--muted);
      letter-spacing: 0.15em; text-transform: uppercase;
    }

    .modal-footer {
      display: flex; align-items: center; justify-content: space-between;
    }

    .modal-price {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 36px; color: var(--white); letter-spacing: 0.04em;
    }
    .modal-price span { font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 300; color: var(--muted); }

    .modal-actions { display: flex; gap: 10px; }

    .modal-btn-primary {
      padding: 13px 28px;
      background: var(--red); border: none; border-radius: 5px;
      color: #fff;
      font-family: 'Bebas Neue', sans-serif;
      font-size: 16px; letter-spacing: 0.1em;
      cursor: pointer;
      transition: background 0.2s;
    }
    .modal-btn-primary:hover { background: var(--red-dark); }

    .modal-btn-sec {
      padding: 13px 20px;
      background: transparent;
      border: 1px solid var(--border); border-radius: 5px;
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      font-size: 13px; cursor: pointer;
      transition: all 0.2s;
    }
    .modal-btn-sec:hover { border-color: var(--border-hover); background: #1c1c1c; }

    /* ══════════════════════════════════════
       RESPONSIVE
    ══════════════════════════════════════ */
    @media (max-width: 1100px) {
      .featured-grid { grid-template-columns: 1fr 1fr; }
      .why-grid { grid-template-columns: repeat(2,1fr); }
      .footer-top { grid-template-columns: 1fr 1fr; gap: 40px; }
    }

    @media (max-width: 768px) {
      nav { padding: 0 24px; }
      .nav-links { display: none; }
      section, #categories, .newsletter, footer { padding: 60px 24px; }
      .hero-content { padding: 0 24px 80px; }
      .hero-stats { display: none; }
      .featured-grid { grid-template-columns: 1fr; }
      .why-grid { grid-template-columns: 1fr; }
      .footer-top { grid-template-columns: 1fr; }
      .modal-specs { grid-template-columns: repeat(2,1fr); }
      .brands-strip { padding: 24px; }
    }
  </style>
</head>
<body>

<!-- ══════════════════════════════════════
     NAV
══════════════════════════════════════ -->
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
    <li><a href="#">Contact</a></li>
  </ul>

  <div class="nav-cta">
    <span class="welcome">Welcome <?php echo $_SESSION['first_name']; ?></span>
    <a href="login.html" class="btn-outline">Sign In</a>
    <a href="index.html" class="btn-red">List Your Car</a>
  </div>
</nav>


<!-- ══════════════════════════════════════
     HERO
══════════════════════════════════════ -->
<section class="hero">
  <div class="hero-bg"></div>
  <div class="hero-lines"></div>

  <!-- Decorative large car SVG -->
  <svg class="hero-car" viewBox="0 0 900 420" xmlns="http://www.w3.org/2000/svg">
    <path d="M40 310 Q70 200 180 175 L330 130 Q450 85 590 118 L770 158 Q860 182 880 235 L900 285 Q900 315 820 320 Q795 265 720 265 Q645 265 620 320 L300 320 Q275 265 200 265 Q125 265 100 320 Z" fill="white"/>
    <circle cx="200" cy="340" r="54" fill="white"/>
    <circle cx="720" cy="340" r="54" fill="white"/>
    <circle cx="200" cy="340" r="34" fill="#0a0a0a"/>
    <circle cx="720" cy="340" r="34" fill="#0a0a0a"/>
    <circle cx="200" cy="340" r="14" fill="white"/>
    <circle cx="720" cy="340" r="14" fill="white"/>
    <path d="M340 135 L365 185 L555 185 L580 135 Z" fill="#0a0a0a" opacity="0.5"/>
    <path d="M180 185 L205 135 L335 135 L360 185 Z" fill="#0a0a0a" opacity="0.5"/>
    <path d="M0 310 L40 310 L100 320 L0 320 Z" fill="white" opacity="0.3"/>
    <path d="M900 310 L860 310 L800 320 L900 320 Z" fill="white" opacity="0.3"/>
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
    <div class="hero-stat">
      <div class="hero-stat-num">48K+</div>
      <div class="hero-stat-label">Active Listings</div>
      <div class="hero-stat-divider"></div>
    </div>
    <div class="hero-stat">
      <div class="hero-stat-num">120+</div>
      <div class="hero-stat-label">Brands</div>
      <div class="hero-stat-divider"></div>
    </div>
    <div class="hero-stat">
      <div class="hero-stat-num">4.9★</div>
      <div class="hero-stat-label">Avg Rating</div>
      <div class="hero-stat-divider"></div>
    </div>
  </div>
</section>


<!-- ══════════════════════════════════════
     BRANDS STRIP
══════════════════════════════════════ -->
<div class="brands-strip">
  <div class="brands-label">Trusted brands on DriveHub</div>
  <div class="brands-scroll">
    <!-- duplicated for infinite loop -->
    <span class="brand-name">Toyota</span>
    <span class="brand-name">BMW</span>
    <span class="brand-name">Mercedes</span>
    <span class="brand-name">Jeep</span>
    <span class="brand-name">Land Rover</span>
    <span class="brand-name">Harley-Davidson</span>
    <span class="brand-name">Volkswagen</span>
    <span class="brand-name">Ford</span>
    <span class="brand-name">Audi</span>
    <span class="brand-name">Porsche</span>
    <span class="brand-name">Honda</span>
    <span class="brand-name">Nissan</span>
    <span class="brand-name">Toyota</span>
    <span class="brand-name">BMW</span>
    <span class="brand-name">Mercedes</span>
    <span class="brand-name">Jeep</span>
    <span class="brand-name">Land Rover</span>
    <span class="brand-name">Harley-Davidson</span>
    <span class="brand-name">Volkswagen</span>
    <span class="brand-name">Ford</span>
    <span class="brand-name">Audi</span>
    <span class="brand-name">Porsche</span>
    <span class="brand-name">Honda</span>
    <span class="brand-name">Nissan</span>
  </div>
</div>


<!-- ══════════════════════════════════════
     CATEGORIES
══════════════════════════════════════ -->
<section id="categories">
  <div class="section-header">
    <div>
      <p class="section-eyebrow">Shop by Type</p>
      <h2 class="section-title">BROWSE CATEGORIES</h2>
    </div>
    <a href="#" class="view-all">View All Listings →</a>
  </div>

  <!-- TABS -->
  <div class="cat-tabs">
    <button class="cat-tab active" onclick="switchCat('4x4',this)">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M1 10h22M5 6l2-4h10l2 4M1 14l4-4h14l4 4"/></svg>
      4X4 / SUV <span class="cat-count">1.2K</span>
    </button>
    <button class="cat-tab" onclick="switchCat('2doors',this)">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M1 12h22M6 8l2-4h8l2 4"/></svg>
      2 Doors <span class="cat-count">860</span>
    </button>
    <button class="cat-tab" onclick="switchCat('sedan',this)">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M1 12h22M4 8l2-5h12l2 5"/></svg>
      Sedan <span class="cat-count">2.1K</span>
    </button>
    <button class="cat-tab" onclick="switchCat('bus',this)">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="13" rx="2"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M2 10h20"/></svg>
      Bus / Van <span class="cat-count">340</span>
    </button>
    <button class="cat-tab" onclick="switchCat('moto',this)">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="5" cy="16" r="3"/><circle cx="19" cy="16" r="3"/><path d="M5 16L8 8h5l3 5h3M10 8l2-4"/></svg>
      Moto <span class="cat-count">720</span>
    </button>
    <button class="cat-tab" onclick="switchCat('electric',this)">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9z"/></svg>
      Electric <span class="cat-count">280</span>
    </button>
    <button class="cat-tab" onclick="switchCat('truck',this)">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="9" width="15" height="10"/><path d="M16 14h5l2 5H16V14z"/><circle cx="5.5" cy="19.5" r="1.5"/><circle cx="18.5" cy="19.5" r="1.5"/></svg>
      Truck <span class="cat-count">415</span>
    </button>
    <button class="cat-tab" onclick="switchCat('luxury',this)">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
      Luxury <span class="cat-count">530</span>
    </button>
  </div>

  <!-- ── 4X4 ── -->
  <div class="cat-section active" id="cat-4x4">
    <div class="car-grid">

      <div class="car-card" onclick="openModal('Toyota Land Cruiser 300','Toyota','The legendary Land Cruiser in its most capable form. Built for the harshest terrains with unmatched reliability.','4X4 / SUV','$89,000','V8 4.5L','285 HP','0–100 in 8.2s','7 Seats','badge-hot')">
        <span class="car-badge badge-hot">HOT</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"/><circle cx="200" cy="300" r="44"/><circle cx="620" cy="300" r="44"/><circle cx="200" cy="300" r="28" fill="#111"/><circle cx="620" cy="300" r="28" fill="#111"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Toyota</div>
          <div class="car-name">Land Cruiser 300</div>
          <div class="car-desc">V8 diesel beast built for any terrain. Full-time 4WD with crawl control.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">V8</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">285HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">4WD</div><div class="car-spec-key">Drive</div></div>
            <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$89,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

      <div class="car-card" onclick="openModal('Jeep Wrangler Rubicon','Jeep','The icon of off-road freedom. Removable doors and roof, trail-rated badge, unstoppable attitude.','4X4 / SUV','$62,500','V6 3.6L','285 HP','0–100 in 7.9s','5 Seats','badge-new')">
        <span class="car-badge badge-new">NEW</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"/><circle cx="200" cy="300" r="44"/><circle cx="620" cy="300" r="44"/><circle cx="200" cy="300" r="28" fill="#111"/><circle cx="620" cy="300" r="28" fill="#111"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Jeep</div>
          <div class="car-name">Wrangler Rubicon</div>
          <div class="car-desc">Trail-rated. Rock-crawling capability with Selec-Trac 4WD system.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">V6</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">285HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">4WD</div><div class="car-spec-key">Drive</div></div>
            <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$62,500 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

      <div class="car-card" onclick="openModal('Land Rover Defender 110','Land Rover','Modern reincarnation of the legendary Defender. Luxury meets unstoppable off-road capability.','4X4 / SUV','$74,000','I6 3.0L','400 HP','0–100 in 5.6s','5 Seats','badge-sale')">
        <span class="car-badge badge-sale">DEAL</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"/><circle cx="200" cy="300" r="44"/><circle cx="620" cy="300" r="44"/><circle cx="200" cy="300" r="28" fill="#111"/><circle cx="620" cy="300" r="28" fill="#111"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Land Rover</div>
          <div class="car-name">Defender 110</div>
          <div class="car-desc">Reborn legend. Air suspension, Terrain Response 2, and iconic silhouette.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">I6</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">400HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">AWD</div><div class="car-spec-key">Drive</div></div>
            <div class="car-spec"><div class="car-spec-val">2023</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$74,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

      <div class="car-card" onclick="openModal('Ford Bronco Wildtrak','Ford','The original adventurer is back. Go-anywhere attitude with modern tech and style.','4X4 / SUV','$51,000','V6 2.7L','330 HP','0–100 in 6.8s','5 Seats','badge-new')">
        <span class="car-badge badge-new">NEW</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"/><circle cx="200" cy="300" r="44"/><circle cx="620" cy="300" r="44"/><circle cx="200" cy="300" r="28" fill="#111"/><circle cx="620" cy="300" r="28" fill="#111"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Ford</div>
          <div class="car-name">Bronco Wildtrak</div>
          <div class="car-desc">G.O.A.T. modes — Goes Over Any Type of terrain. Sasquatch package available.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">V6</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">330HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">4WD</div><div class="car-spec-key">Drive</div></div>
            <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$51,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── 2 DOORS ── -->
  <div class="cat-section" id="cat-2doors">
    <div class="car-grid">

      <div class="car-card" onclick="openModal('Porsche 911 Carrera','Porsche','The eternal sports car. Rear-engine philosophy perfected over 60 years of evolution.','2 Doors','$115,000','Flat-6 3.0L','385 HP','0–100 in 4.2s','2 Seats','badge-hot')">
        <span class="car-badge badge-hot">HOT</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><path d="M80 290 Q90 220 170 200 L300 160 Q400 120 520 145 L680 175 Q760 195 775 235 L790 270 Q790 290 740 295 Q720 252 660 252 Q600 252 580 295 L260 295 Q240 252 180 252 Q120 252 105 295 Z"/><circle cx="180" cy="308" r="40"/><circle cx="660" cy="308" r="40"/><circle cx="180" cy="308" r="24" fill="#111"/><circle cx="660" cy="308" r="24" fill="#111"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Porsche</div>
          <div class="car-name">911 Carrera S</div>
          <div class="car-desc">Flat-six perfection. PDK transmission. The benchmark sports car of every generation.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">F6</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">385HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">RWD</div><div class="car-spec-key">Drive</div></div>
            <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$115,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

      <div class="car-card" onclick="openModal('BMW M4 Competition','BMW','The ultimate driving machine in coupe form. Track-ready with a twin-turbo straight-six.','2 Doors','$88,000','I6 3.0L TT','503 HP','0–100 in 3.9s','4 Seats','badge-new')">
        <span class="car-badge badge-new">NEW</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><path d="M80 290 Q90 220 170 200 L300 160 Q400 120 520 145 L680 175 Q760 195 775 235 L790 270 Q790 290 740 295 Q720 252 660 252 Q600 252 580 295 L260 295 Q240 252 180 252 Q120 252 105 295 Z"/><circle cx="180" cy="308" r="40"/><circle cx="660" cy="308" r="40"/><circle cx="180" cy="308" r="24" fill="#111"/><circle cx="660" cy="308" r="24" fill="#111"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">BMW</div>
          <div class="car-name">M4 Competition</div>
          <div class="car-desc">503 hp twin-turbo inline-six. M xDrive all-wheel drive. Carbon fiber roof standard.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">I6TT</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">503HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">AWD</div><div class="car-spec-key">Drive</div></div>
            <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$88,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

      <div class="car-card" onclick="openModal('Ford Mustang GT','Ford','American muscle. V8 thunder. A cultural icon reborn with modern chassis dynamics.','2 Doors','$43,000','V8 5.0L','450 HP','0–100 in 4.6s','4 Seats','badge-sale')">
        <span class="car-badge badge-sale">DEAL</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><path d="M80 290 Q90 220 170 200 L300 160 Q400 120 520 145 L680 175 Q760 195 775 235 L790 270 Q790 290 740 295 Q720 252 660 252 Q600 252 580 295 L260 295 Q240 252 180 252 Q120 252 105 295 Z"/><circle cx="180" cy="308" r="40"/><circle cx="660" cy="308" r="40"/><circle cx="180" cy="308" r="24" fill="#111"/><circle cx="660" cy="308" r="24" fill="#111"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Ford</div>
          <div class="car-name">Mustang GT</div>
          <div class="car-desc">Coyote 5.0 V8 roar. Independent rear suspension. Active valve exhaust.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">V8</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">450HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">RWD</div><div class="car-spec-key">Drive</div></div>
            <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$43,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

      <div class="car-card" onclick="openModal('Mercedes AMG GT 63','Mercedes-AMG','Four doors, supercar performance. The most practical AMG ever built — and the most thrilling.','2 Doors','$162,000','V8 4.0L TT','630 HP','0–100 in 3.2s','4 Seats','badge-hot')">
        <span class="car-badge badge-hot">HOT</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><path d="M80 290 Q90 220 170 200 L300 160 Q400 120 520 145 L680 175 Q760 195 775 235 L790 270 Q790 290 740 295 Q720 252 660 252 Q600 252 580 295 L260 295 Q240 252 180 252 Q120 252 105 295 Z"/><circle cx="180" cy="308" r="40"/><circle cx="660" cy="308" r="40"/><circle cx="180" cy="308" r="24" fill="#111"/><circle cx="660" cy="308" r="24" fill="#111"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Mercedes-AMG</div>
          <div class="car-name">AMG GT 63 S</div>
          <div class="car-desc">630hp bi-turbo V8. AMG Performance 4MATIC+. Rear-axle steering for precision.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">V8TT</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">630HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">AWD</div><div class="car-spec-key">Drive</div></div>
            <div class="car-spec"><div class="car-spec-val">2023</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$162,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── SEDAN ── -->
  <div class="cat-section" id="cat-sedan">
    <div class="car-grid">

      <div class="car-card" onclick="openModal('Mercedes S-Class S500','Mercedes-Benz','The pinnacle of the sedan world. An autonomous-capable luxury throne room on wheels.','Sedan','$120,000','V8 4.0L TT','429 HP','0–100 in 4.9s','5 Seats','badge-hot')">
        <span class="car-badge badge-hot">HOT</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"/><circle cx="200" cy="300" r="44"/><circle cx="620" cy="300" r="44"/><circle cx="200" cy="300" r="28" fill="#111"/><circle cx="620" cy="300" r="28" fill="#111"/><path d="M290 145 L310 185 L480 185 L500 145 Z" fill="#111" opacity="0.6"/><path d="M160 185 L180 145 L285 145 L305 185 Z" fill="#111" opacity="0.6"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Mercedes-Benz</div>
          <div class="car-name">S-Class S500</div>
          <div class="car-desc">Rear-axle steering, MBUX Hyperscreen, active suspension. The benchmark luxury sedan.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">V8</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">429HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">AWD</div><div class="car-spec-key">Drive</div></div>
            <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$120,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

      <div class="car-card" onclick="openModal('BMW 7 Series 750i','BMW','Flagship luxury and performance. Executive atmosphere with M Sport dynamics.','Sedan','$104,000','V8 4.4L TT','523 HP','0–100 in 4.4s','5 Seats','badge-new')">
        <span class="car-badge badge-new">NEW</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"/><circle cx="200" cy="300" r="44"/><circle cx="620" cy="300" r="44"/><circle cx="200" cy="300" r="28" fill="#111"/><circle cx="620" cy="300" r="28" fill="#111"/><path d="M290 145 L310 185 L480 185 L500 145 Z" fill="#111" opacity="0.6"/><path d="M160 185 L180 145 L285 145 L305 185 Z" fill="#111" opacity="0.6"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">BMW</div>
          <div class="car-name">7 Series 750i</div>
          <div class="car-desc">Theatre Screen in rear. 31" 8K display. Executive Lounge seating. xDrive standard.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">V8TT</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">523HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">AWD</div><div class="car-spec-key">Drive</div></div>
            <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$104,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

      <div class="car-card" onclick="openModal('Audi A8 L','Audi','Understated elegance. Quattro AWD, predictive active suspension, and an immaculate cabin.','Sedan','$98,000','V8 4.0L TT','460 HP','0–100 in 4.1s','5 Seats','badge-sale')">
        <span class="car-badge badge-sale">DEAL</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"/><circle cx="200" cy="300" r="44"/><circle cx="620" cy="300" r="44"/><circle cx="200" cy="300" r="28" fill="#111"/><circle cx="620" cy="300" r="28" fill="#111"/><path d="M290 145 L310 185 L480 185 L500 145 Z" fill="#111" opacity="0.6"/><path d="M160 185 L180 145 L285 145 L305 185 Z" fill="#111" opacity="0.6"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Audi</div>
          <div class="car-name">A8 L Quattro</div>
          <div class="car-desc">48V mild hybrid, air suspension, adaptive cruise. German engineering at its finest.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">V8TT</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">460HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">AWD</div><div class="car-spec-key">Drive</div></div>
            <div class="car-spec"><div class="car-spec-val">2023</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$98,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── BUS / VAN ── -->
  <div class="cat-section" id="cat-bus">
    <div class="car-grid">

      <div class="car-card" onclick="openModal('Mercedes Sprinter 319','Mercedes-Benz','The commercial benchmark. Available in passenger and cargo. Reliable workhorse for any fleet.','Bus / Van','$62,000','I6 3.0L','190 HP','–','15 Seats','badge-new')">
        <span class="car-badge badge-new">NEW</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><rect x="60" y="140" width="680" height="200" rx="30"/><circle cx="160" cy="360" r="44"/><circle cx="640" cy="360" r="44"/><circle cx="160" cy="360" r="28" fill="#111"/><circle cx="640" cy="360" r="28" fill="#111"/><rect x="100" y="160" width="120" height="80" rx="6" fill="#111" opacity="0.5"/><rect x="240" y="160" width="120" height="80" rx="6" fill="#111" opacity="0.5"/><rect x="380" y="160" width="120" height="80" rx="6" fill="#111" opacity="0.5"/><rect x="520" y="160" width="120" height="80" rx="6" fill="#111" opacity="0.5"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Mercedes-Benz</div>
          <div class="car-name">Sprinter 319</div>
          <div class="car-desc">High-roof 15-seater. MBUX infotainment, Active Brake Assist standard.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">I6</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">190HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">RWD</div><div class="car-spec-key">Drive</div></div>
            <div class="car-spec"><div class="car-spec-val">2023</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$62,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

      <div class="car-card" onclick="openModal('Toyota Hiace GL','Toyota','Japan reliability in bus form. Long wheelbase, 14 seats, perfect for daily transport.','Bus / Van','$38,000','I4 2.8L','150 HP','–','14 Seats','badge-hot')">
        <span class="car-badge badge-hot">HOT</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><rect x="60" y="140" width="680" height="200" rx="30"/><circle cx="160" cy="360" r="44"/><circle cx="640" cy="360" r="44"/><circle cx="160" cy="360" r="28" fill="#111"/><circle cx="640" cy="360" r="28" fill="#111"/><rect x="100" y="160" width="120" height="80" rx="6" fill="#111" opacity="0.5"/><rect x="240" y="160" width="120" height="80" rx="6" fill="#111" opacity="0.5"/><rect x="380" y="160" width="120" height="80" rx="6" fill="#111" opacity="0.5"/><rect x="520" y="160" width="120" height="80" rx="6" fill="#111" opacity="0.5"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Toyota</div>
          <div class="car-name">Hiace GL</div>
          <div class="car-desc">The most trusted passenger van in the market. Diesel efficiency, proven reliability.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">I4</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">150HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">RWD</div><div class="car-spec-key">Drive</div></div>
            <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$38,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

      <div class="car-card" onclick="openModal('Ford Transit Custom','Ford','Versatile. Intelligent. Built for business. Available as passenger shuttle or cargo.','Bus / Van','$44,000','I4 2.0L EcoBlue','170 HP','–','9 Seats','badge-sale')">
        <span class="car-badge badge-sale">DEAL</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><rect x="60" y="140" width="680" height="200" rx="30"/><circle cx="160" cy="360" r="44"/><circle cx="640" cy="360" r="44"/><circle cx="160" cy="360" r="28" fill="#111"/><circle cx="640" cy="360" r="28" fill="#111"/><rect x="100" y="160" width="120" height="80" rx="6" fill="#111" opacity="0.5"/><rect x="240" y="160" width="120" height="80" rx="6" fill="#111" opacity="0.5"/><rect x="380" y="160" width="120" height="80" rx="6" fill="#111" opacity="0.5"/><rect x="520" y="160" width="120" height="80" rx="6" fill="#111" opacity="0.5"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Ford</div>
          <div class="car-name">Transit Custom</div>
          <div class="car-desc">Smart cargo management, SYNC 4 infotainment, active safety suite included.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">I4</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">170HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">RWD</div><div class="car-spec-key">Drive</div></div>
            <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$44,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── MOTO ── -->
  <div class="cat-section" id="cat-moto">
    <div class="car-grid">

      <div class="car-card" onclick="openModal('Harley-Davidson Road King','Harley-Davidson','The quintessential American touring motorcycle. Milwaukee-Eight V-Twin rumble that never gets old.','Moto','$22,000','V-Twin 1868cc','90 HP','0–100 in 4.8s','2 Seats','badge-hot')">
        <span class="car-badge badge-hot">HOT</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><circle cx="200" cy="300" r="110"/><circle cx="600" cy="300" r="110"/><circle cx="200" cy="300" r="70" fill="#111"/><circle cx="600" cy="300" r="70" fill="#111"/><circle cx="200" cy="300" r="28"/><circle cx="600" cy="300" r="28"/><path d="M200 190 L320 160 L420 120 L500 130 L560 155 L600 190"/><path d="M310 160 L340 200 L400 200 L440 155"/><path d="M130 280 L200 190 M560 190 L630 240"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Harley-Davidson</div>
          <div class="car-name">Road King Special</div>
          <div class="car-desc">Milwaukee-Eight 114. Blacked-out styling. Premium audio. Made for the open road.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">V2</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">90HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">114ci</div><div class="car-spec-key">Displ.</div></div>
            <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$22,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

      <div class="car-card" onclick="openModal('Ducati Panigale V4','Ducati','MotoGP tech, road-legal. The most exciting production superbike money can buy.','Moto','$28,500','V4 1103cc','215 HP','0–100 in 2.9s','2 Seats','badge-new')">
        <span class="car-badge badge-new">NEW</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><circle cx="200" cy="300" r="110"/><circle cx="600" cy="300" r="110"/><circle cx="200" cy="300" r="70" fill="#111"/><circle cx="600" cy="300" r="70" fill="#111"/><circle cx="200" cy="300" r="28"/><circle cx="600" cy="300" r="28"/><path d="M200 190 L320 160 L420 120 L500 130 L560 155 L600 190"/><path d="M310 160 L340 200 L400 200 L440 155"/><path d="M130 280 L200 190 M560 190 L630 240"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Ducati</div>
          <div class="car-name">Panigale V4 S</div>
          <div class="car-desc">215hp Desmosedici Stradale engine. Öhlins electronic suspension. Track-focused beast.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">V4</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">215HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">1103cc</div><div class="car-spec-key">Displ.</div></div>
            <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$28,500 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

      <div class="car-card" onclick="openModal('BMW R 1250 GS','BMW','The adventure tourer that defined a genre. Pinnacle of long-distance motorcycling.','Moto','$18,000','Boxer 1254cc','136 HP','0–100 in 3.5s','2 Seats','badge-sale')">
        <span class="car-badge badge-sale">DEAL</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><circle cx="200" cy="300" r="110"/><circle cx="600" cy="300" r="110"/><circle cx="200" cy="300" r="70" fill="#111"/><circle cx="600" cy="300" r="70" fill="#111"/><circle cx="200" cy="300" r="28"/><circle cx="600" cy="300" r="28"/><path d="M200 190 L320 160 L420 120 L500 130 L560 155 L600 190"/><path d="M310 160 L340 200 L400 200 L440 155"/><path d="M130 280 L200 190 M560 190 L630 240"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">BMW Motorrad</div>
          <div class="car-name">R 1250 GS Adventure</div>
          <div class="car-desc">ShiftCam technology, Dynamic ESA, Connectivity. The world's most capable adventure bike.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">BOX</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">136HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">1254cc</div><div class="car-spec-key">Displ.</div></div>
            <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$18,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

      <div class="car-card" onclick="openModal('Kawasaki Ninja ZX-10R','Kawasaki','Superbike champion. Cornering management system straight from World Superbike racing.','Moto','$16,500','I4 998cc','203 HP','0–100 in 3.1s','2 Seats','badge-new')">
        <span class="car-badge badge-new">NEW</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><circle cx="200" cy="300" r="110"/><circle cx="600" cy="300" r="110"/><circle cx="200" cy="300" r="70" fill="#111"/><circle cx="600" cy="300" r="70" fill="#111"/><circle cx="200" cy="300" r="28"/><circle cx="600" cy="300" r="28"/><path d="M200 190 L320 160 L420 120 L500 130 L560 155 L600 190"/><path d="M310 160 L340 200 L400 200 L440 155"/><path d="M130 280 L200 190 M560 190 L630 240"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Kawasaki</div>
          <div class="car-name">Ninja ZX-10R</div>
          <div class="car-desc">WSBK-derived tech. 203hp inline-four. Cornering ABS. Quickshifter standard.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">I4</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">203HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">998cc</div><div class="car-spec-key">Displ.</div></div>
            <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$16,500 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── ELECTRIC ── -->
  <div class="cat-section" id="cat-electric">
    <div class="car-grid">

      <div class="car-card" onclick="openModal('Tesla Model S Plaid','Tesla','The fastest production car ever made. Tri-motor, 1,020hp, 0–100 in under 2 seconds.','Electric','$110,000','Tri-Motor Electric','1020 HP','0–100 in 1.99s','5 Seats','badge-hot')">
        <span class="car-badge badge-hot">HOT</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"/><circle cx="200" cy="300" r="44"/><circle cx="620" cy="300" r="44"/><circle cx="200" cy="300" r="28" fill="#111"/><circle cx="620" cy="300" r="28" fill="#111"/><path d="M380 115 L400 65 L420 115 Z" fill="white"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Tesla</div>
          <div class="car-name">Model S Plaid</div>
          <div class="car-desc">Tri-motor setup. 200 mph top speed. 17" touchscreen. Over-the-air updates.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">3M</div><div class="car-spec-key">Motors</div></div>
            <div class="car-spec"><div class="car-spec-val">1020HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">390mi</div><div class="car-spec-key">Range</div></div>
            <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$110,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

      <div class="car-card" onclick="openModal('Porsche Taycan Turbo S','Porsche','Electric performance, Porsche precision. 0–100 in 2.8s with launch control. No compromise.','Electric','$185,000','Dual-Motor Electric','750 HP','0–100 in 2.8s','4 Seats','badge-new')">
        <span class="car-badge badge-new">NEW</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"/><circle cx="200" cy="300" r="44"/><circle cx="620" cy="300" r="44"/><circle cx="200" cy="300" r="28" fill="#111"/><circle cx="620" cy="300" r="28" fill="#111"/><path d="M380 115 L400 65 L420 115 Z" fill="white"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Porsche</div>
          <div class="car-name">Taycan Turbo S</div>
          <div class="car-desc">800V architecture for rapid charging. Air suspension. All-wheel steering standard.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">2M</div><div class="car-spec-key">Motors</div></div>
            <div class="car-spec"><div class="car-spec-val">750HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">301mi</div><div class="car-spec-key">Range</div></div>
            <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$185,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── TRUCK ── -->
  <div class="cat-section" id="cat-truck">
    <div class="car-grid">

      <div class="car-card" onclick="openModal('Ford F-150 Raptor R','Ford','The most extreme performance pickup ever built. Supercharged V8 from the Mustang Shelby GT500.','Truck','$109,000','V8 5.2L SC','700 HP','0–100 in 4.9s','5 Seats','badge-hot')">
        <span class="car-badge badge-hot">HOT</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><rect x="40" y="180" width="480" height="160" rx="20"/><rect x="50" y="140" width="280" height="80" rx="12"/><rect x="520" y="220" width="240" height="120" rx="12"/><circle cx="160" cy="360" r="50"/><circle cx="640" cy="360" r="50"/><circle cx="160" cy="360" r="30" fill="#111"/><circle cx="640" cy="360" r="30" fill="#111"/><rect x="80" y="155" width="120" height="60" rx="6" fill="#111" opacity="0.5"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Ford</div>
          <div class="car-name">F-150 Raptor R</div>
          <div class="car-desc">700hp supercharged V8. Fox Racing Shocks. Baja-proven off-road capability.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">V8SC</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">700HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">4WD</div><div class="car-spec-key">Drive</div></div>
            <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$109,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

      <div class="car-card" onclick="openModal('Toyota Hilux Revo','Toyota','The most trusted pickup in the Middle East. Indestructible, capable, practical.','Truck','$36,000','I4 2.8L Diesel','204 HP','–','5 Seats','badge-new')">
        <span class="car-badge badge-new">NEW</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><rect x="40" y="180" width="480" height="160" rx="20"/><rect x="50" y="140" width="280" height="80" rx="12"/><rect x="520" y="220" width="240" height="120" rx="12"/><circle cx="160" cy="360" r="50"/><circle cx="640" cy="360" r="50"/><circle cx="160" cy="360" r="30" fill="#111"/><circle cx="640" cy="360" r="30" fill="#111"/><rect x="80" y="155" width="120" height="60" rx="6" fill="#111" opacity="0.5"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Toyota</div>
          <div class="car-name">Hilux Revo</div>
          <div class="car-desc">204hp diesel. Automatic with sequential shift. Multi-terrain select. Double cab.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">I4D</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">204HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">4WD</div><div class="car-spec-key">Drive</div></div>
            <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$36,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── LUXURY ── -->
  <div class="cat-section" id="cat-luxury">
    <div class="car-grid">

      <div class="car-card" onclick="openModal('Rolls-Royce Ghost','Rolls-Royce','Post Opulent design. Starlight headliner, bespoke cabin, effortless power. The definition of luxury.','Luxury','$340,000','V12 6.75L TT','563 HP','0–100 in 4.8s','4 Seats','badge-hot')">
        <span class="car-badge badge-hot">HOT</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"/><circle cx="200" cy="300" r="44"/><circle cx="620" cy="300" r="44"/><circle cx="200" cy="300" r="28" fill="#111"/><circle cx="620" cy="300" r="28" fill="#111"/><path d="M290 145 L310 185 L480 185 L500 145 Z" fill="#111" opacity="0.6"/><path d="M160 185 L180 145 L285 145 L305 185 Z" fill="#111" opacity="0.6"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Rolls-Royce</div>
          <div class="car-name">Ghost Series II</div>
          <div class="car-desc">Planar suspension system. Starlight headliner with 1,340 stars. Hand-stitched leather throughout.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">V12TT</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">563HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">AWD</div><div class="car-spec-key">Drive</div></div>
            <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$340,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

      <div class="car-card" onclick="openModal('Bentley Continental GT','Bentley','The grand tourer redefined. Hand-built at Crewe. W12 engine with over 600hp.','Luxury','$225,000','W12 6.0L TT','626 HP','0–100 in 3.6s','4 Seats','badge-new')">
        <span class="car-badge badge-new">NEW</span>
        <div class="car-img">
          <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"/><circle cx="200" cy="300" r="44"/><circle cx="620" cy="300" r="44"/><circle cx="200" cy="300" r="28" fill="#111"/><circle cx="620" cy="300" r="28" fill="#111"/><path d="M290 145 L310 185 L480 185 L500 145 Z" fill="#111" opacity="0.6"/><path d="M160 185 L180 145 L285 145 L305 185 Z" fill="#111" opacity="0.6"/></svg>
          <span class="car-img-label">Add Your Image</span>
        </div>
        <div class="car-body">
          <div class="car-brand">Bentley</div>
          <div class="car-name">Continental GT Speed</div>
          <div class="car-desc">626hp W12. All-wheel drive. Rotating dashboard display. Mulliner bespoke options.</div>
          <div class="car-specs">
            <div class="car-spec"><div class="car-spec-val">W12TT</div><div class="car-spec-key">Engine</div></div>
            <div class="car-spec"><div class="car-spec-val">626HP</div><div class="car-spec-key">Power</div></div>
            <div class="car-spec"><div class="car-spec-val">AWD</div><div class="car-spec-key">Drive</div></div>
            <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
          </div>
          <div class="car-footer">
            <div class="car-price">$225,000 <span>/ neg.</span></div>
            <button class="car-btn">View →</button>
          </div>
        </div>
      </div>

    </div>
  </div>

</section>


<!-- ══════════════════════════════════════
     FEATURED
══════════════════════════════════════ -->
<section id="featured">
  <div class="section-header">
    <div>
      <p class="section-eyebrow">Editor's Picks</p>
      <h2 class="section-title">FEATURED THIS WEEK</h2>
    </div>
    <a href="#" class="view-all">See All Featured →</a>
  </div>

  <div class="featured-grid">

    <div class="featured-main car-card" onclick="openModal('Lamborghini Urus S','Lamborghini','The Super SUV. Lamborghini DNA in a five-door body. Brutal performance meets daily usability.','Luxury SUV','$280,000','V8 4.0L TT','666 HP','0–100 in 3.5s','5 Seats','badge-hot')">
      <span class="featured-tag">FEATURED</span>
      <div class="car-img">
        <svg class="car-img-icon" width="120" height="75" viewBox="0 0 800 400" fill="white"><path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"/><circle cx="200" cy="300" r="44"/><circle cx="620" cy="300" r="44"/><circle cx="200" cy="300" r="28" fill="#111"/><circle cx="620" cy="300" r="28" fill="#111"/></svg>
        <span class="car-img-label">Add Your Image</span>
      </div>
      <div class="car-body">
        <div class="car-brand">Lamborghini</div>
        <div class="car-name">Urus S</div>
        <div class="car-desc">666hp twin-turbo V8. Corsa driving mode. 22" forged wheels. The benchmark Super SUV.</div>
        <div class="car-specs">
          <div class="car-spec"><div class="car-spec-val">V8TT</div><div class="car-spec-key">Engine</div></div>
          <div class="car-spec"><div class="car-spec-val">666HP</div><div class="car-spec-key">Power</div></div>
          <div class="car-spec"><div class="car-spec-val">AWD</div><div class="car-spec-key">Drive</div></div>
          <div class="car-spec"><div class="car-spec-val">2024</div><div class="car-spec-key">Year</div></div>
        </div>
        <div class="car-footer">
          <div class="car-price">$280,000 <span>/ neg.</span></div>
          <button class="car-btn">View Details →</button>
        </div>
      </div>
    </div>

    <div class="featured-side car-card" onclick="openModal('Ferrari Roma','Ferrari','La Dolce Vita in coupe form. The most elegant Ferrari of the modern era.','2 Doors','$225,000','V8 3.9L TT','612 HP','0–100 in 3.4s','2 Seats','badge-new')">
      <div class="car-img">
        <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"/><circle cx="200" cy="300" r="44"/><circle cx="620" cy="300" r="44"/><circle cx="200" cy="300" r="28" fill="#111"/><circle cx="620" cy="300" r="28" fill="#111"/></svg>
        <span class="car-img-label">Add Your Image</span>
      </div>
      <div class="car-body">
        <div class="car-brand">Ferrari</div>
        <div class="car-name">Roma</div>
        <div class="car-desc">612hp. 8-speed DCT. The most beautiful Ferrari of the decade.</div>
        <div class="car-footer">
          <div class="car-price">$225,000 <span>/ neg.</span></div>
          <button class="car-btn">View →</button>
        </div>
      </div>
    </div>

    <div class="featured-side car-card" onclick="openModal('McLaren 720S','McLaren','Pure drivers car. Proactive Chassis Control II. Monocage II carbon structure.','2 Doors','$298,000','V8 4.0L TT','710 HP','0–100 in 2.9s','2 Seats','badge-hot')">
      <div class="car-img">
        <svg class="car-img-icon" width="80" height="50" viewBox="0 0 800 400" fill="white"><path d="M80 290 Q90 220 170 200 L300 160 Q400 120 520 145 L680 175 Q760 195 775 235 L790 270 Q790 290 740 295 Q720 252 660 252 Q600 252 580 295 L260 295 Q240 252 180 252 Q120 252 105 295 Z"/><circle cx="180" cy="308" r="40"/><circle cx="660" cy="308" r="40"/><circle cx="180" cy="308" r="24" fill="#111"/><circle cx="660" cy="308" r="24" fill="#111"/></svg>
        <span class="car-img-label">Add Your Image</span>
      </div>
      <div class="car-body">
        <div class="car-brand">McLaren</div>
        <div class="car-name">720S</div>
        <div class="car-desc">710hp. 0–200 in 7.8s. Electrohydraulic active suspension.</div>
        <div class="car-footer">
          <div class="car-price">$298,000 <span>/ neg.</span></div>
          <button class="car-btn">View →</button>
        </div>
      </div>
    </div>

  </div>
</section>


<!-- ══════════════════════════════════════
     WHY US
══════════════════════════════════════ -->
<section id="why">
  <div class="section-header">
    <div>
      <p class="section-eyebrow">Why DriveHub</p>
      <h2 class="section-title">THE DRIVEHUB DIFFERENCE</h2>
    </div>
  </div>

  <div class="why-grid">
    <div class="why-card">
      <div class="why-icon">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4"/><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z"/></svg>
      </div>
      <div class="why-title">Verified Listings</div>
      <div class="why-desc">Every vehicle goes through a 50-point inspection. No fakes, no surprises — just honest listings.</div>
    </div>
    <div class="why-card">
      <div class="why-icon">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      </div>
      <div class="why-title">Buyer Protection</div>
      <div class="why-desc">Secure escrow payments, full refund guarantee within 7 days if the car isn't as described.</div>
    </div>
    <div class="why-card">
      <div class="why-icon">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
      </div>
      <div class="why-title">Instant Response</div>
      <div class="why-desc">Connect directly with sellers. Average response time under 2 hours on all premium listings.</div>
    </div>
    <div class="why-card">
      <div class="why-icon">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      </div>
      <div class="why-title">Nationwide Delivery</div>
      <div class="why-desc">We partner with licensed transporters to deliver your car safely to any address in Lebanon.</div>
    </div>
  </div>
</section>


<!-- ══════════════════════════════════════
     NEWSLETTER
══════════════════════════════════════ -->
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


<!-- ══════════════════════════════════════
     FOOTER
══════════════════════════════════════ -->
<footer>
  <div class="footer-top">
    <div class="footer-brand">
      <div class="nav-logo" style="margin-bottom:0">
        <div class="logo-mark"><span>⬡</span></div>
        <span class="logo-name">DriveHub</span>
      </div>
      <p>Lebanon's premier automotive marketplace. Connecting buyers and sellers with verified listings since 2020.</p>
    </div>

    <div>
      <div class="footer-col-title">Browse</div>
      <ul class="footer-links">
        <li><a href="#">4X4 / SUV</a></li>
        <li><a href="#">2 Doors</a></li>
        <li><a href="#">Sedan</a></li>
        <li><a href="#">Bus & Van</a></li>
        <li><a href="#">Motorcycles</a></li>
        <li><a href="#">Electric</a></li>
      </ul>
    </div>

    <div>
      <div class="footer-col-title">Company</div>
      <ul class="footer-links">
        <li><a href="#">About Us</a></li>
        <li><a href="#">How It Works</a></li>
        <li><a href="#">List Your Car</a></li>
        <li><a href="#">Careers</a></li>
        <li><a href="#">Press</a></li>
      </ul>
    </div>

    <div>
      <div class="footer-col-title">Support</div>
      <ul class="footer-links">
        <li><a href="#">Help Center</a></li>
        <li><a href="#">Contact Us</a></li>
        <li><a href="#">Buyer Guide</a></li>
        <li><a href="#">Seller Guide</a></li>
        <li><a href="#">Report an Issue</a></li>
      </ul>
    </div>
  </div>

  <div class="footer-bottom">
    <div class="footer-copy">© 2026 <span>DriveHub</span>. All rights reserved.</div>
    <div class="footer-legal">
      <a href="#">Privacy Policy</a>
      <a href="#">Terms of Service</a>
      <a href="#">Cookie Policy</a>
    </div>
  </div>
</footer>


<!-- ══════════════════════════════════════
     MODAL
══════════════════════════════════════ -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModalOutside(event)">
  <div class="modal" id="modal">
    <button class="modal-close" onclick="closeModal()">✕</button>
    <div class="modal-img" id="modalImg">
      <svg class="car-img-icon" width="100" height="62" viewBox="0 0 800 400" fill="white"><path d="M60 280 Q80 200 160 180 L280 140 Q380 100 500 130 L660 160 Q740 180 760 220 L780 260 Q780 280 720 285 Q700 240 640 240 Q580 240 560 285 L280 285 Q260 240 200 240 Q140 240 120 285 Z"/><circle cx="200" cy="300" r="44"/><circle cx="620" cy="300" r="44"/><circle cx="200" cy="300" r="28" fill="#111"/><circle cx="620" cy="300" r="28" fill="#111"/></svg>
      <span class="car-img-label">Add Your Image</span>
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
  /* ── CATEGORY TABS ── */
  function switchCat(id, btn) {
    document.querySelectorAll('.cat-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('cat-' + id).classList.add('active');
    btn.classList.add('active');
  }

  /* ── MODAL ── */
  function openModal(name, brand, desc, cat, price, spec1val, spec2val, spec3val, spec4val, badgeClass) {
    document.getElementById('modalName').textContent  = name;
    document.getElementById('modalBrand').textContent = brand;
    document.getElementById('modalDesc').textContent  = desc;
    document.getElementById('modalCat').textContent   = cat;
    document.getElementById('modalPrice').innerHTML   = price + ' <span>/ negotiable</span>';

    const specLabels = ['Engine','Power','Perf.','Seats'];
    const specVals   = [spec1val, spec2val, spec3val, spec4val];
    document.getElementById('modalSpecs').innerHTML = specVals.map((v,i) => `
      <div class="modal-spec">
        <div class="modal-spec-val">${v}</div>
        <div class="modal-spec-key">${specLabels[i]}</div>
      </div>
    `).join('');

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

  /* ── NAV active on scroll ── */
  const sections = document.querySelectorAll('section[id], div[id]');
  window.addEventListener('scroll', () => {
    let current = '';
    sections.forEach(s => {
      if (window.scrollY >= s.offsetTop - 100) current = s.id;
    });
    document.querySelectorAll('.nav-links a').forEach(a => {
      a.classList.remove('active');
      if (a.getAttribute('href') === '#' + current) a.classList.add('active');
    });
  });
</script>

</body>
</html>