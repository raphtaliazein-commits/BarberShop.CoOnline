<?php

/*
|--------------------------------------------------------------------------
| BARBERSHOP.CO DATABASE CONNECTION
|--------------------------------------------------------------------------
| Directly uses Railway Public MySQL Connection credentials to guarantee
| connection without relying on runtime environment variable mapping.
|--------------------------------------------------------------------------
*/

// Railway MySQL Credentials
$databaseServer   = getenv('MYSQLHOST')     ?: "turntable.proxy.rlwy.net";
$databasePort     = (int)(getenv('MYSQLPORT') ?: 43174);
$databaseUsername = getenv('MYSQLUSER')     ?: "root";
$databasePassword = getenv('MYSQLPASSWORD') ?: "BLRUdpzCNwXhmkwBFwcNsQTwPOFFZhPI";
$databaseName     = getenv('MYSQLDATABASE') ?: "railway";

// Create MySQL connection
$databaseConnection = mysqli_connect(
    $databaseServer,
    $databaseUsername,
    $databasePassword,
    $databaseName,
    $databasePort
);

// Check if connection was successful
if (!$databaseConnection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Set character encoding
mysqli_set_charset($databaseConnection, "utf8mb4");

?>