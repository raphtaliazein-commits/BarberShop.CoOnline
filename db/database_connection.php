<?php

/*
|--------------------------------------------------------------------------
| BARBERSHOP.CO DATABASE CONNECTION
|--------------------------------------------------------------------------
| Handles connection to Railway MySQL or local XAMPP environment.
|--------------------------------------------------------------------------
*/

// Helper function to read environment variables reliably across environments
function getEnvValue($key) {
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return $_SERVER[$key];
    }
    $val = getenv($key);
    return ($val !== false && $val !== '') ? $val : null;
}

// Fetch DB Credentials (Checks MYSQL_* first, then DB_*, then XAMPP defaults)
$databaseServer   = getEnvValue('MYSQLHOST')     ?? getEnvValue('DB_HOST')     ?? "localhost";
$databasePort     = (int)(getEnvValue('MYSQLPORT') ?? getEnvValue('DB_PORT')     ?? 3306);
$databaseUsername = getEnvValue('MYSQLUSER')     ?? getEnvValue('DB_USER')     ?? "root";
$databasePassword = getEnvValue('MYSQLPASSWORD') ?? getEnvValue('DB_PASSWORD') ?? "";
$databaseName     = getEnvValue('MYSQLDATABASE') ?? getEnvValue('DB_NAME')     ?? "barbershop_database";


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