
<?php

$host = "localhost";
$dbname = "ecommerce1";
$username = "root";
$password = "";

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname",
        $username,
        $password
    );

    $conn->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch(PDOException $e) {
    error_log($e->getMessage());
    die("Database connection failed.");
}

?>
