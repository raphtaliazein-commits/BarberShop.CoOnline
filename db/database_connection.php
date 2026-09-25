<?php

/*
|--------------------------------------------------------------------------
| BARBERSHOP.CO DATABASE CONNECTION
|--------------------------------------------------------------------------
| Connects using Railway Private Networking for internal speed & stability.
|--------------------------------------------------------------------------
*/

// Set connection timeout to 5 seconds to prevent 'Application failed to respond'
ini_set('default_socket_timeout', 5);
mysqli_report(MYSQLI_REPORT_OFF);

// Railway Private Internal Details
$databaseServer   = getenv('MYSQLHOST')     ?: "mysql.railway.internal";
$databasePort     = (int)(getenv('MYSQLPORT') ?: 3306);
$databaseUsername = getenv('MYSQLUSER')     ?: "root";
$databasePassword = getenv('MYSQLPASSWORD') ?: "BLRUdpzCNwXhmkwBFwcNsQTwPOFFZhPI";
$databaseName     = getenv('MYSQLDATABASE') ?: "railway";

// Create MySQL connection with 5-second connection timeout limit
$databaseConnection = mysqli_init();
$databaseConnection->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

@$databaseConnection->real_connect(
    $databaseServer,
    $databaseUsername,
    $databasePassword,
    $databaseName,
    $databasePort
);

// Check if connection failed
if ($databaseConnection->connect_error) {
    die("Database Connection Error: " . $databaseConnection->connect_error);
}

// Set character encoding
mysqli_set_charset($databaseConnection, "utf8mb4");

?>