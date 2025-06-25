<?php
require_once '../includes/db.php';

// Sanitize and fetch inputs
$hotel_id = intval($_POST['hotel_id']);
$room_type_id = intval($_POST['room_type_id']);
$arrival_date = $_POST['arrival_date'];
$duration_type = $_POST['duration_type']; // 'weeks' or 'months'
$duration = intval($_POST['duration']);

$first_name = trim($_POST['first_name']);
$last_name = trim($_POST['last_name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$num_guests = intval($_POST['num_guests']);

if (!$hotel_id || !$room_type_id || !$arrival_date || !$duration || !$first_name || !$email || !$num_guests) {
    die("Missing required fields.");
}

// Fetch pricing
$stmt = mysqli_prepare($conn, "SELECT weekly_rate, monthly_rate FROM RoomType WHERE room_type_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $room_type_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $weekly_rate, $monthly_rate);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($duration_type == 'weeks') {
    $departure_date = date('Y-m-d', strtotime("+$duration weeks", strtotime($arrival_date)));
    $amount = $weekly_rate * $duration;
} else {
    $departure_date = date('Y-m-d', strtotime("+$duration months", strtotime($arrival_date)));
    $amount = $monthly_rate * $duration;
}

// Check availability
$sql = "SELECT COUNT(*) AS booked 
        FROM Reservation 
        WHERE room_type_id = ? 
          AND hotel_id = ?
          AND status NOT IN ('CANCELLED')
          AND NOT (
            departure_date <= ? OR arrival_date >= ?
          )";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'iiss', $room_type_id, $hotel_id, $arrival_date, $departure_date);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $booked);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$sql = "SELECT total_rooms FROM RoomTypeInventory WHERE room_type_id = ? AND hotel_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $room_type_id, $hotel_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $total_rooms);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($booked >= $total_rooms) {
    die("Sorry, no suites available for selected dates.");
}

// Insert customer
$sql = "SELECT customer_id FROM Customer WHERE email = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $customer_id);
if (!mysqli_stmt_fetch($stmt)) {
    mysqli_stmt_close($stmt);

    $sql = "INSERT INTO Customer (first_name, last_name, email, phone, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ssss', $first_name, $last_name, $email, $phone);
    if (mysqli_stmt_execute($stmt)) {
        $customer_id = mysqli_insert_id($conn);
    } else {
        die("Error creating customer.");
    }
}
mysqli_stmt_close($stmt);

// Insert reservation
$sql = "INSERT INTO Reservation (hotel_id, customer_id, room_type_id, num_guests, arrival_date, departure_date, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'CONFIRMED', NOW())";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'iiiiss', $hotel_id, $customer_id, $room_type_id, $num_guests, $arrival_date, $departure_date);
if (!mysqli_stmt_execute($stmt)) {
    die("Error saving reservation.");
}
$reservation_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

// Insert billing
$sql = "INSERT INTO BillingRecord (reservation_id, amount, description, created_at)
        VALUES (?, ?, ?, NOW())";
$desc = 'Residential Suite Booking';
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ids', $reservation_id, $amount, $desc);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo "<h3>Suite Reservation Confirmed!</h3>";
echo "<p>Thank you, $first_name. Your suite has been reserved from $arrival_date to $departure_date.</p>";
echo "<p>Total Amount: <strong>$$amount</strong></p>";
?>
