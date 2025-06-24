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
<script>
$(function(){
  $('#checkBtn').on('click', function(){
    const data = $('#availabilityForm').serialize();
    $('#availabilityResult').html('<div class="alert alert-info">Checking...</div>');

    $.ajax({
      url: 'check_availability.php',
      method: 'GET',
      data: data,
      dataType: 'text'    // <-- change here
    })
    .done(function(textResp) {
        console.log('RAW RESPONSE:', textResp);

        // Find the first '{' and strip any garbage before it
        const idx = textResp.indexOf('{');
        if (idx === -1) {
            return $('#availabilityResult').html(
            `<div class="alert alert-danger">
                Invalid server response:<br><pre>${textResp}</pre>
            </div>`
            );
        }
        const jsonText = textResp.slice(idx);

        let resp;
        try {
            resp = JSON.parse(jsonText);
        } catch (e) {
            return $('#availabilityResult').html(
            `<div class="alert alert-danger">
                JSON parse error:<br><pre>${jsonText}</pre>
            </div>`
            );
        }

        // Now the rest stays the same...
        if (resp.error) {
            return $('#availabilityResult').html(
            `<div class="alert alert-danger">${resp.error}</div>`
            );
        }
        if (resp.available > 0) {
            $('#availabilityResult').html(
            `<div class="alert alert-success">
                Available rooms: ${resp.available}
            </div>
            <a href="book.php?hotel_id=${resp.hotel_id}
                &room_type_id=${resp.room_type_id}
                &arrival_date=${resp.arrival_date}
                &departure_date=${resp.departure_date}
                &num_guests=${resp.num_guests}"
                class="btn btn-success mt-2">Proceed to Reservation</a>`
            );
        } else {
            $('#availabilityResult').html(
            `<div class="alert alert-danger">No rooms available for your selection.</div>`
            );
        }
        })

    .fail(function(xhr, status, error) {
      console.error('AJAX ERROR', status, error, xhr.responseText);
      $('#availabilityResult').html(
        `<div class="alert alert-danger">
           AJAX request failed: ${status}<br>
           See console for details.
         </div>`
      );
    });
  });
});
</script>

<?php include '../includes/footer-public.php'; ?>