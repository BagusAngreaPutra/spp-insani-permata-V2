<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$backup = $argv[1] ?? throw new RuntimeException('SQL backup required');
if (($argv[2] ?? '') !== '--restore-confirmed' || !is_file($backup)) {
    throw new RuntimeException('Run the recovery PowerShell script with explicit confirmation.');
}
$config = config('database.connections.'.config('database.default'));
$defaults = tempnam(sys_get_temp_dir(), 'spp-restore-');
$quote = fn ($value) => '"'.str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], (string) $value).'"';
file_put_contents($defaults, "[client]\nuser=".$quote($config['username'])."\npassword=".$quote($config['password'])."\nhost=".$quote($config['host'])."\nport=".(int) $config['port']."\n");
try {
    $process = proc_open(['C:/xampp/mysql/bin/mysql.exe', '--defaults-extra-file='.$defaults, $config['database']], [0 => ['file', $backup, 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    stream_get_contents($pipes[1]); fclose($pipes[1]);
    $error = stream_get_contents($pipes[2]); fclose($pipes[2]);
    if (proc_close($process) !== 0) { throw new RuntimeException($error); }
    // Dump restores existing tables; remove only the two tables introduced by this revision.
    Illuminate\Support\Facades\Schema::dropIfExists('receipt_sequences');
    Illuminate\Support\Facades\Schema::dropIfExists('receipt_settings');
    echo "Database restored\n";
} finally { unlink($defaults); }
