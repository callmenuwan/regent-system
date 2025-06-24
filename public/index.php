<!-- 
Fetched hotels and room types from the database
Wired a jQuery AJAX call to check_availability.php
Displayed the availability result and added a “Proceed to Booking” link when available 
-->


<?php
// No auth required
include('../includes/db.php');

// Fetch hotels
$hotels = [];
$hRes = mysqli_query($conn, "SELECT hotel_id, name FROM Hotel ORDER BY name");
while ($h = mysqli_fetch_assoc($hRes)) {
    $hotels[] = $h;
}

// Fetch room types
$roomTypes = [];
$rtRes = mysqli_query($conn, "SELECT room_type_id, code, description FROM RoomType ORDER BY room_type_id");
while ($rt = mysqli_fetch_assoc($rtRes)) {
    $roomTypes[] = $rt;
}
?>

<?php include '../includes/header-public.php'; ?>

<!-- Book section -->
<div class="container" id="public-booking-page">
  <div class="row">
    <div class="col-md-12">

      <div class="booking-wrapper">
        <form id="availabilityForm" class="row g-3">
          <h2 class="mb-4 text-center page-title">Book Your Stay</h2><br><br>
          <div class="col-md-12">
            <label for="hotel" class="form-label">Select Hotel</label>
            <select id="hotel" name="hotel_id" class="form-select" required>
              <option value="">-- Choose Hotel --</option>
              <?php foreach ($hotels as $h): ?>
                <option value="<?= $h['hotel_id'] ?>"><?= htmlspecialchars($h['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label for="arrival" class="form-label">Arrival Date</label>
            <input type="date" id="arrival" name="arrival_date" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label for="departure" class="form-label">Departure Date</label>
            <input type="date" id="departure" name="departure_date" class="form-control" required>
          </div>

          <div class="col-md-6">
            <label for="room_type" class="form-label">Room Type</label>
            <select id="room_type" name="room_type_id" class="form-select" required>
              <option value="">-- Choose Type --</option>
              <?php foreach ($roomTypes as $rt): ?>
                <option value="<?= $rt['room_type_id'] ?>">
                  <?= htmlspecialchars($rt['code']) ?> — <?= htmlspecialchars($rt['description']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label for="guests" class="form-label">Guests</label>
            <input type="number" id="guests" name="num_guests" class="form-control" value="1" min="1" required>
          </div>
                
          <div class="col-md-12 align-self-end">
            <button id="checkBtn" type="button" class="btn btn-primary w-100">Check Availability</button>
          </div>
        </form>

        <div id="availabilityResult" class="mt-4"></div>
      </div>

    </div>
  </div>
</div>
<!-- End Book section -->


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<?php include '../includes/footer-public.php'; ?>