<?php
ob_start();
session_start();
include 'db.php';

$admin_emails = [
    'admin@drivehub.lb',
    'manager@drivehub.lb',
    'superadmin@drivehub.lb'
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        ob_end_clean();
        echo "<script>alert('Please fill in all fields.'); window.history.back();</script>";
        exit();
    }

    $sql  = "SELECT id, first_name, last_name, email, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        ob_end_clean();
        die("Database prepare error: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['email']      = $user['email'];

            if (in_array(strtolower(trim($email)), array_map('strtolower', $admin_emails))) {
                $_SESSION['role'] = 'admin';
                ob_end_clean();
                header("Location: admin.php");
                exit();
            } else {
                $_SESSION['role'] = 'user';
                ob_end_clean();
                header("Location: home.php");
                exit();
            }

        } else {
            ob_end_clean();
            echo "<script>alert('Incorrect password.'); window.history.back();</script>";
        }
    } else {
        ob_end_clean();
        echo "<script>alert('No account found with that email.'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
}

ob_end_flush();
?>