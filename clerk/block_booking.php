<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['clerk_id'])) {
    header('Location: clerk_login.php');
    exit;
}

// Fetch travel companies, hotels, room types
$companies = mysqli_query($conn, "SELECT * FROM TravelCompany");
$hotels    = mysqli_query($conn, "SELECT hotel_id, name FROM Hotel");
$roomTypes = mysqli_query($conn, "SELECT room_type_id, description, rt.code
                                   FROM RoomType rt
                                   WHERE rt.is_residential = 0"); // only regular rooms

?>

<?php include '../includes/header.php'; ?>
<div class="container py-5">
  <h3>🔒 Block Booking (Travel Company)</h3>
  <form method="POST" action="block_booking_handler.php" class="card p-4">
    <div class="mb-3">
      <label class="form-label">Travel Company</label>
      <select name="company_id" class="form-select" required>
        <option value="">-- Select Company --</option>
        <?php while ($c = mysqli_fetch_assoc($companies)): ?>
          <option value="<?=$c['company_id']?>">
            <?=htmlspecialchars($c['name'])?> (<?= $c['discount_rate'] ?>% off)
          </option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="mb-3 row">
      <div class="col-md-6">
        <label class="form-label">Hotel</label>
        <select name="hotel_id" class="form-select" required>
          <option value="">-- Select Hotel --</option>
          <?php while ($h = mysqli_fetch_assoc($hotels)): ?>
            <option value="<?=$h['hotel_id']?>"><?=htmlspecialchars($h['name'])?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Room Type</label>
        <select name="room_type_id" class="form-select" required>
          <option value="">-- Select Room Type --</option>
          <?php while ($rt = mysqli_fetch_assoc($roomTypes)): ?>
            <option value="<?=$rt['room_type_id']?>"><?=htmlspecialchars($rt['code'].' — '.$rt['description'])?></option>
          <?php endwhile; ?>
        </select>
      </div>
    </div>

    <div class="mb-3 row">
      <div class="col-md-6">
        <label class="form-label">Arrival Date</label>
        <input type="date" name="arrival_date" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Departure Date</label>
        <input type="date" name="departure_date" class="form-control" required>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Number of Rooms (min 3)</label>
      <input type="number" name="num_rooms" class="form-control" min="3" required>
    </div>

    <button type="submit" class="btn btn-success">Create Block Booking</button>
  </form>
</div>
<?php include '../includes/footer.php'; ?>
