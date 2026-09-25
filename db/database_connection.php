<?php

/*
|--------------------------------------------------------------------------
| BARBERSHOP.CO DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

// Subukang basahin ang Railway native variables, kapag wala ay fall back sa custom o local
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

// Check if connection was successful
if (!$databaseConnection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Set character encoding
mysqli_set_charset($databaseConnection, "utf8mb4");

?>