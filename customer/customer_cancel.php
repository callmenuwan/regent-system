<?php
require_once '../includes/db.php';
include '../includes/header.php';

$reservation_id = intval($_GET['reservation_id'] ?? 0);
$email = trim($_GET['email'] ?? '');
echo $reservation_id . ' ' . $email;

$cancelled = false;
$error = '';

if ($reservation_id && $email) {
    // Verify reservation
    $sql = "SELECT r.status
            FROM Reservation r
            JOIN Customer c ON r.customer_id = c.customer_id
            WHERE r.reservation_id = ? AND c.email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'is', $reservation_id, $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($row) {
        if (in_array($row['status'], ['PENDING', 'CONFIRMED'])) {
            // Cancel reservation
            $sql = "UPDATE Reservation r
                    JOIN Customer c ON r.customer_id = c.customer_id
                    SET r.status = 'CANCELLED'
                    WHERE r.reservation_id = ? AND c.email = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'is', $reservation_id, $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $cancelled = true;
        } else {
            $error = "Reservation cannot be cancelled (status: {$row['status']}).";
        }
    } else {
        $error = "Reservation not found or email does not match.";
    }
} else {
    $error = "Invalid request.";
}
?>

<div class="container py-5">
    <h3 class="mb-4">❌ Cancel Reservation</h3>

    <?php if ($cancelled): ?>
        <div class="alert alert-success">
            ✅ Your reservation has been successfully cancelled.
        </div>
        <a href="customer_reservation.php" class="btn btn-primary">🔙 Back to Manage Reservation</a>
    <?php else: ?>
        <div class="alert alert-danger">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
        <a href="javascript:history.back()" class="btn btn-secondary">🔙 Go Back</a>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
