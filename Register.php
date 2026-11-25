<?php
require 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $checkStmt->execute(['email' => $email]);

        if ($checkStmt->fetch()) {
            $error = 'Email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare(
                "INSERT INTO users (full_name, email, password_hash) VALUES (:full_name, :email, :password_hash)"
            );
            $insertStmt->execute([
                'full_name' => $name,
                'email' => $email,
                'password_hash' => $hash,
            ]);

            header("Location: Login.php?registered=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sign Up - Horizon Hotel</title>
<style>
body {
  margin: 0;
  min-height: 100vh;
  font-family: Arial, sans-serif;
  background-color: #060a12;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  position: relative;
}

.bg-image {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: url("Ocean.png") center/cover no-repeat;
  filter: brightness(0.7) contrast(1.05);
  animation: slowKenBurns 20s ease-in-out infinite alternate;
  z-index: 0;
}

@keyframes slowKenBurns {
  0% { transform: scale(1) translate(0, 0); }
  100% { transform: scale(1.06) translate(-10px, -5px); }
}

.bg-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: radial-gradient(circle at 50% 50%, rgba(255, 140, 20, 0.25) 0%, rgba(6, 10, 18, 0.65) 85%);
  z-index: 0;
  pointer-events: none;
  animation: sunGlowPulse 5s ease-in-out infinite alternate;
}

@keyframes sunGlowPulse {
  0% { opacity: 0.6; }
  100% { opacity: 0.9; }
}

#seaSparkleCanvas {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 0;
  pointer-events: none;
}

.bird {
  position: absolute;
  width: 28px;
  height: 16px;
  z-index: 0;
  pointer-events: none;
  opacity: 0.75;
}

.bird::before, .bird::after {
  content: "";
  position: absolute;
  top: 0;
  width: 15px;
  height: 9px;
  border-top: 2.5px solid #111;
  border-radius: 50% 50% 0 0;
}

.bird::before {
  left: 0;
  transform-origin: right bottom;
  animation: wingLeft 0.8s ease-in-out infinite alternate;
}

.bird::after {
  right: 0;
  transform-origin: left bottom;
  animation: wingRight 0.8s ease-in-out infinite alternate;
}

@keyframes wingLeft {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(-35deg); }
}

@keyframes wingRight {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(35deg); }
}

.bird-1 {
  top: 20%;
  left: -60px;
  animation: flyAcross1 26s linear infinite;
}

.bird-2 {
  top: 26%;
  left: -90px;
  transform: scale(0.85);
  animation: flyAcross2 30s linear infinite 3s;
}

.bird-3 {
  top: 15%;
  left: -70px;
  transform: scale(0.65);
  animation: flyAcross3 35s linear infinite 7s;
}

.bird-4 {
  top: 32%;
  left: -100px;
  transform: scale(0.75);
  animation: flyAcross4 28s linear infinite 12s;
}

@keyframes flyAcross1 {
  0% { left: -60px; top: 20%; }
  100% { left: 105vw; top: 14%; }
}

@keyframes flyAcross2 {
  0% { left: -90px; top: 26%; }
  100% { left: 105vw; top: 18%; }
}

@keyframes flyAcross3 {
  0% { left: -70px; top: 15%; }
  100% { left: 105vw; top: 10%; }
}

@keyframes flyAcross4 {
  0% { left: -100px; top: 32%; }
  100% { left: 105vw; top: 24%; }
}

.card {
  width: 800px;
  height: 440px;
  position: relative;
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8),
              0 0 50px rgba(245, 130, 30, 0.25);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  z-index: 1;
}

.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 25px 75px rgba(0, 0, 0, 0.9),
              0 0 60px rgba(245, 130, 30, 0.4);
}

.left {
  float: left;
  width: 50%;
  height: 100%;
  box-sizing: border-box;
  padding: 25px;
  color: white;
  position: relative;
  background: url("BackGround.png") -1px -1px / 785px auto no-repeat;
  overflow: hidden;
}

#sunFlareOverlay {
  position: absolute;
  top: 35%;
  left: 65%;
  width: 230px;
  height: 230px;
  background: radial-gradient(circle, rgba(255, 230, 160, 0.6) 0%, rgba(245, 130, 30, 0.3) 40%, rgba(0,0,0,0) 75%);
  border-radius: 50%;
  transform: translate(-50%, -50%) scale(1);
  pointer-events: none;
  mix-blend-mode: screen;
  animation: flarePulse 4s ease-in-out infinite alternate;
}

@keyframes flarePulse {
  0% { transform: translate(-50%, -50%) scale(0.9); opacity: 0.6; }
  100% { transform: translate(-50%, -50%) scale(1.15); opacity: 0.9; }
}

.left::after {
  content: "";
  position: absolute;
  top: -50%;
  left: -75%;
  width: 50%;
  height: 200%;
  background: linear-gradient(
    to right,
    rgba(255, 255, 255, 0) 0%,
    rgba(255, 215, 130, 0.2) 50%,
    rgba(255, 255, 255, 0) 100%
  );
  transform: rotate(25deg);
  animation: lightSweep 8s infinite ease-in-out;
  pointer-events: none;
}

@keyframes lightSweep {
  0% { left: -75%; }
  30% { left: 125%; }
  100% { left: 125%; }
}

.left h2 {
  position: relative;
  z-index: 2;
  margin-top: 210px;
  font-size: 21px;
  font-weight: 300;
  text-shadow: 0 2px 8px rgba(0,0,0,0.8);
}

.right {
  float: left;
  width: 50%;
  height: 100%;
  box-sizing: border-box;
  background: white;
  padding: 65px 40px;
  border-radius: 80px 0 0 80px;
  position: relative;
  z-index: 2;
}

.right h1 { margin: 0 0 14px; font-size: 24px; color: #121c2b; }

.msg-error {
  color: #dc2626;
  font-size: 13px;
  margin-bottom: 12px;
}

input {
  display: block;
  width: 100%;
  margin-bottom: 18px;
  padding: 6px 0;
  border: 0;
  border-bottom: 1.5px solid #ccc;
  outline: none;
  transition: border-color 0.3s ease, padding 0.3s ease;
}

input:focus {
  border-bottom: 1.5px solid #e8a21c;
  padding-left: 4px;
}

button {
  padding: 9px 28px;
  border: 0;
  border-radius: 16px;
  background: #1c2340;
  color: white;
  font-weight: bold;
  cursor: pointer;
  transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
}

button:hover {
  background: #e8a21c;
  color: #121c2b;
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(232, 162, 28, 0.4);
}

button:active {
  transform: translateY(0);
}

small a {
  color: #e8a21c;
  text-decoration: none;
  font-weight: bold;
  transition: color 0.2s ease;
}

small a:hover {
  color: #1c2340;
  text-decoration: underline;
}
</style>
</head>
<body>

<div class="bg-image"></div>
<div class="bg-overlay"></div>
<canvas id="seaSparkleCanvas"></canvas>

<div class="bird bird-1"></div>
<div class="bird bird-2"></div>
<div class="bird bird-3"></div>
<div class="bird bird-4"></div>

<div class="card">

  <div class="left">
    <div id="sunFlareOverlay"></div>
    <h2><b>Welcome to Horizon Hotel</b><br><b>Enjoy your stay</b></h2>
  </div>

  <div class="right">
    <h1>Register</h1>
    <?php if ($error !== ''): ?>
      <div class="msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form id="registerForm" action="Register.php" method="POST">
      <input type="text" id="name" name="name" placeholder="Name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
      <input type="email" id="email" name="email" placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      <input type="password" id="password" name="password" placeholder="Password" required>
      <button type="submit">Sign Up</button>
    </form>
    <p><small>Already have an account? <a href="Login.php">Login</a></small></p>
  </div>

</div>

<script>
  const canvas = document.getElementById('seaSparkleCanvas');
  const ctx = canvas.getContext('2d');

  function resizeCanvas() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
  }
  window.addEventListener('resize', resizeCanvas);
  resizeCanvas();

  const sparkles = [];
  const sparkleCount = 45;

  for (let i = 0; i < sparkleCount; i++) {
    sparkles.push({
      x: Math.random() * canvas.width,
      y: (canvas.height * 0.55) + Math.random() * (canvas.height * 0.45),
      size: Math.random() * 2 + 0.5,
      alpha: Math.random(),
      speed: Math.random() * 0.02 + 0.005,
      drift: (Math.random() - 0.5) * 0.3
    });
  }

  function animateSparkles() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    for (let s of sparkles) {
      s.alpha += s.speed;
      if (s.alpha > 1 || s.alpha < 0) s.speed = -s.speed;
      s.x += s.drift;

      if (s.x < 0) s.x = canvas.width;
      if (s.x > canvas.width) s.x = 0;

      ctx.beginPath();
      ctx.arc(s.x, s.y, s.size, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(255, 215, 140, ${Math.abs(s.alpha) * 0.7})`;
      ctx.shadowBlur = 6;
      ctx.shadowColor = "rgba(245, 140, 30, 0.8)";
      ctx.fill();
    }

    requestAnimationFrame(animateSparkles);
  }

  animateSparkles();
</script>

</body>
</html>