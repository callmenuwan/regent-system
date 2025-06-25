<!-- This allows a clerk to search reservations using:

Reservation ID

Customer email

Shows reservation info, and gives options to:

View full reservation

Proceed to Check-In -->

<?php
session_start();
if (!isset($_SESSION['clerk_id'])) {
    header('Location: clerk_login.php');
    exit;
}
require_once __DIR__ . '/../includes/db.php';

$search_results = [];
$message = '';

$sql = "SELECT * 
        FROM reservation r 
        JOIN Customer c ON r.customer_id = c.customer_id
        WHERE r.hotel_id = ? AND r.status IN ('CONFIRMED', 'PENDING') AND r.arrival_date >= CURDATE()";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $_SESSION['hotel_id']);

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$search_results = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

if (empty($search_results)) {
    $message = 'No reservations found.';
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container py-5">
    <h3 class="mb-4">🔍 Pending Reservations</h3>

    <?php if ($message): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($search_results): ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Reservation ID</th>
                    <th>Customer</th>
                    <th>Arrival</th>
                    <th>Departure</th>
                    <th>Guests</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($search_results as $res): ?>
                <tr>
                    <td><?= $res['reservation_id'] ?></td>
                    <td><?= htmlspecialchars($res['first_name'] . ' ' . $res['last_name']) ?></td>
                    <td><?= $res['arrival_date'] ?></td>
                    <td><?= $res['departure_date'] ?></td>
                    <td><?= $res['num_guests'] ?></td>
                    <td><?= strtoupper($res['status']) ?></td>
                    <td>
                        <a href="clerk_checkin.php?reservation_id=<?= $res['reservation_id'] ?>" class="btn btn-sm btn-success">Check In</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>