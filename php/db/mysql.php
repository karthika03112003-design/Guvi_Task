<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function mysqlConnect(): mysqli
{
    if (!extension_loaded('mysqli')) {
        throw new RuntimeException('MySQLi PHP extension missing.');
    }

    $host = trim((string) env('MYSQL_HOST', '127.0.0.1'));
    $user = trim((string) env('MYSQL_USER', 'root'));
    $password = (string) env('MYSQL_PASSWORD', '');
    $database = trim((string) env('MYSQL_DATABASE'));
    $port = (int) env('MYSQL_PORT', '3306');

    if ($database === '') {
        throw new RuntimeException('MYSQL_DATABASE is missing.');
    }

    try {
        $mysqli = mysqli_init();
        $mysqli->ssl_set(null, null, null, null, 'TLSv1.2');
        $mysqli->real_connect($host, $user, $password, $database, $port, null, MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
        $mysqli->set_charset('utf8mb4');

        return $mysqli;

    } catch (mysqli_sql_exception $e) {
        error_log('MySQL Connection Error: ' . $e->getMessage());
        throw new RuntimeException('MySQL connection failed: ' . $e->getMessage());
    }
}
