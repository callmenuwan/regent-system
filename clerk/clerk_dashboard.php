<!-- Session check (redirect to login if not logged in)

Shows clerk name and hotel

Simple menu with links to:

Search Reservation

Create Walk-In

Check-In Guest

Update Checkout Date

Check-Out

Add Optional Charges

View/Print Invoice -->

<?php
session_start();
if (!isset($_SESSION['clerk_id'])) {
    header('Location: clerk_login.php');
    exit;
}
$clerk_name = $_SESSION['clerk_name'];
$hotel_id = $_SESSION['hotel_id'];
?>

<?php include '../includes/header.php'; ?>
<div class="container py-5">
    <h3 class="mb-4 text-center">Welcome, <?= htmlspecialchars($clerk_name) ?>!</h3>

    <div class="row row-cols-1 row-cols-md-2 g-4 mt-4">
        <div class="col">
            <a href="/regent/clerk/clerk_find_reservation.php" class="btn btn-outline-primary w-100 p-3">🔍 Find Reservation</a>
        </div>
        <div class="col">
            <a href="/regent/clerk/clerk_walkin.php" class="btn btn-outline-success w-100 p-3">🏨 Walk-in Reservation</a>
        </div>
        <div class="col">
            <a href="/regent/clerk/clerk_pending_reservations.php" class="btn btn-outline-info w-100 p-3">🛎️ Pending Reservations</a>
        </div>
        <div class="col">
            <a href="/regent/clerk/clerk_update_checkout.php" class="btn btn-outline-warning w-100 p-3">📝 Update Checkout Date</a>
        </div>
        <div class="col">
            <a href="/regent/clerk/clerk_checkout.php" class="btn btn-outline-danger w-100 p-3">✅ Check Out</a>
        </div>
        <div class="col">
            <a href="/regent/clerk/clerk_add_charge.php" class="btn btn-outline-secondary w-100 p-3">💰 Add Optional Charges</a>
        </div>
        <div class="col">
            <a href="/regent/clerk/clerk_invoice.php" class="btn btn-outline-dark w-100 p-3">🧾 View Invoice</a>
        </div>
        <div class="col">
            <a href="/regent/clerk/block_booking.php" class="btn btn-outline-secondary w-100 p-3">🚪 Travel Company Bookings</a>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
