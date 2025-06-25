<!-- 
Fetched hotels and room types from the database
Wired a jQuery AJAX call to check_availability.php
Displayed the availability result and added a “Proceed to Booking” link when available 
-->

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>


<?php
// No auth required
include __DIR__ . '/includes/db.php';

// Fetch hotels
$hotels = [];
$hRes = mysqli_query($conn, "SELECT hotel_id, name FROM hotel ORDER BY name");
while ($h = mysqli_fetch_assoc($hRes)) {
    $hotels[] = $h;
}

// Fetch room types
$roomTypes = [];
$rtRes = mysqli_query($conn, "SELECT room_type_id, code, description FROM roomtype ORDER BY room_type_id");
while ($rt = mysqli_fetch_assoc($rtRes)) {
    $roomTypes[] = $rt;
}
?>

<?php include __DIR__ . '/includes/header-public.php'; ?>

<!-- Header section -->
<div id="home-hero-section">
  <div class="container">
    <section id="home-hero-section" class="py-5 text-center container">
      <div class="row py-lg-5">
        <div class="col-lg-6 col-md-8 mx-auto">
          <h1 class="fw-light">Your Holiday with Regent Hotels</h1>
          <p class="lead">Something short and leading about the collection below—its contents, the creator, etc. Make it short and sweet, but not too short so folks don’t simply skip over it entirely.</p>
          <p>
            <a href="/public/index.php" class="btn btn-primary my-2">Book Now</a>
          </p>
        </div>
      </div>
    </section>
  </div>
</div>
<!-- End Header section -->

<!-- Album section -->
<div class="album py-5 bg-body-tertiary">
  <div class="album py-5 bg-body-tertiary">
    <div class="container">
      <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">
        <!-- Regent Beach - Galle -->
        <div class="col">
          <div class="card shadow-sm">
            <img src="/images/hotel-A.jpg" alt="">
            <div class="card-body">
              <h4>Regent Beach - Galle</h4>
              <p class="card-text">This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p>
              <div class="d-flex justify-content-between align-items-center">
                <div class="btn-group">
                  <button type="button" class="btn btn-outline-secondary">Book Now</button>
                </div>
                <small class="text-body-secondary">9 mins</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Queens Castle - Kandy -->
        <div class="col">
          <div class="card shadow-sm">
            <img src="/images/hotel-B.jpg" alt="">
            <div class="card-body">
              <h4>Queens Castle - Kandy</h4>
              <p class="card-text">This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p>
              <div class="d-flex justify-content-between align-items-center">
                <div class="btn-group">
                  <button type="button" class="btn btn-outline-secondary">Book Now</button>
                </div>
                <small class="text-body-secondary">9 mins</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Regent Ella - Bandarawela -->
        <div class="col">
          <div class="card shadow-sm">
            <img src="/images/hotel-C.jpg" alt="">
            <div class="card-body">
              <h4>Regent Ella - Bandarawela</h4>
              <p class="card-text">This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p>
              <div class="d-flex justify-content-between align-items-center">
                <div class="btn-group">
                  <button type="button" class="btn btn-outline-secondary">Book Now</button>
                </div>
                <small class="text-body-secondary">9 mins</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- End Album section -->


<?php include __DIR__ . '/includes/footer-public.php'; ?>
