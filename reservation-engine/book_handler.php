<!-- Retrieve POST data

Find or create a Customer record

Insert a Reservation (status CONFIRMED if CC provided, else PENDING)

If CC info was entered, mask and store it in ReservationCreditCard

Display a confirmation page with the reservation details -->

<?php
session_start();
require_once __DIR__ . '/../includes/db.php';


// Helper to redirect with message
function redirect_with_error($msg) {
    header("Location: index.php?error=" . urlencode($msg));
    exit;
}

// 1) Collect & validate POST data
$hotel_id      = (int)($_POST['hotel_id']       ?? 0);
$room_type_id  = (int)($_POST['room_type_id']   ?? 0);
$arrival_date  = $_POST['arrival_date']        ?? '';
$departure_date= $_POST['departure_date']      ?? '';
$num_guests    = (int)($_POST['num_guests']     ?? 0);
$first_name    = trim($_POST['first_name']      ?? '');
$last_name     = trim($_POST['last_name']       ?? '');
$email         = trim($_POST['email']           ?? '');
$phone         = trim($_POST['phone']           ?? '');

// Residential-specific inputs ⬅️
$num_weeks      = isset($_POST['num_weeks'])  ? (int)$_POST['num_weeks']  : 0;  // ⬅️
$num_months     = isset($_POST['num_months']) ? (int)$_POST['num_months'] : 0;  // ⬅️

// Basic validation
if (!$hotel_id || !$room_type_id || !$arrival_date || !$departure_date
    || $arrival_date >= $departure_date || $num_guests < 1
    || !$first_name || !$last_name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_with_error('Please fill in all required fields correctly.');
}

// 2) Find or create Customer
$sql = "SELECT customer_id FROM Customer WHERE email = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $customer_id);
if (!mysqli_stmt_fetch($stmt)) {
    mysqli_stmt_close($stmt);
    // insert new
    $ins = "INSERT INTO Customer (first_name,last_name,email,phone) VALUES (?,?,?,?)";
    $stmt2 = mysqli_prepare($conn, $ins);
    mysqli_stmt_bind_param($stmt2, 'ssss', $first_name, $last_name, $email, $phone);
    mysqli_stmt_execute($stmt2);
    $customer_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt2);
} else {
    mysqli_stmt_close($stmt);
}

// 3) Insert Reservation
$status = (!empty($_POST['cc_number']) ? 'CONFIRMED' : 'PENDING');
$ins = "INSERT INTO Reservation
    (hotel_id,customer_id,room_type_id,num_guests,arrival_date,departure_date,status)
  VALUES (?,?,?,?,?,?,?)";
$stmt3 = mysqli_prepare($conn, $ins);
mysqli_stmt_bind_param($stmt3, 'iiissss',
    $hotel_id, $customer_id, $room_type_id,
    $num_guests, $arrival_date, $departure_date, $status
);
mysqli_stmt_execute($stmt3);
$reservation_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt3);

// 4) If CC provided, insert masked & auth
if (!empty($_POST['cc_number'])) {
    $card_type = trim($_POST['cc_type']);
    $card_mask = substr(preg_replace('/\D/', '', $_POST['cc_number']), -4);
    $card_mask = str_repeat('X', max(0, strlen($card_mask)-4)) . $card_mask;
    $exp_month = (int)($_POST['cc_expiry_month'] ?? 0);
    $exp_year  = (int)($_POST['cc_expiry_year']  ?? 0);
    $amount    = (float)($_POST['cc_amount']     ?? 0.0);

    $ins2 = "INSERT INTO ReservationCreditCard
      (reservation_id,card_type,card_number_masked,expiry_month,expiry_year,authorized_amount)
      VALUES (?,?,?,?,?,?)";
    $stmt4 = mysqli_prepare($conn, $ins2);
    mysqli_stmt_bind_param($stmt4, 'issiii',
        $reservation_id, $card_type, $card_mask, $exp_month, $exp_year, $amount
    );
    mysqli_stmt_execute($stmt4);
    mysqli_stmt_close($stmt4);
}

// 5) Show confirmation
?>

<?php 

if (isset($_SESSION['clerk_id'])) {
    include '../includes/header.php';
} else {
    include '../includes/header-public.php';
}

?>
<div class="container py-5 text-center">
  <div class="card p-5">
    <h3 class="text-success mb-4">Reservation <?=htmlspecialchars($status)?>!</h3>
    <p>Your reservation ID is:</p>
    <h1><?= $reservation_id ?></h1>
    <p>A confirmation email would normally be sent to <strong><?=htmlspecialchars($email)?></strong>.</p>
  </div>
</div>
</body>
</html>