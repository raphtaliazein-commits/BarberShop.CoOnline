<?php

/*
|--------------------------------------------------------------------------
| BARBERSHOP.CO DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

// Fetch environment variables (Railway first, then DB_*, fallback to XAMPP local)
$databaseServer   = getenv('MYSQLHOST')     ?: (getenv('DB_HOST')     ?: "localhost");
$databasePort     = (int)(getenv('MYSQLPORT') ?: (getenv('DB_PORT')     ?: 3306));
$databaseUsername = getenv('MYSQLUSER')     ?: (getenv('DB_USER')     ?: "root");
$databasePassword = getenv('MYSQLPASSWORD') ?: (getenv('DB_PASSWORD') ?: "");
$databaseName     = getenv('MYSQLDATABASE') ?: (getenv('DB_NAME')     ?: "barbershop_database");

// Create MySQL connection
$databaseConnection = mysqli_connect(
    $databaseServer,
    $databaseUsername,
    $databasePassword,
    $databaseName,
    $databasePort
);

// Check connection
if (!$databaseConnection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Set character encoding
mysqli_set_charset($databaseConnection, "utf8mb4");

?>