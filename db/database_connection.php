<?php

/*
|--------------------------------------------------------------------------
| BARBERSHOP.CO DATABASE CONNECTION
|--------------------------------------------------------------------------
| This file creates the connection between our PHP website
| and the MySQL database.
|
| It reads Railway's native MySQL environment variables first, falls back
| to custom DB_* variables, and defaults to local XAMPP settings if none 
| are present.
|--------------------------------------------------------------------------
*/

// MySQL server address
$databaseServer = getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: "localhost");

// MySQL port
$databasePort = (int)(getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: 3306));

// MySQL username
$databaseUsername = getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: "root");

// MySQL password
$databasePassword = getenv('MYSQLPASSWORD') ?: (getenv('DB_PASSWORD') ?: "");

// Database name
$databaseName = getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: "barbershop_database");


// Create MySQL connection
$databaseConnection = mysqli_connect(
    $databaseServer,
    $databaseUsername,
    $databasePassword,
    $databaseName,
    $databasePort
);


// Check if the connection was successful
if (!$databaseConnection) {
    die(
        "Database connection failed: "
        . mysqli_connect_error()
    );
}


// Set character encoding
mysqli_set_charset(
    $databaseConnection,
    "utf8mb4"
);

?>