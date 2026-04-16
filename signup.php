<?php
include 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match. Please try again.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email already exists. Please try again.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $first_name, $last_name, $email, $hashed_password);

            if ($stmt->execute()) {
                header("Location: login.php");
                exit();
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<?php if (!empty($error)): ?>
    <div class="alert-message">
        <p><?php echo htmlspecialchars($error); ?></p>
    </div>
<?php endif; ?>