<?php

/*
|--------------------------------------------------------------------------
| BARBERSHOP.CO DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

// Wag payagang mag-throw ng fatal uncaught exceptions para ma-handle nang maayos ang fallback
mysqli_report(MYSQLI_REPORT_OFF);

// Helper function para masigurong mababasa ang environment variables sa PHP
function getDbEnv($key, $default = '') {
    if (!empty($_ENV[$key])) return $_ENV[$key];
    if (!empty($_SERVER[$key])) return $_SERVER[$key];
    $val = getenv($key);
    return ($val !== false && $val !== '') ? $val : $default;
}

// 1. Kunin ang Railway Injected Variables (o fallback values)
$host   = getDbEnv('MYSQLHOST', 'mysql.railway.internal');
$port   = (int) getDbEnv('MYSQLPORT', 3306);
$user   = getDbEnv('MYSQLUSER', 'root');
$pass   = getDbEnv('MYSQLPASSWORD', 'BLRUdpzCNwXhmkwBFwcNsQTwPOFFZhPI');
$dbname = getDbEnv('MYSQLDATABASE', 'railway');

// 2. Subukang kumonekta sa Internal Network (2 Seconds Timeout Limit)
$databaseConnection = mysqli_init();
$databaseConnection->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);

$connected = @$databaseConnection->real_connect($host, $user, $pass, $dbname, $port);

// 3. Kapag hindi pumasok sa Internal, subukan ang Public Proxy (2 Seconds Timeout Limit)
if (!$connected) {
    $databaseConnection = mysqli_init();
    $databaseConnection->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);
    $connected = @$databaseConnection->real_connect("turntable.proxy.rlwy.net", $user, $pass, $dbname, 43174);
}

// 4. Kapag parehong nag-fail, magpakita ng malinaw na error (ipipigil ang 30s Railway timeout)
if (!$connected) {
    die("Database Connection Error: " . mysqli_connect_error());
}

// Set character encoding
mysqli_set_charset($databaseConnection, "utf8mb4");

?>