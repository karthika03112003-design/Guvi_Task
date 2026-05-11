<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\Collection;

function mongoConnect(string $collection = 'users'): Collection
{
    static $client = null;

    $uri = trim((string) env('MONGO_URI'));
    $database = trim((string) env('MONGO_DATABASE'));

    if ($uri === '') {
        throw new Exception('MONGO_URI is missing');
    }

    if ($database === '') {
        throw new Exception('MONGO_DATABASE is missing');
    }

    try {

        if ($client === null) {

            $client = new Client($uri, [
                'tlsAllowInvalidCertificates' => true,
                'serverSelectionTimeoutMS' => 10000,
                'connectTimeoutMS' => 10000,
            ], [
                'typeMap' => [
                    'root' => 'array',
                    'document' => 'array'
                ],
            ]);
        }

        return $client->selectCollection($database, $collection);

    } catch (Throwable $e) {

        error_log('MongoDB Connection Error: ' . $e->getMessage());

        throw new Exception('MongoDB connection failed');
    }
}