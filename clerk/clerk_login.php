<?php
session_start();
require_once '../includes/db.php';

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        // Only allow users with 'clerk' role
        $sql = "
            SELECT s.staff_id, s.first_name, s.last_name, s.password_hash, s.hotel_id
            FROM Staff s
            JOIN Role r ON s.role_id = r.role_id
            WHERE r.code = 'clerk' AND s.email = ?
            LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $staff_id, $first_name, $last_name, $password_hash, $hotel_id);

        if (mysqli_stmt_fetch($stmt)) {
            if (password_verify($password, $password_hash)) {
                // Login success
                $_SESSION['clerk_id'] = $staff_id;
                $_SESSION['clerk_name'] = $first_name . ' ' . $last_name;
                $_SESSION['hotel_id'] = $hotel_id;
                header('Location: clerk_dashboard.php');
                exit;
            } else {
                $login_error = 'Invalid password.';
            }
        } else {
            $login_error = 'Clerk not found.';
        }
        mysqli_stmt_close($stmt);
    } else {
        $login_error = 'Please enter both email and password.';
    }
}
?>

<?php include '../includes/header.php'; ?>
<div class="container mt-5" style="max-width: 500px;">
    <h3 class="mb-4 text-center">Clerk Login</h3>

    <?php if ($login_error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($login_error) ?></div>
    <?php endif; ?>

    <form method="POST" class="card p-4 shadow-sm">
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input name="email" id="email" type="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input name="password" id="password" type="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
</div>
<?php include '../includes/footer.php'; ?>