<?php
session_start();
include('../includes/db.php');

$email = trim($_POST['email']);
$password = $_POST['password'];

$sql = "SELECT * FROM Staff WHERE email = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    if (password_verify($password, $row['password_hash'])) {
        $_SESSION['staff_id'] = $row['staff_id'];
        $_SESSION['hotel_id'] = $row['hotel_id'];
        $_SESSION['role_id'] = $row['role_id'];
        $_SESSION['staff_name'] = $row['first_name'];

        header("Location: dashboard.php");
        exit;
    }
}

header("Location: login.php?error=Invalid email or password");
exit;
?>
