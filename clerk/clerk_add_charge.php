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

// Load charge types
$charge_types = [];
$res = mysqli_query($conn, "SELECT * FROM ServiceChargeType ORDER BY code");
while ($row = mysqli_fetch_assoc($res)) {
    $charge_types[] = $row;
}

// Load available reservations with CHECKEDIN status
$reservations = [];
$res = mysqli_query($conn, "
    SELECT r.reservation_id, c.first_name, c.last_name
    FROM Reservation r
    JOIN Customer c ON r.customer_id = c.customer_id
    WHERE r.status = 'CHECKEDIN' AND r.hotel_id = $hotel_id
");
while ($row = mysqli_fetch_assoc($res)) {
    $reservations[] = $row;
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = intval($_POST['reservation_id']);
    $service_type_id = intval($_POST['service_type_id']);
    $quantity = intval($_POST['quantity']);
    $unit_price = floatval($_POST['unit_price']);
    $now = date('Y-m-d H:i:s');

    // Find or create billing record
    $check = mysqli_query($conn, "SELECT checkin_id FROM CheckIn WHERE reservation_id = $reservation_id");
    $checkin = mysqli_fetch_assoc($check);
    $checkin_id = $checkin ? $checkin['checkin_id'] : null;

    if (!$checkin_id) {
        $error = 'No check-in found for this reservation.';
    } else {
        // Create new billing record if needed
        $sql = "INSERT INTO BillingRecord (reservation_id, checkin_id, amount, description, created_at)
                VALUES (?, ?, 0, 'Service charges', ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'iis', $reservation_id, $checkin_id, $now);
        mysqli_stmt_execute($stmt);
        $billing_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // Add charge to ReservationServiceCharge
        $sql = "INSERT INTO ReservationServiceCharge (billing_id, service_type_id, quantity, unit_price)
                VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'iiid', $billing_id, $service_type_id, $quantity, $unit_price);
        $stmt->execute();
        mysqli_stmt_close($stmt);

        // Update billing total
        $total_charge = $quantity * $unit_price;
        mysqli_query($conn, "UPDATE BillingRecord SET amount = amount + $total_charge WHERE billing_id = $billing_id");

        $success = 'Service charge added successfully.';
    }
}
?>

<?php include '../includes/header.php'; ?>
<div class="container py-5">
    <h3 class="mb-4">🧾 Add Service Charge</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" class="card p-4 shadow-sm">
        <div class="mb-3">
            <label class="form-label">Reservation</label>
            <select name="reservation_id" class="form-select" required>
                <option value="">-- Select Reservation --</option>
                <?php foreach ($reservations as $res): ?>
                    <option value="<?= $res['reservation_id'] ?>">#<?= $res['reservation_id'] ?> - <?= htmlspecialchars($res['first_name'] . ' ' . $res['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Charge Type</label>
            <select name="service_type_id" class="form-select" required>
                <option value="">-- Select Type --</option>
                <?php foreach ($charge_types as $type): ?>
                    <option value="<?= $type['service_type_id'] ?>"><?= htmlspecialchars($type['description']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>Quantity</label>
                <input type="number" name="quantity" class="form-control" min="1" required>
            </div>
            <div class="col-md-4 mb-3">
                <label>Unit Price</label>
                <input type="number" step="0.01" name="unit_price" class="form-control" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Add Charge</button>
    </form>
</div>
<?php include '../includes/footer.php'; ?>
