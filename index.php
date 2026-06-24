<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!doctype html>
<html lang="ar" dir="rtl">
  <head>
    <link rel="icon" href="/images/favicon.ico?v=2" />
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>الاستعلام عن المقررات الدراسية</title>

    <meta
      http-equiv="Cache-Control"
      content="no-cache, no-store, must-revalidate"
    />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
      @import url("https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap");

      * {
        box-sizing: border-box;
      }

      body {
        font-family: "Cairo", sans-serif;
        background: linear-gradient(135deg, #0f172a, #020617);
        color: #fff;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        padding-top: 70px;
      }
          body.no-scroll { overflow: hidden; }
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #1e293b; border-radius: 8px; }
    ::-webkit-scrollbar-thumb { background: #3b82f6; border-radius: 8px; transition: background 0.2s; }
    ::-webkit-scrollbar-thumb:hover { background: #60a5fa; }

      .top-nav {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        background: rgba(15, 23, 42, 0.92);
        backdrop-filter: blur(12px);
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
        padding: 10px 20px;
        z-index: 1000;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.5);
        border-bottom: 1px solid rgba(59, 130, 246, 0.3);
      }

      .nav-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        background: transparent;
        border: none;
        color: #e2e8f0;
        font-family: "Cairo", sans-serif;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        padding: 8px 16px;
        border-radius: 10px;
        transition: background 0.3s, color 0.3s, transform 0.2s;
      }

      .nav-btn svg {
        width: 24px;
        height: 24px;
        fill: currentColor;
        transition: transform 0.25s, fill 0.25s;
      }

      .nav-btn:hover {
        background: rgba(59, 130, 246, 0.2);
        color: #60a5fa;
        transform: translateY(-2px);
      }

      .nav-btn:hover svg {
        transform: scale(1.1);
      }

      .nav-btn:active {
        transform: scale(0.96);
      }

      .container {
        max-width: 700px;
        margin: auto;
        padding: 40px 20px;
        flex: 1;
      }

      .logo-wrapper {
        text-align: center;
        margin-bottom: 20px;
      }

      .logo-img {
        width: 80px;
        height: auto;
        border-radius: 24px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3), 0 0 0 2px rgba(59, 130, 246, 0.2);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        background: #1e293b;
        padding: 4px;
        cursor: pointer;
      }

      .logo-img:hover {
        transform: scale(1.05);
        box-shadow: 0 12px 25px rgba(59, 130, 246, 0.4), 0 0 0 2px #3b82f6;
      }

      h1 {
        text-align: center;
        margin-bottom: 20px;
        font-weight: 600;
      }

      .counter {
        text-align: center;
        margin: 0 auto 20px auto;
        color: #94a3b8;
        font-size: 14px;
        background: rgba(30, 41, 59, 0.5);
        padding: 4px 16px;
        border-radius: 20px;
        backdrop-filter: blur(4px);
        width: fit-content;
        display: block;
      }

      .search-box {
        position: relative;
      }
      .search-box input {
        width: 100%;
        padding: 15px 20px 15px 50px;
        border-radius: 15px;
        border: none;
        outline: none;
        font-size: 16px;
        background: #1e293b;
        color: white;
        transition: 0.3s;
      }
      .search-box input:focus {
        box-shadow: 0 0 15px #3b82f6;
      }

      .search-box .clear-btn {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 22px;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 50%;
        line-height: 1;
        display: none;
        transition: background 0.2s, color 0.2s;
      }
      .search-box .clear-btn:hover {
        color: #f87171;
        background: rgba(255, 255, 255, 0.1);
      }

      .history {
        margin-top: 12px;
        background: rgba(30, 41, 59, 0.6);
        border-radius: 12px;
        padding: 10px;
        display: none;
      }
      .history-label {
        font-size: 13px;
        color: #94a3b8;
        margin-bottom: 8px;
        padding: 0 8px;
      }
      .history-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 8px;
      }
      .history-item {
        background: #0f172a;
        border-radius: 8px;
        padding: 4px 10px;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: background 0.2s;
      }
      .history-item:hover {
        background: #1e3a5f;
      }
      .history-text {
        color: #e2e8f0;
        font-size: 14px;
        white-space: normal;
        word-break: break-word;
      }
      .history-delete {
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 16px;
        cursor: pointer;
        padding: 0;
        line-height: 1;
        transition: color 0.2s;
        flex-shrink: 0;
      }
      .history-delete:hover {
        color: #f87171;
      }
      .history-clear {
        display: inline-block;
        width: auto;
        color: #f59e0b;
        font-size: 13px;
        cursor: pointer;
        padding: 4px 12px;
        border-radius: 6px;
        transition: background 0.2s;
      }
      .history-clear:hover {
        background: rgba(245, 158, 11, 0.1);
      }

      .results {
        margin-top: 30px;
      }

      .card {
        background: rgba(30, 41, 59, 0.8);
        padding: 20px;
        border-radius: 20px;
        margin-bottom: 20px;
        animation: fadeIn 0.4s ease;
        transition: 0.3s;
      }
      .card:hover {
        transform: translateY(-5px) scale(1.01);
        box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
      }

      .name {
        font-size: 18px;
        font-weight: 600;
      }
      .number {
        color: #94a3b8;
        margin: 5px 0;
      }

      .course-item {
        background: #0f172a;
        border-radius: 12px;
        padding: 12px;
        margin: 10px 0;
        border-right: 4px solid #3b82f6;
      }
      .course-item.upcoming {
        border-right-color: #ef4444;
        box-shadow: 0 0 12px rgba(239, 68, 68, 0.4);
        animation: pulse-red 2s infinite;
      }

      @keyframes pulse-red {
        0% { box-shadow: 0 0 12px rgba(239, 68, 68, 0.4); }
        50% { box-shadow: 0 0 24px rgba(239, 68, 68, 0.8); }
        100% { box-shadow: 0 0 12px rgba(239, 68, 68, 0.4); }
      }

      .course-name {
        font-weight: 600;
        margin-bottom: 6px;
      }
      .course-topline {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 6px;
      }
      .course-topline .course-name {
        flex: 1;
        min-width: 0;
        margin-bottom: 0;
      }
      .course-actions,
      .pinned-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
        flex-wrap: wrap;
        justify-content: flex-start;
      }
      .course-action-btn {
        background: rgba(59, 130, 246, 0.18);
        border: 1px solid rgba(96, 165, 250, 0.45);
        color: #93c5fd;
        font-family: "Cairo", sans-serif;
        font-size: 12px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 999px;
        cursor: pointer;
        transition: background 0.2s, color 0.2s, border-color 0.2s, transform 0.2s;
        white-space: nowrap;
      }
      .course-action-btn:hover {
        background: rgba(59, 130, 246, 0.35);
        color: #dbeafe;
        transform: translateY(-1px);
      }
      .course-action-btn.pin-course-btn {
        background: rgba(245, 158, 11, 0.16);
        border-color: rgba(251, 191, 36, 0.45);
        color: #fcd34d;
      }
      .course-action-btn.pin-course-btn.is-pinned,
      .course-action-btn.pinned-remove-btn {
        background: rgba(16, 185, 129, 0.18);
        border-color: rgba(52, 211, 153, 0.5);
        color: #86efac;
      }
      .exam-details {
        font-size: 13px;
        color: #cbd5e1;
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
      }
      .exam-details span {
        white-space: nowrap;
      }
      .location-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 26px;
        padding: 2px 10px;
        border: 1px solid rgba(96, 165, 250, 0.55);
        border-radius: 999px;
        background: rgba(59, 130, 246, 0.16);
        color: #bfdbfe;
        font-weight: 700;
        line-height: 1.4;
        text-decoration: none;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.18);
        transition: background 0.2s, border-color 0.2s, color 0.2s, transform 0.2s;
      }
      .location-link:hover,
      .location-link:focus-visible {
        background: rgba(59, 130, 246, 0.32);
        border-color: rgba(147, 197, 253, 0.85);
        color: #eff6ff;
        transform: translateY(-1px);
        outline: none;
      }
      .location-link:active {
        transform: translateY(0);
      }
      .no-exam {
        color: #f59e0b;
        font-style: italic;
      }
      .no-result {
        text-align: center;
        color: #94a3b8;
        margin-top: 20px;
      }
      .remaining-time {
        font-weight: 600;
        margin-top: 4px;
        display: block;
      }
      .pinned-course {
        margin-top: 16px;
        display: none;
      }
      .pinned-course-label {
        color: #fcd34d;
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 8px;
        padding: 0 8px;
      }
      .pinned-course-card {
        background: rgba(30, 41, 59, 0.85);
        border-radius: 16px;
        padding: 16px;
        border: 1px solid rgba(251, 191, 36, 0.28);
        border-right: 5px solid #f59e0b;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.22);
      }
      .pinned-course-card.pinned-upcoming {
        border-color: rgba(251, 191, 36, 0.7);
        border-right-color: #fbbf24;
        box-shadow: 0 0 14px rgba(251, 191, 36, 0.45), 0 14px 34px rgba(0, 0, 0, 0.28);
        animation: pulse-yellow 2s infinite;
      }
      @keyframes pulse-yellow {
        0% { box-shadow: 0 0 14px rgba(251, 191, 36, 0.4), 0 14px 34px rgba(0, 0, 0, 0.28); }
        50% { box-shadow: 0 0 26px rgba(251, 191, 36, 0.75), 0 14px 34px rgba(0, 0, 0, 0.28); }
        100% { box-shadow: 0 0 14px rgba(251, 191, 36, 0.4), 0 14px 34px rgba(0, 0, 0, 0.28); }
      }
      .pinned-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 10px;
      }
      .pinned-title {
        font-size: 17px;
        font-weight: 700;
        color: #f8fafc;
      }
      .pinned-meta {
        color: #94a3b8;
        font-size: 13px;
        margin-top: 4px;
      }
      .toast {
        position: fixed;
        bottom: 28px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(15, 23, 42, 0.96);
        color: #e2e8f0;
        border: 1px solid rgba(96, 165, 250, 0.45);
        padding: 10px 18px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 600;
        z-index: 12000;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s, transform 0.25s;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.35);
      }
      .toast.show {
        opacity: 1;
        transform: translateX(-50%) translateY(-4px);
      }

      /* أنماط حالة الامتحان */
      .status {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        margin-top: 4px;
      }
      .status.ended {
        background: rgba(16, 185, 129, 0.15);
        color: #6ee7b7;
        border: 1px solid rgba(16, 185, 129, 0.4);
      }
      .status.live {
        background: rgba(239, 68, 68, 0.2);
        color: #fca5a5;
        border: 1px solid rgba(239, 68, 68, 0.5);
        animation: pulse-live 1.5s infinite;
      }
      .status.upcoming {
        background: rgba(245, 158, 11, 0.2);
        color: #fcd34d;
        border: 1px solid rgba(245, 158, 11, 0.5);
      }

      @keyframes pulse-live {
        0% { background: rgba(239, 68, 68, 0.2); }
        50% { background: rgba(239, 68, 68, 0.4); }
        100% { background: rgba(239, 68, 68, 0.2); }
      }

      .export-btn-container {
        display: none;
        text-align: center;
        margin: 20px 0;
      }
      .export-btn {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 50px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        display: inline-flex;
        align-items: center;
        gap: 8px;
      }
      .export-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(59, 130, 246, 0.6);
      }

      #export-card {
        position: absolute;
        left: -9999px;
        top: -9999px;
        width: 600px;
        padding: 30px;
        background: linear-gradient(145deg, #0b1120 0%, #1a1f2f 100%);
        border-radius: 24px;
        color: #e2e8f0;
        font-family: "Cairo", sans-serif;
        direction: rtl;
        border: 2px solid rgba(59, 130, 246, 0.4);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
      }
      #export-card .export-header {
        text-align: center;
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 2px solid rgba(59, 130, 246, 0.3);
      }
      #export-card .export-name {
        font-size: 26px;
        font-weight: 700;
        background: linear-gradient(to left, #60a5fa, #a78bfa);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 5px;
      }
      #export-card .export-number {
        font-size: 16px;
        color: #94a3b8;
      }
      #export-card .export-course {
        background: rgba(15, 23, 42, 0.8);
        border-radius: 14px;
        padding: 15px;
        margin: 12px 0;
        border-right: 5px solid #3b82f6;
        backdrop-filter: blur(10px);
      }
      #export-card .export-course-name {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 8px;
        color: #f1f5f9;
        line-height: 1.7;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
      }
      #export-card .export-course-title {
        direction: rtl;
        unicode-bidi: isolate;
      }
      #export-card .export-course-code {
        direction: ltr;
        unicode-bidi: isolate;
        color: #93c5fd;
        font-size: 16px;
      }
      #export-card .export-exam-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        font-size: 14px;
        color: #cbd5e1;
      }
      #export-card .export-exam-row span {
        background: rgba(59, 130, 246, 0.15);
        padding: 4px 12px;
        border-radius: 20px;
        white-space: nowrap;
      }
      #export-card .location-link {
        min-height: 24px;
        padding: 1px 9px;
        background: rgba(59, 130, 246, 0.2);
        box-shadow: none;
      }
      #export-card .no-exam-export {
        color: #f59e0b;
        font-style: italic;
        font-size: 13px;
        margin-top: 5px;
      }
      #export-card .watermark {
        text-align: center;
        margin-top: 20px;
        font-size: 12px;
        color: #475569;
      }
      @media (max-width: 600px) {
        body {
          padding-top: 64px;
        }
        .top-nav {
          display: grid;
          grid-template-columns: repeat(4, minmax(0, 1fr));
          gap: 4px;
          padding: 7px 5px;
        }
        .nav-btn {
          justify-content: center;
          gap: 4px;
          min-width: 0;
          padding: 7px 3px;
          font-size: 11px;
          white-space: nowrap;
        }
        .nav-btn svg {
          width: 18px;
          height: 18px;
        }
        .course-topline,
        .pinned-header {
          flex-direction: column;
          align-items: stretch;
        }
        .course-actions,
        .pinned-actions {
          width: 100%;
        }
        .course-action-btn {
          flex: 1;
          text-align: center;
        }
      }

      footer {
        text-align: center;
        padding: 15px;
        background: rgba(15, 23, 42, 0.9);
        color: #94a3b8;
        font-size: 14px;
        border-top: 1px solid rgba(148, 163, 184, 0.2);
        backdrop-filter: blur(10px);
      }

      footer a {
        color: #60a5fa;
        text-decoration: none;
        margin: 0 5px;
        transition: color 0.2s;
      }
      footer a:hover {
        color: #93c5fd;
        text-decoration: underline;
      }

      @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
      }

      input:-webkit-autofill,
      input:-webkit-autofill:hover,
      input:-webkit-autofill:focus,
      input:-webkit-autofill:active {
        -webkit-box-shadow: 0 0 0 30px #1e293b inset !important;
        -webkit-text-fill-color: white !important;
        caret-color: white !important;
        border: none !important;
        transition: background-color 5000s ease-in-out 0s;
      }
    </style>

<!-- with new label  -->
  <style> 
    .nav-btn.new-feature{
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .btn-icon-box{
      position: relative;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    
    .new-label{
      position: absolute;
      top: -12px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 10px;
      font-weight: 700;
      color: #ef4444;
      line-height: 1;
      white-space: nowrap;
      pointer-events: none;
    }
    
    .btn-icon{
      display: block;
    }
    
    .btn-text{
      white-space: nowrap;
    }
  </style>

  </head>

  <body>
    <nav class="top-nav">
      <button class="nav-btn" onclick="goCourses()">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H8V4h12v12zM10 9h8v2h-8V9zm0 4h6v2h-6v-2z"/>
        </svg>
        <span>المقررات</span>
      </button>

<!-- with new label -->
      <button class="nav-btn new-feature" onclick="goQA()">
          <div class="btn-icon-box">
            <small class="new-label">جديد</small>
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="btn-icon">
          <path d="M11 18h2v-2h-2v2zm1-16C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-2.21 0-4 1.79-4 4h2c0-1.1.9-2 2-2s2 .9 2 2c0 2-3 1.75-3 5h2c0-2.25 3-2.5 3-5 0-2.21-1.79-4-4-4z"/>
        </svg>
         </div>
        <span class="btn-text">أسئلة وروابط</span>
      </button>

      <button class="nav-btn new-feature" onclick="goGPA()">
        <div class="btn-icon-box">
            <small class="new-label">جديد</small>
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="btn-icon">
          <path d="M7 2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm0 4h10V4H7v2zm2 4H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm-8 4H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v6h2v-6zm-8 4H7v2h2v-2zm4 0h-2v2h2v-2z"/>
        </svg>
         </div>
        <span class="btn-text">حساب GPA</span>
      </button>

<!-- without new label -->
      <!-- <button class="nav-btn" onclick="goQA()">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M11 18h2v-2h-2v2zm1-16C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-2.21 0-4 1.79-4 4h2c0-1.1.9-2 2-2s2 .9 2 2c0 2-3 1.75-3 5h2c0-2.25 3-2.5 3-5 0-2.21-1.79-4-4-4z"/>
        </svg>
        <span>أسئلة وروابط</span>
      </button>

      <button class="nav-btn" onclick="goGPA()">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M7 2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm0 4h10V4H7v2zm2 4H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm-8 4H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v6h2v-6zm-8 4H7v2h2v-2zm4 0h-2v2h2v-2z"/>
        </svg>
        <span>حساب GPA</span>
      </button> -->
<!--  -->
 
      <button class="nav-btn" onclick="refreshPage()">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 6v3l4-4-4-4v3c-4.42 0-8 3.58-8 8 0 1.57.46 3.03 1.24 4.26L6.7 14.8c-.45-.83-.7-1.79-.7-2.8 0-3.31 2.69-6 6-6zm6.76 1.74L17.3 9.2c.45.83.7 1.79.7 2.8 0 3.31-2.69 6-6 6v-3l-4 4 4 4v-3c4.42 0 8-3.58 8-8 0-1.57-.46-3.03-1.24-4.26z"/>
        </svg>
        <span>تحديث</span>
      </button>
    </nav>

    <div class="container">
      <div class="logo-wrapper">
        <a href="https://www.facebook.com/profile.php?id=61569852016021" target="_blank" rel="noopener noreferrer">
          <img src="images/LogoFCI.jpeg" alt="شعار الكلية" class="logo-img" />
        </a>
      </div>

      <h1>الاستعلام عن المقررات الدراسية</h1>

      <div class="counter">عدد الزوار: <span id="visitCount">...</span></div>

      <div class="search-box">
        <input type="text" id="search" placeholder="اكتب الاسم أو الرقم الأكاديمي..." autocomplete="on" />
        <button class="clear-btn" id="clearSearchBtn" title="مسح البحث">&times;</button>
      </div>

      <div class="history" id="history">
        <div class="history-label">سجل البحث:</div>
        <div class="history-list" id="history-list"></div>
        <div class="history-clear" id="clear-history">مسح السجل</div>
      </div>

      <div class="pinned-course" id="pinned-course"></div>

      <div class="export-btn-container" id="export-container">
        <button class="export-btn" onclick="exportAsImage()">تحميل صورة المواد</button>
      </div>

      <div class="results" id="results"></div>
    </div>

    <div id="export-card"></div>
    <div class="toast" id="toast"></div>

    <footer>
      StudentsCourses 2026 &middot; Developed by Ali Ashraf &middot;
      <a href="http://wa.me/+201148727448" target="_blank">ContactMe</a>
    </footer>

    <script>
      // ══════════════════════════════════════════════════
      //  الإعدادات
      // ══════════════════════════════════════════════════
      const DEFAULT_EXAM_DURATION_HOURS = 2;

      // ════════ محاكاة الوقت للتجارب (احذف هذا القسم بالكامل بعد الاختبار) ════════
      const SIMULATION_ENABLED = false; // ⚠️ سطر محاكاة - احذف بعد الاختبار
      // const SIMULATION_START = new Date("2026-06-29T9:29:00"); // ⚠️ سطر محاكاة - احذف بعد الاختبار (حدد وقت البداية هنا)
      const SIMULATION_START = new Date("2026-06-01T13:59:50"); // ⚠️ سطر محاكاة - احذف بعد الاختبار (حدد وقت البداية هنا)
      const SIMULATION_PAGE_LOAD_REAL_TIME = Date.now(); // ⚠️ سطر محاكاة - احذف بعد الاختبار
      function getSimulatedNow() { // ⚠️ دالة محاكاة - احذف بعد الاختبار
          if (!SIMULATION_ENABLED) return new Date();
          const elapsed = Date.now() - SIMULATION_PAGE_LOAD_REAL_TIME; // ⚠️ سطر محاكاة - احذف بعد الاختبار
          return new Date(SIMULATION_START.getTime() + elapsed); // ⚠️ سطر محاكاة - احذف بعد الاختبار
      }
      // ════════ نهاية قسم المحاكاة ════════

      function refreshPage() {
        location.reload(true);
      }

      let suppressBlurCommit = false;
      const MIN_QUERY_LENGTH = 3;
      const LIVE_DEBOUNCE_MS = 450;
      const AUTO_COMMIT_MS = 1500;
      const MAX_RESULTS = 15;

      const HISTORY_KEY = "search_history";
      const MAX_HISTORY = 10;
      const PINNED_COURSE_KEY = "pinned_course_card_v1";

      function goCourses() {
        fetch("/counterFiles/counter?action=increment&counter=course", {
          method: "POST",
          keepalive: true,
          cache: "no-store"
        })
          .catch(() => {})
          .finally(() => {
            window.location.href = "/courses";
          });
      }

      function goQA() {
        fetch("/counterFiles/counter?action=increment&counter=qa", {
          method: "POST",
          keepalive: true,
          cache: "no-store"
        })
          .catch(() => {})
          .finally(() => {
            window.location.href = "/qa";
          });
      }

      function goGPA() {
        fetch("/counterFiles/counter?action=increment&counter=gpa", {
          method: "POST",
          keepalive: true,
          cache: "no-store"
        })
          .catch(() => {})
          .finally(() => {
            window.location.href = "/gpa";
          });
      }

      function loadHistory() {
        const raw = localStorage.getItem(HISTORY_KEY);
        return raw ? JSON.parse(raw) : [];
      }
      function saveHistory(h) {
        localStorage.setItem(HISTORY_KEY, JSON.stringify(h));
      }
      function addToHistory(query) {
        if (!query) return;
        let h = loadHistory();
        h = h.filter((item) => !query.startsWith(item));
        const isPrefixOfExisting = h.some((item) => item.startsWith(query));
        if (isPrefixOfExisting) {
          saveHistory(h);
          renderHistory();
          return;
        }
        h = h.filter((item) => item !== query);
        h.unshift(query);
        if (h.length > MAX_HISTORY) h.pop();
        saveHistory(h);
        renderHistory();
      }
      function deleteHistoryItem(query) {
        saveHistory(loadHistory().filter((item) => item !== query));
        renderHistory();
      }
      function clearHistory() {
        localStorage.removeItem(HISTORY_KEY);
        renderHistory();
      }

      function renderHistory() {
        const listEl = document.getElementById("history-list");
        const historyDiv = document.getElementById("history");
        const h = loadHistory();
        if (h.length === 0) {
          historyDiv.style.display = "none";
          return;
        }
        historyDiv.style.display = "block";
        listEl.innerHTML = h
          .map(
            (item) => `
          <div class="history-item">
            <span class="history-text" data-query="${item}">${item}</span>
            <button class="history-delete" data-query="${item}">&times;</button>
          </div>`
          )
          .join("");
        listEl.querySelectorAll(".history-text").forEach((el) => {
          el.addEventListener("click", function () {
            const q = this.dataset.query;
            searchInput.value = q;
            lastPolledValue = q;
            clearBtn.style.display = "block";
            doCommitSearch(q);
          });
        });
        listEl.querySelectorAll(".history-delete").forEach((btn) => {
          btn.addEventListener("click", function (e) {
            e.stopPropagation();
            deleteHistoryItem(this.dataset.query);
          });
        });
      }
      document.addEventListener("click", (e) => {
        if (e.target && e.target.id === "clear-history") clearHistory();
      });

      function getClientId() {
        let id = localStorage.getItem("client_id");
        if (!id) {
          id = "client_" + Math.random().toString(36).substr(2, 9) + Date.now();
          localStorage.setItem("client_id", id);
        }
        return id;
      }
      const CLIENT_ID = getClientId();

      const searchInput = document.getElementById("search");
      const clearBtn = document.getElementById("clearSearchBtn");
      const resultsDiv = document.getElementById("results");
      const exportContainer = document.getElementById("export-container");
      const pinnedCourseDiv = document.getElementById("pinned-course");
      const toast = document.getElementById("toast");

      let debounceTimer = null;
      let autoCommitTimer = null;
      let committedQuery = "";
      let lastPolledValue = "";
      let currentStudentData = null;
      let commitBlocked = false;
      let activeFetchCtrl = null;
      let remainingTimer = null;

      function loadCounter() {
        fetch("/counterFiles/counter?counter=users", { cache: "no-store" })
          .then((r) => r.json())
          .then((d) => {
            const el = document.getElementById("visitCount");
            if (el) el.innerText = d.count ?? 0;
          })
          .catch(() => {
            const el = document.getElementById("visitCount");
            if (el) el.innerText = "--";
          });
      }
      loadCounter();
      setInterval(loadCounter, 4000);

      function toggleClearButton() {
        clearBtn.style.display = searchInput.value.trim() ? "block" : "none";
      }

      function escapeHTML(value) {
        const map = { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" };
        return String(value ?? "").replace(/[&<>"']/g, (char) => map[char]);
      }

      function formatExamRoomHtml(room) {
        if (String(room || "").trim() === "اضغط هنا") {
          return `<a class="location-link" href="images/location.jpg" target="_blank" rel="noopener noreferrer">اضغط هنا</a>`;
        }
        return escapeHTML(room);
      }

      function formatExamRoomText(room) {
        if (String(room || "").trim() === "اضغط هنا") {
          return "اضغط هنا: images/location.jpg";
        }
        return room || "-";
      }

      function formatCourseDisplayName(name) {
        return String(name || "")
          .replace(/المشروع\s*([0-9٠-٩]+)/g, "المشروع $1")
          .trim();
      }

      function formatCourseCodeDisplay(code) {
        return String(code || "").replace(/[.\s]+$/g, "").trim();
      }

      function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add("show");
        clearTimeout(toast._timer);
        toast._timer = setTimeout(() => toast.classList.remove("show"), 2600);
      }

      function buildCourseExamHtml(course) {
        if (!course.exam) return `<div class="no-exam">لم تحدد اللجنة بعد</div>`;

        const range = parseExamTimeRange(course.exam.date, course.exam.time, course.exam.period);
        let statusText = "وقت الامتحان غير معروف";
        let startISO = "";
        let endISO = "";

        if (range) {
          statusText = getExamStatusText(range.start, range.end);
          startISO = range.start.toISOString();
          endISO = range.end.toISOString();
        }

        return `
          <div class="exam-details">
            <span>اللجنة: ${escapeHTML(course.exam.committee)}</span>
            <span>المكان: ${formatExamRoomHtml(course.exam.room)}</span>
            <span>التاريخ: ${escapeHTML(course.exam.day)} ${escapeHTML(course.exam.date)}</span>
            <span>الوقت: ${escapeHTML(course.exam.period)} (${escapeHTML(course.exam.time)})</span>
          </div>
          <div class="remaining-time" data-exam-start="${startISO}" data-exam-end="${endISO}">
            ${statusText}
          </div>`;
      }

      function getExamPlainStatus(course) {
        if (!course.exam) return "لم تحدد اللجنة بعد";
        const range = parseExamTimeRange(course.exam.date, course.exam.time, course.exam.period);
        if (!range) return "وقت الامتحان غير معروف";
        const now = getSimulatedNow();
        if (now >= range.end) return "منتهي";
        if (now >= range.start && now < range.end) {
          return `الامتحان الآن - ${formatTimeRemaining(range.end - now).text}`;
        }
        return formatTimeRemaining(range.start - now).text;
      }

      function buildCourseSharePayload(student, course) {
        const title = `${course.name || "مادة"}`;
        const lines = [
          student?.name ? `الطالب: ${student.name}` : "",
          `المادة: ${title}`,
        ];

        if (course.exam) {
          lines.push(
            `التاريخ: ${course.exam.day || ""} ${course.exam.date || ""}`.trim(),
            `الوقت: ${course.exam.period || ""} (${course.exam.time || ""})`.trim(),
            `المكان: لجنة ${course.exam.committee || "-"} ${formatExamRoomText(course.exam.room)}`,
          );
        } else {
          lines.push("لم تحدد اللجنة بعد");
        }

        return { title, text: lines.filter(Boolean).join("\n") };
      }

      async function shareCourse(student, course) {
        if (!course) return;
        const payload = buildCourseSharePayload(student, course);

        try {
          if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(payload.text);
          } else {
            const textarea = document.createElement("textarea");
            textarea.value = payload.text;
            textarea.setAttribute("readonly", "");
            textarea.style.position = "fixed";
            textarea.style.top = "-9999px";
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand("copy");
            textarea.remove();
          }
          showToast("تم نسخ نص المادة");
        } catch (err) {
          console.error("Copy failed:", err);
          showToast("تعذر نسخ النص");
        }
      }

      function getCoursePinId(student, course) {
        return `${student?.number || "student"}__${course?.code || "course"}`;
      }

      function normalizePinnedCourse(course) {
        return {
          name: course.name || "",
          code: course.code || "",
          driveLink: course.driveLink || "",
          exam: course.exam ? {
            committee: course.exam.committee || "",
            room: course.exam.room || "",
            day: course.exam.day || "",
            date: course.exam.date || "",
            period: course.exam.period || "",
            time: course.exam.time || "",
          } : null,
        };
      }

      function normalizePinnedCourses(courses) {
        return (courses || []).map((course) => normalizePinnedCourse(course));
      }

      function getUpcomingCourseFromCourses(courses) {
        const now = getSimulatedNow();
        const entries = (courses || [])
          .filter((course) => course.exam)
          .map((course) => {
            const range = parseExamTimeRange(course.exam.date, course.exam.time, course.exam.period);
            if (!range) return null;
            return { course, start: range.start, end: range.end };
          })
          .filter(Boolean);

        const activeExam = entries.find((entry) => now >= entry.start && now < entry.end);
        const futureExam = entries.filter((entry) => entry.start > now).sort((a, b) => a.start - b.start)[0];
        return (activeExam || futureExam || null)?.course || null;
      }

      function isPinnedCourseUpcoming(pinned) {
        const course = pinned?.course;
        const courses = pinned?.courses || [];
        if (!course || !courses.length) return false;
        const upcomingCourse = getUpcomingCourseFromCourses(courses);
        return !!upcomingCourse && upcomingCourse.code === course.code;
      }

      function updatePinnedUpcomingVisual() {
        if (!pinnedCourseDiv) return;
        const card = pinnedCourseDiv.querySelector(".pinned-course-card");
        if (!card) return;

        const isUpcomingPinned = isPinnedCourseUpcoming(loadPinnedCourse());
        card.classList.toggle("pinned-upcoming", isUpcomingPinned);

      }

      function loadPinnedCourse() {
        try {
          const raw = localStorage.getItem(PINNED_COURSE_KEY);
          return raw ? JSON.parse(raw) : null;
        } catch (_) {
          return null;
        }
      }

      function isPinnedCourse(student, course) {
        const pinned = loadPinnedCourse();
        return !!pinned && pinned.id === getCoursePinId(student, course);
      }

      function updatePinButtons() {
        document.querySelectorAll(".pin-course-btn").forEach((btn) => {
          const card = btn.closest(".card");
          const courseItem = btn.closest(".course-item");
          const courseIndex = Number(courseItem?.dataset.courseIndex);
          const student = card?.__studentData;
          const course = student?.courses?.[courseIndex];
          const pinned = student && course && isPinnedCourse(student, course);
          btn.classList.toggle("is-pinned", !!pinned);
          btn.textContent = pinned ? "مثبت" : "تثبيت";
          btn.title = pinned ? "إلغاء تثبيت المادة" : "تثبيت المادة";
        });
      }

      function renderPinnedCourse() {
        const pinned = loadPinnedCourse();
        if (!pinned || !pinnedCourseDiv) {
          if (pinnedCourseDiv) {
            pinnedCourseDiv.style.display = "none";
            pinnedCourseDiv.innerHTML = "";
          }
          return;
        }

        const student = pinned.student || {};
        const course = pinned.course || {};
        const isUpcomingPinned = isPinnedCourseUpcoming(pinned);
        const courseTitle = course.driveLink
          ? `<a href="${escapeHTML(course.driveLink)}" target="_blank" style="color:#60a5fa;text-decoration:none;">${escapeHTML(course.name)} (${escapeHTML(course.code)})</a>`
          : `${escapeHTML(course.name)} (${escapeHTML(course.code)})`;

        pinnedCourseDiv.style.display = "block";
        pinnedCourseDiv.innerHTML = `
          <div class="pinned-course-label">المادة المثبتة</div>
          <div class="pinned-course-card${isUpcomingPinned ? " pinned-upcoming" : ""}">
            <div class="pinned-header">
              <div>
                <div class="pinned-title">${courseTitle}</div>
                <div class="pinned-meta">${escapeHTML(student.name || "")}${student.number ? ` - الرقم: ${escapeHTML(student.number)}` : ""}</div>
              </div>
              <div class="pinned-actions">
                <button type="button" class="course-action-btn pinned-share-btn">نسخ</button>
                <button type="button" class="course-action-btn pinned-remove-btn">إلغاء التثبيت</button>
              </div>
            </div>
            ${buildCourseExamHtml(course)}
          </div>`;

        pinnedCourseDiv.querySelector(".pinned-share-btn")?.addEventListener("click", () => shareCourse(student, course));
        pinnedCourseDiv.querySelector(".pinned-remove-btn")?.addEventListener("click", () => {
          localStorage.removeItem(PINNED_COURSE_KEY);
          renderPinnedCourse();
          updatePinButtons();
          showToast("تم إلغاء تثبيت المادة");
        });
        startRemainingUpdates();
      }

      function toggleCoursePin(student, course) {
        if (isPinnedCourse(student, course)) {
          localStorage.removeItem(PINNED_COURSE_KEY);
          renderPinnedCourse();
          updatePinButtons();
          showToast("تم إلغاء تثبيت المادة");
          return;
        }

        const payload = {
          id: getCoursePinId(student, course),
          savedAt: Date.now(),
          student: { name: student?.name || "", number: student?.number || "" },
          course: normalizePinnedCourse(course),
          courses: normalizePinnedCourses(student?.courses || []),
        };
        localStorage.setItem(PINNED_COURSE_KEY, JSON.stringify(payload));
        renderPinnedCourse();
        updatePinButtons();
        showToast("تم تثبيت المادة");
      }

      function restartPinnedTimerIfNeeded() {
        if (loadPinnedCourse()) startRemainingUpdates();
      }

      clearBtn.addEventListener("mousedown", () => {
        suppressBlurCommit = true;
      });
      clearBtn.addEventListener("click", () => {
        searchInput.value = "";
        toggleClearButton();
        lastPolledValue = "";
        committedQuery = "";
        resultsDiv.innerHTML = "";
        exportContainer.style.display = "none";
        currentStudentData = null;
        clearTimeout(remainingTimer);
        clearTimeout(debounceTimer);
        clearTimeout(autoCommitTimer);
        if (activeFetchCtrl) {
          activeFetchCtrl.abort();
          activeFetchCtrl = null;
        }
        restartPinnedTimerIfNeeded();
        searchInput.focus();
        setTimeout(() => {
          suppressBlurCommit = false;
        }, 0);
      });

      searchInput.addEventListener("input", toggleClearButton);
      toggleClearButton();

      function parseDateOnly(dateStr) {
        if (!dateStr) return null;
        const d = dateStr.trim();
        const patterns = [
          /(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/,
          /(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})/,
        ];
        for (const pat of patterns) {
          const m = d.match(pat);
          if (m) {
            let y, mo, day;
            if (pat === patterns[0]) {
              y = +m[1]; mo = +m[2]; day = +m[3];
            } else {
              day = +m[1]; mo = +m[2]; y = +m[3];
            }
            return new Date(y, mo - 1, day, 0, 0, 0);
          }
        }
        return null;
      }

      function parseExamTimeRange(dateStr, timeStr, periodStr) {
        const date = parseDateOnly(dateStr);
        if (!date) return null;

        const timeRangeRegex = /(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})/;
        const match = timeStr ? timeStr.match(timeRangeRegex) : null;

        let startH, startM, endH, endM;
        if (match) {
          startH = +match[1]; startM = +match[2]; endH = +match[3]; endM = +match[4];
        } else {
          const singleTime = timeStr ? timeStr.match(/(\d{1,2}):(\d{2})/) : null;
          if (singleTime) {
            startH = +singleTime[1]; startM = +singleTime[2];
            const endDate = new Date(date);
            endDate.setHours(startH, startM, 0, 0);
            endDate.setTime(endDate.getTime() + DEFAULT_EXAM_DURATION_HOURS * 3600000);
            const startDate = new Date(date);
            startDate.setHours(startH, startM, 0, 0);
            return { start: startDate, end: endDate };
          }
          return null;
        }

        const periodLower = (periodStr || "").toLowerCase();
        const isAM = /صباح|ص|am|الأولى/i.test(periodLower);
        const isPM = /مساء|م|pm|الثانية/i.test(periodLower);

        if (isAM) {
          if (startH === 12) startH = 0;
          if (endH === 12) endH = 0;
        } else if (isPM) {
          if (startH !== 12) startH += 12;
          if (endH !== 12) endH += 12;
        } else {
          if (endH < startH) endH += 12;
        }

        const startDate = new Date(date);
        startDate.setHours(startH, startM, 0, 0);
        const endDate = new Date(date);
        endDate.setHours(endH, endM, 0, 0);

        return { start: startDate, end: endDate };
      }

      // تنسيق الوقت المتبقي مع تحديد فترة التحديث الذكية
      function formatTimeRemaining(diffMs) {
        if (diffMs <= 0) return { text: "انتهى", interval: null };
        const seconds = Math.floor(diffMs / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);
        let text = "متبقي ";
        let interval;
        if (days > 0) {
          const remainingHours = hours % 24;
          text += `${days} ${days === 1 ? "يوم" : "أيام"}`;
          if (remainingHours > 0) {
            text += ` و ${remainingHours} ${remainingHours === 1 ? "ساعة" : "ساعات"}`;
          }
          interval = 3600000; // تحديث كل ساعة
        } else if (hours > 0) {
          const remainingMinutes = minutes % 60;
          text += `${hours} ${hours === 1 ? "ساعة" : "ساعات"}`;
          if (remainingMinutes > 0) {
            text += ` و ${remainingMinutes} ${remainingMinutes === 1 ? "دقيقة" : "دقائق"}`;
          }
          interval = 60000; // تحديث كل دقيقة
        } else if (minutes > 0) {
          const remainingSeconds = seconds % 60;
          text += `${minutes} ${minutes === 1 ? "دقيقة" : "دقائق"}`;
          if (remainingSeconds > 0) {
            text += ` و ${remainingSeconds} ${remainingSeconds === 1 ? "ثانية" : "ثواني"}`;
          }
          interval = 1000; // تحديث كل ثانية
        } else {
          text += `${seconds} ${seconds === 1 ? "ثانية" : "ثواني"}`;
          interval = 1000; // تحديث كل ثانية
        }
        return { text, interval };
      }

      function getExamStatusText(examStart, examEnd) {
        const now = getSimulatedNow(); // ⚠️ تم التعديل للمحاكاة - احذف بعد الاختبار
        if (now >= examEnd) {
          return '<span class="status ended">منتهي</span>';
        }
        if (now >= examStart && now < examEnd) {
          const diffMs = examEnd - now;
          const { text } = formatTimeRemaining(diffMs);
          return `<span class="status live">الامتحان الآن - ${text}</span>`;
        }
        const diffMs = examStart - now;
        const { text } = formatTimeRemaining(diffMs);
        return `<span class="status upcoming">${text}</span>`;
      }

      // دالة جديدة: تحديث تظليل "upcoming" لبطاقة طالب بعد انتهاء امتحان
      function refreshCardUpcoming(card) {
        const studentData = card.__studentData;
        const examEntries = card.__examEntries;
        if (!studentData || !examEntries) return;

        const now = getSimulatedNow();
        const activeExam = examEntries.find(e => now >= e.start && now < e.end);
        const futureExams = examEntries.filter(e => e.start > now).sort((a, b) => a.start - b.start);
        const newUpcoming = activeExam || futureExams[0] || null;

        // إزالة كلاس upcoming من جميع المواد
        card.querySelectorAll('.course-item').forEach(el => el.classList.remove('upcoming'));

        if (newUpcoming) {
          // البحث عن عنصر المادة المطابق وإضافة التظليل
          const courseItems = card.querySelectorAll('.course-item');
          courseItems.forEach(item => {
            const codeEl = item.querySelector('.course-name');
            if (codeEl) {
              const codeText = codeEl.textContent || codeEl.innerText;
              if (codeText.includes(newUpcoming.course.code)) {
                item.classList.add('upcoming');
              }
            }
          });
        }
      }

      function updateAllRemainingTimes() {
        clearTimeout(remainingTimer);
        const elements = document.querySelectorAll(".remaining-time[data-exam-start]");
        const now = getSimulatedNow().getTime();
        let nextUpdateInterval = Infinity;
        let anyChange = false;

        elements.forEach(el => {
          const startStr = el.dataset.examStart;
          const endStr = el.dataset.examEnd;
          if (!startStr || !endStr) return;
          const start = new Date(startStr);
          const end = new Date(endStr);
          if (isNaN(start) || isNaN(end)) return;

          let diffMs, statusHtml, interval;
          if (now >= end) {
            statusHtml = '<span class="status ended">منتهي</span>';
            interval = null;
          } else if (now >= start && now < end) {
            diffMs = end - now;
            const { text, interval: intv } = formatTimeRemaining(diffMs);
            statusHtml = `<span class="status live">الامتحان الآن - ${text}</span>`;
            interval = intv;
          } else {
            diffMs = start - now;
            const { text, interval: intv } = formatTimeRemaining(diffMs);
            statusHtml = `<span class="status upcoming">${text}</span>`;
            interval = intv;
          }
          el.innerHTML = statusHtml;
          if (interval && interval < nextUpdateInterval) {
            nextUpdateInterval = interval;
          }
        });

        // التحقق من الامتحانات المنتهية التي كانت مُظللة ونقل التظليل تلقائياً
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
          const upcomingItem = card.querySelector('.course-item.upcoming');
          if (upcomingItem) {
            const remainingEl = upcomingItem.querySelector('.remaining-time');
            if (remainingEl && remainingEl.innerText.includes('منتهي')) {
              refreshCardUpcoming(card);
              anyChange = true;
            }
          }
        });

        if (anyChange) {
          // بعد النقل، نُعيد جدولة التحديث لالتقاط الحالة الجديدة
          updateAllRemainingTimes();
          return;
        }

        updatePinnedUpcomingVisual();

        if (nextUpdateInterval !== Infinity && nextUpdateInterval > 0) {
          remainingTimer = setTimeout(updateAllRemainingTimes, nextUpdateInterval);
        }
      }

      function startRemainingUpdates() {
        clearTimeout(remainingTimer);
        updateAllRemainingTimes();
      }

      function scheduleAutoCommit(query) {
        clearTimeout(autoCommitTimer);
        if (!query || query === committedQuery) return;
        autoCommitTimer = setTimeout(() => {
          if (searchInput.value.trim() === query && query !== committedQuery) {
            doCommitSearch(query);
          }
        }, AUTO_COMMIT_MS);
      }

      function doLiveSearch(query) {
        if (activeFetchCtrl) {
          activeFetchCtrl.abort();
          activeFetchCtrl = null;
        }
        if (!query || query.length < MIN_QUERY_LENGTH) {
          if (query === "") {
            resultsDiv.innerHTML = "";
            exportContainer.style.display = "none";
            currentStudentData = null;
            clearTimeout(remainingTimer);
            restartPinnedTimerIfNeeded();
          }
          return;
        }
        activeFetchCtrl = new AbortController();
        fetch(`search?q=${encodeURIComponent(query)}&limit=${MAX_RESULTS}`, {
          signal: activeFetchCtrl.signal,
          cache: "no-store"
        })
          .then((r) => r.json())
          .then((data) => {
            activeFetchCtrl = null;
            renderResults(data);
            scheduleAutoCommit(query);
          })
          .catch((err) => {
            if (err.name !== "AbortError") console.error("Live search error:", err);
            activeFetchCtrl = null;
          });
      }

      function doCommitSearch(query) {
        if (!query || commitBlocked || query === committedQuery) return;
        commitBlocked = true;
        clearTimeout(autoCommitTimer);
        if (activeFetchCtrl) {
          activeFetchCtrl.abort();
          activeFetchCtrl = null;
        }
        clearTimeout(debounceTimer);
        fetch(
          `search?q=${encodeURIComponent(query)}&limit=${MAX_RESULTS}&commit=1&client_id=${encodeURIComponent(CLIENT_ID)}`,
          { cache: "no-store" }
        )
          .then((r) => r.json())
          .then((data) => {
            addToHistory(query);
            if (currentStudentData && data.results.length > 0) {
              const nf = data.results[0];
              if (nf.number === currentStudentData.number && nf.name === currentStudentData.name) {
                committedQuery = query;
                loadCounter();
                setTimeout(() => { commitBlocked = false; }, 500);
                return;
              }
            }
            renderResults(data);
            committedQuery = query;
            loadCounter();
            setTimeout(() => { commitBlocked = false; }, 500);
          })
          .catch(() => { commitBlocked = false; });
      }

      function renderResults(data) {
        resultsDiv.innerHTML = "";
        const limited = (data.results || []).slice(0, MAX_RESULTS);
        if (!limited.length) {
          resultsDiv.innerHTML = `<div class="no-result">لا يوجد نتائج</div>`;
          exportContainer.style.display = "none";
          currentStudentData = null;
          clearTimeout(remainingTimer);
          return;
        }
        currentStudentData = limited[0];
        const now = getSimulatedNow();

        const fragment = document.createDocumentFragment();

        limited.forEach((item) => {
          const studentExamEntries = (item.courses || [])
            .filter((c) => c.exam)
            .map((c) => {
              const range = parseExamTimeRange(c.exam.date, c.exam.time, c.exam.period);
              if (!range) return null;
              return { course: c, start: range.start, end: range.end };
            })
            .filter(Boolean);

          const activeExam = studentExamEntries.find((e) => now >= e.start && now < e.end);
          const futureExams = studentExamEntries.filter((e) => e.start > now).sort((a, b) => a.start - b.start);
          const upcomingExam = activeExam || futureExams[0] || null;

          const card = document.createElement("div");
          card.className = "card";
          // تخزين بيانات الطالب ومصفوفة الامتحانات على العنصر للاستخدام التلقائي لاحقاً
          card.__studentData = item;
          card.__examEntries = studentExamEntries;

          const coursesHtml = item.courses
            .map((course, courseIndex) => {
              const isUpcoming = upcomingExam && course === upcomingExam.course;
              const courseClass = "course-item" + (isUpcoming ? " upcoming" : "");
              const examHtml = buildCourseExamHtml(course);

              const titleHtml = course.driveLink
                ? `<a href="${course.driveLink}" target="_blank" style="color:#60a5fa;text-decoration:none;">${course.name} (${course.code})</a>`
                : `${course.name} (${course.code})`;
              const pinned = isPinnedCourse(item, course);

              return `
                <div class="${courseClass}" data-course-index="${courseIndex}">
                  <div class="course-topline">
                    <div class="course-name">${titleHtml}</div>
                    <div class="course-actions">
                      <button type="button" class="course-action-btn share-course-btn" title="نسخ نص المادة">نسخ</button>
                      <button type="button" class="course-action-btn pin-course-btn${pinned ? " is-pinned" : ""}" title="${pinned ? "إلغاء تثبيت المادة" : "تثبيت المادة"}">${pinned ? "مثبت" : "تثبيت"}</button>
                    </div>
                  </div>
                  ${examHtml}
                </div>`;
            })
            .join("") || `<div>لا توجد مواد مسجلة</div>`;

          card.innerHTML = `
            <div class="name">${item.name}</div>
            <div class="number">الرقم: ${item.number}</div>
            <div>عدد المواد: ${item.courses.length}</div>
            ${coursesHtml}`;
          card.addEventListener("click", (e) => {
            const shareBtn = e.target.closest(".share-course-btn");
            const pinBtn = e.target.closest(".pin-course-btn");
            if (!shareBtn && !pinBtn) return;
            e.preventDefault();
            e.stopPropagation();
            const courseItem = e.target.closest(".course-item");
            const courseIndex = Number(courseItem?.dataset.courseIndex);
            const course = item.courses?.[courseIndex];
            if (!course) return;
            if (shareBtn) shareCourse(item, course);
            if (pinBtn) toggleCoursePin(item, course);
          });
          fragment.appendChild(card);
        });

        resultsDiv.appendChild(fragment);
        updatePinButtons();
        exportContainer.style.display = "block";
        startRemainingUpdates();
      }

      searchInput.addEventListener("input", function () {
        const val = this.value.trim();
        lastPolledValue = val;
        clearBtn.style.display = val ? "block" : "none";
        clearTimeout(debounceTimer);
        clearTimeout(autoCommitTimer);
        if (val === "") {
          committedQuery = "";
          resultsDiv.innerHTML = "";
          exportContainer.style.display = "none";
          currentStudentData = null;
          clearTimeout(remainingTimer);
          if (activeFetchCtrl) {
            activeFetchCtrl.abort();
            activeFetchCtrl = null;
          }
          restartPinnedTimerIfNeeded();
          return;
        }
        debounceTimer = setTimeout(() => doLiveSearch(val), LIVE_DEBOUNCE_MS);
      });

      searchInput.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
          e.preventDefault();
          const val = this.value.trim();
          if (val && val !== committedQuery) {
            clearTimeout(debounceTimer);
            clearTimeout(autoCommitTimer);
            doCommitSearch(val);
            this.blur();
          }
        }
      });

      searchInput.addEventListener("blur", function () {
        if (suppressBlurCommit) return;
        const val = this.value.trim();
        if (val && val !== committedQuery && !commitBlocked) {
          clearTimeout(debounceTimer);
          clearTimeout(autoCommitTimer);
          doCommitSearch(val);
        }
      });

      function exportAsImage() {
        if (!currentStudentData) return;
        const exportCard = document.getElementById("export-card");
        const student = currentStudentData;
        let coursesExportHtml = "";
        student.courses.forEach((course) => {
          const courseName = escapeHTML(formatCourseDisplayName(course.name));
          const courseCode = escapeHTML(formatCourseCodeDisplay(course.code));
          let examHtml = "";
          if (course.exam) {
            examHtml = `
              <div class="export-exam-row">
                <span>اللجنة: ${course.exam.committee}</span>
                <span>المكان: ${formatExamRoomHtml(course.exam.room)}</span>
                <span>التاريخ: ${course.exam.day} ${course.exam.date}</span>
                <span>الوقت: ${course.exam.period} (${course.exam.time})</span>
              </div>`;
          } else {
            examHtml = `<div class="no-exam-export">لم تحدد اللجنة بعد</div>`;
          }
          coursesExportHtml += `
            <div class="export-course">
              <div class="export-course-name">
                <span class="export-course-title">${courseName}</span>
                <span class="export-course-code">(${courseCode})</span>
              </div>
              ${examHtml}
            </div>`;
        });
        exportCard.innerHTML = `
          <div class="export-header">
            <div class="export-name">${student.name}</div>
            <div class="export-number">الرقم الأكاديمي: ${student.number}</div>
            <div style="color:#94a3b8; margin-top:5px;">عدد المواد: ${student.courses.length}</div>
          </div>
          ${coursesExportHtml || '<div style="text-align:center; color:#94a3b8;">لا توجد مواد مسجلة</div>'}
          <div class="watermark">StudentsCourses V2 &middot; Developed by Ali Ashraf</div>`;
        html2canvas(exportCard, {
          backgroundColor: null,
          scale: 2,
          useCORS: true,
          allowTaint: true,
        })
          .then((canvas) => {
            const link = document.createElement("a");
            link.download = `student_${student.number}_courses.png`;
            link.href = canvas.toDataURL("image/png");
            link.click();
          })
          .catch((err) => {
            console.error("فشل في تصدير الصورة:", err);
            alert("حدث خطأ أثناء تصدير الصورة. حاول مرة أخرى.");
          });
      }

      renderHistory();
      renderPinnedCourse();
    </script>
    <script src="tour-guide.js?v=0"></script>
  </body>
</html>
