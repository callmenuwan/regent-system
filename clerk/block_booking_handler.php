<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['clerk_id'])) {
    header('Location: clerk_login.php');
    exit;
}

// Collect inputs
$company_id    = intval($_POST['company_id']);
$hotel_id      = intval($_POST['hotel_id']);
$room_type_id  = intval($_POST['room_type_id']);
$arrival_date  = $_POST['arrival_date'];
$departure_date= $_POST['departure_date'];
$num_rooms     = intval($_POST['num_rooms']);

// Validate minimum rooms
if ($num_rooms < 3) {
    die("Must block at least 3 rooms.");
}

// Fetch nightly rate from RoomTypeInventory & RoomType
$sql = "SELECT rt.weekly_rate, rt.monthly_rate, rt.is_residential, ri.total_rooms
        FROM RoomType rt
        JOIN RoomTypeInventory ri ON rt.room_type_id = ri.room_type_id
        WHERE rt.room_type_id = ? AND ri.hotel_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $room_type_id, $hotel_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $weekly_rate, $monthly_rate, $is_res, $total_rooms);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Ensure it's not residential
if ($is_res) {
    die("Block bookings not allowed for residential suites.");
}

// Compute number of nights
$nights = (strtotime($departure_date) - strtotime($arrival_date)) / 86400;
if ($nights < 1) {
    die("Invalid date range.");
}

// Check inventory
$sql = "SELECT SUM(num_rooms) FROM BlockBooking
        WHERE hotel_id=? AND room_type_id=?
          AND arrival_date < ? AND departure_date > ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'iiss', $hotel_id, $room_type_id, $departure_date, $arrival_date);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $blocked_already);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Also subtract customer reservations
$sql = "SELECT COUNT(*) FROM Reservation
        WHERE hotel_id=? AND room_type_id=? AND status IN ('PENDING','CONFIRMED')
          AND arrival_date < ? AND departure_date > ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'iiss', $hotel_id, $room_type_id, $departure_date, $arrival_date);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $reserved_already);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$available = $total_rooms - ($blocked_already + $reserved_already);
if ($available < $num_rooms) {
    die("Only $available rooms remain; cannot block $num_rooms.");
}

// Fetch travel company discount
$sql = "SELECT discount_rate FROM TravelCompany WHERE company_id=?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $company_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $discount);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Compute total price with discount
$base_price = /* nightly_rate */ 100.00 * $nights * $num_rooms;  
// (Replace 100.00 with actual per-night rate from your pricing table)
$total_price = round($base_price * (1 - $discount/100), 2);

// Insert block booking
$sql = "INSERT INTO BlockBooking
        (company_id, hotel_id, room_type_id, arrival_date, departure_date, num_rooms, total_price)
        VALUES (?,?,?,?,?,?,?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt,'iiisidd',
    $company_id, $hotel_id, $room_type_id,
    $arrival_date, $departure_date, $num_rooms, $total_price
);
mysqli_stmt_execute($stmt);
$block_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

// Billing to travel company
$sql = "INSERT INTO TravelCompanyBilling
        (block_id, company_id, amount, description, created_at)
        VALUES (?,?,?,?,NOW())";
$desc = "Block booking #$block_id";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt,'iids',$block_id, $company_id, $total_price, $desc);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo "<div class=\"alert alert-success p-4\">";
echo "<h4>Block Booking Created (#$block_id)</h4>";
echo "<p>$num_rooms rooms from $arrival_date to $departure_date at \$$total_price billed to company.</p>";
echo "</div>";
