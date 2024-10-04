<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mysqli = new mysqli("localhost", "username", "password", "chat_app");

$receiver_id = 2; // Change as needed for testing

// Fetch messages
$stmt = $mysqli->prepare("SELECT message, iv FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
$stmt->bind_param("iiii", $_SESSION['user_id'], $receiver_id, $receiver_id, $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($encrypted_message, $iv);

$messages = [];
while ($stmt->fetch()) {
    $messages[] = [
        'message' => openssl_decrypt($encrypted_message, 'aes-256-cbc', 'secretKey', 0, $iv),
        'iv' => base64_encode($iv)
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Chat Application</title>
    <script>
        var messages = <?php echo json_encode($messages); ?>;
        window.onload = function() {
            for (var i = 0; i < messages.length; i++) {
                document.getElementById('messages').innerHTML += '<p>' + messages[i].message + '</p>';
            }
        };

        var ws = new WebSocket('ws://localhost:8080');
        ws.onmessage = function(event) {
            var message = JSON.parse(event.data);
            var decryptedMessage = decryptMessage(message.content, message.iv);
            document.getElementById('messages').innerHTML += '<p>' + decryptedMessage + '</p>';
        };

        function encryptMessage(message, key, iv) {
            return CryptoJS.AES.encrypt(message, key, { iv: CryptoJS.enc.Base64.parse(iv) }).toString();
        }

        function decryptMessage(message, iv) {
            return CryptoJS.AES.decrypt(message, 'secretKey', { iv: CryptoJS.enc.Base64.parse(iv) }).toString(CryptoJS.enc.Utf8);
        }

        function sendMessage() {
            var message = document.getElementById('message').value;
            var iv = CryptoJS.lib.WordArray.random(16).toString(CryptoJS.enc.Base64);
            var encryptedMessage = encryptMessage(message, 'secretKey', iv);
            ws.send(JSON.stringify({ content: encryptedMessage, iv: iv }));
            document.getElementById('message').value = '';
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.0.0/crypto-js.min.js"></script>
</head>
<body>
    <h1>Chat Application</h1>
    <div id="messages"></div>
    <input type="text" id="message" placeholder="Type a message">
    <button onclick="sendMessage()">Send</button>
</body>
</html>
