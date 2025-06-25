<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['clerk_id'])) {
    header('Location: clerk_login.php');
    exit;
}

$hotel_id = $_SESSION['hotel_id'];
$clerk_id = $_SESSION['clerk_id'];
$error = '';
$success = '';
$checkout_info = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = intval($_POST['reservation_id']);
    $payment_method = $_POST['payment_method'] ?? '';
    $now = date('Y-m-d H:i:s');

    // Load check-in info (only for checked-in)
    $sql = "SELECT ci.checkin_id, ci.checkin_time, r.departure_date, r.customer_id, r.room_type_id, r.status,
                   rt.description AS room_type_desc
            FROM CheckIn ci
            JOIN Reservation r ON ci.reservation_id = r.reservation_id
            JOIN RoomType rt ON r.room_type_id = rt.room_type_id
            WHERE r.reservation_id = ? AND r.hotel_id = ? AND r.status = 'CHECKEDIN'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $reservation_id, $hotel_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $checkout_info = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$checkout_info) {
        $error = 'No active check-in found for this reservation.';
    } else {
        // Calculate charges
        $departure_date = $checkout_info['departure_date'];
        $today_date = date('Y-m-d');

        $late_fee = 0;
        if ($today_date > $departure_date) {
            // Charge for additional night
            // For simplicity, assume flat rate $100 per night (replace with real rate)
            $late_fee = 100;
        }

        // Calculate base amount (for demo, say $100 per night * number of nights)
        $nights = (strtotime($departure_date) - strtotime($checkout_info['checkin_time'])) / 86400;
        if ($nights < 1) $nights = 1;
        $base_amount = 100 * $nights;

        $total_amount = $base_amount + $late_fee;

        // Insert BillingRecord
        $description = 'Room charge for ' . $nights . ' night(s)';
        if ($late_fee > 0) {
            $description .= ' + late checkout fee';
        }
        $stmt = mysqli_prepare($conn, "INSERT INTO BillingRecord (reservation_id, checkin_id, amount, description, created_at)
                                       VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'iidss', $reservation_id, $checkout_info['checkin_id'], $total_amount, $description, $now);
        $stmt->execute();
        $billing_id = $stmt->insert_id;
        $stmt->close();

        // Insert payment method info if credit card (optional - can expand)
        if ($payment_method === 'credit_card') {
            // For simplicity, no card data collected here
        }

        // Insert checkout record
        $stmt = mysqli_prepare($conn, "INSERT INTO CheckOut (checkin_id, checkout_time, clerk_id, late_fee_applied)
                                       VALUES (?, ?, ?, ?)");
        $late_fee_applied = $late_fee > 0 ? 1 : 0;
        mysqli_stmt_bind_param($stmt, 'isis', $checkout_info['checkin_id'], $now, $clerk_id, $late_fee_applied);
        $stmt->execute();
        $stmt->close();

        // Update reservation status
        mysqli_query($conn, "UPDATE Reservation SET status='CHECKEDOUT' WHERE reservation_id=$reservation_id");

        $success = 'Checkout successful. Total amount: $' . number_format($total_amount, 2);
        $checkout_info = null;
    }
}

?>

<?php include '../includes/header.php'; ?>
<div class="container py-5">
    <h3 class="mb-4">✅ Check Out</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" class="mb-4 card p-4 shadow-sm">
        <div class="mb-3">
            <label for="reservation_id" class="form-label">Reservation ID</label>
            <input type="number" name="reservation_id" id="reservation_id" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="payment_method" class="form-label">Payment Method</label>
            <select name="payment_method" id="payment_method" class="form-select" required>
                <option value="">-- Select Payment --</option>
                <option value="cash">Cash</option>
                <option value="credit_card">Credit Card</option>
            </select>
        </div>
        <button type="submit" class="btn btn-danger">Process Checkout</button>
    </form>

</div>
<?php include '../includes/footer.php'; ?>
