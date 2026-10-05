<?php
// P1 evidence scripts: explicit connection guards; no secrets and no SQL writes.
declare(strict_types=1);

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

function p1Connection(): array
{
    $expectedDatabase = getenv('P1_EXPECT_DATABASE');
    $expectedHost = getenv('P1_EXPECT_HOST');
    if (!$expectedDatabase || !$expectedHost) {
        throw new RuntimeException('P1_EXPECT_DATABASE_AND_HOST_REQUIRED');
    }
    $connection = DB::connection();
    $identity = $connection->selectOne(
        "SELECT current_database() AS database_name, current_setting('server_version') AS server_version,inet_server_addr()::text AS server_address,inet_server_port() AS server_port"
    );
    $host = (string) $connection->getConfig('host');
    if ($connection->getDriverName() !== 'pgsql' || $identity->database_name !== $expectedDatabase || $host !== $expectedHost) {
        throw new RuntimeException('P1_CONNECTION_ASSERTION_FAILED');
    }
    if (!str_starts_with($identity->server_version, '18.')) {
        throw new RuntimeException('P1_POSTGRES_MAJOR_ASSERTION_FAILED');
    }
    $vector = $connection->selectOne("SELECT extversion FROM pg_extension WHERE extname='vector'");
    if (!$vector || $vector->extversion !== '0.8.7') {
        throw new RuntimeException('P1_PGVECTOR_VERSION_ASSERTION_FAILED');
    }
    return [
        'database' => $identity->database_name,
        'host' => $host,
        'postgres_version' => $identity->server_version,
        'pgvector_version' => $vector->extversion,
        'driver' => 'pgsql', 'server_address' => $identity->server_address, 'server_port' => (int) $identity->server_port,
    ];
}

function p1ReadOnly(callable $callback): mixed
{
    DB::beginTransaction();
    try {
        DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY');
        return $callback();
    } finally {
        DB::rollBack();
    }
}

function p1Output(string $name, array $data): string
{
    if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,80}$/', $name)) {
        throw new RuntimeException('P1_REPORT_NAME_INVALID');
    }
    $directory = '/var/www/html/storage/app/ai-p1';
    if (!is_dir($directory)) mkdir($directory, 0700, true);
    $path = $directory.'/'.$name.'.json';
    if (file_exists($path)) throw new RuntimeException('P1_REPORT_EXISTS_USE_NEW_NAME');
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
    return $path;
}

function p1Json(mixed $value): array
{
    return is_array($value) ? $value : json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
}
