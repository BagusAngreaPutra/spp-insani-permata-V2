<?php
// Run from the project root. Credentials stay inside the PHP process.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$directory = $argv[1] ?? throw new RuntimeException('Backup directory required');
$config = config('database.connections.'.config('database.default'));
$defaults = tempnam(sys_get_temp_dir(), 'spp-db-');
$quote = fn ($value) => '"'.str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], (string) $value).'"';
file_put_contents($defaults, "[client]\nuser=".$quote($config['username'])."\npassword=".$quote($config['password'])."\nhost=".$quote($config['host'])."\nport=".(int) $config['port']."\n");
try {
    $command = ['C:/xampp/mysql/bin/mysqldump.exe', '--defaults-extra-file='.$defaults, '--single-transaction', '--routines', '--triggers', '--hex-blob', '--result-file='.$directory.'/database.sql', $config['database']];
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    fclose($pipes[0]);
    stream_get_contents($pipes[1]); fclose($pipes[1]);
    $error = stream_get_contents($pipes[2]); fclose($pipes[2]);
    if (proc_close($process) !== 0) { throw new RuntimeException($error); }
    echo "Database backup OK\n";
} finally { unlink($defaults); }
