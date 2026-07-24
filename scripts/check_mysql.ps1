# PowerShell script to verify that MySQL/MariaDB is running and accepting connections.

$host_name = "127.0.0.1"
$port = 3306
$dbname = "trinova_portal"

Write-Host "Checking database connection on $host_name`:$port..." -ForegroundColor Cyan

try {
    $connection = New-Object System.Net.Sockets.TcpClient
    $connection.Connect($host_name, $port)
    if ($connection.Connected) {
        Write-Host "Success: MySQL/MariaDB server is running and listening on port $port!" -ForegroundColor Green
        $connection.Close()
        exit 0
    }
}
catch {
    Write-Host "Error: Could not connect to MySQL/MariaDB server on port $port." -ForegroundColor Red
    Write-Host "Please start the MariaDB/MySQL service." -ForegroundColor Yellow
    exit 1
}
