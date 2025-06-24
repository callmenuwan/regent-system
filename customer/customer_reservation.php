<?php
require_once '../includes/db.php';

$reservation = null;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'lookup') {
        // Lookup reservation
        $reservation_id = intval($_POST['reservation_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');

        if ($reservation_id && $email) {
            $sql = "SELECT r.*, c.first_name, c.last_name, c.email, c.phone, h.name AS hotel_name, rt.description AS room_type
                    FROM Reservation r
                    JOIN Customer c ON r.customer_id = c.customer_id
                    JOIN Hotel h ON r.hotel_id = h.hotel_id
                    JOIN RoomType rt ON r.room_type_id = rt.room_type_id
                    WHERE r.reservation_id = ? AND c.email = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'is', $reservation_id, $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $reservation = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$reservation) {
                $error = "Reservation not found or email does not match.";
            }
        } else {
            $error = "Both fields are required.";
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        // Update reservation
        $reservation_id = intval($_POST['reservation_id']);
        $email = trim($_POST['email']);
        $arrival_date = $_POST['arrival_date'];
        $departure_date = $_POST['departure_date'];
        $num_guests = intval($_POST['num_guests']);

        if ($arrival_date >= $departure_date) {
            $error = "Departure date must be after arrival date.";
        } else {
            $sql = "UPDATE Reservation r
                    JOIN Customer c ON r.customer_id = c.cu.
                    .0stomer_id
                    SET r.arrival_date = ?, r.departure_date = ?, r.num_guests = ?
                    WHERE r.reservation_id = ? AND c.email = ? AND r.status IN ('PENDING', 'CONFIRMED')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'ssiis', $arrival_date, $departure_date, $num_guests, $reservation_id, $email);
            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $success = "Reservation updated successfully.";

                // Reload updated reservation
                $sql = "SELECT r.*, c.first_name, c.last_name, c.email, c.phone, h.name AS hotel_name, rt.description AS room_type
                        FROM Reservation r
                        JOIN Customer c ON r.customer_id = c.customer_id
                        JOIN Hotel h ON r.hotel_id = h.hotel_id
                        JOIN RoomType rt ON r.room_type_id = rt.room_type_id
                        WHERE r.reservation_id = ? AND c.email = ?";
                $stmt2 = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt2, 'is', $reservation_id, $email);
                mysqli_stmt_execute($stmt2);
                $result = mysqli_stmt_get_result($stmt2);
                $reservation = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt2);
            } else {
                $error = "Failed to update reservation or no changes made.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>


<?php include '../includes/header-public.php'; ?>

<div class="booking-wrapper">
    <div class="container">
        <h2 class="mb-4 text-center page-title">🔎 Manage Your Reservation</h2>

        <form method="POST" class="card p-4 shadow-sm mb-4">
            <input type="hidden" name="action" value="lookup">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php elseif ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="reservation_id" class="form-label">Reservation ID</label>
                <input type="number" name="reservation_id" id="reservation_id" class="form-control" required value="<?= htmlspecialchars($_POST['reservation_id'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email used during booking</label>
                <input type="email" name="email" id="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary">View Reservation</button>

        </form>

        <?php if ($reservation): ?>
            <a href="customer_cancel.php?reservation_id=<?= $reservation['reservation_id'] ?>&email=<?= htmlspecialchars($reservation['email']) ?>">Cancel Reservation</a>
            <div class="card p-4 shadow-sm">
                <h5>Reservation #<?= $reservation['reservation_id'] ?></h5>
                <p><strong>Name:</strong> <?= htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($reservation['email']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($reservation['phone']) ?></p>
                <p><strong>Hotel:</strong> <?= htmlspecialchars($reservation['hotel_name']) ?></p>
                <p><strong>Room Type:</strong> <?= htmlspecialchars($reservation['room_type']) ?></p>
                <p><strong>Status:</strong> <?= $reservation['status'] ?></p>

                <?php if (in_array($reservation['status'], ['PENDING', 'CONFIRMED'])): ?>
                    <form method="POST" class="mt-4">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="reservation_id" value="<?= $reservation['reservation_id'] ?>">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($reservation['email']) ?>">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Arrival Date</label>
                                <input type="date" name="arrival_date" class="form-control" required value="<?= $reservation['arrival_date'] ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Departure Date</label>
                                <input type="date" name="departure_date" class="form-control" required value="<?= $reservation['departure_date'] ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Guests</label>
                                <input type="number" name="num_guests" min="1" class="form-control" required value="<?= $reservation['num_guests'] ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success mt-3">💾 Save Changes</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer-public.php'; ?>