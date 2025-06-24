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
exit;