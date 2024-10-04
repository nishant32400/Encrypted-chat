<?php
// Database connection
$mysqli = new mysqli("localhost", "username", "password", "chat_app");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $mysqli->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $password);

    if ($stmt->execute()) {
        echo "Registration successful!";
    } else {
        echo "Registration failed: " . $stmt->error;
    }
}
?>

<form method="POST">
    Username: <input type="text" name="username" required>
    Password: <input type="password" name="password" required>
    <button type="submit">Register</button>
</form>
