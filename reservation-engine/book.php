<!-- Read the query parameters passed from the availability check.

Display a summary of the selection.

Collect customer details and optional credit-card info.

Submit via POST to book_handler.php. 

Credit-card fields are optional; leaving them blank will create a “PENDING” booking.

-->

<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
if (isset($_SESSION['clerk_id'])) {
    include '../includes/header.php';
} else {
    include '../includes/header-public.php';
}


// Grab & validate GET params from availability step
$hotel_id      = isset($_GET['hotel_id'])       ? (int) $_GET['hotel_id']       : 0;
$room_type_id  = isset($_GET['room_type_id'])   ? (int) $_GET['room_type_id']   : 0;
$arrival_date  = $_GET['arrival_date']          ?? '';
$departure_date= $_GET['departure_date']        ?? '';
$num_guests    = isset($_GET['num_guests'])     ? (int) $_GET['num_guests']     : 0;

if (!$hotel_id || !$room_type_id || !$arrival_date || !$departure_date || $arrival_date >= $departure_date) {
    die('Invalid booking parameters. Please go back and try again.');
}

// Fetch hotel and room type names for display
$h = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM Hotel WHERE hotel_id = {$hotel_id}"));
$rt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT code, description FROM RoomType WHERE room_type_id = {$room_type_id}"));
?>

<div class="booking-wrapper">
<div class="container">
  <h2 class="mb-4 text-center page-title">Complete Your Reservation</h2>
  <div class="card p-4 mb-4">
    <p><strong>Hotel:</strong> <?=htmlspecialchars($h['name'])?></p>
    <p><strong>Room Type:</strong> <?=htmlspecialchars($rt['code'])?> – <?=htmlspecialchars($rt['description'])?></p>
    <p><strong>Arrival:</strong> <?=$arrival_date?> &nbsp; <strong>Departure:</strong> <?=$departure_date?></p>
    <p><strong>Guests:</strong> <?=$num_guests?></p>
  </div>

  <form method="POST" action="book_handler.php" class="row g-3">
    <!-- hidden -->
    <input type="hidden" name="hotel_id"        value="<?=$hotel_id?>">
    <input type="hidden" name="room_type_id"    value="<?=$room_type_id?>">
    <input type="hidden" name="arrival_date"    value="<?=$arrival_date?>">
    <input type="hidden" name="departure_date"  value="<?=$departure_date?>">
    <input type="hidden" name="num_guests"      value="<?=$num_guests?>">

    <h5>Personal Details</h5>
    <div class="col-md-6">
      <label for="first_name" class="form-label">First Name</label>
      <input id="first_name" name="first_name" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label for="last_name" class="form-label">Last Name</label>
      <input id="last_name" name="last_name" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label for="email" class="form-label">Email Address</label>
      <input id="email" type="email" name="email" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label for="phone" class="form-label">Phone Number</label>
      <input id="phone" name="phone" class="form-control">
    </div>

    <h5 class="mt-4">Payment (optional)</h5>
    <p class="text-muted">Provide credit card to confirm now; otherwise your reservation will be held as <em>PENDING</em> and auto-cancelled at 7 PM.</p>
    <div class="col-md-4">
      <label for="cc_type" class="form-label">Card Type</label>
      <input id="cc_type" name="cc_type" class="form-control" placeholder="Visa, MC...">
    </div>
    <div class="col-md-4">
      <label for="cc_number" class="form-label">Card Number</label>
      <input id="cc_number" name="cc_number" class="form-control" placeholder="XXXX-XXXX-XXXX-1234">
    </div>
    <div class="col-md-4">
      <label for="cc_expiry_month" class="form-label">Expiry Month</label>
      <input id="cc_expiry_month" name="cc_expiry_month" type="number" min="1" max="12" class="form-control">
    </div>
    <div class="col-md-4">
      <label for="cc_expiry_year" class="form-label">Expiry Year</label>
      <input id="cc_expiry_year" name="cc_expiry_year" type="number" min="<?=date('Y')?>" max="<?=date('Y')+10?>" class="form-control">
    </div>
    <div class="col-md-4">
      <label for="cc_amount" class="form-label">Authorized Amount</label>
      <input id="cc_amount" name="cc_amount" type="number" step="0.01" class="form-control" placeholder="e.g. 100.00">
    </div>

    <div class="col-12 mt-4">
      <button type="submit" class="btn btn-primary">Confirm Reservation</button>
      <a href="index.php" class="btn btn-secondary ms-2">Cancel</a>
    </div>
  </form>
</div>
</div>
<?php include '../includes/footer-public.php'; ?>