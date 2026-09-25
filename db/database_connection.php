<?php

/*
|--------------------------------------------------------------------------
| BARBERSHOP.CO DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

// Function para makuha ang environment variable kahit saan nakatago (ENV, SERVER, or getenv)
function getEnvVar($key, $default = null) {
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return $_SERVER[$key];
    }
    $val = getenv($key);
    return ($val !== false && $val !== '') ? $val : $default;
}

// Subukang kuhanin ang Railway MYSQL variables, tapos DB_* variables, tapos local defaults
$databaseServer   = getEnvVar('MYSQLHOST', getEnvVar('DB_HOST', 'localhost'));
$databasePort     = (int) getEnvVar('MYSQLPORT', getEnvVar('DB_PORT', 3306));
$databaseUsername = getEnvVar('MYSQLUSER', getEnvVar('DB_USER', 'root'));
$databasePassword = getEnvVar('MYSQLPASSWORD', getEnvVar('DB_PASSWORD', ''));
$databaseName     = getEnvVar('MYSQLDATABASE', getEnvVar('DB_NAME', 'barbershop_database'));

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