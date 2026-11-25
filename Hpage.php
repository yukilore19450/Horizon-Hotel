<?php
session_start();
require 'db.php';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header("Location: Hpage.php");
    exit;
}

$loggedInUser = $_SESSION['user_name'] ?? null;
$loggedInUserId = $_SESSION['user_id'] ?? null;
$bookingConfirmation = null;
$bookingError = null;

$roomPrices = [
    'Deluxe Ocean Suite' => 20000,
    'Governor Suite' => 24000,
    'Presidential Suite' => 29000,
    'Deluxe Room' => 9000,
    'Deluxe Twin Room' => 13000,
    'Grand Deluxe Room' => 16000,
];

try {
    $dbRooms = $pdo->query("SELECT room_title, price_per_night FROM rooms")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($dbRooms as $row) {
        if (!empty($row['room_title'])) {
            $roomPrices[$row['room_title']] = (float) $row['price_per_night'];
        }
    }
} catch (Throwable $e) {
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'book_room') {
    $roomTitle = trim($_POST['room_title'] ?? '');
    $checkIn = trim($_POST['check_in'] ?? '');
    $checkOut = trim($_POST['check_out'] ?? '');
    $adults = trim($_POST['adults'] ?? '1 Adult');
    $children = trim($_POST['children'] ?? 'No Child');
    $paymentMethod = trim($_POST['payment_method'] ?? 'Pay at Hotel');
    $paymentRef = trim($_POST['payment_reference'] ?? '');
    $guestName = $loggedInUser ?: 'Guest';

    $inDate = DateTime::createFromFormat('Y-m-d', $checkIn);
    $outDate = DateTime::createFromFormat('Y-m-d', $checkOut);

    $allowedMethods = ['GCash', 'Maya', 'Credit / Debit Card', 'Pay at Hotel'];
    if (!in_array($paymentMethod, $allowedMethods, true)) {
        $paymentMethod = 'Pay at Hotel';
    }

    if ($roomTitle === '' || $checkIn === '' || $checkOut === '') {
        $bookingError = 'Please select both check-in and check-out dates.';
    } elseif (!$inDate || !$outDate || $outDate <= $inDate) {
        $bookingError = 'Check-out date must be after check-in date.';
    } elseif ($paymentMethod !== 'Pay at Hotel' && $paymentRef === '') {
        $bookingError = 'Please enter a payment reference or card details.';
    } else {
        $nights = (int) $inDate->diff($outDate)->days;
        $nightlyRate = (float) ($roomPrices[$roomTitle] ?? 0);
        $totalAmount = $nights * $nightlyRate;

        if ($paymentMethod === 'Pay at Hotel') {
            $paymentStatus = 'Pending';
            $paymentRef = 'HOTEL-DESK';
        } else {
            $paymentStatus = 'Paid';
        }

        $stmt = $pdo->prepare(
            "INSERT INTO bookings (
                user_id, guest_name, room_title, check_in, check_out,
                adults, children, nights, total_amount, payment_method,
                payment_reference, payment_status
             ) VALUES (
                :user_id, :guest_name, :room_title, :check_in, :check_out,
                :adults, :children, :nights, :total_amount, :payment_method,
                :payment_reference, :payment_status
             )"
        );
        $stmt->execute([
            'user_id' => $loggedInUserId,
            'guest_name' => $guestName,
            'room_title' => $roomTitle,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adults' => $adults,
            'children' => $children,
            'nights' => $nights,
            'total_amount' => $totalAmount,
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentRef,
            'payment_status' => $paymentStatus,
        ]);

        $bookingConfirmation = sprintf(
            "Reservation confirmed for %s (%d night%s)!\nTotal: ₱%s\nMethod: %s (%s)\nRef: %s",
            $roomTitle,
            $nights,
            $nights === 1 ? '' : 's',
            number_format($totalAmount, 2),
            $paymentMethod,
            $paymentStatus,
            $paymentRef
        );
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Horizon Hotel - Home</title>
<style>
  :root {
    --bg-dark: #121c2b;
    --bg-header: rgba(18, 28, 43, 0.95);
    --gold: #f8d070;
    --gold-hover: #f0a050;
    --text-white: #ffffff;
    --text-muted: #e2e8f0;
    --card-bg: #ffffff;
    --card-text: #333333;
    --border-dark: #1c2340;
  }

  *, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  html {
    scroll-behavior: smooth;
  }

  body {
    min-height: 100vh;
    font-family: Arial, sans-serif;
    color: var(--text-white);
    background-color: var(--bg-dark);
    overflow-x: hidden;
  }

  .video-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: -2;
  }

  .video-bg video {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .video-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(rgba(18, 28, 43, 0.6), rgba(18, 28, 43, 0.8));
    z-index: -1;
  }

  header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 60px;
    background: var(--bg-header);
    border-bottom: 1px solid var(--border-dark);
    position: sticky;
    top: 0;
    z-index: 100;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
  }

  .logo {
    font-size: 24px;
    font-weight: bold;
    color: var(--gold);
    cursor: pointer;
  }

  nav {
    display: flex;
    align-items: center;
  }

  nav a {
    color: var(--text-white);
    text-decoration: none;
    margin-left: 25px;
    font-size: 16px;
    transition: color 0.3s;
  }

  nav a:hover {
    color: var(--gold);
  }

  nav .btn-login {
    padding: 8px 20px;
    background: var(--border-dark);
    border-radius: 16px;
    border: 1px solid var(--gold);
    color: var(--gold);
    display: inline-block;
  }

  nav .btn-login:hover {
    background: var(--gold);
    color: var(--bg-dark);
  }

  .user-menu {
    position: relative;
    display: inline-block;
  }

  .logout-dropdown {
    display: none;
    position: absolute;
    right: 0;
    top: calc(100% + 8px);
    min-width: 140px;
    background: var(--bg-dark);
    border: 1px solid var(--gold);
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
    padding: 6px;
    z-index: 200;
  }

  .logout-dropdown.open {
    display: block;
  }

  .logout-btn {
    width: 100%;
    padding: 8px 14px;
    background: transparent;
    border: none;
    border-radius: 8px;
    color: var(--text-white);
    font-size: 14px;
    text-align: left;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
  }

  .logout-btn:hover {
    background: var(--gold);
    color: var(--bg-dark);
    font-weight: bold;
  }

  .hero {
    position: relative;
    min-height: calc(100vh - 80px);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    padding: 0 20px;
    z-index: 1;
  }

  .hero h1 {
    font-size: 48px;
    margin-bottom: 10px;
    font-weight: 300;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.7);
  }

  .hero h1 b {
    font-weight: 700;
    color: var(--gold);
  }

  .hero p {
    font-size: 18px;
    color: var(--text-muted);
    max-width: 600px;
    margin-bottom: 30px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.7);
  }

  .hero button {
    padding: 12px 36px;
    border: 0;
    border-radius: 25px;
    background: linear-gradient(180deg, var(--gold-hover), var(--gold));
    color: var(--bg-dark);
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s, background 0.3s;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
  }

  .hero button:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 20px rgba(248, 208, 112, 0.5);
  }

  .content-wrapper {
    position: relative;
    z-index: 2;
    background: url("sunset.png") no-repeat center center;
    background-attachment: fixed;
    background-size: cover;
    padding-top: 100px;
    padding-bottom: 50px;
    min-height: 100vh;
    margin-top: -150px;
    -webkit-mask-image: linear-gradient(to bottom, transparent 0%, black 250px);
    mask-image: linear-gradient(to bottom, transparent 0%, black 250px);
  }

  .container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 20px;
  }

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    flex-wrap: wrap;
    gap: 20px;
  }

  .section-title {
    font-size: 36px;
    color: var(--gold);
    font-weight: 700;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.8);
  }

  .filters {
    display: flex;
    gap: 10px;
  }

  .filters button {
    padding: 10px 20px;
    background: rgba(18, 28, 43, 0.9);
    border: 2px solid var(--gold);
    color: var(--gold);
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.4);
  }

  .filters button.active, .filters button:hover {
    background: var(--gold);
    color: var(--bg-dark);
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(248, 208, 112, 0.4);
  }

  .grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
  }

  .card {
    background: var(--card-bg);
    border-radius: 20px;
    overflow: hidden;
    color: var(--card-text);
    transition: transform 0.3s, box-shadow 0.3s;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 25px rgba(0,0,0,0.4);
    cursor: pointer;
  }

  .card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.6);
  }

  .card-img-wrapper {
    height: 180px;
    width: 100%;
    overflow: hidden;
  }

  .card-img {
    height: 100%;
    width: 100%;
    background-size: cover !important;
    background-position: center !important;
    transition: transform 0.5s ease;
  }

  .card:hover .card-img {
    transform: scale(1.12);
  }

  .card-body {
    padding: 25px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
  }

  .card-body h3 {
    margin: 0 0 10px;
    font-size: 22px;
  }

  .card-body p {
    color: #666;
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 20px;
    flex-grow: 1;
  }

  .card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .price {
    font-size: 20px;
    font-weight: bold;
    color: var(--bg-dark);
  }

  .price span {
    font-size: 12px;
    color: #777;
  }

  .btn-book, .btn-details {
    padding: 8px 16px;
    border: 0;
    border-radius: 16px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.3s, transform 0.2s;
  }

  .btn-book {
    background: var(--bg-dark);
    color: var(--text-white);
  }

  .btn-book:hover {
    background: var(--gold);
    color: var(--bg-dark);
  }

  .btn-details {
    background: #e2e8f0;
    color: #333;
    margin-right: 8px;
  }

  .btn-details:hover {
    background: #cbd5e1;
  }

  .amenities-section {
    margin-top: 100px;
    scroll-margin-top: 100px;
  }

  .slider-controls {
    display: flex;
    gap: 12px;
  }

  .slider-btn {
    padding: 10px 20px;
    background: rgba(18, 28, 43, 0.9);
    border: 2px solid var(--gold);
    color: var(--gold);
    border-radius: 25px;
    cursor: pointer;
    font-weight: 700;
    font-size: 15px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.4);
  }

  .slider-btn:hover {
    background: var(--gold);
    color: var(--bg-dark);
    transform: translateY(-2px);
  }

  .amenities-slider-wrapper {
    position: relative;
    overflow: hidden;
    border-radius: 24px;
    border: 2px solid rgba(248, 208, 112, 0.4);
    background: rgba(18, 28, 43, 0.92);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6);
  }

  .amenities-track {
    display: flex;
    transition: transform 0.55s cubic-bezier(0.25, 1, 0.5, 1);
  }

  .amenity-slide {
    min-width: 100%;
    display: grid;
    grid-template-columns: 1.15fr 1fr;
    align-items: stretch;
  }

  .amenity-slide-img {
    min-height: 380px;
    background-size: cover;
    background-position: center;
    position: relative;
  }

  .amenity-slide-content {
    padding: 45px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .amenity-tag {
    display: inline-block;
    width: fit-content;
    padding: 5px 14px;
    border-radius: 20px;
    background: rgba(248, 208, 112, 0.15);
    border: 1px solid var(--gold);
    color: var(--gold);
    font-size: 12px;
    font-weight: bold;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 16px;
  }

  .amenity-slide-content h3 {
    font-size: 30px;
    color: var(--text-white);
    margin-bottom: 15px;
  }

  .amenity-slide-content p {
    color: var(--text-muted);
    font-size: 16px;
    line-height: 1.7;
    margin-bottom: 25px;
  }

  .amenity-highlights {
    list-style: none;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 30px;
    color: var(--gold);
    font-size: 14px;
  }

  .btn-next-slide {
    width: fit-content;
    padding: 12px 26px;
    background: var(--gold);
    color: var(--bg-dark);
    border: none;
    border-radius: 25px;
    font-weight: bold;
    font-size: 15px;
    cursor: pointer;
    transition: transform 0.2s, background 0.3s;
  }

  .btn-next-slide:hover {
    background: var(--gold-hover);
    transform: translateX(4px);
  }

  .slider-dots {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 22px;
  }

  .dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.35);
    border: none;
    cursor: pointer;
    transition: all 0.3s;
  }

  .dot.active {
    width: 32px;
    border-radius: 10px;
    background: var(--gold);
  }

  @media (max-width: 768px) {
    .amenity-slide {
      grid-template-columns: 1fr;
    }
    .amenity-slide-img {
      min-height: 240px;
    }
    .amenity-slide-content {
      padding: 28px;
    }
  }

  footer {
    text-align: center;
    padding: 60px 30px;
    margin-top: 50px;
    background: transparent;
    color: #e0e0e0;
    font-size: 15px;
    text-shadow: 0 2px 8px rgba(0,0,0,0.8);
  }

  .modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(5px);
    z-index: 1000;
    justify-content: center;
    align-items: center;
    padding: 20px;
  }

  .modal-overlay.active {
    display: flex;
  }

  .modal-content {
    background: var(--bg-dark);
    border: 2px solid var(--gold);
    padding: 30px;
    border-radius: 20px;
    width: 100%;
    max-width: 550px;
    position: relative;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    max-height: 90vh;
    overflow-y: auto;
  }

  .close-btn {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 24px;
    color: var(--gold);
    cursor: pointer;
    background: none;
    border: none;
  }

  .modal-content h2 {
    color: var(--gold);
    margin-bottom: 15px;
  }

  .modal-content input, .modal-content select {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 8px;
    border: 1px solid var(--border-dark);
    background: #1a2538;
    color: white;
  }

  .modal-content label {
    font-size: 14px;
    color: var(--text-muted);
    display: block;
    margin-bottom: 5px;
  }

  .detail-img {
    width: 100%;
    height: 220px;
    background-size: cover;
    background-position: center;
    border-radius: 12px;
    margin-bottom: 15px;
  }

  .amenities-list {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin: 20px 0;
    font-size: 14px;
    color: var(--text-muted);
  }

  .amenities-list div {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .modal-btn-primary {
    width: 100%;
    padding: 12px;
    background: var(--gold);
    color: var(--bg-dark);
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    font-size: 16px;
    transition: transform 0.2s, background 0.3s;
  }

  .modal-btn-primary:hover {
    transform: scale(1.02);
    background: var(--gold-hover);
  }
</style>
</head>
<body>

<div class="video-bg">
  <video autoplay muted loop playsinline>
    <source src="Horizon.mp4" type="video/mp4">
  </video>
  <div class="video-overlay"></div>
</div>

<header>
  <div class="logo">Horizon Hotel</div>
  <nav>
    <a href="#home">Home</a>
    <a href="#rooms">Rooms</a>
    <a href="#amenities">Amenities</a>
    <div class="user-menu" id="userMenu">
      <a href="Login.php" class="btn-login" id="navLoginBtn">Login</a>
      <div class="logout-dropdown" id="logoutDropdown">
        <button type="button" class="logout-btn" id="logoutBtn">Log Out</button>
      </div>
    </div>
  </nav>
</header>

<section id="home" class="hero">
  <h1>Welcome to <b>Horizon Hotel</b></h1>
  <p>Experience world-class luxury, peaceful relaxation, and unforgettable views.</p>
  <button onclick="document.getElementById('rooms').scrollIntoView({behavior: 'smooth'})">Book Your Stay</button>
</section>

<main class="content-wrapper">
  <div id="rooms" class="container">
    <div class="section-header">
      <h2 class="section-title">Our Featured Rooms</h2>
      <div class="filters">
        <button class="active" onclick="filterRooms('all', event)">All</button>
        <button onclick="filterRooms('suite', event)">Suites</button>
        <button onclick="filterRooms('standard', event)">Standard</button>
      </div>
    </div>

    <div class="grid" id="roomGrid">
    </div>
  </div>

  <section id="amenities" class="container amenities-section">
    <div class="section-header">
      <h2 class="section-title">World-Class Amenities</h2>
      <div class="slider-controls">
        <button class="slider-btn" onclick="prevAmenity()">&#8592; Prev</button>
        <button class="slider-btn" onclick="nextAmenity()">Next &#8594;</button>
      </div>
    </div>

    <div class="amenities-slider-wrapper">
      <div class="amenities-track" id="amenitiesTrack">
      </div>
    </div>

    <div class="slider-dots" id="amenitiesDots">
    </div>
  </section>

  <footer>
    <p>&copy; 2026 Horizon Hotel. All rights reserved.</p>
  </footer>
</main>

<div class="modal-overlay" id="roomDetailModal">
  <div class="modal-content">
    <button class="close-btn" onclick="closeDetailModal()">&times;</button>
    <h2 id="detailTitle">Room Title</h2>
    <div class="detail-img" id="detailImg"></div>
    <p id="detailDesc" style="color: #ccc; font-size: 14px; line-height: 1.6;"></p>

    <h3 style="color: var(--gold); margin-top: 20px; font-size: 16px;">Room Amenities & Features:</h3>
    <div class="amenities-list" id="detailAmenities">
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; border-top: 1px solid var(--border-dark); padding-top: 15px;">
      <div id="detailPrice" style="font-size: 22px; font-weight: bold; color: var(--gold);"></div>
      <button class="modal-btn-primary" style="width: auto; padding: 10px 25px;" onclick="proceedToBooking()">Book This Room</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="bookingModal">
  <div class="modal-content">
    <button class="close-btn" onclick="closeBookingModal()">&times;</button>
    <h2>Book Your Stay</h2>
    <p id="modalRoomName" style="margin-bottom: 20px; color: #ccc;"></p>

    <form method="POST" action="Hpage.php" id="bookingForm">
      <input type="hidden" name="action" value="book_room">
      <input type="hidden" name="room_title" id="bookingRoomTitle">

      <label>Check-in Date</label>
      <input type="date" name="check_in" id="checkin" required>

      <label>Check-out Date</label>
      <input type="date" name="check_out" id="checkout" required>

      <label>Adult</label>
      <select name="adults" id="bookingAdults">
        <option>1 Adult</option>
        <option>2 Adults</option>
        <option>3 Adults</option>
        <option>4+ Adults</option>
      </select>

      <label>Child</label>
      <select name="children" id="bookingChildren">
        <option>No Child</option>
        <option>1 Child</option>
        <option>2 Children</option>
        <option>3 Children</option>
        <option>4+ Children</option>
      </select>

      <div style="background:#1a2538; border:1px solid rgba(248,208,112,0.35); border-radius:10px; padding:12px; margin-bottom:15px; font-size:14px;">
        <div style="display:flex; justify-content:space-between; margin-bottom:6px; color:#e2e8f0;">
          <span>Duration:</span>
          <strong id="summaryNights">0 nights</strong>
        </div>
        <div style="display:flex; justify-content:space-between; color:var(--gold); font-size:16px;">
          <span>Total Amount:</span>
          <strong id="summaryTotal">₱0.00</strong>
        </div>
      </div>

      <label>Payment Method</label>
      <select name="payment_method" id="paymentMethod" required>
        <option value="GCash">GCash</option>
        <option value="Maya">Maya</option>
        <option value="Credit / Debit Card">Credit / Debit Card</option>
        <option value="Pay at Hotel">Pay at Hotel</option>
      </select>

      <div id="paymentRefGroup">
        <p id="paymentHint" style="font-size:12px; color:#f8d070; margin:-8px 0 10px;">
          Send to Horizon Hotel GCash: 0917-555-0192
        </p>
        <label id="paymentRefLabel">Reference Number</label>
        <input type="text" name="payment_reference" id="paymentReference" placeholder="e.g. 1004928173645">
      </div>

      <button type="submit" class="modal-btn-primary">Confirm Reservation</button>
    </form>
  </div>
</div>

<script>
  const phpUser = <?= json_encode($loggedInUser) ?>;
  if (phpUser) {
    localStorage.setItem('loggedInUser', phpUser);
  }

  const rooms = [
    {
      id: 1,
      title: "Deluxe Ocean Suite",
      category: "suite",
      description: "Spacious suite featuring a private balcony with panoramic ocean views and a king-size bed.",
      price: 20000,
      image: "Deluxe.jpg",
      amenities: ["1 King Size Bed", "1 Luxury Bathroom", "Private Ocean Balcony", "High-Speed Wi-Fi", "Smart TV & Minibar","Exclusive Pool Access", "24/7 Room Service", "24/7 Executive Fitness Center"]
    },
    {
      id: 2,
      title: "Governor Suite",
      category: "suite",
      description: "An executive luxury space featuring a master bedroom, dedicated living and dining areas, executive lounge access, and 24/7 personal butler service.",
      price: 24000,
      image: "Governor.jpg",
      amenities: ["1 King Size Bed", "1 Bathroom", "Living & Dining Area", "Executive Lounge Access", "Personal Butler Service","Exclusive Pool Access", "High-Speed Wi-Fi", "24/7 Executive Fitness Center"]
    },
    {
      id: 3,
      title: "Presidential Suite",
      category: "suite",
      description: "The peak of luxury featuring two bedrooms, private lounge access, and personal butler service.",
      price: 29000,
      image: "Presidental.jfif",
      amenities: ["2 Bedrooms", "2 Luxury Bathrooms", "Executive Club Lounge Privileges", "24-Hour Butler Service", "Panoramic Skyline View",  "Private Chef Access","Exclusive Pool Access", "High-Speed Wi-Fi", "24/7 Executive Fitness Center"]
    },
    {
      id: 4,
      title: "Deluxe Room",
      category: "standard",
      description: "A comfortable, essential retreat featuring a plush king or queen bed, dedicated workspace, and modern luxury amenities.",
      price: 9000,
      image: "Deluxe Standard.jpg",
      amenities: ["1 Queen Size Bed", "1 Modern Toilet & Shower", "Dedicated Workspace", "High-Speed Wi-Fi", "Flat-screen TV", "Coffee & Tea Maker"]
    },
    {
      id: 5,
      title: "Deluxe Twin Room",
      category: "standard",
      description: "Spacious accommodations featuring two comfortable double or single beds, an expanded seating area, and modern amenities ideal for shared travel.",
      price: 13000,
      image: "Twin.png",
      amenities: ["2 Queen Size Beds", "1 Modern Bathroom", "Expanded Seating Area", "High-Speed Wi-Fi", "Work Desk", "Daily Housekeeping"]
    },
    {
      id: 6,
      title: "Grand Deluxe Room",
      category: "standard",
      description: "An elevated stay offering extra floor space, premium high-floor views, an expanded seating area, and a luxury bathroom with a deep soaking tub.",
      price: 16000,
      image: "Grand Deluxe.jpg",
      amenities: ["1 King Bed", "1 Toilet with Soaking Tub", "Expanded Seating Area", "High-Floor View", "High-Speed Wi-Fi", "Minibar", "24/7 Executive Fitness Center"]
    }
  ];

  const hotelAmenities = [
    {
      tag: "Amenity 01 / 04",
      title: "Infinity Sky Pool",
      description: "Unwind in our temperature-controlled rooftop infinity pool overlooking the sunset horizon, complete with private cabanas and poolside cocktail service.",
      image: "skypool.jpg",
      highlights: ["✓ Heated Infinity Edge", "✓ Sunset Cabanas", "✓ Poolside Bar", "✓ Open 6 AM – 10 PM"]
    },
    {
      tag: "Amenity 02 / 04",
      title: "Horizon Fine Dining & Lounge",
      description: "Savor curated international and local coastal cuisine crafted by our executive chefs, paired with an extensive cellar of vintage wines.",
      image: "finedine.jpg",
      highlights: ["✓ Panoramic Sea Views", "✓ Private Dining Rooms", "✓ Breakfast Buffet", "✓ Sommelier Selection"]
    },
    {
      tag: "Amenity 03 / 04",
      title: "Serenity Spa & Wellness",
      description: "Rejuvenate your body and mind with signature aromatherapy massages, herbal steam rooms, and holistic hydrotherapy treatments.",
      image: "spa.jpg",
      highlights: ["✓ Couples Treatment Suites", "✓ Sauna & Steam Room", "✓ Organic Essential Oils", "✓ In-Room Massage Option"]
    },
    {
      tag: "Amenity 04 / 04",
      title: "24/7 Executive Fitness Center",
      description: "Stay energized with state-of-the-art cardio machines, free weights, personal training sessions, and an outdoor yoga deck.",
      image: "gym.jpg",
      highlights: ["✓ Modern Cardio & Weights", "✓ Sunrise Yoga Deck", "✓ Personal Trainers", "✓ Complimentary Towels"]
    }
  ];

  let currentAmenityIndex = 0;

  function renderAmenitiesSlider() {
    const track = document.getElementById('amenitiesTrack');
    const dotsContainer = document.getElementById('amenitiesDots');
    track.innerHTML = '';
    dotsContainer.innerHTML = '';

    hotelAmenities.forEach((item, index) => {
      const slide = document.createElement('div');
      slide.className = 'amenity-slide';
      slide.innerHTML = `
        <div class="amenity-slide-img" style="background-image: url('${item.image}');"></div>
        <div class="amenity-slide-content">
          <span class="amenity-tag">${item.tag}</span>
          <h3>${item.title}</h3>
          <p>${item.description}</p>
          <ul class="amenity-highlights">
            ${item.highlights.map(h => `<li>${h}</li>`).join('')}
          </ul>
          <button class="btn-next-slide" onclick="nextAmenity()">Explore Next Amenity &#8594;</button>
        </div>
      `;
      track.appendChild(slide);

      const dot = document.createElement('button');
      dot.className = `dot ${index === 0 ? 'active' : ''}`;
      dot.setAttribute('aria-label', `Go to slide ${index + 1}`);
      dot.onclick = () => goToAmenity(index);
      dotsContainer.appendChild(dot);
    });
  }

  function updateAmenitySlider() {
    const track = document.getElementById('amenitiesTrack');
    const dots = document.querySelectorAll('.slider-dots .dot');
    track.style.transform = `translateX(-${currentAmenityIndex * 100}%)`;

    dots.forEach((dot, idx) => {
      dot.classList.toggle('active', idx === currentAmenityIndex);
    });
  }

  function nextAmenity() {
    currentAmenityIndex = (currentAmenityIndex + 1) % hotelAmenities.length;
    updateAmenitySlider();
  }

  function prevAmenity() {
    currentAmenityIndex = (currentAmenityIndex - 1 + hotelAmenities.length) % hotelAmenities.length;
    updateAmenitySlider();
  }

  function goToAmenity(index) {
    currentAmenityIndex = index;
    updateAmenitySlider();
  }

  let currentSelectedRoom = "";
  let currentRoomPrice = 0;
  const roomGrid = document.getElementById('roomGrid');

  function renderRooms(filterCategory = 'all') {
    roomGrid.innerHTML = '';

    const filteredRooms = filterCategory === 'all'
      ? rooms
      : rooms.filter(room => room.category === filterCategory);

    filteredRooms.forEach(room => {
      const card = document.createElement('div');
      card.className = 'card';
      card.onclick = () => openDetailModal(room);
      card.innerHTML = `
        <div class="card-img-wrapper">
          <div class="card-img" style="background-image: url('${room.image}');"></div>
        </div>
        <div class="card-body">
          <h3>${room.title}</h3>
          <p>${room.description}</p>
          <div class="card-footer">
            <div class="price">₱${room.price.toLocaleString()} <span>/ night</span></div>
            <div>
              <button class="btn-details" onclick="event.stopPropagation(); openDetailModal(${JSON.stringify(room).replace(/"/g, '&quot;')})">Details</button>
              <button class="btn-book" onclick="event.stopPropagation(); openBookingModal('${room.title}')">Book</button>
            </div>
          </div>
        </div>
      `;
      roomGrid.appendChild(card);
    });
  }

  function filterRooms(category, evt) {
    const buttons = document.querySelectorAll('.filters button');
    buttons.forEach(btn => btn.classList.remove('active'));
    if (evt && evt.target) {
      evt.target.classList.add('active');
    }

    renderRooms(category);
  }

  const detailModal = document.getElementById('roomDetailModal');
  function openDetailModal(room) {
    currentSelectedRoom = room.title;
    document.getElementById('detailTitle').textContent = room.title;
    document.getElementById('detailImg').style.backgroundImage = `url('${room.image}')`;
    document.getElementById('detailDesc').textContent = room.description;
    document.getElementById('detailPrice').textContent = `₱${room.price.toLocaleString()} / night`;

    const amenitiesContainer = document.getElementById('detailAmenities');
    amenitiesContainer.innerHTML = '';
    room.amenities.forEach(amenity => {
      amenitiesContainer.innerHTML += `<div>✓ ${amenity}</div>`;
    });

    detailModal.classList.add('active');
  }

  function closeDetailModal() {
    detailModal.classList.remove('active');
  }

  function proceedToBooking() {
    closeDetailModal();
    openBookingModal(currentSelectedRoom);
  }

  const bookingModal = document.getElementById('bookingModal');
  const modalRoomName = document.getElementById('modalRoomName');
  const checkinInput = document.getElementById('checkin');
  const checkoutInput = document.getElementById('checkout');
  const paymentMethodEl = document.getElementById('paymentMethod');
  const paymentRefGroup = document.getElementById('paymentRefGroup');
  const paymentHintEl = document.getElementById('paymentHint');
  const paymentRefLabelEl = document.getElementById('paymentRefLabel');
  const paymentRefInput = document.getElementById('paymentReference');
  const summaryNightsEl = document.getElementById('summaryNights');
  const summaryTotalEl = document.getElementById('summaryTotal');

  function updateBookingTotal() {
    if (!checkinInput.value || !checkoutInput.value) {
      summaryNightsEl.textContent = '0 nights';
      summaryTotalEl.textContent = '₱0.00';
      return;
    }

    const start = new Date(checkinInput.value + 'T00:00:00');
    const end = new Date(checkoutInput.value + 'T00:00:00');
    const nights = Math.round((end - start) / (1000 * 60 * 60 * 24));

    if (nights > 0) {
      const total = nights * currentRoomPrice;
      summaryNightsEl.textContent = `${nights} ${nights === 1 ? 'night' : 'nights'}`;
      summaryTotalEl.textContent = '₱' + total.toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    } else {
      summaryNightsEl.textContent = '0 nights';
      summaryTotalEl.textContent = '₱0.00';
    }
  }

  function updatePaymentFields() {
    const method = paymentMethodEl.value;
    if (method === 'Pay at Hotel') {
      paymentRefGroup.style.display = 'none';
      paymentRefInput.required = false;
      paymentRefInput.value = '';
    } else if (method === 'Credit / Debit Card') {
      paymentRefGroup.style.display = 'block';
      paymentHintEl.textContent = 'Enter card last 4 digits or approval code for demo checkout.';
      paymentRefLabelEl.textContent = 'Card Last 4 Digits / Code';
      paymentRefInput.placeholder = 'e.g. CARD-4821';
      paymentRefInput.required = true;
    } else {
      paymentRefGroup.style.display = 'block';
      paymentHintEl.textContent = `Send to Horizon Hotel ${method}: 0917-555-0192`;
      paymentRefLabelEl.textContent = `${method} Reference Number`;
      paymentRefInput.placeholder = 'e.g. 1004928173645';
      paymentRefInput.required = true;
    }
  }

  function openBookingModal(roomTitle) {
    currentSelectedRoom = roomTitle;
    const foundRoom = rooms.find(r => r.title === roomTitle);
    currentRoomPrice = foundRoom ? foundRoom.price : 0;

    modalRoomName.textContent = `Booking: ${roomTitle} (₱${currentRoomPrice.toLocaleString()} / night)`;
    document.getElementById('bookingRoomTitle').value = roomTitle;
    updatePaymentFields();
    updateBookingTotal();
    bookingModal.classList.add('active');
  }

  function closeBookingModal() {
    bookingModal.classList.remove('active');
  }

  checkinInput.addEventListener('change', updateBookingTotal);
  checkoutInput.addEventListener('change', updateBookingTotal);
  paymentMethodEl.addEventListener('change', updatePaymentFields);

  <?php if ($bookingConfirmation): ?>
  alert(<?= json_encode($bookingConfirmation) ?>);
  <?php elseif ($bookingError): ?>
  alert(<?= json_encode($bookingError) ?>);
  <?php endif; ?>

  function updateAuthUI() {
    const savedUser = localStorage.getItem('loggedInUser');
    const loginBtn = document.getElementById('navLoginBtn');
    const dropdown = document.getElementById('logoutDropdown');

    if (!loginBtn) return;

    if (savedUser) {
      loginBtn.textContent = savedUser;
      loginBtn.href = '#';
      loginBtn.title = 'Logged in as ' + savedUser;
    } else {
      loginBtn.textContent = 'Login';
      loginBtn.href = 'Login.php';
      loginBtn.removeAttribute('title');
      if (dropdown) dropdown.classList.remove('open');
    }
  }

  function logoutUser() {
    localStorage.removeItem('loggedInUser');
    window.location.href = 'Hpage.php?logout=1';
  }

  window.onclick = function(event) {
    if (event.target === detailModal) {
      closeDetailModal();
    }
    if (event.target === bookingModal) {
      closeBookingModal();
    }
    const userMenu = document.getElementById('userMenu');
    const dropdown = document.getElementById('logoutDropdown');
    if (userMenu && dropdown && !userMenu.contains(event.target)) {
      dropdown.classList.remove('open');
    }
  };

  window.onload = () => {
    renderRooms('all');
    renderAmenitiesSlider();
  };

  document.addEventListener('DOMContentLoaded', () => {
    const loginBtn = document.getElementById('navLoginBtn');
    const dropdown = document.getElementById('logoutDropdown');
    const logoutBtn = document.getElementById('logoutBtn');

    updateAuthUI();

    if (loginBtn && dropdown) {
      loginBtn.addEventListener('click', (e) => {
        if (localStorage.getItem('loggedInUser')) {
          e.preventDefault();
          dropdown.classList.toggle('open');
        }
      });
    }

    if (logoutBtn) {
      logoutBtn.addEventListener('click', logoutUser);
    }
  });
</script>

</body>
</html>