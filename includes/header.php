<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" href="/regent/images/favicon.png" type="image/x-icon" />
    <title>Regent Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="/regent/css/portal-styles.css" />
  </head>
  <body>

    <!-- Portal nav for clerks -->
    <?php  if (isset($_SESSION['clerk_id'])) { ?>

      <nav class="p-3 mb-3 border-bottom">
      <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
          <h5 class="portal-logo">Regent Portal</h5>
          <ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
            <li>
              <a href="/regent/clerk/clerk_dashboard.php" class="nav-link px-2">[ Home ]</a>
            </li>
            <li>
              <a href="/regent/clerk/clerk_find_reservation.php" class="nav-link px-2">[ Check In ]</a>
            </li>
            <li>
              <a href="/regent/clerk/clerk_checkout.php" class="nav-link px-2">[ Check Out ]</a>
            </li>
            <li>
              <a href="/regent/clerk/clerk_find_reservation.php" class="nav-link px-2">[ Find Reservation ]</a>
            </li>
            <li>
              <a href="/regent/clerk/clerk_walkin.php" class="nav-link px-2">[ Walk-in Reservation ]</a>
            </li>
          </ul>
          <a href="../logout.php" class="btn btn-primary">Logout</a>
        </div>
      </div>
    </nav>

    <?php  } ?>

    <!-- Portal nav for managers -->
    <?php  if (isset($_SESSION['manager_id'])) { ?>

      <nav class="p-3 mb-3 border-bottom">
      <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
          <h5 class="portal-logo">Regent Portal</h5>
          <ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
            <li>
              <a href="/regent/manager/manager_dashboard.php" class="nav-link px-2">[ Dashboard ]</a>
            </li>
          </ul>
          <a href="../logout.php" class="btn btn-primary">Logout</a>
        </div>
      </div>
    </nav>

    <?php  } ?>