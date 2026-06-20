<?php

use App\Kernel;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

$_SERVER['APP_ENV'] = 'test';
// Align with phpunit.dist.xml so Lexik and openssl use the same passphrase (overrides .env).
$_SERVER['JWT_PASSPHRASE'] = $_ENV['JWT_PASSPHRASE'] = 'test_jwt_passphrase_32_chars______';
$testDb = 'sqlite:///'.dirname(__DIR__).'/var/test.sqlite';
$_SERVER['DATABASE_URL'] = $testDb;
$_ENV['DATABASE_URL'] = $testDb;
putenv('DATABASE_URL='.$testDb);

$jwtDir = dirname(__DIR__).'/config/jwt';
$privateKey = $jwtDir.'/private.pem';
$publicKey = $jwtDir.'/public.pem';
// Unencrypted keys: avoids passphrase drift between .env, phpunit, and CI.
if (!is_file($privateKey) || !is_file($publicKey)) {
    if (!is_dir($jwtDir)) {
        mkdir($jwtDir, 0775, true);
    }
    @exec('openssl genpkey -algorithm rsa -pkeyopt rsa_keygen_bits:2048 -out '.escapeshellarg($privateKey).' 2>/dev/null');
    @exec('openssl pkey -in '.escapeshellarg($privateKey).' -out '.escapeshellarg($publicKey).' -pubout 2>/dev/null');
}

if (!getenv('SKIP_TEST_SCHEMA')) {
    $kernel = new Kernel('test', false);
    $kernel->boot();
    $em = $kernel->getContainer()->get('doctrine')->getManager();
    $meta = $em->getMetadataFactory()->getAllMetadata();
    if ($meta !== []) {
        $tool = new SchemaTool($em);
        $tool->dropSchema($meta);
        $tool->createSchema($meta);
    }
    $kernel->shutdown();
}
