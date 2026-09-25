<?php

/*
|--------------------------------------------------------------------------
| BARBERSHOP.CO DATABASE CONNECTION
|--------------------------------------------------------------------------
| This file creates the connection between our PHP website
| and the MySQL database.
|
| It reads DB_HOST / DB_PORT / DB_USER / DB_PASSWORD / DB_NAME from
| environment variables first (set on Railway via Variable References
| to the MySQL service). If those aren't set — e.g. when running
| locally on XAMPP — it falls back to the original local defaults.
|--------------------------------------------------------------------------
*/


// MySQL server address
$databaseServer = getenv('DB_HOST') ?: "localhost";

// MySQL port
$databasePort = getenv('DB_PORT') ?: 3306;

// MySQL username
$databaseUsername = getenv('DB_USER') ?: "root";

// MySQL password
$databasePassword = getenv('DB_PASSWORD') ?: "";

// Database name
$databaseName = getenv('DB_NAME') ?: "barbershop_database";


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