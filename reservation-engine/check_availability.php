<!-- 
Reads inputs from query string and validates them.

Looks up the total number of rooms of the requested type at the selected hotel.

Counts existing reservations in PENDING or CONFIRMED status that overlap the desired date range.

Calculates available rooms and returns a JSON payload.

-->

<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db.php';

$hotel_id       = (int)($_GET['hotel_id']      ?? 0);
$room_type_id   = (int)($_GET['room_type_id']  ?? 0);
$arrival_date   = $_GET['arrival_date']       ?? '';
$departure_date = $_GET['departure_date']     ?? '';
$num_guests     = (int)($_GET['num_guests']    ?? 0);

// Basic sanity check
if (!$hotel_id || !$room_type_id || !$arrival_date || !$departure_date || $arrival_date >= $departure_date || $num_guests < 1) {
    echo json_encode(['error'=>'Invalid parameters','available'=>0]);
    exit;
}

// 0) Check if this is a residential suite
$stmt = mysqli_prepare($conn,"
  SELECT is_residential
  FROM RoomType
  WHERE room_type_id = ?");
mysqli_stmt_bind_param($stmt,'i',$room_type_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt,$is_residential);
if (!mysqli_stmt_fetch($stmt)) {
    echo json_encode(['error'=>'Unknown room type','available'=>0]);
    exit;
}
mysqli_stmt_close($stmt);

// 1) Fetch total rooms
$stmt = mysqli_prepare($conn,"
  SELECT total_rooms
  FROM RoomTypeInventory
  WHERE hotel_id = ? AND room_type_id = ?");
mysqli_stmt_bind_param($stmt,'ii',$hotel_id,$room_type_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt,$total_rooms);
if (!mysqli_stmt_fetch($stmt)) {
    echo json_encode(['error'=>'Room type not found for this hotel','available'=>0]);
    exit;
}
mysqli_stmt_close($stmt);

// 2) Count overlapping customer reservations
$stmt = mysqli_prepare($conn,"
  SELECT COUNT(*) 
  FROM Reservation
  WHERE hotel_id=? AND room_type_id=?
    AND status IN ('PENDING','CONFIRMED')
    AND arrival_date < ? AND departure_date > ?");
mysqli_stmt_bind_param($stmt,'iiss',$hotel_id,$room_type_id,$departure_date,$arrival_date);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt,$booked_customers);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// 2b) Count overlapping block bookings (only for non-residential)
$blocked = 0;
if (!$is_residential) {
    $stmt = mysqli_prepare($conn,"
      SELECT COALESCE(SUM(num_rooms),0)
      FROM BlockBooking
      WHERE hotel_id=? AND room_type_id=?
        AND arrival_date < ? AND departure_date > ?");
    mysqli_stmt_bind_param($stmt,'iiss',$hotel_id,$room_type_id,$departure_date,$arrival_date);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt,$blocked);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}

// 3) Compute availability
$available = max(0, $total_rooms - ($booked_customers + $blocked));

// 4) If non-residential, enforce guest-capacity logic
if (!$is_residential) {
    
    $capacities = [
        1 => 2,  // room_type_id=1 (Standard)
        2 => 3,  // Deluxe
        3 => 5,  // Suite (hotel)
    ];
    if (!isset($capacities[$room_type_id])) {
        echo json_encode(['error'=>'Unknown room capacity','available'=>0]);
        exit;
    }
    $needed_rooms = (int) ceil($num_guests / $capacities[$room_type_id]);
    if ($available < $needed_rooms) {
        echo json_encode([
            'error'     => "Not enough rooms: need {$needed_rooms}, only {$available} available",
            'available' => $available
        ]);
        exit;
    }
    // Return success for hotel rooms
    echo json_encode([
        'hotel_id'       => $hotel_id,
        'room_type_id'   => $room_type_id,
        'arrival_date'   => $arrival_date,
        'departure_date' => $departure_date,
        'num_guests'     => $num_guests,
        'available'      => $available,
        'needed_rooms'   => $needed_rooms
    ]);
} else {
    // Residential suite: each reservation occupies 1 suite
    echo json_encode([
        'hotel_id'       => $hotel_id,
        'room_type_id'   => $room_type_id,
        'arrival_date'   => $arrival_date,
        'departure_date' => $departure_date,
        'num_guests'     => $num_guests,
        'available'      => $available
    ]);
}
exit;