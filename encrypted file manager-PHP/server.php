<?php
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $msgData = json_decode($msg, true);

        $encryptedMessage = $msgData['content'];
        $iv = base64_decode($msgData['iv']);
        $sender_id = 1; // Change as needed for testing
        $receiver_id = 2; // Change as needed for testing

        $mysqli = new mysqli("localhost", "username", "password", "chat_app");
        if ($mysqli->connect_error) {
            error_log("Database connection failed: " . $mysqli->connect_error);
            return;
        }

        $stmt = $mysqli->prepare("INSERT INTO messages (sender_id, receiver_id, message, iv) VALUES (?, ?, ?, ?)");
        if ($stmt === false) {
            error_log("Prepare failed: " . $mysqli->error);
            return;
        }
        $stmt->bind_param("iiss", $sender_id, $receiver_id, $encryptedMessage, $iv);
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            return;
        }

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        error_log("Error: " . $e->getMessage());
        $conn->close();
    }
}

$server = Ratchet\App::factory(
    new Chat(),
    8080
);

$server->run();
