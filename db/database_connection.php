<?php

/*
|--------------------------------------------------------------------------
| BARBERSHOP.CO DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

// Subukang basahin ang environment variables, kapag wala ay gagamitin ang Railway direct credentials
$databaseServer   = getenv('MYSQLHOST')     ?: "mysql.railway.internal";
$databasePort     = (int)(getenv('MYSQLPORT') ?: 3306);
$databaseUsername = getenv('MYSQLUSER')     ?: "root";
$databasePassword = getenv('MYSQLPASSWORD') ?: "BLRUdpzCNwXhmkwBFwcNsQTwPOFFZhPI";
$databaseName     = getenv('MYSQLDATABASE') ?: "railway";

// Subukang kumonekta sa internal network
$databaseConnection = @mysqli_connect(
    $databaseServer,
    $databaseUsername,
    $databasePassword,
    $databaseName,
    $databasePort
);

// Kapag nag-fail ang internal network, subukan ang public proxy host
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

// I-check kung may error
if (!$databaseConnection) {
    die("Database Connection Error: " . mysqli_connect_error());
}

// Set character encoding
mysqli_set_charset($databaseConnection, "utf8mb4");

?>