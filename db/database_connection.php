<?php

/*
|--------------------------------------------------------------------------
| BARBERSHOP.CO DATABASE CONNECTION
|--------------------------------------------------------------------------
| This file creates the connection between our PHP website
| and the MySQL database.
|--------------------------------------------------------------------------
*/


// MySQL server address
$databaseServer = getenv('MYSQLHOST') ?: "localhost";

// MySQL port
$databasePort = getenv('MYSQLPORT') ?: 3306;

// MySQL username
$databaseUsername = getenv('MYSQLUSER') ?: "root";

// MySQL password
$databasePassword = getenv('MYSQLPASSWORD') ?: "";

// Database name
$databaseName = getenv('MYSQLDATABASE') ?: "barbershop_database";


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