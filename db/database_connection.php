<?php

/*
|--------------------------------------------------------------------------
| BARBERSHOP.CO DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

// Basahin ang injected Railway environment variables
$databaseServer   = getenv('MYSQLHOST')     ?: (getenv('DB_HOST')     ?: "mysql.railway.internal");
$databasePort     = (int)(getenv('MYSQLPORT') ?: (getenv('DB_PORT')     ?: 3306));
$databaseUsername = getenv('MYSQLUSER')     ?: (getenv('DB_USER')     ?: "root");
$databasePassword = getenv('MYSQLPASSWORD') ?: (getenv('DB_PASSWORD') ?: "BLRUdpzCNwXhmkwBFwcNsQTwPOFFZhPI");
$databaseName     = getenv('MYSQLDATABASE') ?: (getenv('DB_NAME')     ?: "railway");

// Subukang kumonekta sa internal host
$databaseConnection = @mysqli_connect(
    $databaseServer,
    $databaseUsername,
    $databasePassword,
    $databaseName,
    $databasePort
);

// Kapag nag-fail ang internal host, i-fallback sa public host
if (!$databaseConnection) {
    $databaseServer = "turntable.proxy.rlwy.net";
    $databasePort   = 43174;
    
    $databaseConnection = mysqli_connect(
        $databaseServer,
        $databaseUsername,
        $databasePassword,
        $databaseName,
        $databasePort
    );
}

// I-check kung may error pa rin
if (!$databaseConnection) {
    die("Database Connection Error: " . mysqli_connect_error());
}

// Set character encoding
mysqli_set_charset($databaseConnection, "utf8mb4");

?>