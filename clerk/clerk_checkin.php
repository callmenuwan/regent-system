<!-- This will:

Verify reservation exists, is for today or earlier, and is not already checked in

Show list of available rooms matching room type

On submit:

Insert record into CheckIn

Update reservation status to CHECKEDIN
 -->

<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['clerk_id'])) {
    header('Location: clerk_login.php');
    exit;
}

$clerk_id = $_SESSION['clerk_id'];
$hotel_id = $_SESSION['hotel_id'];

$reservation_id = $_GET['reservation_id'] ?? null;
$reservation = null;
$available_rooms = [];
$error = '';
$success = '';

if ($reservation_id) {
    // Get reservation
    $sql = "SELECT r.*, c.first_name, c.last_name
            FROM reservation r
            JOIN customer c ON r.customer_id = c.customer_id
            WHERE r.reservation_id = ? AND r.hotel_id = ? AND r.status IN ('CONFIRMED', 'PENDING')";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $reservation_id, $hotel_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $reservation = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($reservation) {
        // Get available rooms
        $sql = "SELECT room_id, room_number FROM room
                WHERE hotel_id = ? AND room_type_id = ?
                  AND room_id NOT IN (
                      SELECT room_id FROM checkin
                      WHERE checkin_time >= ? AND room_id IS NOT NULL
                  )
                ORDER BY room_number";
        $today = date('Y-m-d') . ' 00:00:00';
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'iis', $hotel_id, $reservation['room_type_id'], $today);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $available_rooms = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    } else {
        $error = "Reservation not found or not valid for check-in.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_id'], $_POST['reservation_id'])) {
    $room_id = intval($_POST['room_id']);
    $reservation_id = intval($_POST['reservation_id']);
    $customer_id = intval($_POST['customer_id']);

    // Insert check-in record
    $now = date('Y-m-d H:i:s');
    $sql = "INSERT INTO checkin (reservation_id, room_id, customer_id, clerk_id, checkin_time)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'iiiis', $reservation_id, $room_id, $customer_id, $clerk_id, $now);
    $success = mysqli_stmt_execute($stmt) ? "Customer checked in successfully." : "Failed to check in.";
    mysqli_stmt_close($stmt);

    // Update reservation status
    mysqli_query($conn, "UPDATE reservation SET status = 'CHECKEDIN' WHERE reservation_id = $reservation_id");
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="container py-5">
    <h3 class="mb-4">🛎️ Check In</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($reservation): ?>
        <div class="card p-3 mb-4">
            <h5>Reservation #<?= $reservation['reservation_id'] ?></h5>
            <p><strong>Customer:</strong> <?= htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']) ?></p>
            <p><strong>Dates:</strong> <?= $reservation['arrival_date'] ?> to <?= $reservation['departure_date'] ?></p>
            <p><strong>Guests:</strong> <?= $reservation['num_guests'] ?></p>
        </div>

        <?php if (count($available_rooms) > 0): ?>
            <form method="POST" class="card p-4">
                <input type="hidden" name="reservation_id" value="<?= $reservation['reservation_id'] ?>">
                <input type="hidden" name="customer_id" value="<?= $reservation['customer_id'] ?>">
                <div class="mb-3">
                    <label class="form-label">Assign Room</label>
                    <select name="room_id" class="form-select" required>
                        <option value="">-- Select Room --</option>
                        <?php foreach ($available_rooms as $room): ?>
                            <option value="<?= $room['room_id'] ?>">Room <?= htmlspecialchars($room['room_number']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-success">Confirm Check-In</button>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">No rooms available in this type.</div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

