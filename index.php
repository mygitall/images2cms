<?php
session_start();
// 未安装 → 跳安装向导
if (file_exists(__DIR__ . '/config.php')) {
    $__cfg = require __DIR__ . '/config.php';
    if (empty($__cfg['installed'])) { header('Location: install.php'); exit; }
} else {
    header('Location: install.php'); exit;
}
?><!doctype html>
<html lang="zh">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <base href="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/'; ?>">
  <title>token link 多图工具</title>

  <!-- Three.js 库 -->
  <script src="https://cdn.jsdelivr.net/npm/three@0.140.0/build/three.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/three@0.140.0/examples/js/controls/OrbitControls.js"></script>

  <style>
    /* ====== Design Tokens ====== */
    :root {
      /* Light (default) */
      --bg: #f5f5f7;
      --glow-1: rgba(0,0,0,0.03);
      --glow-2: rgba(0,0,0,0.02);
      --card-bg: rgba(255,255,255,0.7);
      --card-border: rgba(0,0,0,0.06);
      --card-hover: rgba(0,0,0,0.03);
      --text: #1a1a1a;
      --text-secondary: #666;
      --text-tertiary: #999;
      --accent: #1a1a1a;
      --accent-dim: rgba(0,0,0,0.06);
      --danger: #dc2626;
      --success: #16a34a;
      --input-bg: rgba(0,0,0,0.03);
      --input-focus: rgba(0,0,0,0.1);
      --border-hover: rgba(0,0,0,0.12);
      --border-strong: rgba(0,0,0,0.15);
      --border-focus: rgba(0,0,0,0.2);
      --overlay: rgba(0,0,0,0.04);
      --modal-bg: #fff;
      --btn-text: #fff;
      --radius-sm: 10px;
      --radius: 18px;
      --radius-lg: 24px;
      --font: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Inter", "Helvetica Neue", sans-serif;
      --font-mono: "SF Mono", "Fira Code", "JetBrains Mono", monospace;
    }

    /* Dark theme */
    [data-theme="dark"] {
      --bg: #1a1a1a;
      --glow-1: rgba(255,255,255,0.015);
      --glow-2: rgba(255,255,255,0.01);
      --card-bg: rgba(255,255,255,0.03);
      --card-border: rgba(255,255,255,0.08);
      --card-hover: rgba(255,255,255,0.06);
      --text: #eee;
      --text-secondary: #999;
      --text-tertiary: #777;
      --accent: #eee;
      --accent-dim: rgba(255,255,255,0.12);
      --danger: #f87171;
      --success: #4ade80;
      --input-bg: rgba(255,255,255,0.05);
      --input-focus: rgba(255,255,255,0.15);
      --border-hover: rgba(255,255,255,0.15);
      --border-strong: rgba(255,255,255,0.18);
      --border-focus: rgba(255,255,255,0.22);
      --overlay: rgba(255,255,255,0.06);
      --modal-bg: #222;
      --btn-text: #1a1a1a;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }

    body {
      font-family: var(--font);
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      position: relative;
      overflow-x: hidden;
    }

    /* Subtle background glows */
    body::before, body::after {
      content: '';
      position: fixed;
      border-radius: 50%;
      pointer-events: none;
      z-index: 0;
    }
    body::before {
      top: -30vh; left: -10vw;
      width: 70vw; height: 70vh;
      background: radial-gradient(circle, var(--glow-1), transparent 70%);
    }
    body::after {
      bottom: -20vh; right: -15vw;
      width: 60vw; height: 60vh;
      background: radial-gradient(circle, var(--glow-2), transparent 70%);
    }

    /* ====== Theme Toggle ====== */
    .theme-toggle {
      width: 40px; height: 40px; border-radius: 50%;
      border: 1px solid var(--card-border);
      background: var(--card-bg);
      color: var(--text-secondary);
      cursor: pointer; display: flex; align-items: center;
      justify-content: center; flex-shrink: 0;
      transition: border-color 0.15s, color 0.15s;
      margin-top: 8px;
    }
    .theme-toggle:hover { border-color: var(--border-hover); color: var(--text); }
    [data-theme="dark"] .theme-toggle .sun { display: none; }
    .theme-toggle .moon { display: none; }
    [data-theme="dark"] .theme-toggle .moon { display: block; }

    /* ====== Toast 通知 ====== */
    .toast-container {
      position: fixed; top: 24px; left: 50%; transform: translateX(-50%);
      z-index: 9999; display: flex; flex-direction: column; gap: 8px;
      pointer-events: none;
    }
    .toast {
      padding: 12px 24px; border-radius: 12px; font-size: 14px; font-weight: 500;
      font-family: var(--font); white-space: nowrap;
      animation: toastIn 0.3s ease, toastOut 0.3s ease 2.7s forwards;
      pointer-events: auto; backdrop-filter: blur(12px);
      box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    .toast.success { background: rgba(22,163,74,0.9); color: #fff; }
    .toast.error   { background: rgba(220,38,38,0.9); color: #fff; }
    .toast.info    { background: rgba(37,99,235,0.9); color: #fff; }
    @keyframes toastIn  { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes toastOut { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(-12px); } }

    /* ====== Layout ====== */
    .app {
      max-width: 1240px;
      margin: 0 auto;
      padding: 40px 24px 80px;
      position: relative;
      z-index: 1;
    }

    /* ====== Hero ====== */
    .hero {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 28px; gap: 16px; flex-wrap: wrap;
    }
    .hero h1 {
      font-size: clamp(24px, 4vw, 36px);
      font-weight: 700;
      letter-spacing: -0.03em;
      line-height: 1.1;
      color: var(--text);
    }
    .hero .sub {
      font-size: 14px;
      color: var(--text-secondary);
      margin-top: 2px;
      font-weight: 400;
    }
    .hero .badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      margin-top: 6px;
      padding: 4px 10px;
      border-radius: 100px;
      font-size: 11px;
      font-weight: 500;
      color: var(--success);
      background: rgba(52,211,153,0.08);
      border: 1px solid rgba(52,211,153,0.15);
    }
    .hero-left { flex: 1; min-width: 0; }

    /* ====== Bento Grid ====== */
    .bento {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 12px;
    }

    /* ====== Glass Card ====== */
    .glass-card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: var(--radius);
      padding: 24px;
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      transition: border-color 0.2s ease, background 0.2s ease;
    }
    .glass-card:hover {
      border-color: var(--accent-dim);
    }

    /* Card sizes */
    .card-prompt      { grid-column: span 2; }
    .card-settings    { grid-column: span 2; }
    .card-results     { grid-column: span 2; }
    .card-history     { grid-column: span 2; margin-top: 4px; }
    .card-upload      { grid-column: span 3; }
    .card-config      { grid-column: span 3; }
    .card-folder      { grid-column: span 2; }
    .card-presets     { grid-column: span 4; margin-top: 4px; }

    /* results / history 共享同一位置，互斥显示，高度与 card-settings 一致 */
    .results-area {
      grid-column: span 2;
      display: flex; flex-direction: column;
    }
    .results-area .glass-card {
      flex: 1; overflow-y: auto; min-height: 0;
    }
    @media (max-width: 900px) { .results-area { grid-column: span 1; } }

    .config-inline {
      display: flex; gap: 12px; align-items: flex-end;
    }
    .config-inline > div { flex: 1; min-width: 0; }
    .config-inline label { white-space: nowrap; }

    /* Button group (pill selection) */
    .btn-group {
      display: flex; gap: 4px; flex-wrap: wrap;
    }
    .btn-group button {
      padding: 6px 14px;
      border: 1px solid var(--card-border);
      border-radius: 8px;
      background: transparent;
      color: var(--text-secondary);
      font-size: 13px;
      font-family: var(--font);
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .btn-group button:hover {
      border-color: var(--border-hover);
      color: var(--text);
    }
    .btn-group button.active {
      background: var(--text);
      color: var(--btn-text);
      border-color: var(--text);
    }

    /* ====== Typography ====== */
    .glass-card h2 {
      font-size: 16px;
      font-weight: 600;
      letter-spacing: -0.01em;
      color: var(--text);
    }

    label {
      display: block;
      font-size: 12px;
      font-weight: 500;
      color: var(--text-tertiary);
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin-bottom: 8px;
    }

    /* ====== Form Elements ====== */
    input[type="text"],
    input[type="number"],
    textarea,
    select {
      width: 100%;
      background: var(--input-bg);
      border: 1px solid var(--card-border);
      border-radius: var(--radius-sm);
      padding: 10px 14px;
      color: var(--text);
      font-size: 14px;
      font-family: var(--font);
      outline: none;
      transition: border-color 0.15s ease, background 0.15s ease;
      -webkit-appearance: none;
      appearance: none;
    }
    input:focus, textarea:focus, select:focus {
      border-color: var(--border-focus);
      background: var(--card-hover);
    }
    select {
      height: 42px;
      cursor: pointer;
      background-image: url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23555' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 14px center;
      padding-right: 36px;
    }
    textarea {
      min-height: 120px;
      resize: vertical;
      line-height: 1.6;
    }
    input[type="number"] {
      font-variant-numeric: tabular-nums;
    }
    input[readonly] {
      opacity: 0.5;
      cursor: not-allowed;
    }

    /* File input button */
    input[type="file"] {
      width: 100%;
      padding: 9px 14px;
      font-size: 13px;
      font-family: var(--font);
      color: var(--text-secondary);
      background: var(--input-bg);
      border: 1px solid var(--card-border);
      border-radius: 8px;
      cursor: pointer;
      transition: border-color 0.15s;
    }
    input[type="file"]:hover { border-color: var(--border-hover); }
    input[type="file"]::file-selector-button {
      padding: 5px 14px;
      margin-right: 10px;
      border: 1px solid var(--card-border);
      border-radius: 6px;
      background: transparent;
      color: var(--text);
      font-size: 12px;
      font-weight: 500;
      font-family: var(--font);
      cursor: pointer;
      transition: background 0.15s;
    }
    input[type="file"]::file-selector-button:hover { background: var(--card-hover); }

    /* ====== Buttons ====== */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      border: none;
      border-radius: 100px;
      padding: 12px 24px;
      font-size: 14px;
      font-weight: 600;
      font-family: var(--font);
      cursor: pointer;
      background: var(--text);
      color: var(--btn-text);
      letter-spacing: -0.01em;
      transition: opacity 0.15s ease;
    }
    .btn:hover { opacity: 0.85; }
    .btn:active { opacity: 0.7; }
    .btn:disabled { opacity: 0.3; cursor: not-allowed; }

    .btn-ghost {
      background: transparent;
      color: var(--text-secondary);
      border: 1px solid var(--card-border);
      border-radius: 100px;
      padding: 8px 16px;
      font-size: 13px;
      font-weight: 500;
      font-family: var(--font);
      cursor: pointer;
      transition: border-color 0.15s, color 0.15s;
    }
    .btn-ghost:hover { border-color: var(--border-focus); color: var(--text); }

    .btn-mini {
      padding: 4px 10px;
      border-radius: 6px;
      border: 1px solid var(--card-border);
      background: transparent;
      color: var(--text-secondary);
      font-size: 11px;
      font-weight: 500;
      font-family: var(--font);
      cursor: pointer;
      transition: border-color 0.15s, color 0.15s;
      white-space: nowrap;
    }
    .btn-mini:hover { border-color: var(--border-focus); color: var(--text); }

    .btn-mini.primary {
      background: var(--text);
      color: var(--btn-text);
      border: none;
    }
    .btn-mini.primary:hover { opacity: 0.8; }

    /* Prompt action buttons */
    .save-prompt-from-input-btn,
    .optimize-prompt-btn {
      flex: 1;
      padding: 10px 16px;
      border-radius: 100px;
      font-size: 13px;
      font-weight: 500;
      font-family: var(--font);
      cursor: pointer;
      border: 1px solid var(--card-border);
      background: transparent;
      color: var(--text-secondary);
      transition: border-color 0.15s, color 0.15s;
      display: flex; align-items: center; justify-content: center; gap: 6px;
    }
    .save-prompt-from-input-btn:hover,
    .optimize-prompt-btn:hover { border-color: var(--border-focus); color: var(--text); }
    .save-prompt-from-input-btn:disabled,
    .optimize-prompt-btn:disabled { opacity: 0.4; cursor: not-allowed; }

    .prompt-buttons-container { display: flex; gap: 10px; margin-top: 12px; }

    /* ====== Upload Area ====== */
    .uploads {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
      gap: 8px;
      margin-top: 10px;
    }
    .thumb {
      position: relative;
      border: 1px solid var(--card-border);
      border-radius: 12px;
      overflow: hidden;
      background: var(--input-bg);
    }
    .thumb img {
      width: 100%; height: 110px; object-fit: cover; display: block;
    }
    .thumb button {
      position: absolute; top: 5px; right: 5px;
      border: none; background: rgba(0,0,0,0.8); color: #fff;
      border-radius: 6px; padding: 4px 7px; cursor: pointer; font-size: 11px;
    }
    .thumb .size-label {
      position: absolute; bottom: 5px; left: 5px;
      background: rgba(0,0,0,0.75); color: rgba(255,255,255,0.8);
      font-size: 10px; font-weight: 500; padding: 2px 6px; border-radius: 4px;
    }

    /* ====== Status ====== */
    .status {
      font-size: 12px; color: var(--text-tertiary); min-height: 18px; margin-top: 10px;
    }
    .status.info  { color: var(--danger); }
    .status.success { color: var(--success); }
    .muted { color: var(--text-tertiary); font-size: 12px; }

    /* ====== Folder Settings ====== */
    .save-settings {
      display: flex; align-items: center; gap: 10px;
      font-size: 13px; flex-wrap: wrap;
    }
    .save-settings .path-label { color: var(--text-tertiary); }
    .save-settings .path-value {
      color: var(--text-secondary); font-size: 13px; font-weight: 500;
      max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .save-settings .mini-btn { font-size: 12px; }

    /* ====== Results ====== */
    .results {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 10px;
      margin-top: 12px;
      align-items: start;
    }
    .card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: var(--radius);
      padding: 12px;
      display: flex; flex-direction: column; gap: 10px;
      animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(6px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .card img {
      width: 100%; border-radius: 12px; object-fit: cover;
    }
    .card .text { color: var(--text-secondary); white-space: pre-wrap; font-size: 13px; }
    .card .actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .card .time-label {
      color: var(--text-secondary); font-size: 11px; font-weight: 500;
      padding: 4px 8px; background: var(--card-hover); border-radius: 6px;
    }
    .card .mini-btn {
      padding: 5px 10px; border-radius: 6px; border: 1px solid var(--card-border);
      background: transparent; color: var(--text-secondary); font-size: 12px;
      text-decoration: none; cursor: pointer; font-family: var(--font);
    }
    .card .mini-btn:hover { border-color: var(--border-focus); color: var(--text); }
    .card .mini-btn.primary { background: var(--text); color: var(--btn-text); border: none; }
    .card img.zoomable {
      cursor: zoom-in; transition: transform 0.15s ease;
    }
    .card img.zoomable:hover { transform: scale(1.01); }

    /* ====== Result Groups ====== */
    .result-group {
      grid-column: 1 / -1;
      background: var(--overlay);
      border: 1px solid var(--border-hover)
      border-radius: var(--radius);
      padding: 20px;
      margin-bottom: 8px;
    }
    .result-group-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 14px; padding-bottom: 14px;
      border-bottom: 1px solid var(--card-border);
    }
    .result-group-title { font-size: 15px; font-weight: 600; color: var(--text); }
    .result-group-meta { font-size: 12px; color: var(--text-tertiary); }
    .result-group-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 10px;
    }

    /* ====== Continue Panel ====== */
    .continue-panel {
      display: none; padding: 12px; border-radius: var(--radius-sm);
      background: var(--card-bg); border: 1px solid var(--card-border);
    }
    .continue-panel.show { display: block; animation: fadeIn 0.15s ease; }
    .continue-panel textarea {
      width: 100%; min-height: 56px; background: var(--input-bg);
      border: 1px solid var(--card-border); border-radius: 8px; padding: 10px;
      color: var(--text); font-size: 13px; font-family: var(--font);
      resize: vertical; margin-bottom: 8px; outline: none;
    }
    .continue-panel .panel-actions { display: flex; gap: 8px; }
    .continue-panel .gen-btn {
      flex: 1; padding: 8px 12px; border: none; border-radius: 8px;
      background: var(--text); color: var(--btn-text); font-weight: 600; font-size: 13px;
      cursor: pointer; font-family: var(--font);
    }
    .continue-panel .gen-btn:disabled { opacity: 0.4; cursor: not-allowed; }
    .continue-panel .cancel-btn {
      padding: 8px 12px; border: 1px solid var(--card-border); border-radius: 8px;
      background: transparent; color: var(--text-secondary); font-size: 13px;
      cursor: pointer; font-family: var(--font);
    }

    /* ====== History ====== */
    .history-header {
      display: flex; align-items: center; gap: 12px; margin-bottom: 4px; flex-wrap: wrap;
    }
    .history-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 10px;
      margin-top: 12px;
    }
    .history-card {
      background: var(--card-bg); border: 1px solid var(--card-border);
      border-radius: 14px; overflow: hidden; cursor: pointer;
      transition: border-color 0.15s;
    }
    .history-card:hover { border-color: var(--border-strong); }
    .history-card img { width: 100%; height: 120px; object-fit: cover; display: block; }
    .history-card .info { padding: 8px 10px; }
    .history-card .prompt {
      font-size: 11px; color: var(--text-secondary);
      display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
      overflow: hidden; line-height: 1.4; margin-bottom: 4px;
      cursor: pointer; user-select: none;
    }
    .history-card .meta {
      display: flex; justify-content: space-between; align-items: center;
      font-size: 10px; color: var(--text-tertiary);
    }
    .history-card .delete-btn {
      background: transparent; border: none; color: var(--danger);
      cursor: pointer; padding: 2px 4px; font-size: 11px; opacity: 0.6;
    }
    .history-card .delete-btn:hover { opacity: 1; }
    .history-card .regen-btn { background: rgba(59,130,246,0.15); border:none; color:#3b82f6; cursor:pointer; padding:2px 6px; font-size:10px; border-radius:4px; }
    .history-card .hd-btn, .history-card .add-btn, .history-card .save-prompt-btn {
      background: var(--accent-dim); border: none; color: var(--text-secondary);
      cursor: pointer; padding: 2px 6px; font-size: 10px; border-radius: 4px;
    }
    .history-card .hd-btn:hover, .history-card .add-btn:hover { color: var(--text); }
    .history-card .hd-btn:disabled, .history-card .add-btn:disabled { opacity: 0.4; cursor: wait; }
    .history-empty { text-align: center; color: var(--text-tertiary); padding: 48px 20px; font-size: 14px; }

    /* ====== Preset Scenarios ====== */
    .preset-scenarios {
      background: transparent; border: 1px solid var(--card-border);
      border-radius: var(--radius); overflow: hidden;
    }
    .preset-collapse-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 16px 20px; cursor: pointer; user-select: none;
      transition: background 0.15s;
    }
    .preset-collapse-header:hover { background: var(--card-bg); }
    .preset-collapse-title {
      display: flex; align-items: center; gap: 8px;
      font-size: 14px; font-weight: 600; color: var(--text);
    }
    .preset-collapse-icon {
      font-size: 11px; color: var(--text-tertiary); transition: transform 0.3s ease;
    }
    .preset-scenarios.collapsed .preset-collapse-icon { transform: rotate(-90deg); }
    .preset-collapse-content {
      max-height: 2000px; overflow: hidden;
      transition: max-height 0.4s ease, padding 0.4s ease;
      padding: 0 20px 20px 20px;
    }
    .preset-scenarios.collapsed .preset-collapse-content { max-height: 0; padding: 0 20px; }
    .preset-scenarios-title {
      font-size: 12px; color: var(--text-tertiary); margin-bottom: 12px;
      font-weight: 500; text-transform: uppercase; letter-spacing: 0.04em;
    }
    .preset-buttons {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 8px;
    }
    .preset-btn {
      padding: 14px 16px; border: 1px solid var(--card-border); border-radius: 12px;
      background: var(--card-bg); color: var(--text);
      font-size: 14px; cursor: pointer; text-align: left; font-family: var(--font);
      transition: border-color 0.15s;
      position: relative;
    }
    .preset-btn:hover:not(:disabled) { border-color: var(--border-focus); }
    .preset-btn:disabled { opacity: 0.4; cursor: not-allowed; }
    .preset-btn-label { font-weight: 600; font-size: 13px; display: block; margin-bottom: 4px; }
    .preset-btn-desc { font-size: 11px; color: var(--text-tertiary); line-height: 1.4; }
    .preset-btn-customize {
      position: absolute; top: 10px; right: 10px;
      width: 24px; height: 24px; border-radius: 6px;
      background: var(--accent-dim); color: white; border: none;
      cursor: pointer; display: flex; align-items: center; justify-content: center;
      font-size: 12px; transition: background 0.15s; z-index: 1;
    }
    .preset-btn-customize:hover { background: rgba(255,255,255,0.2); }

    /* ====== Tabs ====== */
    .preset-tab-buttons {
      display: flex; gap: 4px; margin-bottom: 16px;
      background: var(--card-bg); border-radius: 8px;
      padding: 4px; width: fit-content;
    }
    .preset-tab-btn {
      padding: 8px 16px; background: transparent; border: none;
      color: var(--text-tertiary); font-size: 13px; font-weight: 500;
      cursor: pointer; border-radius: 6px; font-family: var(--font);
      transition: all 0.15s;
    }
    .preset-tab-btn:hover { color: var(--text); }
    .preset-tab-btn.active { background: var(--accent-dim); color: var(--text); }
    .preset-tab-content { display: none; }
    .preset-tab-content.active { display: block; }

    /* ====== Prompt Library ====== */
    .prompt-library-container { display: flex; flex-direction: column; gap: 10px; }
    .prompt-library-empty { text-align: center; color: var(--text-tertiary); padding: 40px 20px; font-size: 14px; }
    .prompt-library-list { display: flex; flex-direction: column; gap: 8px; }
    .prompt-lib-item {
      padding: 14px; background: var(--card-bg); border: 1px solid var(--card-border);
      border-radius: 12px; transition: border-color 0.15s;
    }
    .prompt-lib-item:hover { border-color: var(--border-strong); }
    .prompt-lib-item-title { font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 4px; }
    .prompt-lib-item-content {
      font-size: 12px; color: var(--text-tertiary); line-height: 1.5; margin-bottom: 8px;
      display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
      overflow: hidden; word-break: break-word;
    }
    .prompt-lib-actions { display: flex; gap: 8px; justify-content: flex-end; }
    .prompt-lib-btn {
      padding: 5px 10px; background: transparent; border: 1px solid var(--card-border);
      border-radius: 6px; color: var(--text-secondary); font-size: 11px;
      cursor: pointer; font-family: var(--font); transition: border-color 0.15s, color 0.15s;
    }
    .prompt-lib-btn:hover { border-color: var(--border-focus); color: var(--text); }
    .prompt-lib-btn-delete { color: var(--danger); border-color: rgba(239,68,68,0.2); }
    .prompt-lib-btn-delete:hover { border-color: rgba(239,68,68,0.4); color: var(--danger); }

    /* ====== Prompt Tooltip ====== */
    .prompt-tooltip {
      position: absolute; background: var(--modal-bg); border: 1px solid rgba(255,255,255,0.1);
      border-radius: 10px; padding: 12px; font-size: 12px; color: var(--text);
      max-width: 360px; word-break: break-word; line-height: 1.5;
      box-shadow: 0 8px 32px rgba(0,0,0,0.6); z-index: 1000;
      pointer-events: none; animation: tooltipFadeIn 0.15s ease;
    }
    @keyframes tooltipFadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }

    /* ====== Dialog ====== */
    .dialog-overlay {
      display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.8); z-index: 9998;
      align-items: center; justify-content: center;
      backdrop-filter: blur(4px);
    }
    .dialog-overlay.active { display: flex; }
    .dialog-content {
      background: var(--modal-bg); border: 1px solid var(--border-hover)
      border-radius: var(--radius-lg); padding: 28px; max-width: 400px; width: 90%;
    }
    .dialog-title { font-size: 16px; font-weight: 600; color: var(--text); margin-bottom: 16px; }
    .dialog-input {
      width: 100%; background: var(--input-bg); border: 1px solid var(--card-border);
      border-radius: var(--radius-sm); padding: 10px 14px; color: var(--text);
      font-size: 14px; margin-bottom: 16px; outline: none; font-family: var(--font);
    }
    .dialog-input:focus { border-color: var(--border-focus); }
    .dialog-actions { display: flex; gap: 12px; justify-content: flex-end; }
    .dialog-btn {
      padding: 10px 20px; border-radius: 100px; font-size: 14px; font-weight: 500;
      cursor: pointer; font-family: var(--font); transition: opacity 0.15s; border: none;
    }
    .dialog-btn-cancel { background: var(--accent-dim); color: var(--text); }
    .dialog-btn-confirm { background: var(--text); color: var(--btn-text); }
    .dialog-btn-confirm:disabled { opacity: 0.4; cursor: not-allowed; }

    /* ====== Lightbox ====== */
    .lightbox {
      display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.94); z-index: 9999;
      justify-content: center; align-items: center; cursor: zoom-out;
      animation: fadeIn 0.15s ease;
    }
    .lightbox.show { display: flex; }
    .lightbox img {
      max-width: 95vw; max-height: 95vh; object-fit: contain;
      border-radius: 4px;
      cursor: default;
    }
    @keyframes zoomIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    .lightbox.show img { animation: zoomIn 0.15s ease; }
    .lightbox .close-btn {
      position: absolute; top: 20px; right: 24px;
      background: var(--accent-dim); border: none; color: #fff;
      font-size: 24px; width: 44px; height: 44px; border-radius: 50%;
      cursor: pointer; display: flex; align-items: center; justify-content: center;
      transition: background 0.15s;
    }
    .lightbox .close-btn:hover { background: rgba(255,255,255,0.2); }

    /* ====== Angle Modal ====== */
    .angle-modal-overlay {
      display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.8); z-index: 9999;
      align-items: center; justify-content: center;
    }
    .angle-modal-overlay.active { display: flex; }
    .angle-modal {
      background: var(--modal-bg); border: 1px solid var(--border-hover)
      border-radius: var(--radius-lg); width: 95%; max-width: 1400px; height: 95vh;
      overflow: hidden; display: flex; flex-direction: column;
    }
    .angle-modal-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 20px 24px; border-bottom: 1px solid var(--card-border);
    }
    .angle-modal-title { font-size: 17px; font-weight: 600; color: var(--text); }
    .angle-modal-close {
      width: 32px; height: 32px; border-radius: 8px; background: transparent;
      border: none; color: var(--text-secondary); cursor: pointer; font-size: 18px;
      display: flex; align-items: center; justify-content: center;
    }
    .angle-modal-close:hover { background: var(--accent-dim); color: var(--text); }
    .angle-modal-body { padding: 0; display: flex; flex-direction: column; flex: 1; overflow: hidden; }
    .angle-modal-main { display: flex; flex: 1; gap: 20px; padding: 20px; overflow: hidden; }
    .angle-reference-panel {
      width: 220px; background: var(--card-bg); border: 1px solid var(--card-border);
      border-radius: 14px; padding: 16px; display: flex; flex-direction: column;
      max-height: 360px;
    }
    .angle-reference-title { font-size: 12px; color: var(--text-tertiary); margin-bottom: 12px; font-weight: 500; }
    .angle-reference-image {
      flex: 1; background: var(--overlay); border: 1px solid var(--card-border);
      border-radius: 10px; overflow: hidden; display: flex; align-items: center;
      justify-content: center; position: relative;
    }
    .angle-reference-placeholder { color: var(--text-tertiary); font-size: 12px; text-align: center; }
    .angle-reference-image img { width: 100%; height: 100%; object-fit: contain; }
    .angle-reference-close {
      position: absolute; top: 6px; right: 6px; width: 22px; height: 22px;
      border-radius: 6px; background: rgba(0,0,0,0.7); color: white;
      border: none; cursor: pointer; font-size: 14px;
      display: flex; align-items: center; justify-content: center;
      opacity: 0; transition: opacity 0.2s;
    }
    .angle-reference-image:hover .angle-reference-close { opacity: 1; }
    .angle-canvas-container {
      flex: 1; background: var(--overlay); border: 1px solid var(--card-border);
      border-radius: 14px; position: relative; overflow: hidden;
      display: flex; align-items: center; justify-content: center;
    }
    .angle-canvas-hint {
      position: absolute; top: 16px; left: 50%; transform: translateX(-50%);
      font-size: 12px; color: var(--text-tertiary); background: rgba(0,0,0,0.7);
      padding: 8px 14px; border-radius: 6px; z-index: 10; pointer-events: none;
    }
    #angle-canvas { width: 100%; height: 100%; display: block; }
    .angle-controls-panel {
      position: absolute; bottom: 28px; left: 50%; transform: translateX(-50%);
      width: 80%; max-width: 800px;
      background: rgba(0,0,0,0.9); backdrop-filter: blur(16px);
      border: 1px solid var(--border-hover)
      border-radius: 14px; padding: 20px 24px; z-index: 10;
    }
    .angle-controls-title { font-size: 12px; color: var(--text-tertiary); margin-bottom: 16px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.04em; }
    .angle-control-group { margin-bottom: 18px; }
    .angle-control-label { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; font-size: 13px; color: var(--text); }
    .angle-control-value { font-weight: 600; color: var(--text-secondary); }
    .angle-slider {
      width: 100%; height: 4px; border-radius: 2px;
      background: rgba(255,255,255,0.08); outline: none;
      -webkit-appearance: none; appearance: none;
    }
    .angle-slider::-webkit-slider-thumb {
      -webkit-appearance: none; appearance: none;
      width: 16px; height: 16px; border-radius: 50%;
      background: var(--text); cursor: pointer;
    }
    .angle-slider::-moz-range-thumb {
      width: 16px; height: 16px; border-radius: 50%;
      background: var(--text); cursor: pointer; border: none;
    }
    .angle-modal-footer { display: flex; gap: 12px; justify-content: flex-end; padding: 16px 24px; border-top: 1px solid var(--card-border); }
    .angle-modal-btn {
      padding: 10px 20px; border-radius: 100px; font-size: 14px; font-weight: 500;
      cursor: pointer; font-family: var(--font); transition: opacity 0.15s; border: none;
    }
    .angle-modal-btn-cancel { background: var(--accent-dim); color: var(--text); }
    .angle-modal-btn-confirm { background: var(--text); color: var(--btn-text); }

    /* ====== Storyboard ====== */
    .storyboard-overlay {
      position: fixed; top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.8); display: flex; align-items: center;
      justify-content: center; z-index: 10000; animation: fadeIn 0.15s ease;
    }
    .storyboard-panel {
      background: var(--modal-bg); border: 1px solid var(--border-hover)
      border-radius: var(--radius-lg); width: 90%; max-width: 800px; max-height: 80vh;
      display: flex; flex-direction: column;
    }
    .storyboard-header {
      padding: 20px 24px; border-bottom: 1px solid var(--card-border);
      display: flex; align-items: center; justify-content: space-between;
    }
    .storyboard-header h3 { margin: 0; font-size: 17px; color: var(--text); font-weight: 600; }
    .storyboard-close {
      background: none; border: none; color: var(--text-secondary); font-size: 22px;
      cursor: pointer; width: 32px; height: 32px; display: flex;
      align-items: center; justify-content: center; border-radius: 8px;
    }
    .storyboard-close:hover { background: var(--accent-dim); color: var(--text); }
    .storyboard-content { padding: 24px; overflow-y: auto; flex: 1; }
    .storyboard-input-area { margin-bottom: 16px; }
    .storyboard-textarea {
      width: 100%; min-height: 280px; background: var(--input-bg);
      border: 1px solid var(--card-border); border-radius: 12px; padding: 14px;
      color: var(--text); font-size: 14px; font-family: var(--font);
      resize: vertical; outline: none; line-height: 1.6;
    }
    .storyboard-textarea:focus { border-color: var(--border-focus); }
    .storyboard-actions { padding: 20px 24px; border-top: 1px solid var(--card-border); display: flex; gap: 12px; justify-content: flex-end; }
    .storyboard-btn {
      padding: 10px 20px; border-radius: 100px; font-size: 14px; font-weight: 600;
      cursor: pointer; font-family: var(--font); transition: opacity 0.15s; border: none;
    }
    .storyboard-btn-primary { background: var(--text); color: var(--btn-text); }
    .storyboard-btn-secondary { background: var(--accent-dim); color: var(--text); }
    .storyboard-preview-section { margin-bottom: 20px; }
    .storyboard-section-title { font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; }
    .storyboard-global-req {
      width: 100%; background: var(--input-bg); border: 1px solid var(--card-border);
      border-radius: 10px; padding: 12px; color: var(--text); font-size: 13px;
      line-height: 1.6; resize: vertical; min-height: 44px; font-family: var(--font);
      outline: none;
    }
    .storyboard-shots-list { display: flex; flex-direction: column; gap: 8px; }
    .storyboard-shot-item {
      background: var(--card-bg); border: 1px solid var(--card-border);
      border-radius: 10px; padding: 12px; display: flex; gap: 8px;
    }
    .storyboard-shot-number { color: var(--text-secondary); font-weight: 600; font-size: 12px; flex-shrink: 0; }
    .storyboard-shot-desc {
      color: var(--text); font-size: 13px; line-height: 1.6; flex: 1;
      background: var(--input-bg); border: 1px solid var(--card-border);
      border-radius: 8px; padding: 8px; resize: vertical; min-height: 44px;
      font-family: var(--font); outline: none;
    }

    /* ====== Prompt Compare ====== */
    .prompt-compare-overlay {
      position: fixed; top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.8); display: flex; align-items: center;
      justify-content: center; z-index: 10000; animation: fadeIn 0.15s ease;
    }
    .prompt-compare-panel {
      background: var(--modal-bg); border: 1px solid var(--border-hover)
      border-radius: var(--radius-lg); width: 90%; max-width: 900px; max-height: 85vh;
      display: flex; flex-direction: column; overflow: hidden;
    }
    .prompt-compare-header {
      padding: 20px 24px; border-bottom: 1px solid var(--card-border);
      display: flex; align-items: center; justify-content: space-between;
    }
    .prompt-compare-header h3 { margin: 0; font-size: 17px; color: var(--text); font-weight: 600; }
    .prompt-compare-close {
      background: none; border: none; color: var(--text-secondary); font-size: 22px;
      cursor: pointer; width: 32px; height: 32px; display: flex;
      align-items: center; justify-content: center; border-radius: 8px;
    }
    .prompt-compare-close:hover { background: var(--accent-dim); color: var(--text); }
    .prompt-compare-content { padding: 24px; overflow-y: auto; flex: 1; }
    .prompt-compare-section { margin-bottom: 24px; }
    .prompt-compare-label { font-size: 13px; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
    .prompt-compare-text {
      background: var(--input-bg); border: 1px solid var(--card-border);
      border-radius: 12px; padding: 16px; color: var(--text); font-size: 14px;
      line-height: 1.6; white-space: pre-wrap; word-break: break-word;
      max-height: 180px; overflow-y: auto; width: 100%; font-family: var(--font);
    }
    .prompt-compare-actions { padding: 20px 24px; border-top: 1px solid var(--card-border); display: flex; gap: 12px; justify-content: flex-end; }
    .prompt-compare-btn {
      padding: 10px 20px; border-radius: 100px; font-size: 14px; font-weight: 600;
      cursor: pointer; font-family: var(--font); transition: opacity 0.15s; border: none;
    }
    .prompt-compare-btn-secondary { background: var(--accent-dim); color: var(--text); }
    .prompt-compare-btn-primary { background: var(--text); color: var(--btn-text); }
    .prompt-compare-btn-primary:disabled { opacity: 0.4; cursor: not-allowed; }

    /* ====== Misc ====== */
    .config-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    .config-row > * { flex: 1; min-width: 200px; }
    .row { display: grid; grid-template-columns: 2fr 1fr; gap: 12px; align-items: start; }

    .card-header {
      display: flex; align-items: center; gap: 12px; margin-bottom: 4px;
    }
    .card-header h2 { flex: 0 0 auto; }

    /* ====== Responsive ====== */
    @media (max-width: 900px) {
      .bento { grid-template-columns: 1fr; gap: 10px; }
      .card-prompt, .card-settings, .card-history,
      .card-upload, .card-config, .card-folder,
      .card-presets, .card-results { grid-column: span 1; }
      .config-inline { flex-direction: column; gap: 8px; }
      .app { padding: 24px 16px 60px; }
      .hero { margin-bottom: 24px; }
      .hero h1 { font-size: 24px; }
      .results { grid-template-columns: 1fr; }
      .history-grid { grid-template-columns: repeat(2, 1fr); }
      .row { grid-template-columns: 1fr; }
      .config-row { flex-direction: column; }
      .config-row > * { min-width: 0; }
      .prompt-buttons-container { flex-direction: column; }
    }

    @media (min-width: 901px) and (max-width: 1100px) {
      .bento { grid-template-columns: repeat(4, 1fr); }
      .card-prompt { grid-column: span 2; }
      .card-settings { grid-column: span 2; }
      .card-history { grid-column: span 2; }
      .card-upload { grid-column: span 2; }
      .card-config { grid-column: span 2; }
      .card-folder { grid-column: span 2; }
      .card-presets { grid-column: span 4; }
      .card-results { grid-column: span 4; }
    }
  </style>
</head>

<body>
  <div class="toast-container" id="toast-container"></div>
  <div class="app">
    <!-- Hero -->
    <header class="hero">
      <div class="hero-left">
        <h1>Image Studio</h1>
        <p class="sub">文生图 / 图生图 · 参考图最多 4 张</p>
        <span class="badge">&#9702; API 已在后端配置</span>
      </div>
      <div style="display:flex;align-items:center;gap:10px;">
        <span class="hero-user" id="hero-user" style="display:none;font-size:13px;color:var(--text-secondary)"></span>
        <button class="btn-ghost" id="changepw-btn" style="display:none;font-size:11px;">改密</button>
        <button class="btn-ghost" id="login-btn" style="font-size:12px;">登录</button>
        <button class="btn-ghost" id="logout-btn" style="display:none;font-size:12px;">退出</button>
        <a class="btn-ghost" id="admin-link" href="admin/" target="_blank" style="display:none;font-size:12px;text-decoration:none;">后台</a>
        <button class="theme-toggle" id="theme-toggle" title="切换深色/浅色模式">&#9788;</button>
      </div>
    </header>

    <!-- 修改密码弹窗 -->
    <div class="dialog-overlay" id="changepw-dialog">
      <div class="dialog-content">
        <div class="dialog-title">修改密码</div>
        <input class="dialog-input" id="changepw-old" type="password" placeholder="原密码">
        <input class="dialog-input" id="changepw-new" type="password" placeholder="新密码（至少4位）">
        <div class="dialog-actions">
          <button class="dialog-btn dialog-btn-cancel" id="changepw-cancel">取消</button>
          <button class="dialog-btn dialog-btn-confirm" id="changepw-submit">确认修改</button>
        </div>
      </div>
    </div>

    <!-- 登录注册弹窗 -->
    <div class="dialog-overlay" id="auth-dialog">
      <div class="dialog-content">
        <div class="dialog-title" id="auth-title">登录</div>
        <input class="dialog-input" id="auth-username" type="text" placeholder="用户名">
        <input class="dialog-input" id="auth-password" type="password" placeholder="密码">
        <input class="dialog-input" id="auth-password2" type="password" placeholder="确认密码" style="display:none">
        <div class="dialog-actions">
          <button class="dialog-btn dialog-btn-cancel" id="auth-cancel">取消</button>
          <button class="dialog-btn dialog-btn-confirm" id="auth-submit">登录</button>
        </div>
        <div style="text-align:center;margin-top:12px;font-size:12px;color:var(--text-tertiary)">
          <a href="#" id="auth-switch" style="color:var(--text-secondary)">没有账号？去注册</a>
        </div>
      </div>
    </div>

    <!-- Bento Grid -->
    <div class="bento">
      <!-- 提示词 -->
      <div class="glass-card card-prompt">
        <label for="prompt">提示词（必填）</label>
        <textarea id="prompt" placeholder="例如：女生坐在口播室，柔和光线，电影感"></textarea>
        <div class="prompt-buttons-container">
          <button class="save-prompt-from-input-btn" id="save-prompt-from-input">保存到提示词库</button>
          <button class="optimize-prompt-btn" id="optimize-prompt-btn">AI 优化提示词</button>
        </div>
      </div>

      <!-- 参数 & 生成 -->
      <div class="glass-card card-settings">
        <label for="aspect">图片比例</label>
        <select id="aspect" hidden>
          <option value="auto" selected>自动</option>
          <option value="1:1">1:1</option>
          <option value="3:4">3:4</option>
          <option value="4:3">4:3</option>
          <option value="16:9">16:9</option>
          <option value="9:16">9:16</option>
        </select>
        <div class="btn-group" id="aspect-btns"></div>

        <label for="resolution" style="margin-top:10px;">清晰度</label>
        <select id="resolution" hidden>
          <option value="1K" selected>1K</option>
          <option value="2K">2K</option>
          <option value="4K">4K</option>
        </select>
        <div class="btn-group" id="resolution-btns"></div>

        <label for="count" style="margin-top:10px;">生成张数</label>
        <div style="display:flex;align-items:center;gap:8px;">
          <input id="count" type="number" min="1" max="10" value="1" style="width:72px;">
          <span class="muted">最多 10 张</span>
        </div>

        <div class="status" id="status">请先登录后再生成图片</div>
        <button class="btn" id="run" style="width:100%; margin-top:8px;">开始生产图片</button>
      </div>

      <!-- 结果 & 历史（共享同一位置，互斥显示） -->
      <div class="results-area">
        <div class="glass-card card-results" id="results-card" style="display:none;">
          <div class="card-header">
            <h2>返回结果</h2>
            <span class="muted" id="result-count">0 条</span>
            <button class="btn-ghost" id="clear-results">清空结果</button>
          </div>
          <div class="results" id="results"></div>
        </div>

        <div class="glass-card card-history" id="history-card">
          <div class="history-header">
            <h2>历史记录</h2>
            <span class="muted" id="history-count">0 条</span>
            <button class="btn-ghost" id="batch-download" style="display:none">批量下载</button>
            <button class="btn-ghost" id="clear-history">清空历史</button>
          </div>
          <div class="history-grid" id="history-grid">
            <div class="history-empty">暂无历史记录</div>
          </div>
        </div>
      </div>

      <!-- 上传参考图 -->
      <div class="glass-card card-upload">
        <label for="image">上传参考图（最多 4 张 · 拖拽/粘贴）</label>
        <input id="image" type="file" accept="image/png,image/jpeg" multiple style="margin-bottom:8px;">
        <div class="uploads" id="upload-preview"></div>
      </div>

      <!-- 模型 & 协议 -->
      <div class="glass-card card-config">
        <label>模型 &amp; 协议</label>
        <div class="config-inline">
          <div>
            <select id="image-model">
              <option value="gpt-image-2" selected>gpt-image-2</option>
            </select>
          </div>
          <div>
            <select id="text-model">
              <option value="deepseek-v4-pro" selected>deepseek-v4-pro</option>
            </select>
          </div>
          <div>
            <select id="api-protocol">
              <option value="gemini">Gemini 原生</option>
              <option value="openai-images" selected>OpenAI Images</option>
              <option value="openai-images">OpenAI Images</option>
            </select>
          </div>
        </div>
        <button class="btn-mini" id="fetch-models-btn" style="margin-top:10px;">拉取模型列表</button>
      </div>

      <!-- 保存状态 -->
      <div class="glass-card card-folder">
        <label>图片存储</label>
        <div class="save-settings">
          <span class="path-value" id="save-path">服务器 uploads/ 目录</span>
        </div>
      </div>

      <!-- 快捷工具 -->
      <div class="card-presets">
        <div class="preset-scenarios collapsed">
          <div class="preset-collapse-header" id="preset-collapse-toggle">
            <div class="preset-collapse-title">快捷工具</div>
            <div class="preset-collapse-icon">&#9660;</div>
          </div>
          <div class="preset-collapse-content">
            <div class="preset-tab-buttons">
              <button class="preset-tab-btn active" data-tab="scenarios">快捷场景</button>
              <button class="preset-tab-btn" data-tab="library">提示词库</button>
            </div>
            <div class="preset-tab-content active" data-tab="scenarios">
              <div class="preset-scenarios-title">快捷场景</div>
              <div class="preset-buttons"></div>
            </div>
            <div class="preset-tab-content" data-tab="library">
              <div class="prompt-library-container">
                <div class="prompt-library-empty">暂无保存的提示词</div>
                <div class="prompt-library-list"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- 图片放大模态框 -->
  <div class="lightbox" id="lightbox">
    <button class="close-btn" id="lightbox-close">&#10005;</button>
    <img src="" alt="放大图片" id="lightbox-img">
  </div>

  <!-- 角度调整弹窗 -->
  <div class="angle-modal-overlay" id="angle-modal">
    <div class="angle-modal">
      <div class="angle-modal-header">
        <div class="angle-modal-title">自定义产品角度</div>
        <button class="angle-modal-close" id="angle-modal-close">&#10005;</button>
      </div>
      <div class="angle-modal-body">
        <div class="angle-modal-main">
          <div class="angle-reference-panel">
            <div class="angle-reference-title">参考图片</div>
            <div class="angle-reference-image" id="angle-reference-image">
              <div class="angle-reference-placeholder">未选择参考图片</div>
            </div>
          </div>
          <div class="angle-canvas-container">
            <div class="angle-canvas-hint">左键：旋转 | 右键：平移 | 滚轮：缩放</div>
            <canvas id="angle-canvas"></canvas>
            <div class="angle-controls-panel">
              <div class="angle-controls-title">镜头参数</div>
              <div class="angle-control-group">
                <div class="angle-control-label">
                  <span>方位角（旋转）</span>
                  <span class="angle-control-value" id="azimuth-value">0°</span>
                </div>
                <input type="range" class="angle-slider" id="azimuth-slider" min="0" max="360" value="0" step="1">
              </div>
              <div class="angle-control-group">
                <div class="angle-control-label">
                  <span>俯仰角（Pitch）</span>
                  <span class="angle-control-value" id="pitch-value">0°</span>
                </div>
                <input type="range" class="angle-slider" id="pitch-slider" min="-90" max="90" value="0" step="1">
              </div>
              <div class="angle-control-group">
                <div class="angle-control-label">
                  <span>缩放（距离）</span>
                  <span class="angle-control-value" id="zoom-value">1.0x</span>
                </div>
                <input type="range" class="angle-slider" id="zoom-slider" min="0.5" max="3.0" value="1.0" step="0.1">
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="angle-modal-footer">
        <button class="angle-modal-btn angle-modal-btn-cancel" id="angle-modal-cancel">取消</button>
        <button class="angle-modal-btn angle-modal-btn-confirm" id="angle-modal-confirm">确定</button>
      </div>
    </div>
  </div>

  <script>
    (() => {
      // API 请求走 PHP 代理，Key 在后端 config.php 中配置
      const PROXY_URL = 'proxy.php?path=';

      function getBaseUrl() {
        return PROXY_URL;
      }

      // 模型选择下拉框
      const imageModelSelect = document.getElementById('image-model');
      const textModelSelect = document.getElementById('text-model');
      const protocolSelect = document.getElementById('api-protocol');

      function getImageModel() {
        return imageModelSelect.value || 'gpt-image-2';
      }
      function generateFilename(ext) {
        const model = getImageModel().replace(/[^a-zA-Z0-9\-]/g, '-');
        return `${model}-${Date.now()}.${ext}`;
      }
      function getTextModel() {
        return textModelSelect.value || 'deepseek-v4-pro';
      }
      function getProtocol() {
        return protocolSelect.value || 'openai-images';
      }

      // 获取生图 endpoint（根据协议自动切换）
      function getEndpoint() {
        const protocol = getProtocol();
        let path;
        if (protocol === 'openai-chat') {
          path = '/v1/chat/completions';
        } else if (protocol === 'openai-images') {
          path = '/v1/images/generations';
        } else {
          path = `/v1beta/models/${getImageModel()}:generateContent`;
        }
        return getBaseUrl() + encodeURIComponent(path);
      }

      // 文本操作 endpoint（分镜分析、优化、翻译）
      function getFlashEndpoint() {
        const protocol = getProtocol();
        let path;
        if (protocol === 'gemini') {
          path = `/v1beta/models/${getTextModel()}:generateContent`;
        } else {
          path = '/v1/chat/completions';
        }
        return getBaseUrl() + encodeURIComponent(path);
      }

      const promptInput = document.getElementById('prompt');
      const fileInput = document.getElementById('image');
      const aspectSelect = document.getElementById('aspect');
      const resolutionSelect = document.getElementById('resolution');
      const countInput = document.getElementById('count');
      const statusEl = document.getElementById('status');
      const runBtn = document.getElementById('run');
      const preview = document.getElementById('upload-preview');
      const resultsEl = document.getElementById('results');
      const resultCountEl = document.getElementById('result-count');

      const state = { images: [] };
      let timeoutHandle = null;

      const originalAspectOptions = Array.from(aspectSelect.options).map(option => ({
        value: option.value,
        text: option.textContent
      }));

      function getAllowedOpenAIImageAspects(resolution) {
        if (resolution === '4K') return ['auto', '16:9', '9:16'];
        if (resolution === '2K') return ['auto', '1:1', '16:9', '9:16'];
        return ['auto', '1:1', '3:4', '4:3', '16:9', '9:16'];
      }

      // ---- 按钮组：同步 <select> 到按钮 ----
      function buildBtnGroup(selectEl, containerEl) {
        containerEl.innerHTML = '';
        Array.from(selectEl.options).forEach(opt => {
          const btn = document.createElement('button');
          btn.textContent = opt.textContent;
          btn.dataset.value = opt.value;
          btn.addEventListener('click', () => {
            selectEl.value = opt.value;
            selectEl.dispatchEvent(new Event('change'));
            updateBtnGroup(containerEl, opt.value);
          });
          containerEl.appendChild(btn);
        });
        updateBtnGroup(containerEl, selectEl.value);
      }

      function updateBtnGroup(containerEl, value) {
        containerEl.querySelectorAll('button').forEach(btn => {
          btn.classList.toggle('active', btn.dataset.value === value);
        });
      }

      function refreshAspectOptions() {
        const previousValue = aspectSelect.value;
        const allowedValues = getProtocol() === 'openai-images'
          ? getAllowedOpenAIImageAspects(resolutionSelect.value)
          : originalAspectOptions.map(option => option.value);

        aspectSelect.innerHTML = '';
        originalAspectOptions
          .filter(option => allowedValues.includes(option.value))
          .forEach(option => aspectSelect.add(new Option(option.text, option.value)));

        aspectSelect.value = allowedValues.includes(previousValue) ? previousValue : 'auto';
        buildBtnGroup(aspectSelect, document.getElementById('aspect-btns'));
      }

      // 任务管理：每个任务有独立的定时器
      const taskTimers = new Map(); // taskId -> intervalId

      // 预设场景配置
      const presetScenarios = [
        {
          id: 'multi-angle',
          label: '📦 产品多角度展示',
          description: '通过3D场景自定义产品拍摄角度',
          requiresReference: true,
          prompts: [
            'Front view of the product, centered, professional product photography, white background, studio lighting, high quality, detailed',
            'Side view of the product, 90 degree angle, professional product photography, white background, studio lighting, high quality, detailed',
            'Top view of the product, bird\'s eye view, professional product photography, white background, studio lighting, high quality, detailed',
            'Back view of the product, rear angle, professional product photography, white background, studio lighting, high quality, detailed'
          ],
          angles: ['正面', '侧面', '俯视', '背面']
        },
        {
          id: 'storyboard',
          label: '🎬 分镜生成',
          description: '智能识别分镜脚本，批量生成图片',
          requiresReference: false,
          isStoryboard: true
        }
      ];

      // 任务管理变量
      let activeTasks = new Map();
      let taskIdCounter = 0;

      // Lightbox 相关元素
      const lightbox = document.getElementById('lightbox');
      const lightboxImg = document.getElementById('lightbox-img');
      const lightboxClose = document.getElementById('lightbox-close');

      // 打开 Lightbox
      function openLightbox(imgSrc) {
        lightboxImg.src = imgSrc;
        lightbox.classList.add('show');
        document.body.style.overflow = 'hidden';
      }

      // 关闭 Lightbox
      function closeLightbox() {
        lightbox.classList.remove('show');
        document.body.style.overflow = '';
      }

      // Lightbox 事件监听
      lightboxClose.addEventListener('click', (e) => {
        e.stopPropagation();
        closeLightbox();
      });

      lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) {
          closeLightbox();
        }
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && lightbox.classList.contains('show')) {
          closeLightbox();
        }
      });

      // ========== IndexedDB 历史记录模块 ==========
      const DB_NAME = 'GeminiImageHistory';
      const DB_VERSION = 2;  // 版本 2：添加提示词库功能
      const STORE_NAME = 'history';
      const MAX_HISTORY = 100;
      let db = null;

      // 历史记录相关 DOM 元素
      const historyGrid = document.getElementById('history-grid');
      const historyCountEl = document.getElementById('history-count');
      const clearHistoryBtn = document.getElementById('clear-history');
      const savePathEl = document.getElementById('save-path');

      // 初始化 IndexedDB
      function initDB() {
        return new Promise((resolve, reject) => {
          const request = indexedDB.open(DB_NAME, DB_VERSION);

          request.onerror = () => reject(request.error);

          request.onsuccess = () => {
            db = request.result;
            resolve(db);
          };

          request.onupgradeneeded = (event) => {
            const database = event.target.result;
            if (!database.objectStoreNames.contains(STORE_NAME)) {
              const store = database.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
              store.createIndex('timestamp', 'timestamp', { unique: false });
            }
            // 创建提示词库 Store
            if (!database.objectStoreNames.contains('prompts')) {
              const promptStore = database.createObjectStore('prompts', { keyPath: 'id', autoIncrement: true });
              promptStore.createIndex('createdAt', 'createdAt', { unique: false });
            }
          };
        });
      }

      // 保存历史记录
      async function saveHistory(record) {
        if (!db) await initDB();
        console.log('[saveHistory] 准备写入:', record.filename, 'prompt:', record.prompt?.slice(0,30));

        return new Promise((resolve, reject) => {
          const transaction = db.transaction([STORE_NAME], 'readwrite');
          const store = transaction.objectStore(STORE_NAME);

          const request = store.add(record);
          request.onsuccess = () => {
            console.log('[saveHistory] 写入成功, id:', request.result);
            trimHistory().then(() => resolve(request.result));
          };
          request.onerror = () => {
            console.error('[saveHistory] 写入失败:', request.error);
            reject(request.error);
          };
        });
      }

      // 限制历史记录数量
      async function trimHistory() {
        return new Promise((resolve) => {
          const transaction = db.transaction([STORE_NAME], 'readwrite');
          const store = transaction.objectStore(STORE_NAME);
          const index = store.index('timestamp');
          const countRequest = store.count();

          countRequest.onsuccess = () => {
            const count = countRequest.result;
            if (count > MAX_HISTORY) {
              const deleteCount = count - MAX_HISTORY;
              const cursorRequest = index.openCursor();
              let deleted = 0;

              cursorRequest.onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor && deleted < deleteCount) {
                  store.delete(cursor.primaryKey);
                  deleted++;
                  cursor.continue();
                } else {
                  resolve();
                }
              };
            } else {
              resolve();
            }
          };
        });
      }

      // 加载所有历史记录
      async function loadHistory() {
        if (!db) await initDB();

        return new Promise((resolve, reject) => {
          const transaction = db.transaction([STORE_NAME], 'readonly');
          const store = transaction.objectStore(STORE_NAME);
          const request = store.getAll();

          request.onsuccess = () => {
            // 按时间戳倒序排列（最新的在前）
            const records = request.result.sort((a, b) => b.timestamp - a.timestamp);
            resolve(records);
          };
          request.onerror = () => reject(request.error);
        });
      }

      // 删除单条历史记录
      async function deleteHistoryById(id) {
        if (!db) await initDB();

        return new Promise((resolve, reject) => {
          const transaction = db.transaction([STORE_NAME], 'readwrite');
          const store = transaction.objectStore(STORE_NAME);
          const request = store.delete(id);

          request.onsuccess = () => resolve();
          request.onerror = () => reject(request.error);
        });
      }

      // 清空所有历史记录
      async function clearAllHistory() {
        if (!db) await initDB();

        return new Promise((resolve, reject) => {
          const transaction = db.transaction([STORE_NAME], 'readwrite');
          const store = transaction.objectStore(STORE_NAME);
          const request = store.clear();

          request.onsuccess = () => resolve();
          request.onerror = () => reject(request.error);
        });
      }

      // ========== 提示词库模块 ==========

      // 保存提示词到库
      async function savePromptToLibrary(title, content) {
        if (!db) await initDB();

        return new Promise((resolve, reject) => {
          const transaction = db.transaction(['prompts'], 'readwrite');
          const store = transaction.objectStore('prompts');

          const record = {
            title: title,
            content: content,
            createdAt: Date.now(),
            usageCount: 0
          };

          const request = store.add(record);
          request.onsuccess = () => resolve(request.result);
          request.onerror = () => reject(request.error);
        });
      }

      // 加载所有提示词
      async function loadAllPrompts() {
        if (!db) await initDB();

        return new Promise((resolve, reject) => {
          const transaction = db.transaction(['prompts'], 'readonly');
          const store = transaction.objectStore('prompts');
          const request = store.getAll();

          request.onsuccess = () => {
            // 按创建时间倒序排列（最新的在前）
            const records = request.result.sort((a, b) => b.createdAt - a.createdAt);
            resolve(records);
          };
          request.onerror = () => reject(request.error);
        });
      }

      // 删除提示词
      async function deletePrompt(id) {
        if (!db) await initDB();

        return new Promise((resolve, reject) => {
          const transaction = db.transaction(['prompts'], 'readwrite');
          const store = transaction.objectStore('prompts');
          const request = store.delete(id);

          request.onsuccess = () => resolve();
          request.onerror = () => reject(request.error);
        });
      }

      // 增加提示词使用次数
      async function incrementPromptUsage(id) {
        if (!db) await initDB();

        return new Promise((resolve, reject) => {
          const transaction = db.transaction(['prompts'], 'readwrite');
          const store = transaction.objectStore('prompts');
          const getRequest = store.get(id);

          getRequest.onsuccess = () => {
            const record = getRequest.result;
            if (record) {
              record.usageCount = (record.usageCount || 0) + 1;
              const updateRequest = store.put(record);
              updateRequest.onsuccess = () => resolve();
              updateRequest.onerror = () => reject(updateRequest.error);
            } else {
              resolve();
            }
          };
          getRequest.onerror = () => reject(getRequest.error);
        });
      }

      // 生成缩略图
      function createThumbnail(base64Src, maxSize = 200) {
        return new Promise((resolve, reject) => {
          const img = new Image();
          // 跨域 URL 走 PHP 代理避免 CORS 问题
          const src = /^https?:\/\//.test(base64Src)
            ? `image-proxy.php?url=${encodeURIComponent(base64Src)}`
            : base64Src;
          img.onload = () => {
            try {
              const canvas = document.createElement('canvas');
              let { width, height } = img;

              if (width > height) {
                if (width > maxSize) { height = Math.round(height * maxSize / width); width = maxSize; }
              } else {
                if (height > maxSize) { width = Math.round(width * maxSize / height); height = maxSize; }
              }

              canvas.width = width;
              canvas.height = height;
              const ctx = canvas.getContext('2d');
              ctx.drawImage(img, 0, 0, width, height);
              resolve(canvas.toDataURL('image/jpeg', 0.7));
            } catch (e) {
              // 跨域图片导致 canvas 污染，直接用原图作缩略图
              console.warn('缩略图生成失败（跨域），使用原图', e.message);
              resolve(base64Src);
            }
          };
          img.onerror = () => {
            console.warn('缩略图加载失败，使用原图');
            resolve(base64Src);
          };
          img.src = src;
        });
      }

      // 渲染历史记录
      let historyPage = 1;
      let historyServerTotal = 0; // 服务器端记录总数
      const HISTORY_PAGE_SIZE = 2; // 1行 x 2列

      async function renderHistory(page = 1) {
        try {
          // 未登录 → 隐藏历史
          if (!currentUser) {
            historyGrid.innerHTML = '<div class="history-empty">请登录后查看历史记录</div>';
            historyCountEl.textContent = '0 条';
            return;
          }

          // 登录后从服务器加载（跨设备同步）
          let records = [];
          let totalFromServer = 0;
          try {
            const serverPage = Math.ceil((page * HISTORY_PAGE_SIZE) / 50); // 服务端每页50条
            const res = await fetch(`api/history.php?page=${serverPage}`);
            if (res.ok) {
              const data = await res.json();
              historyServerTotal = data.total || 0;
              totalFromServer = data.total || 0;
              records = (data.list || []).map(r => ({
                id: 'srv_' + r.id,
                filename: r.filename,
                prompt: r.prompt || '',
                timestamp: new Date(r.created_at).getTime(),
                thumbnail: `load.php?file=${encodeURIComponent(r.filename)}&user=${encodeURIComponent(currentUser.username)}`,
                username: currentUser.username
              }));
            }
          } catch (e) {
            console.warn('服务器历史加载失败', e.message);
          }

          // 服务端无数据 → 保持空（不再降级到本地 IndexedDB，以保证和后台数据一致）

          const totalPages = Math.ceil(totalFromServer / HISTORY_PAGE_SIZE) || 1;
          historyPage = Math.min(page, totalPages);
          const start = (historyPage - 1) * HISTORY_PAGE_SIZE;
          const pageRecords = records.slice(start, start + HISTORY_PAGE_SIZE);

          console.log('[renderHistory] 总', totalFromServer, '条，第', historyPage, '页');
          historyCountEl.textContent = `${totalFromServer} 条`;

          if (totalFromServer === 0) {
            historyGrid.innerHTML = '<div class="history-empty">暂无生成记录</div>';
            return;
          }

          historyGrid.innerHTML = '';
          pageRecords.forEach(record => {
            const card = document.createElement('div');
            card.className = 'history-card';

            // 判断是否有文件名（新版本记录才有）
            const hasFilename = record.filename && record.filename.length > 0;

            card.style.position = 'relative';
            card.innerHTML = `
              <input type="checkbox" class="hist-check" data-file="${escapeHtml(record.filename||'')}" style="position:absolute;top:8px;left:8px;z-index:2;width:16px;height:16px;accent-color:var(--text);cursor:pointer" title="选择下载">
              <img src="${record.thumbnail}" alt="缩略图">
              <div class="info">
                <div class="prompt-container">
                  <div class="prompt" title="点击复制提示词">${escapeHtml(record.prompt || '无提示词')}</div>
                </div>
                <div class="meta">
                  <span>${formatDate(record.timestamp)}</span>
                  <button class="regen-btn" title="重新生成">🔄</button>
                  ${hasFilename ? '<button class="add-btn" title="添加到参考图">➕</button>' : ''}
                  ${hasFilename ? '<button class="hd-btn" title="从文件夹加载高清图">🔍</button>' : ''}
                  <button class="save-prompt-btn" title="保存提示词到库">💾</button>
                  <button class="delete-btn" data-id="${record.id}">🗑️</button>
                </div>
              </div>
            `;

            // 点击缩略图放大查看（优先加载服务器高清原图）
            const histImg = card.querySelector('img');
            if (record.filename) {
              histImg.style.cursor = 'zoom-in';
              histImg.title = '点击查看高清原图';
            }
            histImg.addEventListener('click', async () => {
              if (record.filename) {
                try {
                  const hdImage = await loadServerImage(record.filename);
                  openLightbox(hdImage);
                  return;
                } catch (_) { /* fallback */ }
              }
              if (record.thumbnail) openLightbox(record.thumbnail);
            });

            // 点击提示词复制
            const promptEl = card.querySelector('.prompt');
            promptEl.style.cursor = 'pointer';
            promptEl.addEventListener('click', async () => {
              const promptText = record.prompt || '无提示词';
              let ok = false;
              try {
                if (navigator.clipboard) {
                  await navigator.clipboard.writeText(promptText);
                  ok = true;
                }
              } catch (_) {}
              if (!ok) {
                // HTTP 环境降级方案
                try {
                  const ta = document.createElement('textarea');
                  ta.value = promptText; ta.style.position = 'fixed'; ta.style.left = '-9999px';
                  document.body.appendChild(ta); ta.select();
                  document.execCommand('copy');
                  document.body.removeChild(ta);
                  ok = true;
                } catch (_) {}
              }
              if (ok) {
                const originalText = promptEl.textContent;
                promptEl.textContent = '✓ 已复制';
                promptEl.style.color = 'var(--success)';
                setTimeout(() => {
                  promptEl.textContent = originalText;
                  promptEl.style.color = '';
                }, 1500);
              }
            });

            // 添加到参考图按钮
            const addBtn = card.querySelector('.add-btn');
            if (addBtn && hasFilename) {
              addBtn.addEventListener('click', async (e) => {
                e.stopPropagation();

                if (state.images.length >= 4) {
                  alert('参考图最多只能添加 4 张');
                  return;
                }

                try {
                  addBtn.textContent = '⏳';
                  addBtn.disabled = true;

                  const hdImage = await loadServerImage(record.filename);

                  // 添加到参考图
                  state.images.push({
                    name: record.filename,
                    mime: 'image/png',
                    dataUrl: hdImage
                  });

                  renderUploads();
                  flashStatus(`已添加到参考图（共 ${state.images.length} 张）`, 'success');

                  addBtn.textContent = '✓';
                  setTimeout(() => {
                    addBtn.textContent = '➕';
                    addBtn.disabled = false;
                  }, 1500);
                } catch (err) {
                  addBtn.textContent = '➕';
                  addBtn.disabled = false;
                  alert(err.message || '加载图片失败');
                }
              });
            }

            // 查看高清按钮
            const hdBtn = card.querySelector('.hd-btn');
            if (hdBtn && hasFilename) {
              hdBtn.addEventListener('click', async (e) => {
                e.stopPropagation();

                try {
                  hdBtn.textContent = '⏳';
                  hdBtn.disabled = true;

                  const hdImage = await loadServerImage(record.filename);
                  openLightbox(hdImage);

                  hdBtn.textContent = '🔍HD';
                  hdBtn.disabled = false;
                } catch (err) {
                  hdBtn.textContent = '🔍HD';
                  hdBtn.disabled = false;
                  alert(err.message || '加载高清图失败');
                }
              });
            }

            // 保存提示词到库按钮
            // 重新生成按钮
            const regenBtn = card.querySelector('.regen-btn');
            if (regenBtn && record.prompt) {
              regenBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                promptInput.value = record.prompt;
                toggleResults(true);
                handleRun();
              });
            }

            const savePromptBtn = card.querySelector('.save-prompt-btn');
            if (savePromptBtn && record.prompt) {
              savePromptBtn.addEventListener('click', async (e) => {
                e.stopPropagation();
                showSavePromptDialog(record.prompt);
              });
            }

            // 长提示词 tooltip
            const promptContainer = card.querySelector('.prompt-container');
            if (promptContainer) {
              const promptEl = promptContainer.querySelector('.prompt');
              const fullPrompt = record.prompt || '无提示词';

              // 鼠标移入时显示 tooltip
              promptContainer.addEventListener('mouseenter', () => {
                if (fullPrompt.length > 0) {
                  const tooltip = document.createElement('div');
                  tooltip.className = 'prompt-tooltip';
                  tooltip.textContent = fullPrompt;

                  // 计算位置（在提示词下方）
                  const rect = promptContainer.getBoundingClientRect();
                  tooltip.style.position = 'fixed';
                  tooltip.style.top = (rect.bottom + 8) + 'px';
                  tooltip.style.left = rect.left + 'px';

                  document.body.appendChild(tooltip);
                  promptContainer._tooltip = tooltip;

                  // 避免超出屏幕右边界
                  setTimeout(() => {
                    const tooltipRect = tooltip.getBoundingClientRect();
                    if (tooltipRect.right > window.innerWidth - 10) {
                      tooltip.style.left = 'auto';
                      tooltip.style.right = '10px';
                    }
                  }, 10);
                }
              });

              // 鼠标移出时隐藏 tooltip
              promptContainer.addEventListener('mouseleave', () => {
                if (promptContainer._tooltip) {
                  promptContainer._tooltip.remove();
                  promptContainer._tooltip = null;
                }
              });
            }

            // 删除按钮
            card.querySelector('.delete-btn').addEventListener('click', async (e) => {
              e.stopPropagation();
              if (confirm('确定删除这条历史记录？')) {
                // 服务端记录 → 软删除（管理员后台仍可见）
                if (String(record.id).startsWith('srv_')) {
                  const srvId = String(record.id).replace('srv_', '');
                  try { await fetch(`api/history.php?action=delete&id=${srvId}`); } catch (_) {}
                }
                // 也从本地 IndexedDB 删除
                if (!String(record.id).startsWith('srv_')) {
                  await deleteHistoryById(record.id);
                }
                await renderHistory();
              }
            });

            historyGrid.appendChild(card);
          });

          // 翻页控制（最多显示 7 个页码）
          if (totalPages > 1) {
            const pager = document.createElement('div');
            pager.style.cssText = 'grid-column:1/-1;display:flex;align-items:center;justify-content:center;gap:4px;margin-top:12px;flex-wrap:wrap;';

            const pages = [];
            if (totalPages <= 7) {
              for (let p = 1; p <= totalPages; p++) pages.push(p);
            } else {
              pages.push(1);
              if (historyPage > 3) pages.push('...');
              for (let p = Math.max(2, historyPage - 1); p <= Math.min(totalPages - 1, historyPage + 1); p++) pages.push(p);
              if (historyPage < totalPages - 2) pages.push('...');
              pages.push(totalPages);
            }

            let btns = `<button class="btn-mini hist-page-btn" ${historyPage <= 1 ? 'disabled' : ''} data-p="${historyPage - 1}">‹</button>`;
            pages.forEach(p => {
              if (p === '...') btns += '<span style="padding:0 4px;color:var(--text-tertiary)">…</span>';
              else btns += `<button class="btn-mini hist-page-btn" style="${p === historyPage ? 'background:var(--text);color:var(--btn-text);border:none;min-width:28px' : 'min-width:28px'}" data-p="${p}">${p}</button>`;
            });
            btns += `<button class="btn-mini hist-page-btn" ${historyPage >= totalPages ? 'disabled' : ''} data-p="${historyPage + 1}">›</button>`;
            pager.innerHTML = btns;
            historyGrid.appendChild(pager);

            pager.querySelectorAll('.hist-page-btn').forEach(btn => {
              btn.addEventListener('click', () => {
                const p = parseInt(btn.dataset.p);
                if (p >= 1 && p <= totalPages) renderHistory(p);
              });
            });
          }
        } catch (err) {
          console.error('加载历史记录失败:', err);
          historyGrid.innerHTML = '<div class="history-empty">加载历史记录失败</div>';
        }
      }

      // 辅助函数：HTML 转义
      function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
      }

      // 辅助函数：格式化日期
      function formatDate(timestamp) {
        const date = new Date(timestamp);
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hour = String(date.getHours()).padStart(2, '0');
        const minute = String(date.getMinutes()).padStart(2, '0');
        return `${month}-${day} ${hour}:${minute}`;
      }

      // ========== 提示词库 UI 交互 ==========

      // 显示保存提示词对话框
      function showSavePromptDialog(promptContent) {
        // 创建对话框 HTML
        const dialogOverlay = document.createElement('div');
        dialogOverlay.className = 'dialog-overlay active';

        // 自动生成标题（取前 20 个字符）
        const autoTitle = promptContent.substring(0, 20) + (promptContent.length > 20 ? '...' : '');

        dialogOverlay.innerHTML = `
          <div class="dialog-content">
            <div class="dialog-title">💾 保存提示词到库</div>
            <input class="dialog-input" type="text" placeholder="输入提示词标题" value="${escapeHtml(autoTitle)}" />
            <div class="dialog-actions">
              <button class="dialog-btn dialog-btn-cancel">取消</button>
              <button class="dialog-btn dialog-btn-confirm">保存</button>
            </div>
          </div>
        `;

        document.body.appendChild(dialogOverlay);

        const input = dialogOverlay.querySelector('.dialog-input');
        const cancelBtn = dialogOverlay.querySelector('.dialog-btn-cancel');
        const confirmBtn = dialogOverlay.querySelector('.dialog-btn-confirm');

        // 输入框自动获焦
        setTimeout(() => input.focus(), 100);
        input.select();

        // 取消按钮
        cancelBtn.addEventListener('click', () => {
          dialogOverlay.remove();
        });

        // 保存按钮
        confirmBtn.addEventListener('click', async () => {
          const title = input.value.trim();
          if (!title) {
            alert('请输入提示词标题');
            return;
          }

          try {
            confirmBtn.disabled = true;
            confirmBtn.textContent = '保存中...';

            // 保存到 IndexedDB
            await savePromptToLibrary(title, promptContent);

            // 刷新提示词库列表
            await renderPromptLibrary();

            // 显示成功反馈
            dialogOverlay.remove();
            flashStatus('✓ 已保存到提示词库', 'success');

            // 切换到提示词库标签页
            switchPresetTab('library');
          } catch (err) {
            confirmBtn.disabled = false;
            confirmBtn.textContent = '保存';
            console.error('保存失示词失败:', err);
            alert('保存失败: ' + err.message);
          }
        });

        // Enter 键保存
        input.addEventListener('keypress', (e) => {
          if (e.key === 'Enter') {
            confirmBtn.click();
          }
        });

        // Escape 键取消
        dialogOverlay.addEventListener('keydown', (e) => {
          if (e.key === 'Escape') {
            cancelBtn.click();
          }
        });
      }

      // 切换标签页
      function switchPresetTab(tabName) {
        // 隐藏所有标签页内容
        document.querySelectorAll('.preset-tab-content').forEach(tab => {
          tab.classList.remove('active');
        });

        // 取消所有标签页按钮的激活状态
        document.querySelectorAll('.preset-tab-btn').forEach(btn => {
          btn.classList.remove('active');
        });

        // 显示目标标签页内容
        const activeContent = document.querySelector(`.preset-tab-content[data-tab="${tabName}"]`);
        if (activeContent) {
          activeContent.classList.add('active');
        }

        // 激活目标标签页按钮
        const activeBtn = document.querySelector(`.preset-tab-btn[data-tab="${tabName}"]`);
        if (activeBtn) {
          activeBtn.classList.add('active');
        }
      }

      // 渲染提示词库列表
      async function renderPromptLibrary() {
        try {
          const prompts = await loadAllPrompts();
          const libraryList = document.querySelector('.prompt-library-list');
          const libraryEmpty = document.querySelector('.prompt-library-empty');

          if (prompts.length === 0) {
            libraryList.innerHTML = '';
            libraryEmpty.style.display = 'block';
            return;
          }

          libraryEmpty.style.display = 'none';
          libraryList.innerHTML = '';

          prompts.forEach(prompt => {
            const item = document.createElement('div');
            item.className = 'prompt-lib-item';

            item.innerHTML = `
              <div class="prompt-lib-item-title">${escapeHtml(prompt.title)}</div>
              <div class="prompt-lib-item-content" title="点击复制完整内容">${escapeHtml(prompt.content)}</div>
              <div class="prompt-lib-actions">
                <button class="prompt-lib-btn" data-action="copy" data-id="${prompt.id}" title="复制到剪贴板">📋 复制</button>
                <button class="prompt-lib-btn prompt-lib-btn-delete" data-action="delete" data-id="${prompt.id}" title="删除此提示词">🗑️ 删除</button>
              </div>
            `;

            // 复制按钮
            const copyBtn = item.querySelector('[data-action="copy"]');
            copyBtn.addEventListener('click', async () => {
              let ok = false;
              try { if (navigator.clipboard) { await navigator.clipboard.writeText(prompt.content); ok = true; } } catch (_) {}
              if (!ok) {
                try {
                  const ta = document.createElement('textarea');
                  ta.value = prompt.content; ta.style.position = 'fixed'; ta.style.left = '-9999px';
                  document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
                  ok = true;
                } catch (_) {}
              }
              if (ok) {
                const originalText = copyBtn.textContent;
                copyBtn.textContent = '✓ 已复制';
                copyBtn.style.color = 'var(--success)';
                setTimeout(() => { copyBtn.textContent = originalText; copyBtn.style.color = ''; }, 1500);
              }
            });

            // 删除按钮
            const deleteBtn = item.querySelector('[data-action="delete"]');
            deleteBtn.addEventListener('click', async () => {
              if (confirm('确定删除此提示词吗？')) {
                try {
                  await deletePrompt(prompt.id);
                  await renderPromptLibrary();
                  flashStatus('已删除提示词', 'success');
                } catch (err) {
                  alert('删除失败：' + err.message);
                }
              }
            });

            libraryList.appendChild(item);
          });
        } catch (err) {
          console.error('加载提示词库失败:', err);
        }
      }

      // ========== 文件夹选择模块 ==========


      // 选择保存文件夹
      // 保存生成记录到服务端 MySQL
      async function saveImageRecord(filename, prompt) {
        if (!currentUser) return;
        try {
          await fetch('api/record.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              filename, prompt,
              model: getImageModel(),
              aspect: aspectSelect.value,
              resolution: resolutionSelect.value
            })
          });
        } catch (_) {}
      }

      // 保存图片到服务器 uploads/ 目录
      async function saveImageFile(imgSrc, filename) {
        try {
          const formData = new FormData();
          formData.append('filename', filename);
          if (currentUser) formData.append('username', currentUser.username);

          if (/^https?:\/\//.test(imgSrc)) {
            formData.append('url', imgSrc);
          } else {
            formData.append('image', imgSrc);
          }

          const res = await fetch('save.php', { method: 'POST', body: formData });
          const data = await res.json();
          if (data.success) {
            console.log(`图片已保存到服务器: ${data.path}`);
            return true;
          }
          console.error('服务器保存失败:', data.error);
          return false;
        } catch (err) {
          console.error('保存到服务器失败:', err);
          return false;
        }
      }

      // 从服务器加载高清原图
      async function loadServerImage(filename) {
        let url = `load.php?file=${encodeURIComponent(filename)}`;
        if (currentUser) url += `&user=${encodeURIComponent(currentUser.username)}`;
        const res = await fetch(url);
        if (!res.ok) throw new Error(`文件不存在：${filename}`);
        const blob = await res.blob();
        return new Promise((resolve, reject) => {
          const reader = new FileReader();
          reader.onload = () => resolve(reader.result);
          reader.onerror = () => reject(new Error('读取文件失败'));
          reader.readAsDataURL(blob);
        });
      }

      // 转换图片格式
      function convertImageFormat(base64Src, targetMime, quality = 0.92) {
        return new Promise((resolve, reject) => {
          const img = new Image();
          img.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);

            const newBase64 = canvas.toDataURL(targetMime, quality);
            resolve(newBase64);
          };
          img.onerror = () => reject(new Error('图片格式转换失败'));
          img.src = base64Src;
        });
      }

      // 获取图片信息（尺寸和文件大小）
      function getImageInfo(base64Src) {
        return new Promise((resolve, reject) => {
          const img = new Image();
          img.onload = () => {
            const size = Math.round(base64Src.length * 0.75); // base64 转字节数
            resolve({
              width: img.width,
              height: img.height,
              size: size
            });
          };
          img.onerror = () => reject(new Error('获取图片信息失败'));
          img.src = base64Src;
        });
      }

      // 清空历史按钮
      clearHistoryBtn.addEventListener('click', async () => {
        if (confirm('确定清空所有历史记录？此操作不可恢复。')) {
          await clearAllHistory();
          await renderHistory();
          flashStatus('已清空历史记录', 'success');
        }
      });

      // 拉取模型列表
      const fetchModelsBtn = document.getElementById('fetch-models-btn');

      async function fetchModelList() {
        fetchModelsBtn.disabled = true;
        fetchModelsBtn.textContent = '拉取中...';

        try {
          const res = await fetch(getBaseUrl() + encodeURIComponent('/v1/models'));
          if (!res.ok) throw new Error(`HTTP ${res.status}`);
          const data = await res.json();

          let models = [];
          if (data.data && Array.isArray(data.data)) {
            // OpenAI 格式: { data: [{ id: "xxx" }] }
            models = data.data.map(m => ({ id: m.id, name: m.id }));
          } else if (data.models && Array.isArray(data.models)) {
            // Gemini 格式: { models: [{ name: "models/xxx" }] }
            models = data.models.map(m => {
              const id = m.name?.replace('models/', '') || m.id || m.name;
              return { id, name: m.displayName || id };
            });
          }

          if (models.length === 0) {
            flashStatus('未获取到模型列表，请检查 API', 'danger');
            return;
          }

          const prevImage = imageModelSelect.value;
          const prevText = textModelSelect.value;

          imageModelSelect.innerHTML = '';
          textModelSelect.innerHTML = '';
          models.forEach(m => {
            imageModelSelect.add(new Option(m.name, m.id));
            textModelSelect.add(new Option(m.name, m.id));
          });

          // 恢复之前的选中值
          if ([...imageModelSelect.options].some(o => o.value === prevImage)) {
            imageModelSelect.value = prevImage;
          }
          if ([...textModelSelect.options].some(o => o.value === prevText)) {
            textModelSelect.value = prevText;
          }

          localStorage.setItem('model_list', JSON.stringify(models));
          flashStatus(`已获取 ${models.length} 个模型`, 'success');
        } catch (err) {
          console.error('拉取模型列表失败:', err);
          flashStatus('拉取模型列表失败: ' + err.message, 'danger');
        } finally {
          fetchModelsBtn.disabled = false;
          fetchModelsBtn.textContent = '拉取列表';
        }
      }

      fetchModelsBtn.addEventListener('click', fetchModelList);

      function loadSettings() {
        // 恢复协议选择
        const savedProtocol = localStorage.getItem('api_protocol');
        if (savedProtocol && [...protocolSelect.options].some(o => o.value === savedProtocol)) {
          protocolSelect.value = savedProtocol;
        }

        // 恢复模型列表
        const savedModels = localStorage.getItem('model_list');
        if (savedModels) {
          try {
            const models = JSON.parse(savedModels);
            if (models.length > 0) {
              imageModelSelect.innerHTML = '';
              textModelSelect.innerHTML = '';
              models.forEach(m => {
                imageModelSelect.add(new Option(m.name, m.id));
                textModelSelect.add(new Option(m.name, m.id));
              });
            }
          } catch (e) { /* ignore */ }
        }
        const savedImageModel = localStorage.getItem('image_model');
        const savedTextModel = localStorage.getItem('text_model');
        if (savedImageModel && [...imageModelSelect.options].some(o => o.value === savedImageModel)) {
          imageModelSelect.value = savedImageModel;
        }
        if (savedTextModel && [...textModelSelect.options].some(o => o.value === savedTextModel)) {
          textModelSelect.value = savedTextModel;
        }
        refreshAspectOptions();
      }

      function saveSettings() {
        localStorage.setItem('image_model', imageModelSelect.value);
        localStorage.setItem('text_model', textModelSelect.value);
        localStorage.setItem('api_protocol', protocolSelect.value);

        flashStatus('已保存设置', 'success');
      }

      function flashStatus(msg, type) {
        statusEl.textContent = msg;
        statusEl.classList.remove('danger', 'success', 'info');
        if (type) statusEl.classList.add(type);
      }

      function showToast(msg, type = 'info') {
        const el = document.createElement('div');
        el.className = 'toast ' + type;
        el.textContent = msg;
        document.getElementById('toast-container').appendChild(el);
        setTimeout(() => el.remove(), 3000);
      }

      // 解析 API 错误并返回中文提示
      function parseApiError(errorMessage) {
        // 先尝试直接匹配英文错误消息并翻译
        if (errorMessage.includes('token quota is not enough') ||
          errorMessage.includes('pre_consume_token_quota_failed')) {
          // 提取剩余配额和所需配额
          const remainMatch = errorMessage.match(/remain quota: ¥([\d.]+)/);
          const needMatch = errorMessage.match(/need quota: ¥([\d.]+)/);
          if (remainMatch && needMatch) {
            return `Token 配额不足！剩余: ¥${remainMatch[1]}，所需: ¥${needMatch[1]}，请充值后重试`;
          }
          return 'Token 配额不足，请充值后重试';
        }

        try {
          // 尝试解析 JSON 格式的错误
          const errorData = JSON.parse(errorMessage);

          // 处理 token 配额不足的错误
          if (errorData.code === 'pre_consume_token_quota_failed' ||
            errorData.type === 'new_api_error') {
            const message = errorData.message || '';
            // 提取剩余配额和所需配额
            const remainMatch = message.match(/remain quota: ¥([\d.]+)/);
            const needMatch = message.match(/need quota: ¥([\d.]+)/);
            if (remainMatch && needMatch) {
              return `Token 配额不足！剩余: ¥${remainMatch[1]}，所需: ¥${needMatch[1]}，请充值后重试`;
            }
            return 'Token 配额不足，请充值后重试';
          }

          // 处理其他常见错误类型
          if (errorData.error) {
            const error = errorData.error;
            if (error.code === 'UNAUTHENTICATED' || error.status === 'UNAUTHENTICATED') {
              return 'API Key 无效或已过期，请检查后重试';
            }
            if (error.code === 'PERMISSION_DENIED' || error.status === 'PERMISSION_DENIED') {
              return '没有权限访问此 API，请检查 API Key 权限';
            }
            if (error.code === 'RESOURCE_EXHAUSTED' || error.status === 'RESOURCE_EXHAUSTED') {
              return '请求频率超限，请稍后重试';
            }
            if (error.code === 'INVALID_ARGUMENT' || error.status === 'INVALID_ARGUMENT') {
              return '请求参数无效：' + translateErrorMessage(error.message || '请检查输入');
            }
            if (error.message) {
              return translateErrorMessage(error.message);
            }
          }

          // 返回原始消息（翻译后）
          if (errorData.message) {
            return translateErrorMessage(errorData.message);
          }
        } catch (e) {
          // 不是 JSON 格式，继续处理
        }

        // 处理网络相关错误
        if (errorMessage.includes('Failed to fetch') || errorMessage.includes('NetworkError')) {
          return '网络连接失败，请检查网络后重试';
        }
        if (errorMessage.includes('aborted') || errorMessage.includes('timeout')) {
          return '请求超时，请稍后重试';
        }

        // 返回翻译后的错误消息
        return translateErrorMessage(errorMessage) || '未知错误';
      }

      // 翻译常见英文错误消息为中文
      function translateErrorMessage(msg) {
        if (!msg) return '未知错误';

        const translations = {
          'token quota is not enough': 'Token 配额不足',
          'remain quota': '剩余配额',
          'need quota': '所需配额',
          'request id': '请求ID',
          'Invalid API key': 'API Key 无效',
          'API key expired': 'API Key 已过期',
          'Rate limit exceeded': '请求频率超限',
          'Internal server error': '服务器内部错误',
          'Service unavailable': '服务暂时不可用',
          'Bad request': '请求格式错误',
          'Unauthorized': '未授权访问',
          'Forbidden': '禁止访问',
          'Not found': '资源不存在',
          'Request timeout': '请求超时',
          'Too many requests': '请求过于频繁'
        };

        let translated = msg;
        for (const [en, zh] of Object.entries(translations)) {
          translated = translated.replace(new RegExp(en, 'gi'), zh);
        }
        return translated;
      }

      function renderUploads() {
        preview.innerHTML = '';
        state.images.forEach((img, idx) => {
          const wrapper = document.createElement('div');
          wrapper.className = 'thumb';
          const imageEl = document.createElement('img');
          imageEl.src = img.dataUrl;
          imageEl.style.cursor = 'zoom-in';
          imageEl.title = '点击预览';

          // 点击预览
          imageEl.addEventListener('click', () => {
            openLightbox(img.dataUrl);
          });

          // 显示图片大小
          const sizeKB = Math.round(img.dataUrl.length * 0.75 / 1024);
          const sizeLabel = document.createElement('span');
          sizeLabel.className = 'size-label';
          sizeLabel.textContent = sizeKB > 1024 ? `${(sizeKB / 1024).toFixed(1)}MB` : `${sizeKB}KB`;

          const btn = document.createElement('button');
          btn.textContent = `删除`;
          btn.onclick = () => {
            state.images.splice(idx, 1);
            renderUploads();
          };
          wrapper.appendChild(imageEl);
          wrapper.appendChild(sizeLabel);
          wrapper.appendChild(btn);
          preview.appendChild(wrapper);
        });
        flashStatus(state.images.length ? `已选择 ${state.images.length} 张` : '待发送...');
      }

      function handleFiles(fileList) {
        const files = Array.from(fileList || []);
        if (!files.length) return;
        // 计算还能添加多少张
        const remaining = 4 - state.images.length;
        if (remaining <= 0) {
          flashStatus('最多只能上传 4 张参考图', 'danger');
          return;
        }
        const filesToAdd = files.slice(0, remaining);
        flashStatus(`正在处理 ${filesToAdd.length} 张图片...`);

        Promise.all(filesToAdd.map(processAndCompressImage)).then(list => {
          state.images = [...state.images, ...list];
          renderUploads();
          if (files.length > remaining) {
            flashStatus(`已添加 ${filesToAdd.length} 张，超出的已忽略（最多 4 张）`, 'success');
          } else {
            flashStatus(`已添加 ${list.length} 张图片（已自动压缩至10MB内）`, 'success');
          }
        }).catch(err => {
          console.error('处理图片失败:', err);
          flashStatus('处理图片失败，请重试', 'danger');
        });
      }

      // 图片大小限制（字节）
      const MIN_IMAGE_SIZE = 5 * 1024 * 1024; // 最小目标：5MB
      const MAX_IMAGE_SIZE = 9 * 1024 * 1024; // 最大目标：9MB

      // 压缩图片到指定尺寸和质量
      function compressImageOnce(img, maxWidth, maxHeight, quality, mime) {
        let { width, height } = img;

        // 计算缩放比例
        if (width > maxWidth || height > maxHeight) {
          const ratio = Math.min(maxWidth / width, maxHeight / height);
          width = Math.round(width * ratio);
          height = Math.round(height * ratio);
        }

        // 创建 canvas 进行压缩
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, width, height);

        // 转换为 base64，对于大文件优先使用 JPEG 格式
        const outputMime = mime === 'image/png' ? 'image/png' : 'image/jpeg';
        const dataUrl = canvas.toDataURL(outputMime, quality);

        return {
          dataUrl,
          mime: outputMime,
          width,
          height,
          size: Math.round(dataUrl.length * 0.75)
        };
      }

      // 递进式压缩图片，确保不超过 10MB
      function compressImageToLimit(file) {
        return new Promise((resolve, reject) => {
          const img = new Image();
          const url = URL.createObjectURL(file);

          img.onload = () => {
            URL.revokeObjectURL(url);

            const originalWidth = img.width;
            const originalHeight = img.height;
            const originalSizeKB = file.size / 1024;

            // 如果原图已经 ≤ 9MB，直接使用原图，不压缩
            if (file.size <= MAX_IMAGE_SIZE) {
              console.log(
                `图片无需压缩: ${file.name}\n` +
                `  尺寸: ${originalWidth}x${originalHeight}\n` +
                `  大小: ${originalSizeKB.toFixed(1)}KB (${(file.size / 1024 / 1024).toFixed(2)}MB)`
              );

              // 读取原图为 dataUrl
              const reader = new FileReader();
              reader.onload = () => {
                resolve({
                  name: file.name,
                  mime: file.type,
                  dataUrl: reader.result,
                  originalSize: file.size,
                  compressedSize: file.size
                });
              };
              reader.onerror = () => reject(new Error('读取图片失败'));
              reader.readAsDataURL(file);
              return;
            }

            // 压缩参数配置：[最大宽度, 最大高度, 质量, MIME类型]
            // 策略：从高质量JPEG开始，逐步降低质量和尺寸
            // 注意：PNG不支持质量参数，所以不使用PNG压缩级别
            const compressionLevels = [];

            // 尝试高质量JPEG（从1.0开始，逐步降低，增加细粒度）
            compressionLevels.push(
              [originalWidth, originalHeight, 1.00, 'image/jpeg'],  // 最高质量
              [originalWidth, originalHeight, 0.99, 'image/jpeg'],  // 极高质量
              [originalWidth, originalHeight, 0.98, 'image/jpeg'],
              [originalWidth, originalHeight, 0.97, 'image/jpeg'],
              [originalWidth, originalHeight, 0.96, 'image/jpeg'],
              [originalWidth, originalHeight, 0.95, 'image/jpeg'],
              [originalWidth, originalHeight, 0.93, 'image/jpeg'],
              [originalWidth, originalHeight, 0.90, 'image/jpeg'],
              [originalWidth, originalHeight, 0.87, 'image/jpeg'],
              [originalWidth, originalHeight, 0.85, 'image/jpeg'],
              [originalWidth, originalHeight, 0.80, 'image/jpeg'],
              [4096, 4096, 0.92, 'image/jpeg'],  // 开始缩放尺寸
              [3072, 3072, 0.85, 'image/jpeg'],
              [2560, 2560, 0.80, 'image/jpeg'],
              [2048, 2048, 0.75, 'image/jpeg'],
              [1920, 1920, 0.70, 'image/jpeg'],
              [1600, 1600, 0.65, 'image/jpeg'],
              [1280, 1280, 0.60, 'image/jpeg'],
              [1024, 1024, 0.55, 'image/jpeg'],
              [800, 800, 0.50, 'image/jpeg'],
              [640, 640, 0.45, 'image/jpeg']
            );

            let result = null;
            let previousResult = null;
            let finalLevel = 0;

            // 调试：输出压缩级别数组长度
            console.log(`压缩级别总数: ${compressionLevels.length}`);

            // 尝试各级压缩，目标是找到 5-9MB 之间的结果
            for (let i = 0; i < compressionLevels.length; i++) {
              const [maxW, maxH, quality, mimeType] = compressionLevels[i];
              console.log(`尝试压缩级别 ${i + 1}/${compressionLevels.length}: ${mimeType}, 质量=${quality}, 尺寸=${maxW}x${maxH}`);

              result = compressImageOnce(img, maxW, maxH, quality, mimeType);
              finalLevel = i + 1;

              const resultSizeMB = (result.size / 1024 / 1024).toFixed(2);
              console.log(`  结果: ${resultSizeMB}MB (${result.width}x${result.height})`);

              // 如果结果在 5-9MB 之间，完美！
              if (result.size >= MIN_IMAGE_SIZE && result.size <= MAX_IMAGE_SIZE) {
                console.log(`  ✓ 在目标范围内，停止压缩`);
                break;
              }

              // 如果结果 < 5MB，检查是否在容忍范围内（4-9MB）
              if (result.size < MIN_IMAGE_SIZE) {
                const toleranceSize = 4 * 1024 * 1024; // 4MB容忍下限

                if (result.size >= toleranceSize) {
                  // 在容忍范围内（4-5MB），接受这个结果
                  console.log(`  ✓ 在容忍范围内 (4-5MB)，接受结果`);
                  break;
                } else {
                  // < 4MB，压缩过度
                  console.log(`  ⚠ 压缩过度 (<4MB)`);

                  // 如果有上一级结果，且上一级在合理范围内（<= 9MB），才回退
                  if (previousResult && previousResult.size <= MAX_IMAGE_SIZE) {
                    result = previousResult;
                    finalLevel = i; // 回退到上一级
                    console.log(`  → 回退到上一级`);
                  } else if (previousResult) {
                    // 上一级超出9MB，当前级虽然<4MB，但比超出范围的结果好
                    console.log(`  → 上一级超出范围，保持当前结果`);
                  }
                  // 否则使用当前结果（第一级就 < 4MB 的情况）
                  break;
                }
              }

              // 如果结果 > 9MB，继续尝试下一级
              console.log(`  → 继续尝试下一级`);
              previousResult = result;
            }

            // 如果所有级别都 > 9MB，尝试强制转为 JPEG
            if (result.size > MAX_IMAGE_SIZE && file.type === 'image/png') {
              const jpegResult = compressImageOnce(img, 640, 640, 0.40, 'image/jpeg');
              if (jpegResult.size >= MIN_IMAGE_SIZE) {
                result = jpegResult;
                finalLevel = 'JPEG强制';
              }
            }

            const finalSizeKB = result.size / 1024;
            const finalSizeMB = (result.size / 1024 / 1024).toFixed(2);
            const compressionRatio = ((1 - result.size / file.size) * 100).toFixed(1);
            const inTargetRange = result.size >= MIN_IMAGE_SIZE && result.size <= MAX_IMAGE_SIZE;

            console.log(
              `图片压缩完成: ${file.name}\n` +
              `  原始: ${originalWidth}x${originalHeight}, ${originalSizeKB.toFixed(1)}KB (${(file.size / 1024 / 1024).toFixed(2)}MB)\n` +
              `  压缩后: ${result.width}x${result.height}, ${finalSizeKB.toFixed(1)}KB (${finalSizeMB}MB)\n` +
              `  压缩级别: ${finalLevel}, 压缩率: ${compressionRatio}%\n` +
              `  目标范围: 5-9MB, 状态: ${inTargetRange ? '✓ 在范围内' : '⚠ 超出范围'}`
            );

            resolve({
              name: file.name,
              mime: result.mime,
              dataUrl: result.dataUrl,
              originalSize: file.size,
              compressedSize: result.size
            });
          };

          img.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('图片加载失败'));
          };

          img.src = url;
        });
      }

      // 处理并压缩图片
      async function processAndCompressImage(file) {
        const fileSizeBytes = file.size;
        const fileSizeKB = fileSizeBytes / 1024;
        const fileSizeMB = fileSizeKB / 1024;

        console.log(`处理图片: ${file.name}, 原始大小: ${fileSizeMB.toFixed(2)}MB`);

        // 如果图片已经小于 10MB，直接读取
        if (fileSizeKB <= 10240) {
          console.log(`图片较小，无需压缩: ${file.name}`);
          return await readFileAsDataUrl(file);
        }

        // 对于大于 10MB 的图片，进行压缩
        return await compressImageToLimit(file);
      }

      function readFileAsDataUrl(file) {
        return new Promise((resolve, reject) => {
          const reader = new FileReader();
          reader.onload = () => resolve({
            name: file.name,
            mime: file.type || 'image/png',
            dataUrl: reader.result
          });
          reader.onerror = reject;
          reader.readAsDataURL(file);
        });
      }

      function buildPayload(prompt) {
        const protocol = getProtocol();
        const imgs = state.images.slice(0, 4);

        if (protocol === 'openai-images') {
          // OpenAI Images 格式: POST /v1/images/generations
          const payload = {
            model: getImageModel(),
            prompt: prompt,
            n: 1,
            size: getImageSize()
          };
          if (imgs.length > 0) {
            payload.image = imgs.map(img => img.dataUrl);
          }
          return payload;
        }

        if (protocol === 'openai-chat') {
          // OpenAI Chat 格式: POST /v1/chat/completions
          const content = [];
          content.push({ type: 'text', text: prompt });
          imgs.forEach(img => {
            content.push({
              type: 'image_url',
              image_url: { url: img.dataUrl }
            });
          });
          return {
            model: getImageModel(),
            messages: [{ role: 'user', content: content.length === 1 ? prompt : content }],
            stream: false
          };
        }

        // Gemini 原生格式
        const parts = [{ text: prompt }];
        imgs.forEach(img => {
          const base64 = img.dataUrl.split(',')[1];
          parts.push({
            inline_data: {
              mime_type: img.mime || 'image/png',
              data: base64
            }
          });
        });
        const imageConfig = { imageSize: resolutionSelect.value };
        if (aspectSelect.value !== 'auto') {
          imageConfig.aspectRatio = aspectSelect.value;
        }
        return {
          contents: [{ role: 'user', parts }],
          generationConfig: {
            responseModalities: ['Image'],
            imageConfig: imageConfig
          }
        };
      }

      // 根据清晰度和比例获取像素尺寸（用于 OpenAI Images 格式）
      function getImageSize() {
        const aspect = aspectSelect.value;
        const resolution = resolutionSelect.value;
        const sizes = {
          '1K': {
            'auto': 'auto',
            '1:1': '1024x1024',
            '3:4': '1024x1536',
            '4:3': '1536x1024',
            '16:9': '1536x1024',
            '9:16': '1024x1536'
          },
          '2K': {
            'auto': 'auto',
            '1:1': '2048x2048',
            '16:9': '2048x1152',
            '9:16': '1152x2048'
          },
          '4K': {
            'auto': 'auto',
            '16:9': '3840x2160',
            '9:16': '2160x3840'
          }
        };
        const size = sizes[resolution]?.[aspect];
        if (!size) {
          throw new Error(`OpenAI Images 协议不支持 ${resolution} + ${aspect}，请改用 16:9 或 9:16`);
        }
        return size;
      }

      function guessMimeFromUrl(url) {
        if (!url) return '';
        const lower = url.toLowerCase().split('?')[0];
        if (lower.endsWith('.jpg') || lower.endsWith('.jpeg')) return 'image/jpeg';
        if (lower.endsWith('.png')) return 'image/png';
        if (lower.endsWith('.gif')) return 'image/gif';
        if (lower.endsWith('.webp')) return 'image/webp';
        return '';
      }

      function extractResult(data) {
        const emptyResult = (extra) => ({ text: '', imageBase64: '', imageUrl: '', mime: 'image/png', blocked: false, ...extra });

        // 检测是否被安全策略拦截
        const candidate = data?.candidates?.[0];
        const finishReason = candidate?.finishReason;
        const blockReason = data?.promptFeedback?.blockReason;

        if (blockReason) {
          return emptyResult({ blocked: true, blockMessage: `内容被拦截：${blockReason}` });
        }
        if (finishReason && finishReason !== 'STOP' && !candidate?.content?.parts?.length) {
          const reasonMap = { 'SAFETY': '安全策略拦截', 'RECITATION': '内容重复', 'OTHER': '其他原因', 'BLOCKLIST': '命中黑名单' };
          return emptyResult({ blocked: true, blockMessage: `生成被拒绝：${reasonMap[finishReason] || finishReason}` });
        }

        const parts = candidate?.content?.parts
          || data?.contents?.[0]?.parts
          || data?.content?.parts
          || [];
        const textList = [];
        let imageBase64 = '';
        let imageUrl = '';
        let mime = 'image/png';

        parts.forEach(p => {
          if (p.text) textList.push(p.text);
          const inline = p.inline_data || p.inlineData;
          if (inline?.data) {
            imageBase64 = inline.data;
            mime = inline.mime_type || inline.mimeType || mime;
          }
          if (p.file_data?.file_uri || p.fileData?.fileUri) {
            imageUrl = p.file_data?.file_uri || p.fileData?.fileUri;
          }
        });

        if (!imageBase64 && data?.imageBase64) {
          imageBase64 = data.imageBase64;
          mime = data.mimeType || mime;
        }
        if (!textList.length && typeof data?.text === 'string') textList.push(data.text);

        // 1. OpenAI images 格式: { data: [{ url }] } 或 { data: [{ b64_json }] }
        if (!imageBase64 && !imageUrl && Array.isArray(data?.data)) {
          const withUrl = data.data.find(d => d.url);
          if (withUrl) { imageUrl = withUrl.url; mime = guessMimeFromUrl(withUrl.url) || mime; }
          const withB64 = data.data.find(d => d.b64_json);
          if (!imageBase64 && withB64) imageBase64 = withB64.b64_json;
        }

        // 2. OpenAI chat 格式: { choices: [{ message: { content } }] }
        if (!imageBase64 && !imageUrl && data?.choices?.[0]?.message?.content) {
          const content = data.choices[0].message.content;
          if (Array.isArray(content)) {
            content.forEach(item => {
              if (item.type === 'image_url' && item.image_url?.url) imageUrl = item.image_url.url;
              if (item.type === 'text' && item.text) textList.push(item.text);
            });
          } else if (typeof content === 'string' && content.trim()) {
            textList.push(content);
          }
        }

        // 3. text 中包含 URL JSON 数组: [{"url":"..."}]
        if (!imageBase64 && !imageUrl && textList.length) {
          const fullText = textList.join('\n').trim();
          try {
            const parsed = JSON.parse(fullText);
            if (Array.isArray(parsed)) {
              const firstUrl = parsed.find(item => item.url);
              if (firstUrl) { imageUrl = firstUrl.url; mime = guessMimeFromUrl(firstUrl.url) || mime; textList.length = 0; }
            } else if (parsed && parsed.url) {
              imageUrl = parsed.url; mime = guessMimeFromUrl(parsed.url) || mime; textList.length = 0;
            }
          } catch (_) {}
        }

        // 4. text 中包含 markdown 图片 ![...](data:...) 或 ![...](https://...)
        if (!imageBase64 && !imageUrl && textList.length) {
          const fullText = textList.join('\n');
          const mdMatch = fullText.match(/!\[.*?\]\((data:image\/[^;]+;base64,[A-Za-z0-9+/=]+)\)/);
          if (mdMatch) {
            const dataUri = mdMatch[1];
            const mimeMatch = dataUri.match(/^data:(image\/[^;]+);base64,/);
            if (mimeMatch) mime = mimeMatch[1];
            imageBase64 = dataUri.split(',')[1];
            textList.length = 0;
          }
          if (!imageBase64 && !imageUrl) {
            const mdUrlMatch = fullText.match(/!\[.*?\]\((https?:\/\/[^\s)]+)\)/);
            if (mdUrlMatch) { imageUrl = mdUrlMatch[1]; mime = guessMimeFromUrl(imageUrl) || mime; textList.length = 0; }
          }
        }

        // 5. 单条 text 本身就是图片 URL 或 data URI
        if (!imageBase64 && !imageUrl && textList.length === 1) {
          const single = textList[0].trim();
          if (/^data:image\/[^;]+;base64,/.test(single)) {
            const mimeMatch = single.match(/^data:(image\/[^;]+);base64,/);
            if (mimeMatch) mime = mimeMatch[1];
            imageBase64 = single.split(',')[1];
            textList.length = 0;
          } else if (/^https?:\/\/.+/i.test(single)) {
            imageUrl = single; mime = guessMimeFromUrl(single) || mime; textList.length = 0;
          }
        }

        // 6. text 中包含图片 URL
        if (!imageBase64 && !imageUrl && textList.length) {
          const fullText = textList.join('\n');
          const urlMatch = fullText.match(/https?:\/\/[^\s"'<>]+\.(jpg|jpeg|png|gif|webp)(\?[^\s"'<>]*)?/i);
          if (urlMatch) { imageUrl = urlMatch[0]; mime = guessMimeFromUrl(imageUrl) || mime; }
        }

        return { text: textList.join('\n\n'), imageBase64, imageUrl, mime, blocked: false };
      }

      // 从 result 获取可显示的图片 src
      function getResultImgSrc(result) {
        if (!result) return '';
        if (result.imageBase64) {
          return result.imageBase64.startsWith('data:')
            ? result.imageBase64
            : `data:${result.mime || 'image/png'};base64,${result.imageBase64}`;
        }
        return result.imageUrl || '';
      }

      // 判断 result 是否包含图片
      function hasResultImage(result) {
        return !!(result && (result.imageBase64 || result.imageUrl));
      }

      // 根据 MIME 类型获取正确的文件扩展名
      function getExtensionFromMime(mime) {
        const mimeToExt = {
          'image/jpeg': 'jpg',
          'image/jpg': 'jpg',
          'image/png': 'png',
          'image/gif': 'gif',
          'image/webp': 'webp'
        };
        return mimeToExt[mime] || 'png';
      }

      // 统一的文本API调用：根据协议自动构建请求和解析响应
      async function callTextAPI(promptText, options = {}) {
        const protocol = getProtocol();
        const endpoint = getFlashEndpoint();
        let payload;

        if (protocol === 'gemini') {
          payload = {
            contents: [{ role: 'user', parts: [{ text: promptText }] }],
            generationConfig: {
              temperature: options.temperature ?? 0.3,
              candidateCount: 1
            }
          };
        } else {
          payload = {
            model: getTextModel(),
            messages: [{ role: 'user', content: promptText }],
            temperature: options.temperature ?? 0.3
          };
        }

        const res = await fetch(endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });

        if (!res.ok) {
          const errText = await res.text();
          throw new Error(`API 错误 (${res.status}): ${errText}`);
        }

        const data = await res.json();

        // 提取文本：兼容 Gemini 和 OpenAI 格式
        const text = data?.candidates?.[0]?.content?.parts?.[0]?.text
          || (typeof data?.choices?.[0]?.message?.content === 'string' ? data.choices[0].message.content : '')
          || '';

        if (!text) throw new Error('API 返回内容为空');
        return text;
      }

      // 分镜识别：降级正则方案
      function fallbackRegexParse(scriptText) {
        const lines = scriptText.split('\n');
        let globalRequirements = '';
        const shots = [];

        // 提取全局要求（第一行包含"严格执行"或"要求"）
        if (lines[0] && (lines[0].includes('严格执行') || lines[0].includes('要求'))) {
          globalRequirements = lines[0];
        }

        // 识别分镜（匹配"分镜X："、"镜头X："、"场景X："）
        const shotRegex = /(分镜|镜头|场景)\s*(\d+)[：:]/;
        let currentShot = null;

        lines.forEach(line => {
          const match = line.match(shotRegex);
          if (match) {
            if (currentShot) shots.push(currentShot);
            currentShot = {
              index: parseInt(match[2]),
              description: line.replace(shotRegex, '').trim()
            };
          } else if (currentShot && line.trim()) {
            currentShot.description += ' ' + line.trim();
          }
        });

        if (currentShot) shots.push(currentShot);
        return { globalRequirements, shots };
      }

      // 分镜识别：调用文本API
      async function analyzeStoryboard(scriptText) {
        const promptText = `请分析以下视频分镜脚本，提取所有分镜描述。

要求：
1. 识别所有分镜（可能是"分镜X"、"镜头X"、"场景X"等格式）
2. 提取每个分镜的完整描述
3. 如果脚本开头有全局要求，需要**智能改写**：
   - 理解哪些要求适用于"单张静态图片"（如视角、色彩、光线、风格等）
   - 移除那些需要"多个时间点"或"多个画面"才能表达的要求
   - 特别注意：将"每张图片"、"所有图片"、"全部画面"等表述改写为适合单张图片的描述
   - 改写后的全局要求应该能直接用于指导AI生成单张静态图片
4. 返回严格的 JSON 格式，不要添加任何markdown标记

示例说明：
- 原文："每张图片需体现擦拭动作，并清晰展示出擦拭后的洁净区域"
- 改写："第一视角，喷出的液体为透明色，画面风格统一"
- 原因：单张图片无法同时展示"擦拭动作"和"擦拭后效果"，这需要拆分成多个分镜

脚本内容：
${scriptText}

返回格式示例：
{
  "globalRequirements": "改写后适合单张图片的全局要求",
  "shots": [
    {"index": 1, "description": "分镜描述"},
    {"index": 2, "description": "分镜描述"}
  ]
}`;

        try {
          const text = await callTextAPI(promptText, { temperature: 0.1 });
          try {
            const cleanText = text.replace(/```json\n?/g, '').replace(/```\n?/g, '').trim();
            return JSON.parse(cleanText);
          } catch (e) {
            console.warn('JSON解析失败，使用降级方案', e);
            return fallbackRegexParse(scriptText);
          }
        } catch (error) {
          console.error('Flash识别失败:', error);
          return fallbackRegexParse(scriptText);
        }
      }

      // 优化提示词：调用文本API
      async function optimizePromptWithAI(originalPrompt) {
        const promptText = `你是一个专业的AI图像生成提示词优化专家。请优化以下提示词，使其更适合AI图像生成。

优化要求：
1. 保持原始意图和主题不变
2. 添加更多视觉细节描述（光线、色彩、构图、氛围等）
3. 使用更专业、更精确的描述词汇
4. 增强画面感和艺术性
5. 保持简洁，不要过度冗长
6. **必须使用中文输出优化后的提示词**
7. 直接返回优化后的提示词，不要添加任何解释或额外内容

原始提示词：
${originalPrompt}

请直接返回优化后的中文提示词：`;

        const text = await callTextAPI(promptText, { temperature: 0.7 });
        return text.replace(/```.*?\n?/g, '').trim();
      }

      // 检测文本是否主要为英文
      function isEnglishText(text) {
        // 统计英文字符和中文字符的数量
        const englishChars = text.match(/[a-zA-Z]/g) || [];
        const chineseChars = text.match(/[\u4e00-\u9fa5]/g) || [];

        // 如果英文字符数量明显多于中文字符，判定为英文
        return englishChars.length > chineseChars.length * 2;
      }

      // 翻译英文提示词为中文
      async function translatePromptToChinese(englishPrompt) {
        const text = await callTextAPI(`请将以下英文AI图像生成提示词翻译成中文，保持原意和专业性。只返回翻译后的中文文本，不要添加任何解释。

英文提示词：
${englishPrompt}

请直接返回中文翻译：`, { temperature: 0.3 });
        return text.replace(/```.*?\n?/g, '').trim();
      }

      // 翻译中文提示词为英文
      async function translatePromptToEnglish(chinesePrompt) {
        const text = await callTextAPI(`请将以下中文AI图像生成提示词翻译成英文，保持原意和专业性。只返回翻译后的英文文本，不要添加任何解释。

中文提示词：
${chinesePrompt}

请直接返回英文翻译：`, { temperature: 0.3 });
        return text.replace(/```.*?\n?/g, '').trim();
      }

      // 显示提示词对比弹窗
      async function showPromptCompareDialog(originalPrompt) {
        const overlay = document.createElement('div');
        overlay.className = 'prompt-compare-overlay';

        overlay.innerHTML = `
          <div class="prompt-compare-panel">
            <div class="prompt-compare-header">
              <h3>✨ 提示词优化</h3>
              <button class="prompt-compare-close">✕</button>
            </div>
            <div class="prompt-compare-content">
              <div class="prompt-compare-section">
                <div class="prompt-compare-label">📝 原始提示词</div>
                <textarea class="prompt-compare-text" rows="4" id="original-textarea">${escapeHtml(originalPrompt)}</textarea>
              </div>
              <div class="prompt-compare-section">
                <div class="prompt-compare-label">
                  ✨ 优化后的提示词（中文）
                  <button class="prompt-compare-btn prompt-compare-btn-secondary" id="optimize-now-btn" style="margin-left: 10px; padding: 4px 12px; font-size: 12px;">
                    开始优化
                  </button>
                </div>
                <textarea class="prompt-compare-text" rows="4" id="optimized-textarea" placeholder="点击上方「开始优化」按钮进行优化..." readonly style="background: var(--panel);"></textarea>
                <div style="margin-top: 8px; display: flex; gap: 8px; justify-content: flex-end;">
                  <button class="prompt-compare-btn prompt-compare-btn-primary use-optimized-btn" disabled>使用优化后的</button>
                  <button class="prompt-compare-btn prompt-compare-btn-secondary translate-to-english-btn" disabled>翻译成英文</button>
                </div>
              </div>
              <div class="prompt-compare-section" id="english-translation-section" style="display: none;">
                <div class="prompt-compare-label">🌍 英文翻译版本</div>
                <textarea class="prompt-compare-text" rows="4" id="english-textarea" placeholder="点击上方「翻译成英文」按钮进行翻译..."></textarea>
                <div style="margin-top: 8px; display: flex; gap: 8px; justify-content: flex-end;">
                  <button class="prompt-compare-btn prompt-compare-btn-primary use-english-btn" disabled>使用英文版本</button>
                </div>
              </div>
            </div>
            <div class="prompt-compare-actions">
              <button class="prompt-compare-btn prompt-compare-btn-secondary close-btn">取消</button>
            </div>
          </div>
        `;

        document.body.appendChild(overlay);

        const closeBtn = overlay.querySelector('.close-btn');
        const closeIconBtn = overlay.querySelector('.prompt-compare-close');
        const optimizeNowBtn = overlay.querySelector('#optimize-now-btn');
        const originalTextarea = overlay.querySelector('#original-textarea');
        const optimizedTextarea = overlay.querySelector('#optimized-textarea');
        const useOptimizedBtn = overlay.querySelector('.use-optimized-btn');
        const englishTranslationSection = overlay.querySelector('#english-translation-section');

        // "开始优化"按钮点击事件
        optimizeNowBtn.addEventListener('click', async () => {
          const currentPrompt = originalTextarea.value.trim();

          if (!currentPrompt) {
            flashStatus('原始提示词不能为空', 'danger');
            return;
          }

          // 禁用按钮，显示加载状态
          optimizeNowBtn.disabled = true;
          optimizeNowBtn.textContent = '优化中...';
          optimizedTextarea.placeholder = '正在优化中，请稍候...';

          try {
            // 调用API优化提示词
            const optimizedPrompt = await optimizePromptWithAI(currentPrompt);

            // 更新优化后的提示词
            optimizedTextarea.value = optimizedPrompt;
            optimizedTextarea.readOnly = false;
            optimizedTextarea.style.background = 'var(--card)';

            // 启用"使用优化后的"按钮
            useOptimizedBtn.disabled = false;

            // 恢复按钮状态
            optimizeNowBtn.disabled = false;
            optimizeNowBtn.textContent = '重新优化';

            flashStatus('优化完成', 'success');

            // 启用"翻译成英文"按钮
            const translateToEnglishBtn = overlay.querySelector('.translate-to-english-btn');
            if (translateToEnglishBtn) {
              translateToEnglishBtn.disabled = false;
            }

          } catch (error) {
            console.error('优化提示词失败:', error);
            optimizedTextarea.placeholder = '优化失败，请重试';
            flashStatus(error.message || '优化失败，请重试', 'danger');

            // 恢复按钮状态
            optimizeNowBtn.disabled = false;
            optimizeNowBtn.textContent = '开始优化';
          }
        });

        // "翻译成英文"按钮事件处理
        const translateToEnglishBtn = overlay.querySelector('.translate-to-english-btn');
        if (translateToEnglishBtn) {
          translateToEnglishBtn.addEventListener('click', async () => {
            const chineseValue = optimizedTextarea.value.trim();

            if (!chineseValue) {
              flashStatus('优化后的提示词为空，请先进行优化', 'danger');
              return;
            }

            // 禁用按钮并显示加载状态
            translateToEnglishBtn.disabled = true;
            translateToEnglishBtn.textContent = '翻译中...';

            try {
              const englishPrompt = await translatePromptToEnglish(chineseValue);

              // 更新英文翻译section
              englishTranslationSection.innerHTML = `
                <div class="prompt-compare-label">🌍 英文翻译版本</div>
                <textarea class="prompt-compare-text" rows="4" id="english-textarea">${escapeHtml(englishPrompt)}</textarea>
                <div style="margin-top: 8px; display: flex; gap: 8px; justify-content: flex-end;">
                  <button class="prompt-compare-btn prompt-compare-btn-primary use-english-btn">使用英文版本</button>
                </div>
              `;

              // 显示英文翻译区域
              englishTranslationSection.style.display = 'block';

              // 绑定使用英文版本按钮
              const useEnglishBtn = englishTranslationSection.querySelector('.use-english-btn');
              useEnglishBtn.addEventListener('click', () => {
                const englishTextarea = overlay.querySelector('#english-textarea');
                if (englishTextarea) {
                  const englishValue = englishTextarea.value.trim();
                  if (englishValue) {
                    promptInput.value = englishValue;
                    overlay.remove();
                    flashStatus('已使用英文版本的提示词', 'success');
                  }
                }
              });

              // 恢复按钮状态
              translateToEnglishBtn.disabled = false;
              translateToEnglishBtn.textContent = '翻译成英文';

              flashStatus('翻译成功', 'success');
            } catch (error) {
              console.error('英文翻译失败:', error);

              // 恢复按钮状态
              translateToEnglishBtn.disabled = false;
              translateToEnglishBtn.textContent = '翻译成英文';

              flashStatus(`翻译失败: ${error.message}`, 'danger');
            }
          });
        }

        // 关闭按钮
        closeBtn.addEventListener('click', () => overlay.remove());
        closeIconBtn.addEventListener('click', () => overlay.remove());

        // 使用优化后的提示词（从textarea读取）
        useOptimizedBtn.addEventListener('click', () => {
          const optimizedValue = optimizedTextarea.value.trim();
          if (optimizedValue) {
            promptInput.value = optimizedValue;
            overlay.remove();
            flashStatus('已使用优化后的提示词', 'success');
          }
        });

        // ESC键关闭
        const escHandler = (e) => {
          if (e.key === 'Escape') {
            overlay.remove();
            document.removeEventListener('keydown', escHandler);
          }
        };
        document.addEventListener('keydown', escHandler);
      }

      // 显示分镜输入框
      function showStoryboardInput() {
        const overlay = document.createElement('div');
        overlay.className = 'storyboard-overlay';
        overlay.innerHTML = `
          <div class="storyboard-panel">
            <div class="storyboard-header">
              <h3>🎬 分镜脚本输入</h3>
              <button class="storyboard-close">✕</button>
            </div>
            <div class="storyboard-content">
              <div class="storyboard-input-area">
                <label>请粘贴分镜脚本：</label>
                <textarea class="storyboard-textarea" placeholder="例如：
严格执行：喷出的液体为透明色，且必须保证每个分镜都有擦拭和展示擦后干净的画面。全程第一视角。

分镜1：中景展示用户清洁充满油污的油烟机表面，喷洒后轻轻一擦即可去除污渍...
分镜2：中景展示用户清洁充满油污的锅底表面，喷洒后轻轻一擦即可去除污渍..."></textarea>
              </div>
            </div>
            <div class="storyboard-actions">
              <button class="storyboard-btn storyboard-btn-secondary close-btn">取消</button>
              <button class="storyboard-btn storyboard-btn-primary analyze-btn">识别分镜</button>
            </div>
          </div>
        `;

        document.body.appendChild(overlay);

        const textarea = overlay.querySelector('.storyboard-textarea');
        const closeBtn = overlay.querySelectorAll('.close-btn, .storyboard-close');
        const analyzeBtn = overlay.querySelector('.analyze-btn');

        // 关闭弹窗
        closeBtn.forEach(btn => {
          btn.addEventListener('click', () => overlay.remove());
        });

        // 点击遮罩层关闭
        overlay.addEventListener('click', (e) => {
          if (e.target === overlay) overlay.remove();
        });

        // 识别分镜
        analyzeBtn.addEventListener('click', async () => {
          const scriptText = textarea.value.trim();
          if (!scriptText) {
            alert('请输入分镜脚本');
            return;
          }

          // 显示加载状态
          analyzeBtn.disabled = true;
          analyzeBtn.textContent = '识别中...';

          try {
            const result = await analyzeStoryboard(scriptText);
            overlay.remove();
            showStoryboardPreview(result, scriptText);
          } catch (error) {
            alert('识别失败：' + error.message);
            analyzeBtn.disabled = false;
            analyzeBtn.textContent = '识别分镜';
          }
        });

        textarea.focus();
      }

      // 显示分镜预览界面
      function showStoryboardPreview(result, scriptText) {
        const { globalRequirements, shots } = result;

        if (!shots || shots.length === 0) {
          alert('未识别到分镜，请检查脚本格式');
          return;
        }

        const overlay = document.createElement('div');
        overlay.className = 'storyboard-overlay';

        let shotsHtml = '';
        shots.forEach(shot => {
          shotsHtml += `
            <div class="storyboard-shot-item">
              <span class="storyboard-shot-number">分镜${shot.index}：</span>
              <textarea class="storyboard-shot-desc" data-index="${shot.index}" rows="2">${shot.description}</textarea>
            </div>
          `;
        });

        overlay.innerHTML = `
          <div class="storyboard-panel">
            <div class="storyboard-header">
              <h3>🎬 分镜识别结果</h3>
              <button class="storyboard-close">✕</button>
            </div>
            <div class="storyboard-content">
              ${globalRequirements ? `
                <div class="storyboard-preview-section">
                  <div class="storyboard-section-title">全局要求：</div>
                  <textarea class="storyboard-global-req" rows="2">${globalRequirements}</textarea>
                </div>
              ` : ''}
              <div class="storyboard-preview-section">
                <div class="storyboard-section-title">识别到 ${shots.length} 个分镜：</div>
                <div class="storyboard-shots-list">
                  ${shotsHtml}
                </div>
              </div>
            </div>
            <div class="storyboard-actions">
              <button class="storyboard-btn storyboard-btn-secondary retry-btn">重新识别</button>
              <button class="storyboard-btn storyboard-btn-primary generate-btn">开始生成图片</button>
            </div>
          </div>
        `;

        document.body.appendChild(overlay);

        const closeBtn = overlay.querySelector('.storyboard-close');
        const retryBtn = overlay.querySelector('.retry-btn');
        const generateBtn = overlay.querySelector('.generate-btn');

        closeBtn.addEventListener('click', () => overlay.remove());
        overlay.addEventListener('click', (e) => {
          if (e.target === overlay) overlay.remove();
        });

        retryBtn.addEventListener('click', () => {
          overlay.remove();
          showStoryboardInput();
        });

        generateBtn.addEventListener('click', () => {
          // 读取用户编辑后的全局要求
          const globalReqTextarea = overlay.querySelector('.storyboard-global-req');
          const updatedGlobalRequirements = globalReqTextarea ? globalReqTextarea.value.trim() : '';

          // 读取用户编辑后的分镜描述
          const textareas = overlay.querySelectorAll('.storyboard-shot-desc');
          const updatedShots = [];
          textareas.forEach(textarea => {
            const index = parseInt(textarea.dataset.index);
            const description = textarea.value.trim();
            if (description) {
              updatedShots.push({ index, description });
            }
          });

          if (updatedShots.length === 0) {
            alert('请至少保留一个分镜描述');
            return;
          }

          // 使用更新后的数据
          const updatedResult = {
            globalRequirements: updatedGlobalRequirements,
            shots: updatedShots
          };

          overlay.remove();
          generateStoryboardImages(updatedResult);
        });
      }

      // 生成单个分镜图片
      // 通用的生图API调用（分镜、多角度等都用这个）
      async function callImageAPI(prompt, images) {
        const protocol = getProtocol();
        const imgs = (images || []).filter(img => img.dataUrl);
        let endpoint, fetchHeaders, fetchBody;

        if (protocol === 'openai-images') {
          if (imgs.length > 0) {
            endpoint = getBaseUrl() + encodeURIComponent('/v1/images/edits');
            const formData = new FormData();
            formData.append('model', getImageModel());
            formData.append('prompt', prompt);
            const imgSize = getImageSize();
            if (imgSize !== 'auto') formData.append('size', imgSize);
            formData.append('n', '1');
            for (const img of imgs) {
              const resp = await fetch(img.dataUrl);
              const blob = await resp.blob();
              const ext = getExtensionFromMime(img.mime || blob.type || 'image/png');
              formData.append('image[]', blob, `ref.${ext}`);
            }
            fetchHeaders = {};
            fetchBody = formData;
          } else {
            endpoint = getEndpoint();
            const imgSize2 = getImageSize();
            const payload = {
              model: getImageModel(),
              prompt: prompt,
              n: 1
            };
            if (imgSize2 !== 'auto') payload.size = imgSize2;
            fetchHeaders = { 'Content-Type': 'application/json' };
            fetchBody = JSON.stringify(payload);
          }
        } else if (protocol === 'openai-chat') {
          endpoint = getEndpoint();
          const content = [{ type: 'text', text: prompt }];
          imgs.forEach(img => {
            content.push({ type: 'image_url', image_url: { url: img.dataUrl } });
          });
          const payload = {
            model: getImageModel(),
            messages: [{ role: 'user', content: content.length === 1 ? prompt : content }],
            stream: false
          };
          fetchHeaders = { 'Content-Type': 'application/json' };
          fetchBody = JSON.stringify(payload);
        } else {
          endpoint = getEndpoint();
          const parts = [];
          imgs.forEach(img => {
            const base64 = img.dataUrl.split(',')[1];
            parts.push({ inline_data: { mime_type: img.mime || 'image/png', data: base64 } });
          });
          parts.push({ text: prompt });
          const imageConfig = { imageSize: resolutionSelect.value };
          if (aspectSelect.value !== 'auto') imageConfig.aspectRatio = aspectSelect.value;
          const payload = {
            contents: [{ role: 'user', parts }],
            generationConfig: { responseModalities: ['Image'], imageConfig }
          };
          fetchHeaders = { 'Content-Type': 'application/json' };
          fetchBody = JSON.stringify(payload);
        }

        console.log('[callImageAPI] protocol:', protocol, 'endpoint:', endpoint, 'hasImages:', imgs.length > 0);
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 900000); // 15分钟超时
        let res;
        try {
          res = await fetch(endpoint, {
            method: 'POST',
            headers: fetchHeaders,
            body: fetchBody,
            signal: controller.signal
          });
        } catch (fetchErr) {
          clearTimeout(timeoutId);
          if (fetchErr.name === 'AbortError') throw new Error('请求超时（600秒），请稍后重试');
          throw fetchErr;
        }
        clearTimeout(timeoutId);
        if (!res.ok) {
          const errBody = await res.text();
          console.error('[callImageAPI] error response:', errBody);
          throw new Error(errBody || `API 错误: ${res.status}`);
        }
        const raw = await res.text();
        console.log('[callImageAPI] raw response:', raw.slice(0, 2000));
        let data;
        try { data = JSON.parse(raw); } catch(_) { data = raw; }
        const result = extractResult(data);
        console.log('[callImageAPI] extractResult:', { text: result.text?.slice(0,200), imageBase64: !!result.imageBase64, imageUrl: result.imageUrl });
        return result;
      }

      async function generateStoryboardShot(prompt) {
        return callImageAPI(prompt, state.images.slice(0, 4));
      }

      // 批量生成分镜图片
      async function generateStoryboardImages(analysisResult) {
        const { globalRequirements, shots } = analysisResult;

        toggleResults(true);
        document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });

        // 创建任务信息
        const taskId = ++taskIdCounter;
        const taskInfo = {
          taskId,
          scenario: {
            id: 'storyboard',
            label: '🎬 分镜生成',
            angles: shots.map(s => `分镜${s.index}`)
          }
        };

        // 创建分组容器
        const groupContainer = createResultGroup(taskInfo);
        resultsEl.insertBefore(groupContainer, resultsEl.firstChild);

        // 为每个分镜创建占位符
        const placeholders = [];
        shots.forEach((shot, index) => {
          const placeholderId = `storyboard-placeholder-${taskId}-${index}`;
          const card = createPlaceholderCard(`分镜${shot.index}`, placeholderId);
          const gridEl = groupContainer.querySelector('.result-group-grid');
          gridEl.appendChild(card);
          placeholders.push({ placeholderId, shot, index });
        });

        // 并发生成所有分镜（立即返回，后台继续生成）
        placeholders.forEach(async ({ placeholderId, shot, index }) => {
          if (index > 0) {
            await new Promise(r => setTimeout(r, 500));
          }

          try {
            const finalPrompt = globalRequirements
              ? `${globalRequirements}\n\n${shot.description}`
              : shot.description;

            const result = await generateStoryboardShot(finalPrompt);

            // 替换占位符
            const placeholderEl = document.getElementById(placeholderId);
            if (placeholderEl) {
              if (placeholderEl.dataset.intervalId) {
                clearInterval(parseInt(placeholderEl.dataset.intervalId));
              }
              const actualElapsedMs = placeholderEl.dataset.startTime
                ? (performance.now() - placeholderEl.dataset.startTime)
                : 0;
              placeholderEl.remove();
              await appendResultToGroup(groupContainer, result, `分镜${shot.index}`, actualElapsedMs, finalPrompt);
            }
          } catch (error) {
            console.error(`分镜${shot.index}生成失败:`, error);
            const placeholderEl = document.getElementById(placeholderId);
            if (placeholderEl) {
              if (placeholderEl.dataset.intervalId) {
                clearInterval(parseInt(placeholderEl.dataset.intervalId));
              }
              const elapsed = placeholderEl.dataset.startTime
                ? ((performance.now() - placeholderEl.dataset.startTime) / 1000).toFixed(1)
                : '0.0';
              placeholderEl.innerHTML = `
                <div style="text-align: center; color: var(--danger); padding: 20px;">
                  <div style="font-size: 32px; margin-bottom: 8px;">❌</div>
                  <div style="font-size: 14px; font-weight: 600;">生成失败</div>
                  <div style="font-size: 12px; margin-top: 4px; color: var(--muted);">${error.message}</div>
                  <div style="font-size: 12px; margin-top: 4px; color: var(--muted);">耗时: ${elapsed}s</div>
                </div>
              `;
            }
          }
        });
      }

      // 创建加载中的占位符卡片
      function createLoadingPlaceholder(index) {
        const card = document.createElement('div');
        card.className = 'card';
        card.style.minHeight = '300px';
        card.style.display = 'flex';
        card.style.alignItems = 'center';
        card.style.justifyContent = 'center';

        // 记录开始时间
        card.dataset.startTime = performance.now();

        card.innerHTML = `
          <div style="text-align: center; color: var(--muted);">
            <div style="font-size: 48px; margin-bottom: 12px; animation: spin 2s linear infinite;">⏳</div>
            <div style="font-size: 14px; font-weight: 600; color: var(--text);">生成中 -大约2分钟完成#${index}</div>
            <div class="card-timer" style="font-size: 12px; margin-top: 4px; color: var(--accent);">0.0s</div>
          </div>
          <style>
            @keyframes spin {
              from { transform: rotate(0deg); }
              to { transform: rotate(360deg); }
            }
          </style>
        `;

        // 启动计时器，每100ms更新一次
        const timerEl = card.querySelector('.card-timer');
        const intervalId = setInterval(() => {
          const elapsed = ((performance.now() - card.dataset.startTime) / 1000).toFixed(1);
          timerEl.textContent = `${elapsed}s`;
        }, 100);

        // 保存计时器ID，以便后续清理
        card.dataset.intervalId = intervalId;

        return card;
      }

      // 在卡片中显示错误信息
      function showErrorInCard(card, errorMsg) {
        // 清理计时器
        if (card.dataset.intervalId) {
          clearInterval(parseInt(card.dataset.intervalId));
        }

        // 计算实际耗时
        const elapsed = card.dataset.startTime
          ? ((performance.now() - card.dataset.startTime) / 1000).toFixed(1)
          : '?';

        card.style.minHeight = '200px';
        card.innerHTML = `
          <div style="text-align: center; color: var(--danger); padding: 20px;">
            <div style="font-size: 48px; margin-bottom: 12px;">❌</div>
            <div style="font-size: 14px; font-weight: 600;">生成失败</div>
            <div style="font-size: 12px; margin-top: 8px; color: var(--muted);">${errorMsg}</div>
            <div style="font-size: 11px; margin-top: 4px; color: var(--muted);">耗时: ${elapsed}s</div>
          </div>
        `;
      }

      // 替换占位符卡片为真实结果
      async function replaceCardWithResult(placeholderCard, result, meta) {
        // 清理计时器
        if (placeholderCard.dataset.intervalId) {
          clearInterval(parseInt(placeholderCard.dataset.intervalId));
        }

        // 计算卡片自己的实际耗时
        const actualElapsedMs = placeholderCard.dataset.startTime
          ? (performance.now() - placeholderCard.dataset.startTime)
          : (meta?.runtimeMs || 0);

        // 清空占位符内容
        placeholderCard.innerHTML = '';
        placeholderCard.style.minHeight = '';
        placeholderCard.style.display = '';
        placeholderCard.style.alignItems = '';
        placeholderCard.style.justifyContent = '';

        console.log('[replaceCardWithResult] hasImage:', hasResultImage(result), 'imgSrc preview:', getResultImgSrc(result)?.slice(0, 80));
        if (hasResultImage(result)) {
          const imgSrc = getResultImgSrc(result);
          const imgEl = document.createElement('img');
          imgEl.src = imgSrc;
          imgEl.className = 'zoomable';
          imgEl.title = '点击放大查看';
          imgEl.addEventListener('click', () => openLightbox(imgSrc));
          placeholderCard.appendChild(imgEl);

          // 根据实际 MIME 类型确定文件扩展名
          const fileExt = getExtensionFromMime(result.mime);

          // 操作按钮区域
          const actions = document.createElement('div');
          actions.className = 'actions';

          // 显示生成耗时（使用卡片自己的实际耗时）
          const timeLabel = document.createElement('span');
          timeLabel.className = 'time-label';
          timeLabel.textContent = `⏱️ ${(actualElapsedMs / 1000).toFixed(2)}s`;
          actions.appendChild(timeLabel);

          // 下载按钮
          const downloadLink = document.createElement('a');
          downloadLink.className = 'mini-btn';
          downloadLink.textContent = '下载图片';
          downloadLink.href = imgSrc;
          downloadLink.download = generateFilename(getExtensionFromMime(result.mime));
          actions.appendChild(downloadLink);

          // 基于此图继续按钮
          const continueBtn = document.createElement('button');
          continueBtn.className = 'mini-btn primary';
          continueBtn.textContent = '🔄 基于此图继续';
          actions.appendChild(continueBtn);

          placeholderCard.appendChild(actions);

          // 继续生成面板
          const continuePanel = document.createElement('div');
          continuePanel.className = 'continue-panel';
          continuePanel.innerHTML = `
            <textarea placeholder="请输入修改提示词，例如：把背景换成海边、添加阳光效果..."></textarea>
            <div class="panel-actions">
              <button class="gen-btn">🚀 生成</button>
              <button class="cancel-btn">取消</button>
            </div>
          `;
          placeholderCard.appendChild(continuePanel);

          // 点击展开/收起面板
          continueBtn.addEventListener('click', () => {
            continuePanel.classList.toggle('show');
            if (continuePanel.classList.contains('show')) {
              continuePanel.querySelector('textarea').focus();
            }
          });

          // 取消按钮
          continuePanel.querySelector('.cancel-btn').addEventListener('click', () => {
            continuePanel.classList.remove('show');
          });

          // 生成按钮
          continuePanel.querySelector('.gen-btn').addEventListener('click', async () => {
            const newPrompt = continuePanel.querySelector('textarea').value.trim();
            if (!newPrompt) {
              flashStatus('请输入修改提示词', 'danger');
              return;
            }
            await generateFromImage(imgSrc, newPrompt, continuePanel.querySelector('.gen-btn'));
            continuePanel.classList.remove('show');
          });

          // 自动保存历史记录和下载图片
          try {
            const thumbnail = await createThumbnail(imgSrc);
            const imgInfo = await getImageInfo(imgSrc);
            const base64Size = imgSrc.length;
            const fileSize = Math.round(base64Size * 0.75);
            const mimeType = imgSrc.match(/data:([^;]+);/)?.[1] || 'unknown';

            const fileExt = getExtensionFromMime(mimeType);
            const filename = generateFilename(fileExt);

            const historyRecord = {
              thumbnail,
              filename,
              prompt: meta?.prompt || '',
              aspect: meta?.aspect || '',
              resolution: meta?.resolution || '',
              username: currentUser?.username || '',
              timestamp: Date.now()
            };
            await saveHistory(historyRecord);
            await renderHistory();
            await saveImageFile(imgSrc, filename);
            saveImageRecord(filename, meta?.prompt || '');

            console.log('图片已自动保存并添加到历史记录:', filename);
          } catch (err) {
            console.error('保存历史记录或图片失败:', err);
          }
        }
      }

      async function appendResult(result, meta) {
        const card = document.createElement('div');
        card.className = 'card';
        if (hasResultImage(result)) {
          const imgSrc = getResultImgSrc(result);
          const imgEl = document.createElement('img');
          imgEl.src = imgSrc;
          imgEl.className = 'zoomable';
          imgEl.title = '点击放大查看';
          imgEl.addEventListener('click', () => openLightbox(imgSrc));
          card.appendChild(imgEl);

          // 根据实际 MIME 类型确定文件扩展名
          const fileExt = getExtensionFromMime(result.mime);

          // 操作按钮区域
          const actions = document.createElement('div');
          actions.className = 'actions';

          // 显示生成耗时
          if (meta && meta.runtimeMs) {
            const timeLabel = document.createElement('span');
            timeLabel.className = 'time-label';
            timeLabel.textContent = `⏱️ ${(meta.runtimeMs / 1000).toFixed(2)}s`;
            actions.appendChild(timeLabel);
          }

          // 下载按钮
          const downloadLink = document.createElement('a');
          downloadLink.className = 'mini-btn';
          downloadLink.textContent = '下载图片';
          downloadLink.href = imgSrc;
          downloadLink.download = generateFilename(getExtensionFromMime(result.mime));
          actions.appendChild(downloadLink);

          // 基于此图继续按钮
          const continueBtn = document.createElement('button');
          continueBtn.className = 'mini-btn primary';
          continueBtn.textContent = '🔄 基于此图继续';
          actions.appendChild(continueBtn);

          card.appendChild(actions);

          // 继续生成面板（默认隐藏）
          const continuePanel = document.createElement('div');
          continuePanel.className = 'continue-panel';
          continuePanel.innerHTML = `
            <textarea placeholder="请输入修改提示词，例如：把背景换成海边、添加阳光效果..."></textarea>
            <div class="panel-actions">
              <button class="gen-btn">🚀 生成</button>
              <button class="cancel-btn">取消</button>
            </div>
          `;
          card.appendChild(continuePanel);

          // 点击展开/收起面板
          continueBtn.addEventListener('click', () => {
            continuePanel.classList.toggle('show');
            if (continuePanel.classList.contains('show')) {
              continuePanel.querySelector('textarea').focus();
            }
          });

          // 取消按钮
          continuePanel.querySelector('.cancel-btn').addEventListener('click', () => {
            continuePanel.classList.remove('show');
          });

          // 生成按钮
          continuePanel.querySelector('.gen-btn').addEventListener('click', async () => {
            const newPrompt = continuePanel.querySelector('textarea').value.trim();
            if (!newPrompt) {
              flashStatus('请输入修改提示词', 'danger');
              return;
            }
            await generateFromImage(imgSrc, newPrompt, continuePanel.querySelector('.gen-btn'));
            continuePanel.classList.remove('show');
          });

          // === 自动保存历史记录和下载图片 ===
          try {
            // 生成缩略图
            const thumbnail = await createThumbnail(imgSrc);

            // 获取图片实际尺寸和详细信息
            const imgInfo = await getImageInfo(imgSrc);
            const base64Size = imgSrc.length;
            const fileSize = Math.round(base64Size * 0.75); // base64 转实际字节数
            const mimeType = imgSrc.match(/data:([^;]+);/)?.[1] || 'unknown';

            console.log('========== 图片详细信息 ==========');
            console.log(`分辨率: ${imgInfo.width}×${imgInfo.height} (${(imgInfo.width * imgInfo.height / 1000000).toFixed(2)}M像素)`);
            console.log(`MIME类型: ${mimeType}`);
            console.log(`Base64长度: ${base64Size.toLocaleString()} 字符`);
            console.log(`实际文件大小: ${(fileSize / 1024 / 1024).toFixed(2)}MB (${fileSize.toLocaleString()} 字节)`);
            console.log(`平均每像素: ${(fileSize / (imgInfo.width * imgInfo.height)).toFixed(2)} 字节`);
            console.log('===================================');

            // 根据API返回的MIME类型生成文件名
            const fileExt = getExtensionFromMime(mimeType);
            const filename = generateFilename(fileExt);

            // 保存到历史记录（包含文件名）
            const historyRecord = {
              thumbnail,
              filename,
              prompt: meta?.prompt || '',
              aspect: meta?.aspect || '',
              resolution: meta?.resolution || '',
              username: currentUser?.username || '',
              timestamp: Date.now()
            };
            await saveHistory(historyRecord);

            // 刷新历史记录显示
            await renderHistory();

            // 自动保存原图到文件夹或下载
            await saveImageFile(imgSrc, filename);
            saveImageRecord(filename, meta?.prompt || '');

            console.log('图片已自动保存并添加到历史记录:', filename);
          } catch (err) {
            console.error('保存历史记录或图片失败:', err);
          }
        }
        resultsEl.prepend(card);
        resultCountEl.textContent = `${resultsEl.children.length} 条`;
      }

      // 基于图片继续生成
      async function generateFromImage(imageSrc, prompt, btn) {
        const originalText = btn.textContent;
        btn.disabled = true;
        const startTime = performance.now();

        const timingInterval = setInterval(() => {
          const elapsed = ((performance.now() - startTime) / 1000).toFixed(1);
          btn.textContent = `生成中... ${elapsed}s`;
        }, 100);

        flashStatus('基于图片生成中...', 'info');

        try {
          const mimeType = imageSrc.match(/data:([^;]+);/)?.[1] || 'image/png';
          // 跨域 URL 走代理
          const proxiedSrc = /^https?:\/\//.test(imageSrc)
            ? `image-proxy.php?url=${encodeURIComponent(imageSrc)}`
            : imageSrc;
          const refImage = { dataUrl: proxiedSrc, mime: mimeType };
          const result = await callImageAPI(prompt, [refImage]);
          if (!hasResultImage(result)) {
            throw new Error(result.text || '未返回图片，请调整提示词后重试');
          }
          const elapsed = performance.now() - startTime;
          appendResult(result, { prompt, runtimeMs: elapsed });
          flashStatus(`基于图片生成完成！耗时 ${(elapsed / 1000).toFixed(2)}s`, 'success');
          showToast('生成完成', 'success');
        } catch (err) {
          console.error('基于图片生成失败:', err);
          const errorMsg = parseApiError(err.message);
          const elapsed = ((performance.now() - startTime) / 1000).toFixed(2);
          flashStatus(`生成失败 (${elapsed}s): ${errorMsg}`, 'danger');
        } finally {
          clearInterval(timingInterval);
          btn.disabled = false;
          btn.textContent = originalText;
        }
      }

      function clearResults() {
        resultsEl.innerHTML = '';
        resultCountEl.textContent = '0 条';
        flashStatus('已清空结果', 'success');
        toggleResults(false);
      }

      function toggleResults(show) {
        const resultsCard = document.getElementById('results-card');
        const historyCard = document.getElementById('history-card');
        if (show) {
          resultsCard.style.display = '';
          historyCard.style.display = 'none';
        } else {
          resultsCard.style.display = 'none';
          historyCard.style.display = '';
        }
      }

      async function handleRun() {
        if (!currentUser) return flashStatus('请先登录后再生成图片', 'danger');

        // 检查生成限制
        try {
          const check = await (await fetch('api/record.php?action=check')).json();
          if (!check.can_generate) { flashStatus(check.reason, 'danger'); showToast(check.reason, 'error'); return; }
        } catch (_) {}

        const prompt = promptInput.value.trim();
        const count = Math.max(1, Math.min(10, parseInt(countInput.value, 10) || 1));
        if (!prompt) return flashStatus('提示词必填', 'danger');

        toggleResults(true);
        // 自动滚动到结果区
        document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });

        const startedAtAll = performance.now();
        let completed = 0;
        let failed = 0;
        let lastErrorMsg = ''; // 保存最后一个错误信息

        // 显示简单的进度提示（不显示时间）
        flashStatus(`生成中... 已完成 ${completed}/${count}`, 'info');

        // 单个请求的处理函数
        async function generateOne(index, placeholderCard) {
          const startedAt = performance.now();

          try {
            const result = await callImageAPI(prompt, state.images.slice(0, 4));
            const durationMs = performance.now() - startedAt;

            // 替换占位符为真实结果
            await replaceCardWithResult(placeholderCard, result, {
              prompt,
              aspect: aspectSelect.value,
              resolution: resolutionSelect.value,
              runtimeMs: durationMs
            });
            completed++;
          } catch (err) {
            console.error(`请求 #${index + 1} 失败:`, err);
            failed++;
            lastErrorMsg = parseApiError(err.message);
            showErrorInCard(placeholderCard, parseApiError(err.message));
          }
        }

        // 按频率发送所有请求（并发执行，但启动间隔 500ms，即每秒 2 次）
        const promises = [];
        for (let i = 0; i < count; i++) {
          // 立即创建占位符卡片
          const placeholderCard = createLoadingPlaceholder(i + 1);
          resultsEl.insertBefore(placeholderCard, resultsEl.firstChild);
          resultCountEl.textContent = `${resultsEl.children.length} 条`;

          if (i > 0) {
            await new Promise(r => setTimeout(r, 500));
          }
          promises.push(generateOne(i, placeholderCard));
        }
        await Promise.all(promises);

        // 显示完成状态
        if (failed === 0) {
          flashStatus(`完成 ${completed} 张`, 'success');
          showToast(`生成完成 · ${completed} 张`, 'success');
        } else {
          flashStatus(`失败 ${failed} 张: ${lastErrorMsg}`, 'danger');
          showToast(`生成失败 · ${failed}/${completed + failed} 张`, 'error');
        }
        // 不需要重新启用按钮，因为从未禁用
        // runBtn.disabled = false;
      }

      // ========== 多角度生成功能 ==========

      // 初始化快捷按钮
      function initPresetButtons() {
        const container = document.querySelector('.preset-buttons');
        if (!container) return;

        presetScenarios.forEach(scenario => {
          const btn = document.createElement('button');
          btn.className = 'preset-btn';
          btn.innerHTML = `
            <span class="preset-btn-label">${scenario.label}</span>
            <span class="preset-btn-desc">${scenario.description}</span>
          `;
          btn.onclick = () => scenario.id === 'multi-angle' ? openAngleModal() : handlePresetClick(scenario);

          container.appendChild(btn);
        });
      }

      // ========== 自定义角度功能 ==========

      // 角度转提示词
      function angleToPrompt(azimuth, pitch, zoom) {
        // 方位角描述（0-360度）
        let azimuthDesc = '';
        if (azimuth >= 0 && azimuth < 30) azimuthDesc = '正面';
        else if (azimuth >= 30 && azimuth < 60) azimuthDesc = '右前方';
        else if (azimuth >= 60 && azimuth < 120) azimuthDesc = '右侧';
        else if (azimuth >= 120 && azimuth < 150) azimuthDesc = '右后方';
        else if (azimuth >= 150 && azimuth < 210) azimuthDesc = '背面';
        else if (azimuth >= 210 && azimuth < 240) azimuthDesc = '左后方';
        else if (azimuth >= 240 && azimuth < 300) azimuthDesc = '左侧';
        else if (azimuth >= 300 && azimuth < 330) azimuthDesc = '左前方';
        else azimuthDesc = '正面';

        // 俯仰角描述（-90到90度）
        let pitchDesc = '';
        if (pitch >= -90 && pitch < -45) pitchDesc = '从下方仰视';
        else if (pitch >= -45 && pitch < -15) pitchDesc = '从稍低角度';
        else if (pitch >= -15 && pitch <= 15) pitchDesc = '平视';
        else if (pitch > 15 && pitch <= 45) pitchDesc = '从稍高角度俯视';
        else if (pitch > 45 && pitch <= 90) pitchDesc = '从正上方俯视';

        // 缩放描述
        let zoomDesc = '';
        if (zoom < 0.8) zoomDesc = '远景';
        else if (zoom >= 0.8 && zoom < 1.2) zoomDesc = '中景';
        else if (zoom >= 1.2 && zoom < 2.0) zoomDesc = '近景';
        else zoomDesc = '特写';

        return `${azimuthDesc}${pitchDesc}拍摄产品，${zoomDesc}镜头，专业产品摄影，高质量，细节丰富`;
      }

      // ========== 3D场景相关变量 ==========
      let angleScene = null;
      let angleCamera = null;
      let angleRenderer = null;
      let angleControls = null;
      let angleAnimationId = null;
      let referenceImageMesh = null;
      let cameraIconMesh = null;
      let cameraDirectionLine = null;

      // 初始化3D场景
      function init3DScene() {
        const canvas = document.getElementById('angle-canvas');
        if (!canvas) return;

        // 创建场景
        angleScene = new THREE.Scene();
        angleScene.background = new THREE.Color(0x0a0f1e);

        // 创建相机
        const width = canvas.clientWidth;
        const height = canvas.clientHeight;
        angleCamera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
        angleCamera.position.set(0, 5, 10);
        angleCamera.lookAt(0, 0, 0);

        // 创建渲染器
        angleRenderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
        angleRenderer.setSize(width, height);
        angleRenderer.setPixelRatio(window.devicePixelRatio);

        // 创建轨道控制器
        angleControls = new THREE.OrbitControls(angleCamera, canvas);
        angleControls.enableDamping = true;
        angleControls.dampingFactor = 0.05;
        angleControls.minDistance = 5;
        angleControls.maxDistance = 20;

        // 创建3D对象
        create3DObjects();

        // 启动渲染循环
        animate3DScene();

        // 监听窗口大小变化
        window.addEventListener('resize', onWindowResize);
      }

      // 创建3D对象
      function create3DObjects() {
        // 创建水平圆环（青色）
        const horizontalRingGeometry = new THREE.TorusGeometry(3, 0.02, 16, 100);
        const horizontalRingMaterial = new THREE.MeshBasicMaterial({ color: 0x22d3ee });
        const horizontalRing = new THREE.Mesh(horizontalRingGeometry, horizontalRingMaterial);
        horizontalRing.rotation.x = Math.PI / 2;
        angleScene.add(horizontalRing);

        // 创建垂直椭圆轨道（灰白色）
        const verticalEllipseCurve = new THREE.EllipseCurve(
          0, 0,           // 中心点
          3, 4,           // x半径, y半径
          0, 2 * Math.PI, // 起始角度, 结束角度
          false,          // 顺时针
          0               // 旋转角度
        );
        const verticalEllipsePoints = verticalEllipseCurve.getPoints(100);
        const verticalEllipseGeometry = new THREE.BufferGeometry().setFromPoints(verticalEllipsePoints);
        const verticalEllipseMaterial = new THREE.LineBasicMaterial({ color: 0x94a3b8 });
        const verticalEllipse = new THREE.Line(verticalEllipseGeometry, verticalEllipseMaterial);
        verticalEllipse.rotation.y = Math.PI / 2;
        angleScene.add(verticalEllipse);

        // 创建参考图片平面
        createReferenceImagePlane();

        // 创建相机图标
        createCameraIcon();

        // 创建相机方向指示线（从相机指向图片中心）
        const lineGeometry = new THREE.BufferGeometry().setFromPoints([
          new THREE.Vector3(0, 0, 0),
          new THREE.Vector3(0, 0, 0)
        ]);
        const lineMaterial = new THREE.LineBasicMaterial({
          color: 0xffd700,  // 金黄色
          linewidth: 2,
          opacity: 0.8,
          transparent: true
        });
        cameraDirectionLine = new THREE.Line(lineGeometry, lineMaterial);
        angleScene.add(cameraDirectionLine);

        // 添加环境光
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
        angleScene.add(ambientLight);

        // 添加方向光
        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.4);
        directionalLight.position.set(5, 5, 5);
        angleScene.add(directionalLight);
      }

      // 创建参考图片平面
      function createReferenceImagePlane() {
        const geometry = new THREE.PlaneGeometry(2, 2);
        const material = new THREE.MeshBasicMaterial({
          color: 0xffffff,
          side: THREE.DoubleSide,
          transparent: true,
          opacity: 0.9
        });
        referenceImageMesh = new THREE.Mesh(geometry, material);
        referenceImageMesh.position.set(0, 0, 0);
        angleScene.add(referenceImageMesh);
      }

      // 创建相机图标
      function createCameraIcon() {
        const group = new THREE.Group();

        // 相机主体（更大的立方体，黑色）
        const bodyGeometry = new THREE.BoxGeometry(0.5, 0.6, 0.8);
        const bodyMaterial = new THREE.MeshPhongMaterial({
          color: 0x2d3748,
          shininess: 30
        });
        const body = new THREE.Mesh(bodyGeometry, bodyMaterial);
        group.add(body);

        // 镜头主体（更大的圆柱体，深灰色）- 朝向-Z方向
        const lensBodyGeometry = new THREE.CylinderGeometry(0.25, 0.25, 0.4, 32);
        const lensBodyMaterial = new THREE.MeshPhongMaterial({
          color: 0x1a202c,
          shininess: 50
        });
        const lensBody = new THREE.Mesh(lensBodyGeometry, lensBodyMaterial);
        lensBody.rotation.x = Math.PI / 2;
        lensBody.position.set(0, 0, 0.45);
        group.add(lensBody);

        // 镜头外环（银色，更突出）
        const lensRingGeometry = new THREE.CylinderGeometry(0.28, 0.28, 0.05, 32);
        const lensRingMaterial = new THREE.MeshPhongMaterial({
          color: 0x718096,
          shininess: 80,
          metalness: 0.5
        });
        const lensRing = new THREE.Mesh(lensRingGeometry, lensRingMaterial);
        lensRing.rotation.x = Math.PI / 2;
        lensRing.position.set(0, 0, 0.65);
        group.add(lensRing);

        // 镜头玻璃（深蓝色，半透明）
        const lensGlassGeometry = new THREE.CylinderGeometry(0.22, 0.22, 0.05, 32);
        const lensGlassMaterial = new THREE.MeshPhongMaterial({
          color: 0x1e3a8a,
          shininess: 100,
          transparent: true,
          opacity: 0.8
        });
        const lensGlass = new THREE.Mesh(lensGlassGeometry, lensGlassMaterial);
        lensGlass.rotation.x = Math.PI / 2;
        lensGlass.position.set(0, 0, 0.68);
        group.add(lensGlass);

        // 取景器（顶部的小突起）
        const viewfinderGeometry = new THREE.BoxGeometry(0.2, 0.2, 0.25);
        const viewfinderMaterial = new THREE.MeshPhongMaterial({
          color: 0x1a202c,
          shininess: 30
        });
        const viewfinder = new THREE.Mesh(viewfinderGeometry, viewfinderMaterial);
        viewfinder.position.set(0, 0.4, -0.15);
        group.add(viewfinder);

        // 闪光灯（顶部的小方块，青色发光）
        const flashGeometry = new THREE.BoxGeometry(0.1, 0.1, 0.15);
        const flashMaterial = new THREE.MeshPhongMaterial({
          color: 0x22d3ee,
          emissive: 0x22d3ee,
          emissiveIntensity: 0.5,
          shininess: 100
        });
        const flash = new THREE.Mesh(flashGeometry, flashMaterial);
        flash.position.set(0, 0.35, 0.2);
        group.add(flash);

        // 握把（底部的突起）
        const gripGeometry = new THREE.BoxGeometry(0.4, 0.5, 0.3);
        const gripMaterial = new THREE.MeshPhongMaterial({
          color: 0x374151,
          shininess: 20
        });
        const grip = new THREE.Mesh(gripGeometry, gripMaterial);
        grip.position.set(0, -0.1, -0.45);
        group.add(grip);

        // 快门按钮（顶部的小圆柱）
        const shutterGeometry = new THREE.CylinderGeometry(0.08, 0.08, 0.08, 16);
        const shutterMaterial = new THREE.MeshPhongMaterial({
          color: 0xef4444,
          shininess: 80
        });
        const shutter = new THREE.Mesh(shutterGeometry, shutterMaterial);
        shutter.position.set(0.15, 0.35, -0.3);
        group.add(shutter);

        cameraIconMesh = group;
        angleScene.add(cameraIconMesh);
      }

      // 渲染循环
      function animate3DScene() {
        if (!angleRenderer || !angleScene || !angleCamera) return;

        angleAnimationId = requestAnimationFrame(animate3DScene);

        if (angleControls) {
          angleControls.update();
        }

        angleRenderer.render(angleScene, angleCamera);
      }

      // 窗口大小变化处理
      function onWindowResize() {
        if (!angleCamera || !angleRenderer) return;

        const canvas = document.getElementById('angle-canvas');
        if (!canvas) return;

        const width = canvas.clientWidth;
        const height = canvas.clientHeight;

        angleCamera.aspect = width / height;
        angleCamera.updateProjectionMatrix();
        angleRenderer.setSize(width, height);
      }

      // 根据滑块值更新3D视图
      function update3DView(azimuth, pitch, zoom) {
        if (!cameraIconMesh || !referenceImageMesh) return;

        // 将角度转换为弧度
        const azimuthRad = (azimuth * Math.PI) / 180;
        const pitchRad = (pitch * Math.PI) / 180;

        // 计算相机图标的位置（在椭圆轨道上）
        const radius = 3;
        const x = radius * Math.cos(azimuthRad) * Math.cos(pitchRad);
        const y = radius * Math.sin(pitchRad);
        const z = radius * Math.sin(azimuthRad) * Math.cos(pitchRad);

        cameraIconMesh.position.set(x, y, z);

        // 让相机图标朝向参考图片
        cameraIconMesh.lookAt(0, 0, 0);

        // 更新相机方向指示线（从相机位置指向图片中心）
        if (cameraDirectionLine) {
          const positions = cameraDirectionLine.geometry.attributes.position.array;
          positions[0] = x;
          positions[1] = y;
          positions[2] = z;
          positions[3] = 0;
          positions[4] = 0;
          positions[5] = 0;
          cameraDirectionLine.geometry.attributes.position.needsUpdate = true;
        }

        // 更新参考图片的缩放
        const scale = zoom;
        referenceImageMesh.scale.set(scale, scale, scale);
      }

      // 清理3D场景
      function dispose3DScene() {
        if (angleAnimationId) {
          cancelAnimationFrame(angleAnimationId);
          angleAnimationId = null;
        }

        if (angleRenderer) {
          angleRenderer.dispose();
          angleRenderer = null;
        }

        if (angleControls) {
          angleControls.dispose();
          angleControls = null;
        }

        angleScene = null;
        angleCamera = null;
        referenceImageMesh = null;
        cameraIconMesh = null;
        cameraDirectionLine = null;

        window.removeEventListener('resize', onWindowResize);
      }

      // 加载参考图片到3D场景
      function loadReferenceImage(file) {
        if (!file || !file.type.startsWith('image/')) return;

        const reader = new FileReader();
        reader.onload = (e) => {
          const img = new Image();
          img.onload = () => {
            // 更新左侧预览
            const referenceImageContainer = document.getElementById('angle-reference-image');
            if (referenceImageContainer) {
              referenceImageContainer.innerHTML = `
                <img src="${e.target.result}" alt="参考图片">
                <button class="angle-reference-close" onclick="clearReferenceImage()">✕</button>
              `;
            }

            // 更新3D场景中的纹理
            if (referenceImageMesh) {
              const texture = new THREE.TextureLoader().load(e.target.result);
              referenceImageMesh.material.map = texture;
              referenceImageMesh.material.needsUpdate = true;

              // 根据图片比例调整平面尺寸
              const aspect = img.width / img.height;
              if (aspect > 1) {
                referenceImageMesh.scale.set(aspect, 1, 1);
              } else {
                referenceImageMesh.scale.set(1, 1 / aspect, 1);
              }
            }
          };
          img.src = e.target.result;
        };
        reader.readAsDataURL(file);
      }

      // 从 dataUrl 加载参考图片到3D场景
      function loadReferenceImageFromDataUrl(dataUrl) {
        if (!dataUrl) return;

        const img = new Image();
        img.onload = () => {
          // 更新左侧预览（不显示关闭按钮，因为是自动加载的）
          const referenceImageContainer = document.getElementById('angle-reference-image');
          if (referenceImageContainer) {
            referenceImageContainer.innerHTML = `
              <img src="${dataUrl}" alt="参考图片">
            `;
          }

          // 更新3D场景中的纹理
          if (referenceImageMesh) {
            const texture = new THREE.TextureLoader().load(dataUrl);
            referenceImageMesh.material.map = texture;
            referenceImageMesh.material.needsUpdate = true;

            // 根据图片比例调整平面尺寸
            const aspect = img.width / img.height;
            if (aspect > 1) {
              referenceImageMesh.scale.set(aspect, 1, 1);
            } else {
              referenceImageMesh.scale.set(1, 1 / aspect, 1);
            }
          }
        };
        img.src = dataUrl;
      }

      // 清除参考图片
      function clearReferenceImage() {
        const referenceImageContainer = document.getElementById('angle-reference-image');
        if (referenceImageContainer) {
          referenceImageContainer.innerHTML = '<div class="angle-reference-placeholder">未选择参考图片</div>';
        }

        // 清除3D场景中的纹理
        if (referenceImageMesh) {
          referenceImageMesh.material.map = null;
          referenceImageMesh.material.needsUpdate = true;
          referenceImageMesh.scale.set(1, 1, 1);
        }
      }

      // 暴露到全局作用域，供HTML onclick使用
      window.clearReferenceImage = clearReferenceImage;

      // 更新角度预览
      function updateAnglePreview() {
        const azimuth = parseInt(document.getElementById('azimuth-slider').value);
        const pitch = parseInt(document.getElementById('pitch-slider').value);
        const zoom = parseFloat(document.getElementById('zoom-slider').value);

        // 更新显示值
        document.getElementById('azimuth-value').textContent = `${azimuth}°`;
        document.getElementById('pitch-value').textContent = `${pitch}°`;
        document.getElementById('zoom-value').textContent = `${zoom.toFixed(1)}x`;

        // 更新3D视图
        update3DView(azimuth, pitch, zoom);
      }

      // 打开角度调整弹窗
      function openAngleModal() {
        // 检查是否有参考图
        if (!state.images || state.images.length === 0) {
          alert('⚠️ 请先上传参考图\n\n请在主界面上传产品图片后再使用此功能。');
          return;
        }

        const modal = document.getElementById('angle-modal');
        if (modal) {
          modal.classList.add('active');

          // 延迟初始化3D场景，等待DOM渲染完成
          setTimeout(() => {
            init3DScene();
            // 自动加载主界面的第一张参考图
            loadReferenceImageFromDataUrl(state.images[0].dataUrl);
            updateAnglePreview(); // 初始化预览
          }, 100);
        }
      }

      // 关闭角度调整弹窗
      function closeAngleModal() {
        const modal = document.getElementById('angle-modal');
        if (modal) {
          modal.classList.remove('active');

          // 清理3D场景
          dispose3DScene();
        }
      }

      // 处理快捷按钮点击
      async function handlePresetClick(scenario) {
        // 如果是分镜生成，直接使用外面的提示词
        if (scenario.isStoryboard) {
          const scriptText = promptInput.value.trim();
          if (!scriptText) {
            alert('请先在提示词输入框中输入分镜脚本');
            return;
          }

          // 显示加载状态
          flashStatus('正在识别分镜...', 'info');

          try {
            const result = await analyzeStoryboard(scriptText);
            showStoryboardPreview(result, scriptText);
          } catch (error) {
            flashStatus('识别失败：' + error.message, 'danger');
          }
          return;
        }

        // 检查是否需要参考图
        if (scenario.requiresReference) {
          const hasReference = state.images.length > 0;
          if (!hasReference) {
            alert('⚠️ 此场景需要参考图\n\n请先上传参考图或生成一张满意的产品图，然后再使用此功能。');
            return;
          }
        }

        // 确认生成
        const confirmed = confirm(
          `${scenario.label}\n\n` +
          `将基于当前参考图生成 ${scenario.prompts.length} 张图片。\n\n` +
          `⚠️ 提示：AI生成的多角度图可能存在细节差异，建议多次生成选择最佳效果。\n\n` +
          `是否继续？`
        );

        if (!confirmed) return;

        // 创建任务
        const taskId = ++taskIdCounter;
        const taskInfo = {
          id: taskId,
          scenario: scenario,
          startTime: Date.now(),
          completed: 0,
          total: scenario.prompts.length,
          results: []
        };

        activeTasks.set(taskId, taskInfo);

        // 开始生成
        await generateMultiAngle(taskInfo);
      }

      // 多角度生成核心函数
      async function generateMultiAngle(taskInfo) {
        const { scenario, id: taskId } = taskInfo;

        toggleResults(true);
        document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });

        // 创建结果分组容器
        const groupContainer = createResultGroup(taskInfo);
        resultsEl.insertBefore(groupContainer, resultsEl.firstChild);

        // 创建占位符卡片
        const gridEl = groupContainer.querySelector('.result-group-grid');
        const placeholders = scenario.angles.map((angleName, index) => {
          const placeholder = createPlaceholderCard(angleName, `placeholder-${taskId}-${index}`);
          gridEl.appendChild(placeholder);
          return { element: placeholder, id: `placeholder-${taskId}-${index}` };
        });

        // 获取当前参考图
        const currentReferenceImages = [...state.images];

        // 并发生成所有角度
        const promises = scenario.prompts.map(async (promptTemplate, index) => {
          try {
            // 延迟启动（避免API限流）
            if (index > 0) {
              await new Promise(r => setTimeout(r, 500 * index));
            }

            // 更新进度
            updateTaskProgress(taskId, `正在生成 ${scenario.angles[index]}...`);

            // 调用图生图API
            const result = await generateWithReference(
              promptTemplate,
              currentReferenceImages,
              scenario.angles[index]
            );

            // 保存结果
            taskInfo.results.push(result);
            taskInfo.completed++;

            // 替换占位符为实际结果
            const placeholderId = `placeholder-${taskId}-${index}`;
            const placeholderEl = document.getElementById(placeholderId);
            let actualElapsedMs = 0;

            if (placeholderEl) {
              // 清理计时器
              if (placeholderEl.dataset.intervalId) {
                clearInterval(parseInt(placeholderEl.dataset.intervalId));
              }

              // 计算实际耗时
              if (placeholderEl.dataset.startTime) {
                actualElapsedMs = performance.now() - placeholderEl.dataset.startTime;
              }

              placeholderEl.remove();
            }

            // 显示结果（传递实际耗时）
            appendResultToGroup(groupContainer, result, scenario.angles[index], actualElapsedMs);

            // 更新进度
            updateTaskProgress(taskId, `已完成 ${taskInfo.completed}/${taskInfo.total}`);

            return result;
          } catch (error) {
            console.error(`生成 ${scenario.angles[index]} 失败:`, error);
            taskInfo.completed++;
            return null;
          }
        });

        // 等待所有生成完成
        await Promise.all(promises);

        // 任务完成
        const elapsed = ((Date.now() - taskInfo.startTime) / 1000).toFixed(1);
        updateTaskProgress(taskId, `✅ 全部完成！耗时 ${elapsed}s`);
        showToast(`多角度生成完成 · ${elapsed}s`, 'success');

        // 3秒后移除任务
        setTimeout(() => {
          activeTasks.delete(taskId);
        }, 3000);
      }

      // 图生图API调用
      async function generateWithReference(promptTemplate, referenceImages, angleName) {
        return callImageAPI(promptTemplate, referenceImages);
      }

      // 创建结果分组容器
      function createResultGroup(taskInfo) {
        const { scenario, id: taskId } = taskInfo;

        const group = document.createElement('div');
        group.className = 'result-group';
        group.id = `task-group-${taskId}`;

        group.innerHTML = `
          <div class="result-group-header">
            <div class="result-group-title">${scenario.label}</div>
            <div class="result-group-meta">
              <span id="task-progress-${taskId}">准备中...</span>
            </div>
          </div>
          <div class="result-group-grid" id="task-grid-${taskId}"></div>
        `;

        return group;
      }

      // 更新任务进度
      function updateTaskProgress(taskId, message) {
        const progressEl = document.getElementById(`task-progress-${taskId}`);
        if (progressEl) {
          progressEl.textContent = message;
        }
      }

      // 创建占位符卡片
      function createPlaceholderCard(angleName, placeholderId) {
        const card = document.createElement('div');
        card.className = 'card';
        card.id = placeholderId;
        card.style.minHeight = '300px';
        card.style.display = 'flex';
        card.style.alignItems = 'center';
        card.style.justifyContent = 'center';
        card.style.background = 'var(--card)';
        card.style.border = '2px dashed var(--border)';

        // 记录开始时间
        card.dataset.startTime = performance.now();

        card.innerHTML = `
          <div style="text-align: center; color: var(--muted);">
            <div style="font-size: 48px; margin-bottom: 12px; animation: spin 2s linear infinite;">⏳</div>
            <div style="font-size: 14px; font-weight: 600; color: var(--text);">${angleName}</div>
            <div class="card-timer" style="font-size: 12px; margin-top: 4px; color: var(--accent);">0.0s</div>
          </div>
          <style>
            @keyframes spin {
              from { transform: rotate(0deg); }
              to { transform: rotate(360deg); }
            }
          </style>
        `;

        // 启动计时器，每100ms更新一次
        const timerEl = card.querySelector('.card-timer');
        const intervalId = setInterval(() => {
          const elapsed = ((performance.now() - card.dataset.startTime) / 1000).toFixed(1);
          timerEl.textContent = `${elapsed}s`;
        }, 100);

        // 保存计时器ID，以便后续清理
        card.dataset.intervalId = intervalId;

        return card;
      }

      // 添加结果到分组
      async function appendResultToGroup(groupContainer, result, angleName, actualElapsedMs, retryPrompt) {
        const gridEl = groupContainer.querySelector('.result-group-grid');
        if (!gridEl || !result || !hasResultImage(result)) return;

        const card = document.createElement('div');
        card.className = 'card';

        const imgSrc = getResultImgSrc(result);

        const imgEl = document.createElement('img');
        imgEl.src = imgSrc;
        imgEl.className = 'zoomable';
        imgEl.title = '点击放大查看';
        imgEl.addEventListener('click', () => openLightbox(imgSrc));
        card.appendChild(imgEl);

        // 操作按钮
        const actions = document.createElement('div');
        actions.className = 'actions';

        // 显示实际耗时（如果有）
        if (actualElapsedMs > 0) {
          const timeLabel = document.createElement('span');
          timeLabel.className = 'time-label';
          timeLabel.textContent = `⏱️ ${(actualElapsedMs / 1000).toFixed(2)}s`;
          actions.appendChild(timeLabel);
        }

        // 角度标签
        const angleLabel = document.createElement('span');
        angleLabel.className = 'time-label';
        angleLabel.textContent = angleName;
        actions.appendChild(angleLabel);

        // 下载按钮
        const downloadLink = document.createElement('a');
        downloadLink.className = 'mini-btn';
        downloadLink.textContent = '下载';
        downloadLink.href = imgSrc;
        downloadLink.download = `${angleName}-${Date.now()}.${getExtensionFromMime(result.mime)}`;
        actions.appendChild(downloadLink);

        // 重试按钮（如果有 retryPrompt）
        if (retryPrompt) {
          const retryBtn = document.createElement('button');
          retryBtn.className = 'mini-btn';
          retryBtn.textContent = '🔄 重试';
          retryBtn.title = '使用相同参数重新生成此分镜';
          retryBtn.addEventListener('click', async () => {
            const originalText = retryBtn.textContent;
            retryBtn.disabled = true;
            retryBtn.textContent = '生成中...';

            try {
              // 重新生成
              const newResult = await generateStoryboardShot(retryPrompt);

              // 替换当前卡片的图片
              const newImgSrc = getResultImgSrc(newResult);

              imgEl.src = newImgSrc;
              downloadLink.href = newImgSrc;
              downloadLink.download = `${angleName}-${Date.now()}.${getExtensionFromMime(newResult.mime)}`;

              // 保存新图片
              const thumbnail = await createThumbnail(newImgSrc);
              const filename = `${angleName}-${Date.now()}.${getExtensionFromMime(newResult.mime)}`;
              await saveHistory({
                thumbnail,
                filename,
                prompt: angleName,
                aspect: aspectSelect.value,
                resolution: resolutionSelect.value,
                username: currentUser?.username || '',
                timestamp: Date.now()
              });
              await saveImageFile(newImgSrc, filename);
              saveImageRecord(filename, angleName);
              await renderHistory();

              flashStatus(`${angleName} 重新生成成功`, 'success');
            } catch (error) {
              console.error('重试失败:', error);
              flashStatus(`${angleName} 重试失败: ${parseApiError(error.message)}`, 'danger');
            } finally {
              retryBtn.disabled = false;
              retryBtn.textContent = originalText;
            }
          });
          actions.appendChild(retryBtn);
        }

        card.appendChild(actions);
        gridEl.appendChild(card);

        // 自动保存
        try {
          const thumbnail = await createThumbnail(imgSrc);
          const filename = `${angleName}-${Date.now()}.${getExtensionFromMime(result.mime)}`;

          await saveHistory({
            thumbnail,
            filename,
            prompt: angleName,
            aspect: aspectSelect.value,
            resolution: resolutionSelect.value,
            username: currentUser?.username || '',
            timestamp: Date.now()
          });

          await saveImageFile(imgSrc, filename);
          saveImageRecord(filename, angleName);

          // 刷新历史记录显示
          await renderHistory();
        } catch (err) {
          console.error('保存失败:', err);
        }
      }

      const clearResultsBtn = document.getElementById('clear-results');

      fileInput.addEventListener('change', e => handleFiles(e.target.files));
      runBtn.addEventListener('click', handleRun);
      protocolSelect.addEventListener('change', refreshAspectOptions);
      resolutionSelect.addEventListener('change', refreshAspectOptions);
      countInput.addEventListener('input', () => {
        let val = parseInt(countInput.value, 10);
        if (val > 10) countInput.value = 10;
        if (val < 1 && countInput.value !== '') countInput.value = 1;
      });
      countInput.addEventListener('blur', () => {
        let val = parseInt(countInput.value, 10);
        if (isNaN(val) || val < 1) countInput.value = 1;
        if (val > 10) countInput.value = 10;
      });
      clearResultsBtn.addEventListener('click', clearResults);
      window.addEventListener('paste', e => {
        if (e.clipboardData?.files?.length) handleFiles(e.clipboardData.files);
      });
      window.addEventListener('dragover', e => e.preventDefault());
      window.addEventListener('drop', e => {
        e.preventDefault();
        if (e.dataTransfer?.files?.length) handleFiles(e.dataTransfer.files);
      });

      // 模型/协议变更时自动保存
      imageModelSelect.addEventListener('change', saveSettings);
      textModelSelect.addEventListener('change', saveSettings);
      protocolSelect.addEventListener('change', saveSettings);

      loadSettings();
      renderUploads();
      fetchModelList(); // 每次打开网页自动拉取模型列表

      // 初始化按钮组
      buildBtnGroup(aspectSelect, document.getElementById('aspect-btns'));
      buildBtnGroup(resolutionSelect, document.getElementById('resolution-btns'));

      // 初始化快捷按钮
      initPresetButtons();

      // 初始化角度调整弹窗事件监听器
      const angleModal = document.getElementById('angle-modal');
      const angleModalClose = document.getElementById('angle-modal-close');
      const angleModalCancel = document.getElementById('angle-modal-cancel');
      const angleModalConfirm = document.getElementById('angle-modal-confirm');
      const azimuthSlider = document.getElementById('azimuth-slider');
      const pitchSlider = document.getElementById('pitch-slider');
      const zoomSlider = document.getElementById('zoom-slider');

      // 移除了参考图片上传功能，改为自动加载主界面的第一张参考图

      // 滑块实时更新预览
      if (azimuthSlider) azimuthSlider.addEventListener('input', updateAnglePreview);
      if (pitchSlider) pitchSlider.addEventListener('input', updateAnglePreview);
      if (zoomSlider) zoomSlider.addEventListener('input', updateAnglePreview);

      // 关闭按钮
      if (angleModalClose) angleModalClose.addEventListener('click', closeAngleModal);
      if (angleModalCancel) angleModalCancel.addEventListener('click', closeAngleModal);

      // 点击遮罩层关闭弹窗
      if (angleModal) {
        angleModal.addEventListener('click', (e) => {
          if (e.target === angleModal) closeAngleModal();
        });
      }

      // 确定按钮：将提示词填入输入框
      if (angleModalConfirm) {
        angleModalConfirm.addEventListener('click', () => {
          const azimuth = parseInt(document.getElementById('azimuth-slider').value);
          const pitch = parseInt(document.getElementById('pitch-slider').value);
          const zoom = parseFloat(document.getElementById('zoom-slider').value);
          const prompt = angleToPrompt(azimuth, pitch, zoom);

          // 填入提示词输入框
          promptInput.value = prompt;

          // 关闭弹窗
          closeAngleModal();

          // 提示用户
          flashStatus('提示词已生成', 'success');
        });
      }

      // 初始化 IndexedDB 并加载历史记录
      initDB().then(() => {
        renderHistory();
        renderPromptLibrary();

        // 标签页切换事件
        document.querySelectorAll('.preset-tab-btn').forEach(btn => {
          btn.addEventListener('click', () => {
            const tabName = btn.getAttribute('data-tab');
            switchPresetTab(tabName);
          });
        });

        // 折叠/展开功能
        const presetCollapseToggle = document.getElementById('preset-collapse-toggle');
        const presetScenariosContainer = document.querySelector('.preset-scenarios');

        presetCollapseToggle.addEventListener('click', () => {
          presetScenariosContainer.classList.toggle('collapsed');
        });

        // 从输入框保存提示词按钮
        const savePromptFromInputBtn = document.getElementById('save-prompt-from-input');
        const promptInput = document.getElementById('prompt');

        savePromptFromInputBtn.addEventListener('click', () => {
          const promptContent = promptInput.value.trim();

          if (!promptContent) {
            flashStatus('请先输入提示词内容', 'danger');
            promptInput.focus();
            return;
          }

          // 显示保存对话框
          showSavePromptDialog(promptContent);
        });

        // 优化提示词按钮
        const optimizePromptBtn = document.getElementById('optimize-prompt-btn');

        optimizePromptBtn.addEventListener('click', () => {
          const promptContent = promptInput.value.trim();

          if (!promptContent) {
            flashStatus('请先输入提示词内容', 'danger');
            promptInput.focus();
            return;
          }

          // 直接显示弹窗，不调用API
          showPromptCompareDialog(promptContent);
        });
      }).catch(err => {
        console.error('初始化数据库失败:', err);
      });

      // ====== 登录 & 注册 ======
      const authDialog = document.getElementById('auth-dialog');
      const authTitle  = document.getElementById('auth-title');
      const authSubmit = document.getElementById('auth-submit');
      const authSwitch = document.getElementById('auth-switch');
      const authCancel = document.getElementById('auth-cancel');
      const authUser   = document.getElementById('auth-username');
      const authPass   = document.getElementById('auth-password');
      const authPass2  = document.getElementById('auth-password2');
      const loginBtn   = document.getElementById('login-btn');
      const logoutBtn  = document.getElementById('logout-btn');
      const adminLink  = document.getElementById('admin-link');
      const heroUser   = document.getElementById('hero-user');

      let authMode = 'login'; // 'login' | 'register'
      let currentUser = null;

      function updateAuthUI() {
        if (currentUser) {
          heroUser.style.display = ''; heroUser.textContent = currentUser.username;
          changepwBtn.style.display = ''; loginBtn.style.display = 'none'; logoutBtn.style.display = '';
          if (currentUser.role === 'admin') { adminLink.style.display = ''; }
          if (statusEl.textContent === '请先登录后再生成图片') statusEl.textContent = '待发送...';
        } else {
          heroUser.style.display = 'none'; changepwBtn.style.display = 'none'; loginBtn.style.display = '';
          logoutBtn.style.display = 'none'; adminLink.style.display = 'none';
          statusEl.textContent = '请先登录后再生成图片';
        }
        renderHistory(); // 切换用户时刷新历史
      }

      function openAuthDialog(mode) {
        authMode = mode;
        authTitle.textContent = mode === 'login' ? '登录' : '注册';
        authSubmit.textContent = mode === 'login' ? '登录' : '注册';
        authSwitch.innerHTML = mode === 'login'
          ? '<a href="#" style="color:var(--text-secondary)">没有账号？去注册</a>'
          : '<a href="#" style="color:var(--text-secondary)">已有账号？去登录</a>';
        authDialog.classList.add('active');
        authUser.value = ''; authPass.value = ''; authPass2.value = '';
        authPass2.style.display = mode === 'register' ? '' : 'none';
        setTimeout(() => authUser.focus(), 100);
      }

      function closeAuthDialog() { authDialog.classList.remove('active'); }

      async function doAuth() {
        const username = authUser.value.trim();
        const password = authPass.value.trim();
        if (!username || !password) { flashStatus('请填写用户名和密码', 'danger'); return; }
        if (authMode === 'register' && password !== authPass2.value) { flashStatus('两次密码不一致', 'danger'); return; }

        authSubmit.disabled = true; authSubmit.textContent = '处理中...';
        try {
          const res = await fetch(`api/auth.php?action=${authMode}`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
          });
          const data = await res.json();
          if (!res.ok || data.error) {
            alert(data.error || `请求失败 (${res.status})`);
            return;
          }
          currentUser = data;
          updateAuthUI();
          closeAuthDialog();
          flashStatus(authMode === 'login' ? '登录成功' : '注册成功', 'success');
          showToast(authMode === 'login' ? '登录成功' : '注册成功', 'success');
        } catch (err) {
          flashStatus('网络请求失败，请检查服务', 'danger');
          console.error('Auth error:', err);
        } finally {
          authSubmit.disabled = false;
          authSubmit.textContent = authMode === 'login' ? '登录' : '注册';
        }
      }

      async function doLogout() {
        await fetch('api/auth.php?action=logout', { method: 'POST' });
        currentUser = null;
        updateAuthUI();
        toggleResults(false); // 切回历史视图
        flashStatus('已退出登录', 'success');
      }

      async function checkSession() {
        try {
          const res = await fetch('api/auth.php?action=me');
          const data = await res.json();
          if (data && data.id) { currentUser = data; updateAuthUI(); }
        } catch (_) {}
      }

      let features = {};
      loginBtn.addEventListener('click', () => openAuthDialog('login'));
      logoutBtn.addEventListener('click', doLogout);
      authCancel.addEventListener('click', closeAuthDialog);
      authSubmit.addEventListener('click', doAuth);
      authSwitch.addEventListener('click', (e) => {
        e.preventDefault();
        if (authMode === 'login' && features.disable_register) {
          alert(features.register_block_msg || '暂时停止注册');
          return;
        }
        openAuthDialog(authMode === 'login' ? 'register' : 'login');
      });
      authDialog.addEventListener('click', (e) => { if (e.target === authDialog) closeAuthDialog(); });
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && authDialog.classList.contains('active')) closeAuthDialog();
        if (e.key === 'Enter' && authDialog.classList.contains('active')) doAuth();
      });

      // ====== Ctrl+Enter 发送 ======
      promptInput.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
          e.preventDefault();
          handleRun();
        }
      });

      // ====== 批量下载 ======
      const batchDownloadBtn = document.getElementById('batch-download');
      batchDownloadBtn.addEventListener('click', async () => {
        const checks = document.querySelectorAll('.hist-check:checked');
        if (checks.length === 0) { flashStatus('请先勾选要下载的图片', 'danger'); return; }
        const files = Array.from(checks).map(cb => cb.dataset.file);
        const username = currentUser?.username || '';
        batchDownloadBtn.textContent = '打包中...'; batchDownloadBtn.disabled = true;
        try {
          const res = await fetch('download.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ files, user: username })
          });
          if (!res.ok) { flashStatus('下载失败，文件可能不存在', 'danger'); return; }
          const blob = await res.blob();
          const url = URL.createObjectURL(blob);
          const a = document.createElement('a'); a.href = url;
          a.download = files.length === 1 ? files[0] : 'images.zip'; a.click();
          URL.revokeObjectURL(url);
          flashStatus(`已下载 ${files.length} 张`, 'success');
        } catch (e) { flashStatus('下载失败', 'danger'); }
        finally { batchDownloadBtn.textContent = '批量下载'; batchDownloadBtn.disabled = false; }
      });

      // 监听复选框变化，显示/隐藏批量下载按钮
      document.addEventListener('change', (e) => {
        if (e.target.classList.contains('hist-check')) {
          const any = document.querySelectorAll('.hist-check:checked').length > 0;
          batchDownloadBtn.style.display = any ? '' : 'none';
        }
      });

      // ====== 读取功能开关 ======
      async function loadFeatureToggles() {
        try {
          const res = await fetch('api/features.php');
          features = await res.json();
          // 字符串值需转回布尔
          if (features.show_folder_card === 'false') features.show_folder_card = false;
          if (features.show_presets === 'false') features.show_presets = false;
          if (features.disable_register === 'false') features.disable_register = false;
          if (!features.show_folder_card) {
            const el = document.querySelector('.card-folder');
            if (el) el.style.display = 'none';
          }
          if (!features.show_presets) {
            const el = document.querySelector('.card-presets');
            if (el) el.style.display = 'none';
          }
        } catch (_) {}
      }
      loadFeatureToggles();

      // ====== 修改密码 ======
      const changepwBtn = document.getElementById('changepw-btn');
      const changepwDialog = document.getElementById('changepw-dialog');
      changepwBtn.addEventListener('click', () => changepwDialog.classList.add('active'));
      document.getElementById('changepw-cancel').addEventListener('click', () => changepwDialog.classList.remove('active'));
      document.getElementById('changepw-submit').addEventListener('click', async () => {
        const oldPw = document.getElementById('changepw-old').value;
        const newPw = document.getElementById('changepw-new').value;
        if (!oldPw || !newPw) { flashStatus('请填写原密码和新密码', 'danger'); return; }
        const res = await fetch('api/auth.php?action=changepw', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ old_password: oldPw, new_password: newPw })
        });
        const data = await res.json();
        if (data.error) { alert(data.error); return; }
        changepwDialog.classList.remove('active');
        document.getElementById('changepw-old').value = '';
        document.getElementById('changepw-new').value = '';
        showToast('密码已修改', 'success');
      });

      checkSession();

      // ====== 主题切换 ======
      const themeToggle = document.getElementById('theme-toggle');
      const STORAGE_KEY = 'app-theme';

      function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(STORAGE_KEY, theme);
      }

      // 默认浅色
      const saved = localStorage.getItem(STORAGE_KEY) || 'light';
      setTheme(saved);

      themeToggle.addEventListener('click', () => {
        const current = document.documentElement.getAttribute('data-theme');
        setTheme(current === 'dark' ? 'light' : 'dark');
      });
    })();
  </script>
</body>

</html>
