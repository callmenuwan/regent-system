<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['clerk_id'])) {
    header('Location: clerk_login.php');
    exit;
}

$error = '';
$invoice = null;
$billing = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = intval($_POST['reservation_id']);

    // Load reservation + customer + billing
    $sql = "SELECT r.*, c.first_name, c.last_name, c.email, c.phone
            FROM Reservation r
            JOIN Customer c ON r.customer_id = c.customer_id
            WHERE r.reservation_id = ? AND r.status = 'CHECKEDOUT'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $reservation_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $invoice = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$invoice) {
        $error = 'No checked-out reservation found.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM BillingRecord WHERE reservation_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $reservation_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $billing[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<?php include '../includes/header.php'; ?>
<div class="container py-5">
    <h3 class="mb-4">📄 Generate Invoice</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="mb-4 card p-4 shadow-sm">
        <label for="reservation_id" class="form-label">Reservation ID</label>
        <input type="number" name="reservation_id" id="reservation_id" class="form-control" required>
        <button type="submit" class="btn btn-primary mt-3">Generate Invoice</button>
    </form>

    <?php if ($invoice): ?>
        <div class="card shadow-sm p-4" id="invoice">
            <h4>Invoice for Reservation #<?= $invoice['reservation_id'] ?></h4>
            <p><strong>Customer:</strong> <?= htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($invoice['email']) ?> | <strong>Phone:</strong> <?= htmlspecialchars($invoice['phone']) ?></p>
            <p><strong>Stay:</strong> <?= $invoice['arrival_date'] ?> to <?= $invoice['departure_date'] ?></p>

            <hr>
            <h5>Charges</h5>
            <ul class="list-group mb-3">
                <?php $total = 0; ?>
                <?php foreach ($billing as $item): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= htmlspecialchars($item['description']) ?></span>
                        <span>$<?= number_format($item['amount'], 2) ?></span>
                    </li>
                    <?php $total += $item['amount']; ?>
                <?php endforeach; ?>
                <li class="list-group-item d-flex justify-content-between fw-bold">
                    <span>Total</span>
                    <span>$<?= number_format($total, 2) ?></span>
                </li>
            </ul>
            <button class="btn btn-secondary" onclick="window.print()">🖨️ Print</button>
        </div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>