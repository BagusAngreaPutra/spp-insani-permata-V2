param([switch]$CekSaja, [switch]$KonfirmasiPulihkanKodeDanDatabase)
$ErrorActionPreference = 'Stop'
$taskWorkspace = [IO.Path]::GetFullPath('D:\Kerjoan\5. Coding\Web SPP Insani Permata')
$taskBackup = 'D:\Kerjoan\5. Coding\SPP-backup-sebelum-revisi-20261014'
$taskManifestPath = Join-Path $taskBackup 'manifest-revisi.json'
if (!(Test-Path -LiteralPath $taskManifestPath) -or !(Test-Path -LiteralPath (Join-Path $taskBackup 'database.sql'))) { throw 'Cadangan atau manifest tidak ditemukan.' }
$taskManifest = Get-Content -LiteralPath $taskManifestPath -Raw | ConvertFrom-Json
if ($taskWorkspace -ne $taskManifest.workspace) { throw 'Workspace berbeda dari lokasi cadangan.' }
$taskRestorePaths = @()
foreach ($taskFile in $taskManifest.files) {
    $taskTarget = [IO.Path]::GetFullPath((Join-Path $taskWorkspace $taskFile.path))
    if (!$taskTarget.StartsWith($taskWorkspace + '\', [StringComparison]::OrdinalIgnoreCase)) { throw "Path keluar workspace: $taskTarget" }
    if ($taskFile.existed) {
        $taskOriginal = Join-Path (Join-Path $taskBackup 'kode') $taskFile.path
        if (!(Test-Path -LiteralPath $taskOriginal)) { throw "Kode cadangan tidak ditemukan: $taskOriginal" }
        if ((Get-FileHash -LiteralPath $taskOriginal -Algorithm SHA256).Hash -ne $taskFile.original_hash) { throw "Cadangan berubah: $taskOriginal" }
    }
    $taskRestorePaths += $taskTarget
}
if ((Get-FileHash -LiteralPath (Join-Path $taskBackup 'database.sql') -Algorithm SHA256).Hash -ne $taskManifest.database_hash) { throw 'Checksum database cadangan tidak cocok.' }
if ($CekSaja) { Write-Output "Cadangan terverifikasi. $($taskRestorePaths.Count) file revisi; database tersedia. Tidak ada data diubah."; exit 0 }
if (!$KonfirmasiPulihkanKodeDanDatabase) { throw 'Pemulihan mengembalikan kode DAN database ke sebelum revisi. Transaksi sesudah cadangan tidak ada pada versi lama. Gunakan -CekSaja untuk memeriksa, atau -KonfirmasiPulihkanKodeDanDatabase untuk menjalankan.' }

$taskCurrentBackup = Join-Path (Split-Path -Parent $taskWorkspace) ('SPP-backup-sebelum-pemulihan-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path (Join-Path $taskCurrentBackup 'kode') | Out-Null
foreach ($taskFile in $taskManifest.files) {
    $taskSource = Join-Path $taskWorkspace $taskFile.path
    if (Test-Path -LiteralPath $taskSource) {
        $taskDestination = Join-Path (Join-Path $taskCurrentBackup 'kode') $taskFile.path
        New-Item -ItemType Directory -Path (Split-Path -Parent $taskDestination) -Force | Out-Null
        Copy-Item -LiteralPath $taskSource -Destination $taskDestination
    }
}
Push-Location $taskWorkspace
try {
    & 'C:\xampp\php\php.exe' artisan down
    if ($LASTEXITCODE -ne 0) { throw 'Tidak dapat mengaktifkan mode pemeliharaan.' }
    & 'C:\xampp\php\php.exe' scripts\backup-revisi.php $taskCurrentBackup
    if ($LASTEXITCODE -ne 0) { throw 'Cadangan kondisi terbaru gagal; pemulihan dibatalkan.' }
    & 'C:\xampp\php\php.exe' scripts\restore-revisi-database.php (Join-Path $taskBackup 'database.sql') '--restore-confirmed'
    if ($LASTEXITCODE -ne 0) { throw "Pemulihan database gagal. Cadangan terbaru: $taskCurrentBackup" }
    foreach ($taskFile in $taskManifest.files) {
        $taskTarget = [IO.Path]::GetFullPath((Join-Path $taskWorkspace $taskFile.path))
        if (!$taskTarget.StartsWith($taskWorkspace + '\', [StringComparison]::OrdinalIgnoreCase)) { throw 'Target pemulihan tidak valid.' }
        if ($taskFile.existed) {
            Copy-Item -LiteralPath (Join-Path (Join-Path $taskBackup 'kode') $taskFile.path) -Destination $taskTarget -Force
        } elseif (Test-Path -LiteralPath $taskTarget) {
            Remove-Item -LiteralPath $taskTarget -Force
        }
    }
    & 'C:\xampp\php\php.exe' artisan optimize:clear
    & 'C:\xampp\php\php.exe' artisan up
    Write-Output "Versi sebelum revisi dipulihkan. Kondisi terbaru juga dicadangkan di $taskCurrentBackup"
} finally { Pop-Location }
