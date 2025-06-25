<?php
$hotel_id = intval($selected_hotel);
$report_date = $selected_date;
$today = date('Y-m-d');

// --- Occupancy on Selected Date ---
$sql = "SELECT rt.description AS room_type, COUNT(*) AS rooms_booked, SUM(r.num_guests) AS guests
        FROM Reservation r
        JOIN RoomType rt ON r.room_type_id = rt.room_type_id
        WHERE r.hotel_id = ? AND ? BETWEEN r.arrival_date AND DATE_SUB(r.departure_date, INTERVAL 1 DAY)
              AND r.status IN ('CONFIRMED', 'CHECKEDIN', 'CHECKEDOUT')
        GROUP BY rt.description";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'is', $hotel_id, $report_date);
mysqli_stmt_execute($stmt);
$occupancy_result = mysqli_stmt_get_result($stmt);
$occupancy_data = mysqli_fetch_all($occupancy_result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// --- Projected Bookings (next 7 days) ---
$sql = "SELECT r.arrival_date, COUNT(*) AS reservations
        FROM Reservation r
        WHERE r.hotel_id = ? AND r.arrival_date >= CURDATE()
              AND r.status IN ('CONFIRMED', 'CHECKEDIN')
        GROUP BY r.arrival_date ORDER BY r.arrival_date ASC LIMIT 7";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $hotel_id);
mysqli_stmt_execute($stmt);
$projection_result = mysqli_stmt_get_result($stmt);
$projection_data = mysqli_fetch_all($projection_result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// --- Revenue Summary (for selected date) ---
$sql = "SELECT SUM(br.amount) AS total_revenue
        FROM BillingRecord br
        JOIN Reservation r ON br.reservation_id = r.reservation_id
        WHERE r.hotel_id = ? AND DATE(br.created_at) = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'is', $hotel_id, $report_date);
mysqli_stmt_execute($stmt);
$revenue_result = mysqli_stmt_get_result($stmt);
$revenue_data = mysqli_fetch_assoc($revenue_result);
mysqli_stmt_close($stmt);
?>

<div class="card mb-4 p-4 shadow-sm">
    <h5 class="mb-3">📅 Occupancy Report — <?= date('F j, Y', strtotime($report_date)) ?></h5>
    <?php if (empty($occupancy_data)): ?>
        <p>No bookings found on this date.</p>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Room Type</th>
                    <th>Rooms Booked</th>
                    <th>Guests</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($occupancy_data as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['room_type']) ?></td>
                        <td><?= $row['rooms_booked'] ?></td>
                        <td><?= $row['guests'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card mb-4 p-4 shadow-sm">
    <h5 class="mb-3">📈 Projected Occupancy (Next 7 Days)</h5>
    <?php if (empty($projection_data)): ?>
        <p>No upcoming reservations found.</p>
    <?php else: ?>
        <table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reservations</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projection_data as $row): ?>
                    <tr>
                        <td><?= date('Y-m-d', strtotime($row['arrival_date'])) ?></td>
                        <td><?= $row['reservations'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card mb-4 p-4 shadow-sm">
    <h5 class="mb-3">💰 Revenue Report — <?= date('F j, Y', strtotime($report_date)) ?></h5>
    <p>Total Revenue: <strong>$<?= number_format($revenue_data['total_revenue'] ?? 0, 2) ?></strong></p>
</div>
<?php include '../includes/footer.php'; ?>