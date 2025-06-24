<?php
include('../includes/db.php');

$sql = "SELECT * FROM hotel LIMIT 1";
$result = mysqli_query($conn, $sql);

if ($result) {
    echo "✅ Database connected. Hotel count: " . mysqli_num_rows($result);
} else {
    echo "❌ Query failed: " . mysqli_error($conn);
}
?>
