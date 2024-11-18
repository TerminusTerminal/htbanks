<?php
ob_start();
header('Content-Type: application/json');
ini_set('display_errors', 0); // Set to 1 for debugging
ini_set('log_errors', 1);
error_reporting(E_ALL);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Database connection settings
$host = 'localhost';
$user = 'root';
$password = '1234';
$database = 'simple_payments';
$port = 3308;

// Handle OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Connect to the database
$conn = new mysqli($host, $user, $password, $database, $port);
if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}

// Get the action parameter
$action = $_POST['action'] ?? $_GET['action'] ?? null;
if (!$action) {
    echo json_encode(["error" => "Action parameter is required."]);
    exit;
}

switch ($action) {
    case 'create_account':
        $holderName = $_POST['holderName'] ?? null;
        $bankName = $_POST['bankName'] ?? null;
        $accountNumber = $_POST['accountNumber'] ?? null;
        $password = $_POST['password'] ?? null;

        if (!$holderName || !$bankName || !$accountNumber || !$password) {
            echo json_encode(["error" => "All fields are required!"]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO bank_accounts (account_holder_name, bank_name, account_number, password, balance) VALUES (?, ?, ?, ?, 0)");
        $stmt->bind_param("ssss", $holderName, $bankName, $accountNumber, $password);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Account created successfully!"]);
        } else {
            echo json_encode(["error" => "Error creating account: " . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'view_balance':
        $accountNumber = $_POST['accountNumber'] ?? null;
        $password = $_POST['password'] ?? null;

        if (!$accountNumber || !$password) {
            echo json_encode(["error" => "Account number and password are required."]);
            exit;
        }

        $stmt = $conn->prepare("SELECT balance FROM bank_accounts WHERE account_number = ? AND password = ?");
        $stmt->bind_param("ss", $accountNumber, $password);
        $stmt->execute();
        $stmt->bind_result($balance);

        if ($stmt->fetch()) {
            echo json_encode(["balance" => $balance]);
        } else {
            echo json_encode(["error" => "Invalid account number or password."]);
        }
        $stmt->close();
        break;

    case 'deposit':
        $accountNumber = $_POST['accountNumber'] ?? null;
        $amount = $_POST['amount'] ?? null;
        $password = $_POST['password'] ?? null;

        if (!$accountNumber || !$amount || !$password) {
            echo json_encode(["error" => "Account number, password, and amount are required."]);
            exit;
        }

        $stmt = $conn->prepare("UPDATE bank_accounts SET balance = balance + ? WHERE account_number = ? AND password = ?");
        $stmt->bind_param("dss", $amount, $accountNumber, $password);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(["message" => "Deposit successful!"]);
        } else {
            echo json_encode(["error" => "Deposit failed. Check account number and password."]);
        }
        $stmt->close();
        break;

    case 'login':
        $accountNumber = $_POST['accountNumber'] ?? null;
        $password = $_POST['password'] ?? null;

        if (!$accountNumber || !$password) {
            echo json_encode(["error" => "Account number and password are required."]);
            exit;
        }

        $stmt = $conn->prepare("SELECT * FROM bank_accounts WHERE account_number = ? AND password = ?");
        $stmt->bind_param("ss", $accountNumber, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo json_encode(["success" => true, "message" => "Login successful!"]);
        } else {
            echo json_encode(["error" => "Invalid account number or password."]);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(["error" => "Invalid action."]);
        break;
}

$conn->close();
ob_end_flush();

