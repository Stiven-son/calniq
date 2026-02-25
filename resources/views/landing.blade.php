<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Calniq — Booking Widget for Service Businesses</title>
<meta name="description" content="Embeddable booking widget for home services, beauty, wellness & more. Full GA4 tracking. Currently in beta.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
<style>
  :root {
    --primary: #F59E0B;
    --primary-dark: #D97706;
    --primary-light: #FEF3C7;
    --secondary: #1E293B;
    --text: #334155;
    --text-light: #64748B;
    --bg: #FAFAF9;
    --surface: #F5F5F4;
    --accent: #FB923C;
    --success: #10B981;
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
  }

  /* Animated background */
  .bg-pattern {
    position: fixed;
    inset: 0;
    z-index: 0;
    overflow: hidden;
    pointer-events: none;
  }

  .bg-pattern::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -30%;
    width: 800px;
    height: 800px;
    background: radial-gradient(circle, rgba(245,158,11,0.08) 0%, transparent 70%);
    animation: float 20s ease-in-out infinite;
  }

  .bg-pattern::after {
    content: '';
    position: absolute;
    bottom: -40%;
    left: -20%;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(251,146,60,0.06) 0%, transparent 70%);
    animation: float 25s ease-in-out infinite reverse;
  }

  @keyframes float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(60px, -40px) scale(1.1); }
    66% { transform: translate(-30px, 30px) scale(0.95); }
  }

  /* Grid dots */
  .grid-dots {
    position: fixed;
    inset: 0;
    z-index: 0;
    background-image: radial-gradient(circle, rgba(30,41,59,0.04) 1px, transparent 1px);
    background-size: 32px 32px;
    pointer-events: none;
  }

  /* Main content */
  .container {
    position: relative;
    z-index: 1;
    max-width: 680px;
    width: 100%;
    padding: 40px 24px;
    text-align: center;
  }

  /* Logo */
  .logo-wrap {
    margin-bottom: 48px;
    animation: fadeDown 0.8s ease-out;
  }

  .logo {
    display: inline-flex;
    align-items: center;
    gap: 12px;
  }

  .logo-icon {
    width: 48px;
    height: 48px;
    background: var(--primary);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 16px rgba(245,158,11,0.3);
    position: relative;
    overflow: hidden;
  }

  .logo-icon::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 50%);
  }

  .logo-icon svg {
    width: 26px;
    height: 26px;
    fill: white;
    position: relative;
    z-index: 1;
  }

  .logo-text {
    font-family: 'DM Sans', sans-serif;
    font-size: 28px;
    font-weight: 700;
    color: var(--secondary);
    letter-spacing: -0.5px;
  }

  /* Badge */
  .beta-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 16px;
    background: var(--primary-light);
    border: 1px solid rgba(245,158,11,0.2);
    border-radius: 100px;
    font-size: 13px;
    font-weight: 600;
    color: var(--primary-dark);
    margin-bottom: 32px;
    animation: fadeDown 0.8s ease-out 0.15s both;
  }

  .beta-badge .dot {
    width: 6px;
    height: 6px;
    background: var(--success);
    border-radius: 50%;
    animation: pulse 2s ease-in-out infinite;
  }

  @keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.3); }
  }

  /* Typography */
  h1 {
    font-family: 'Instrument Serif', serif;
    font-size: clamp(40px, 7vw, 64px);
    font-weight: 400;
    color: var(--secondary);
    line-height: 1.1;
    margin-bottom: 20px;
    letter-spacing: -1px;
    animation: fadeDown 0.8s ease-out 0.3s both;
  }

  h1 em {
    font-style: italic;
    color: var(--primary);
    position: relative;
  }

  h1 em::after {
    content: '';
    position: absolute;
    bottom: 4px;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--primary);
    opacity: 0.3;
    border-radius: 2px;
  }

  .subtitle {
    font-size: 18px;
    line-height: 1.7;
    color: var(--text-light);
    max-width: 520px;
    margin: 0 auto 48px;
    animation: fadeDown 0.8s ease-out 0.45s both;
  }

  /* Features mini */
  .features-row {
    display: flex;
    justify-content: center;
    gap: 32px;
    flex-wrap: wrap;
    margin-bottom: 56px;
    animation: fadeDown 0.8s ease-out 0.6s both;
  }

  .feature-pill {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 500;
    color: var(--text);
  }

  .feature-pill .icon {
    width: 32px;
    height: 32px;
    background: var(--surface);
    border: 1px solid rgba(0,0,0,0.06);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
  }

  /* Contact card */
  .contact-card {
    background: white;
    border: 1px solid rgba(0,0,0,0.06);
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 8px 32px rgba(0,0,0,0.04);
    animation: fadeUp 0.8s ease-out 0.75s both;
    position: relative;
    overflow: hidden;
  }

  .contact-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), var(--accent));
  }

  .contact-label {
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--text-light);
    margin-bottom: 16px;
  }

  .contact-email {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-size: 20px;
    font-weight: 600;
    color: var(--secondary);
    text-decoration: none;
    padding: 12px 28px;
    background: var(--surface);
    border-radius: 12px;
    transition: all 0.3s ease;
    border: 1px solid transparent;
  }

  .contact-email:hover {
    background: var(--primary-light);
    border-color: rgba(245,158,11,0.2);
    color: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(245,158,11,0.15);
  }

  .contact-email svg {
    width: 20px;
    height: 20px;
    opacity: 0.5;
  }

  .contact-note {
    margin-top: 20px;
    font-size: 14px;
    color: var(--text-light);
    line-height: 1.6;
  }

  /* Footer */
  .footer {
    margin-top: 56px;
    font-size: 13px;
    color: var(--text-light);
    animation: fadeUp 0.8s ease-out 0.9s both;
    opacity: 0.7;
  }

  /* Animations */
  @keyframes fadeDown {
    from { opacity: 0; transform: translateY(-16px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* Mobile */
  @media (max-width: 640px) {
    .container { padding: 32px 20px; }
    .features-row { gap: 16px; }
    .feature-pill { font-size: 13px; }
    .contact-card { padding: 28px 20px; }
    .contact-email { font-size: 16px; padding: 10px 20px; }
    .features-row { flex-direction: column; align-items: center; gap: 12px; }
  }
</style>
</head>
<body>

<div class="bg-pattern"></div>
<div class="grid-dots"></div>

<main class="container">

  <div class="logo-wrap">
    <div class="logo">
      <div class="logo-icon">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M19 4h-1V3c0-.6-.4-1-1-1s-1 .4-1 1v1H8V3c0-.6-.4-1-1-1s-1 .4-1 1v1H5C3.3 4 2 5.3 2 7v12c0 1.7 1.3 3 3 3h14c1.7 0 3-1.3 3-3V7c0-1.7-1.3-3-3-3zm1 15c0 .6-.4 1-1 1H5c-.6 0-1-.4-1-1v-9h16v9zm0-11H4V7c0-.6.4-1 1-1h1v1c0 .6.4 1 1 1s1-.4 1-1V6h8v1c0 .6.4 1 1 1s1-.4 1-1V6h1c.6 0 1 .4 1 1v1z"/>
          <circle cx="8" cy="14" r="1.2"/>
          <circle cx="12" cy="14" r="1.2"/>
          <circle cx="16" cy="14" r="1.2"/>
          <circle cx="8" cy="17.5" r="1.2"/>
          <circle cx="12" cy="17.5" r="1.2"/>
        </svg>
      </div>
      <span class="logo-text">Calniq</span>
    </div>
  </div>

  <div class="beta-badge">
    <span class="dot"></span>
    Currently in Beta
  </div>

  <h1>The booking widget your <em>service business</em> needs</h1>

  <p class="subtitle">
    Embeddable booking system for home services, beauty, wellness & more. 
    Full GA4 funnel tracking. Works with your existing tools. 
    We're currently in beta — launching soon.
  </p>

  <div class="features-row">
    <div class="feature-pill">
      <span class="icon">📊</span>
      GA4 Tracking
    </div>
    <div class="feature-pill">
      <span class="icon">📅</span>
      Calendar Sync
    </div>
    <div class="feature-pill">
      <span class="icon">🔗</span>
      Webhooks
    </div>
    <div class="feature-pill">
      <span class="icon">🎨</span>
      White-label
    </div>
  </div>

  <div class="contact-card">
    <div class="contact-label">Questions or early access</div>
    <a href="mailto:contact@profiadverts.com" class="contact-email">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
      </svg>
      contact@profiadverts.com
    </a>
    <p class="contact-note">
      Interested in early access? Have questions about Calniq?<br>
      We'd love to hear from you.
    </p>
  </div>

  <div class="footer">
    &copy; 2026 Calniq. All rights reserved.
  </div>

</main>

</body>
</html>
