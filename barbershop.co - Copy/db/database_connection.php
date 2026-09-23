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
$databaseServer = "localhost";

// MySQL username
$databaseUsername = "root";

// MySQL password
$databasePassword = "";

// Database name
$databaseName = "barbershop_database";


// Create MySQL connection
$databaseConnection = mysqli_connect(
    $databaseServer,
    $databaseUsername,
    $databasePassword,
    $databaseName
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