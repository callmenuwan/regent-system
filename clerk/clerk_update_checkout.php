<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['clerk_id'])) {
    header('Location: clerk_login.php');
    exit;
}

$hotel_id = $_SESSION['hotel_id'];
$success = '';
$error = '';
$reservation = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = intval($_POST['reservation_id']);

    // Load reservation
    $stmt = mysqli_prepare($conn, "SELECT * FROM Reservation WHERE reservation_id = ? AND hotel_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $reservation_id, $hotel_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $reservation = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$reservation) {
        $error = 'Reservation not found.';
    } elseif (isset($_POST['new_date'])) {
        $new_date = $_POST['new_date'];
        if (strtotime($new_date) <= strtotime($reservation['arrival_date'])) {
            $error = 'New checkout date must be after arrival.';
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE Reservation SET departure_date = ?, updated_at = NOW() WHERE reservation_id = ?");
            mysqli_stmt_bind_param($stmt, 'si', $new_date, $reservation_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $success = 'Checkout date updated successfully.';
            $reservation['departure_date'] = $new_date;
        }
    }
}
?>

<?php include '../includes/header.php'; ?>
<div class="container py-5">
    <h3 class="mb-4">📝 Update Checkout Date</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" class="card p-4 mb-4 shadow-sm">
        <div class="mb-3">
            <label for="reservation_id" class="form-label">Reservation ID</label>
            <input type="number" name="reservation_id" id="reservation_id" class="form-control" required
                   value="<?= isset($_POST['reservation_id']) ? htmlspecialchars($_POST['reservation_id']) : '' ?>">
        </div>
        <button type="submit" class="btn btn-primary">Load Reservation</button>
    </form>

    <?php if ($reservation): ?>
        <div class="card p-4">
            <h5>Reservation #<?= $reservation['reservation_id'] ?></h5>
            <p><strong>Arrival:</strong> <?= $reservation['arrival_date'] ?></p>
            <p><strong>Current Checkout:</strong> <?= $reservation['departure_date'] ?></p>

            <form method="POST" class="mt-3">
                <input type="hidden" name="reservation_id" value="<?= $reservation['reservation_id'] ?>">
                <div class="mb-3">
                    <label for="new_date" class="form-label">New Checkout Date</label>
                    <input type="date" name="new_date" id="new_date" class="form-control" required
                           value="<?= $reservation['departure_date'] ?>">
                </div>
                <button type="submit" class="btn btn-success">Update Checkout</button>
            </form>
        </div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
