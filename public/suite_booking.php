<?php
require_once '../includes/db.php';
include '../includes/header.php';

// Fetch hotels
$hotels = mysqli_query($conn, "SELECT hotel_id, name FROM Hotel");

// Fetch residential suite types only
$suites = mysqli_query($conn, "
  SELECT room_type_id, description, weekly_rate, monthly_rate 
  FROM RoomType 
  WHERE is_residential = 1
");
?>

<div class="container mt-5">
  <h2>Book a Residential Suite</h2>
  <form method="post" action="suite_booking_handler.php">

    <div class="mb-3">
      <label for="hotel_id" class="form-label">Hotel</label>
      <select name="hotel_id" id="hotel_id" class="form-select" required>
        <?php while ($hotel = mysqli_fetch_assoc($hotels)): ?>
          <option value="<?= $hotel['hotel_id'] ?>"><?= htmlspecialchars($hotel['name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="mb-3">
      <label for="room_type_id" class="form-label">Suite Type</label>
      <select name="room_type_id" id="room_type_id" class="form-select" required>
        <?php while ($suite = mysqli_fetch_assoc($suites)): ?>
          <option value="<?= $suite['room_type_id'] ?>">
            <?= htmlspecialchars($suite['description']) ?> — Weekly: $<?= $suite['weekly_rate'] ?> / Monthly: $<?= $suite['monthly_rate'] ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="mb-3">
      <label for="arrival_date" class="form-label">Arrival Date</label>
      <input type="date" name="arrival_date" id="arrival_date" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Duration Type</label>
      <select name="duration_type" class="form-select" required>
        <option value="weeks">Weeks</option>
        <option value="months">Months</option>
      </select>
    </div>

    <div class="mb-3">
      <label for="duration" class="form-label">Number of Weeks/Months</label>
      <input type="number" name="duration" id="duration" class="form-control" min="1" required>
    </div>

    <hr>
    <h5>Customer Information</h5>

    <div class="mb-3">
      <label for="first_name" class="form-label">First Name</label>
      <input type="text" name="first_name" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="last_name" class="form-label">Last Name</label>
      <input type="text" name="last_name" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="email" class="form-label">Email</label>
      <input type="email" name="email" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="phone" class="form-label">Phone</label>
      <input type="text" name="phone" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="num_guests" class="form-label">Number of Guests</label>
      <input type="number" name="num_guests" class="form-control" min="1" required>
    </div>

    <button type="submit" class="btn btn-primary">Submit Reservation</button>
  </form>
</div>

<?php include '../includes/footer.php'; ?>
