<?php
session_start();
require 'db.php';

$error = '';
$registered = isset($_GET['registered']);
$loginSuccessUser = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $password === '') {
        $error = 'Please enter your name and password.';
    } else {
        $stmt = $pdo->prepare(
    "SELECT id, full_name, password_hash FROM users WHERE full_name = :login OR email = :login LIMIT 1"
);
$stmt->execute(['login' => $name]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Invalid name or password.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            header("Location: Hpage.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Horizon Hotel</title>
<style>
  :root {
    --primary: #121c2b;
    --accent: #f0a050;
    --accent-light: #f8d070;
    --link-gold: #d99b26;
    --bg-color: #e2e8f0;
    --text-color: #333;
  }

  body {
    margin: 0;
    min-height: 100vh;
    font-family: 'Segoe UI', Arial, sans-serif;
    background-color: #1c130d;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
  }

  #bgLayer {
    position: fixed;
    inset: -20px;
    background-image: url("ocean1.jpg");
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    z-index: 0;
    will-change: transform;
  }

  #bgVignette {
    position: fixed;
    inset: 0;
    background: radial-gradient(
      circle at 50% 48%,
      rgba(18, 28, 43, 0.08) 0%,
      rgba(12, 16, 24, 0.48) 100%
    );
    z-index: 1;
    pointer-events: none;
  }

  #ambienceCanvas {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 2;
    pointer-events: none;
  }

  .page {
    position: relative;
    width: 100%;
    max-width: 380px;
    padding: 15px;
    box-sizing: border-box;
    z-index: 10;
  }

  .card {
    position: relative;
    width: 100%;
    box-sizing: border-box;
    padding: 65px 30px 25px;
    background: white;
    border-radius: 25px;
    text-align: center;
    box-shadow: 0 20px 45px rgba(0, 0, 0, 0.45);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }

  .card:hover {
    transform: translateY(-4px);
    box-shadow: 0 25px 55px rgba(0, 0, 0, 0.55);
  }

  .lock {
    position: absolute;
    top: -45px;
    left: 50%;
    transform: translateX(-50%);
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: linear-gradient(180deg, var(--accent), var(--accent-light));
    box-shadow: 0 10px 20px rgba(240, 160, 80, 0.4);
    transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    cursor: pointer;
  }

  .card:hover .lock {
    transform: translateX(-50%) scale(1.05) rotate(5deg);
  }

  .lock::before {
    content: "";
    position: absolute;
    left: 50%;
    top: 15px;
    width: 28px;
    height: 24px;
    box-sizing: border-box;
    border: 6px solid #000;
    border-bottom: 0;
    border-radius: 14px 14px 0 0;
    transform: translateX(-50%);
    transition: top 0.3s ease;
  }

  .lock.unlocked::before {
    top: 8px;
  }

  .lock::after {
    content: "";
    position: absolute;
    left: 50%;
    top: 37px;
    width: 40px;
    height: 30px;
    border-radius: 6px;
    background: radial-gradient(circle at 50% 45%, #f5b560 0 4px, transparent 5px), #000;
    transform: translateX(-50%);
  }

  .card h1 {
    margin: 0 0 15px;
    font-size: 28px;
    color: var(--primary);
  }

  .msg-error {
    color: #dc2626;
    font-size: 13px;
    margin-bottom: 12px;
  }

  .msg-success {
    color: #059669;
    font-size: 13px;
    margin-bottom: 12px;
  }

  .input-group {
    position: relative;
    margin-bottom: 15px;
  }

  input {
    display: block;
    width: 100%;
    height: 48px;
    padding: 0 42px 0 15px;
    box-sizing: border-box;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 15px;
    background: #f8fafc;
    transition: all 0.3s ease;
  }

  input:focus {
    outline: none;
    border-color: var(--primary);
    background: white;
    box-shadow: 0 0 0 4px rgba(18, 28, 43, 0.1);
  }

  .toggle-btn {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #64748b;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s;
  }

  .toggle-btn:hover {
    color: var(--primary);
  }

  .forgot-link {
    text-align: right;
    margin: 0 0 15px 0;
  }

  .forgot-link a {
    color: #64748b;
    text-decoration: none;
    font-size: 13px;
    transition: color 0.2s;
  }

  .forgot-link a:hover {
    color: var(--primary);
    text-decoration: underline;
  }

  button[type="submit"] {
    position: relative;
    width: 100%;
    height: 48px;
    border: 0;
    border-radius: 24px;
    background: var(--primary);
    color: white;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    overflow: hidden;
    transition: transform 0.2s, background 0.3s, box-shadow 0.3s;
  }

  button[type="submit"]:hover {
    transform: translateY(-2px);
    background: #1a2942;
    box-shadow: 0 8px 15px rgba(18, 28, 43, 0.3);
  }

  button[type="submit"]:active {
    transform: translateY(0);
  }

  .spinner {
    display: none;
    width: 18px;
    height: 18px;
    border: 3px solid rgba(255,255,255,0.3);
    border-top: 3px solid white;
    border-radius: 50%;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    0% { transform: translate(-50%, -50%) rotate(0deg); }
    100% { transform: translate(-50%, -50%) rotate(360deg); }
  }

  .btn-text {
    transition: opacity 0.2s;
  }

  button.loading .btn-text {
    opacity: 0;
  }

  button.loading .spinner {
    display: block;
  }

  .signup-link {
    margin: 15px 0 0;
    color: #64748b;
  }

  .signup-link a {
    color: var(--link-gold);
    font-weight: 600;
    text-decoration: none;
  }

  .signup-link a:hover {
    text-decoration: underline;
  }
</style>
</head>
<body>

<div id="bgLayer"></div>
<div id="bgVignette"></div>
<canvas id="ambienceCanvas"></canvas>

<div class="page">
  <div class="card">
    <div class="lock <?= $loginSuccessUser ? 'unlocked' : '' ?>" id="lockIcon"></div>
    <h1>Login</h1>

    <?php if ($registered): ?>
      <div class="msg-success">Account created. Please sign in.</div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
      <div class="msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form id="loginForm" action="Login.php" method="POST">
      <div class="input-group">
        <input type="text" name="name" id="name" placeholder="Name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required autocomplete="off">
      </div>

      <div class="input-group">
        <input type="password" name="password" id="password" placeholder="Password" required>
        <button type="button" class="toggle-btn" id="togglePassword" aria-label="Toggle password visibility">
          <svg id="eyeOpen" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
          </svg>
          <svg id="eyeClosed" style="display: none;" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 10c2.5 4.5 6.2 6.5 10 6.5s7.5-2 10-6.5"></path>
            <path d="M12 16.5v3"></path>
            <path d="M7.5 15.5l-1.5 2.5"></path>
            <path d="M16.5 15.5l1.5 2.5"></path>
          </svg>
        </button>
      </div>

      <p class="forgot-link"><a href="Register.php">Forgot password?</a></p>

      <button type="submit" id="submitBtn">
        <span class="btn-text">SIGN IN</span>
        <div class="spinner"></div>
      </button>
    </form>

    <p class="signup-link"><small>Don't have an account? <a href="Register.php">Sign up</a></small></p>
  </div>
</div>

<script>
  const togglePassword = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');
  const eyeOpen = document.getElementById('eyeOpen');
  const eyeClosed = document.getElementById('eyeClosed');

  togglePassword.addEventListener('click', () => {
    const isPassword = passwordInput.getAttribute('type') === 'password';
    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
    eyeOpen.style.display = isPassword ? 'none' : 'block';
    eyeClosed.style.display = isPassword ? 'block' : 'none';
  });

  const lockIcon = document.getElementById('lockIcon');
  const nameInput = document.getElementById('name');

  nameInput.addEventListener('input', () => {
    if (nameInput.value.length > 0) {
      lockIcon.style.transform = `translateX(-50%) rotate(${Math.min(nameInput.value.length * 2, 20)}deg)`;
    } else {
      lockIcon.style.transform = 'translateX(-50%)';
    }
  });

 <?php if ($loginSuccessUser): ?>
  const submitBtn = document.getElementById('submitBtn');
  submitBtn.querySelector('.btn-text').textContent = 'SUCCESS!';
  submitBtn.style.background = '#10b981';
  localStorage.setItem('loggedInUser', <?= json_encode($loginSuccessUser) ?>);
  setTimeout(() => {
    window.location.href = 'Hpage.php';
  }, 1200);
  <?php endif; ?>

  const bgLayer = document.getElementById('bgLayer');
  const canvas = document.getElementById('ambienceCanvas');
  const ctx = canvas.getContext('2d');

  let width = 0;
  let height = 0;

  function resizeCanvas() {
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    width = window.innerWidth;
    height = window.innerHeight;
    canvas.width = width * dpr;
    canvas.height = height * dpr;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }
  window.addEventListener('resize', resizeCanvas);
  resizeCanvas();

  let mouseNormX = 0;
  let mouseNormY = 0;
  let currentX = 0;
  let currentY = 0;

  window.addEventListener('mousemove', (e) => {
    mouseNormX = (e.clientX / width) - 0.5;
    mouseNormY = (e.clientY / height) - 0.5;
  });

  const motes = Array.from({ length: 42 }, () => ({
    x: Math.random() * window.innerWidth,
    y: Math.random() * window.innerHeight,
    radius: Math.random() * 2.2 + 0.8,
    vx: (Math.random() - 0.5) * 0.25,
    vy: -(Math.random() * 0.35 + 0.1),
    alpha: Math.random() * 0.55 + 0.15,
    phase: Math.random() * Math.PI * 2,
    speed: Math.random() * 0.025 + 0.01,
    warmth: Math.random() > 0.35 ? '#f8d070' : '#fff3c4'
  }));

  const waterGlints = Array.from({ length: 24 }, (_, i) => ({
    offsetX: (Math.random() - 0.5) * 140,
    relY: 0.69 + (i / 24) * 0.29,
    baseWidth: 14 + (i / 24) * 46,
    phase: Math.random() * Math.PI * 2,
    speed: 0.02 + Math.random() * 0.025
  }));

  const distantGulls = Array.from({ length: 5 }, (_, i) => ({
    x: (i / 5) * window.innerWidth,
    y: window.innerHeight * (0.16 + (i % 3) * 0.11),
    speed: (0.28 + (i % 3) * 0.12) * (i % 2 === 0 ? 1 : -1),
    span: 7 + (i % 3) * 2.5,
    flapPhase: Math.random() * Math.PI * 2,
    flapSpeed: 0.045 + Math.random() * 0.02,
    glideTimer: Math.random() * 120
  }));

  function drawDistantGull(g) {
    g.x += g.speed;
    if (g.x > width + 40) g.x = -40;
    if (g.x < -40) g.x = width + 40;

    g.glideTimer += 1;
    const isGliding = Math.sin(g.glideTimer * 0.015) > 0.3;
    if (!isGliding) {
      g.flapPhase += g.flapSpeed;
    }
    const wingY = isGliding ? -1.5 : Math.sin(g.flapPhase) * (g.span * 0.45);

    ctx.save();
    ctx.translate(g.x, g.y + Math.sin(g.glideTimer * 0.02) * 2);
    ctx.strokeStyle = 'rgba(28, 16, 8, 0.62)';
    ctx.lineWidth = 1.6;
    ctx.lineCap = 'round';

    ctx.beginPath();
    ctx.moveTo(-g.span, wingY);
    ctx.quadraticCurveTo(-g.span * 0.45, -g.span * 0.35, 0, 0);
    ctx.quadraticCurveTo(g.span * 0.45, -g.span * 0.35, g.span, wingY);
    ctx.stroke();
    ctx.restore();
  }

  let time = 0;

  function renderAmbience() {
    time += 0.012;
    ctx.clearRect(0, 0, width, height);

    const targetX = -mouseNormX * 18 + Math.sin(time * 0.5) * 4;
    const targetY = -mouseNormY * 14 + Math.cos(time * 0.4) * 3;
    currentX += (targetX - currentX) * 0.05;
    currentY += (targetY - currentY) * 0.05;
    const breatheScale = 1.03 + Math.sin(time * 0.35) * 0.01;

    bgLayer.style.transform = `translate3d(${currentX}px, ${currentY}px, 0) scale(${breatheScale})`;

    const sunCenterX = width * 0.49 + currentX * 0.6;
    waterGlints.forEach((glint) => {
      glint.phase += glint.speed;
      const shimmerAlpha = Math.max(0, Math.sin(glint.phase)) * 0.22;
      if (shimmerAlpha > 0.01) {
        const gx = sunCenterX + glint.offsetX + Math.sin(glint.phase * 0.7) * 8;
        const gy = height * glint.relY + currentY * 0.6;
        const gw = glint.baseWidth * (0.7 + Math.sin(glint.phase) * 0.3);

        ctx.fillStyle = `rgba(255, 224, 145, ${shimmerAlpha})`;
        ctx.beginPath();
        ctx.ellipse(gx, gy, gw, 1.4, 0, 0, Math.PI * 2);
        ctx.fill();
      }
    });

    distantGulls.forEach(drawDistantGull);

    motes.forEach((m) => {
      m.x += m.vx + Math.sin(m.phase) * 0.15;
      m.y += m.vy;
      m.phase += m.speed;

      if (m.y < -10) {
        m.y = height + 10;
        m.x = Math.random() * width;
      }
      if (m.x < -10) m.x = width + 10;
      if (m.x > width + 10) m.x = -10;

      const twinkle = 0.45 + Math.sin(m.phase) * 0.55;
      ctx.save();
      ctx.globalAlpha = m.alpha * twinkle;
      ctx.fillStyle = m.warmth;
      ctx.shadowBlur = 8;
      ctx.shadowColor = '#f0a050';
      ctx.beginPath();
      ctx.arc(m.x, m.y, m.radius, 0, Math.PI * 2);
      ctx.fill();
      ctx.restore();
    });

    requestAnimationFrame(renderAmbience);
  }
  renderAmbience();
</script>

</body>
</html>