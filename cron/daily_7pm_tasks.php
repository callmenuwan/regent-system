#!/usr/bin/php
<?php
require_once __DIR__ . '/../includes/db.php';

date_default_timezone_set('Asia/Colombo');
$today = date('Y-m-d');

// 1. Cancel pending reservations for today without credit card
$cancel_sql = "
  UPDATE reservation
  SET status = 'CANCELLED'
  WHERE status = 'PENDING'
    AND arrival_date = ?
    AND reservation_id NOT IN (
      SELECT reservation_id FROM reservationcreditcard
    )";
$stmt = mysqli_prepare($conn, $cancel_sql);
mysqli_stmt_bind_param($stmt, 's', $today);
mysqli_stmt_execute($stmt);
$cancelled = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

// 2. Mark no-shows (confirmed, not checked in)
$noshow_sql = "
  SELECT reservation_id, hotel_id
  FROM reservation
  WHERE status = 'CONFIRMED'
    AND arrival_date = ?
    AND reservation_id NOT IN (
      SELECT reservation_id FROM checkin
    )";
$stmt = mysqli_prepare($conn, $noshow_sql);
mysqli_stmt_bind_param($stmt, 's', $today);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $res_id, $hotel_id);

$noshow_ids = [];
$noshow_hotel_map = [];
while (mysqli_stmt_fetch($stmt)) {
  $noshow_ids[] = $res_id;
  $noshow_hotel_map[$res_id] = $hotel_id;
}
mysqli_stmt_close($stmt);

foreach ($noshow_ids as $res_id) {
  // Update reservation
  mysqli_query($conn, "UPDATE reservation SET status = 'NOSHOW' WHERE reservation_id = $res_id");

  // Insert billing record
  $desc = 'No-show charge';
  $amt = 100.00; // static rate
  $sql = "INSERT INTO billingrecord (reservation_id, amount, description)
          VALUES (?, ?, ?)";
  $stmt2 = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt2, 'ids', $res_id, $amt, $desc);
  mysqli_stmt_execute($stmt2);
  mysqli_stmt_close($stmt2);
}

// 3. Generate daily report for each hotel
$report_sql = "
  SELECT h.hotel_id, COUNT(ci.checkin_id) AS rooms_occupied,
         IFNULL(SUM(b.amount), 0) AS revenue
  FROM hotel h
  LEFT JOIN room r ON h.hotel_id = r.hotel_id
  LEFT JOIN checkin ci ON ci.room_id = r.room_id
      AND DATE(ci.checkin_time) = ?
  LEFT JOIN billingrecord b ON b.checkin_id = ci.checkin_id
      OR (b.reservation_id IN (
          SELECT reservation_id FROM reservation
          WHERE hotel_id = h.hotel_id AND status = 'NOSHOW' AND arrival_date = ?
      ))
  GROUP BY h.hotel_id";
$stmt = mysqli_prepare($conn, $report_sql);
mysqli_stmt_bind_param($stmt, 'ss', $today, $today);
mysqli_stmt_execute($stmt);

// Rewind previous result set before running new queries
mysqli_stmt_store_result($stmt);
mysqli_stmt_bind_result($stmt, $h_id, $occupied, $revenue);

while (mysqli_stmt_fetch($stmt)) {
    // Insert into DailyReport
    $insert = "INSERT INTO dailyreport (report_date, hotel_id, rooms_occupied, revenue)
               VALUES (?, ?, ?, ?)";
    $stmt2 = mysqli_prepare($conn, $insert);
    if (!$stmt2) {
        echo "Error preparing statement: " . mysqli_error($conn);
        continue;
    }
    mysqli_stmt_bind_param($stmt2, 'siid', $today, $h_id, $occupied, $revenue);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);
}

// Close the original $stmt AFTER fetching
mysqli_stmt_close($stmt);

echo "Done: Cancelled = $cancelled, No-shows = " . count($noshow_ids) . "\n";
