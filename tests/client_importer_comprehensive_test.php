<?php

$appDir = dirname(__DIR__);
if (file_exists($appDir . '/vendor/autoload.php')) {
    require_once $appDir . '/vendor/autoload.php';
} else {
    spl_autoload_register(function ($class) use ($appDir) {
        $prefix = 'Application\\';
        $baseDir = $appDir . '/application/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) return;
        $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
        if (file_exists($file)) require_once $file;
    });
}

use Application\Config\ClientCsv;
use Application\Exceptions\UserFacingException;
use Application\Services\ClientCsvImportService;

function runTest(string $name, callable $fn) {
    try {
        $fn();
        echo " [PASS] $name\n";
    } catch (\Throwable $e) {
        echo " [FAIL] $name: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
        exit(1);
    }
}

echo "=== CLIENT CSV IMPORTER TEST SUITE ===\n\n";

// Helper to simulate findHeaders and row reading on temporary CSV content
function parseCsvContent(string $content): array {
    $tmp = tmpfile();
    fwrite($tmp, $content);
    rewind($tmp);

    $reflection = new \ReflectionClass(ClientCsvImportService::class);
    $service = $reflection->newInstanceWithoutConstructor();
    $refHeader = $reflection->getMethod('findHeaders');
    $refClean = $reflection->getMethod('cleanCsvValue');

    [$headers, $headerLine] = $refHeader->invoke($service, $tmp);
    $mapping = ClientCsv::defaultMapping($headers);
    
    $rows = [];
    while (($row = fgetcsv($tmp, null, ',', '"', '')) !== false) {
        if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue;
        $row = array_map(fn($v) => $refClean->invoke($service, (string)$v), $row);
        $rows[] = $row;
    }
    fclose($tmp);

    return [
        'headers' => $headers,
        'mapping' => $mapping,
        'rows'    => $rows,
    ];
}

// Test 1: Existing working file
runTest('Test 1 — Existing working file (trinova-client-import-ready (1).csv)', function() {
    $content = file_get_contents(__DIR__ . '/fixtures/trinova-client-import-ready (1).csv');
    $result = parseCsvContent($content);
    assert(isset($result['mapping']['client_name']), 'Client Name must be mapped');
    assert($result['mapping']['client_name'] === 0, 'Client name must be index 0');
    assert(count($result['headers']) === 19, 'Must detect 19 columns');
    assert(count($result['rows']) === 2, 'Must detect 2 rows');
    assert($result['rows'][0][0] === 'Trinova Accounting', 'First row client name matches');
    assert($result['rows'][0][6] === '42 London rd, Stroud, GL5 2AJ', 'Address matches');
});

// Test 2: Previously failing template
runTest('Test 2 — Previously failing template (trinova-client-import-template.csv)', function() {
    $content = file_get_contents(__DIR__ . '/fixtures/trinova-client-import-template.csv');
    $result = parseCsvContent($content);
    assert(isset($result['mapping']['client_name']), 'Client Name must be mapped');
    assert($result['mapping']['client_name'] === 0, 'Client name must be index 0');
    assert(count($result['headers']) === 19, 'Must detect 19 columns');
    assert(count($result['rows']) === 2, 'Must detect 2 rows');
    assert($result['rows'][0][0] === 'Trinova Accounting', 'First row client name matches');
    assert($result['rows'][0][6] === '42 London rd, Stroud, GL5 2AJ', 'Address matches');
});

// Test 3: Fully Quoted CSV
runTest('Test 3 — Fully Quoted CSV headers and data', function() {
    $csv = "\"COMPANY NAME\",\"Company Number\",\"UTR\",\"VAT NUMBER\"\n\"Alpha Corp Ltd\",\"11223344\",\"1234567890\",\"GB123456789\"\n";
    $result = parseCsvContent($csv);
    assert(isset($result['mapping']['client_name']), 'Client name mapped');
    assert($result['rows'][0][0] === 'Alpha Corp Ltd', 'Company name quoted parsed cleanly');
    assert($result['rows'][0][1] === '11223344', 'Company number parsed cleanly');
});

// Test 4: Windows CRLF CSV
runTest('Test 4 — CRLF Line Endings', function() {
    $csv = "COMPANY NAME,Company Number,UTR,VAT NUMBER\r\nBeta Industries Ltd,99887766,9876543210,GB987654321\r\n";
    $result = parseCsvContent($csv);
    assert(isset($result['mapping']['client_name']), 'Client name mapped');
    assert($result['rows'][0][0] === 'Beta Industries Ltd');
});

// Test 5: Unix LF CSV
runTest('Test 5 — LF Line Endings', function() {
    $csv = "COMPANY NAME,Company Number,UTR,VAT NUMBER\nGamma Global Ltd,55667788,5555555555,GB555555555\n";
    $result = parseCsvContent($csv);
    assert(isset($result['mapping']['client_name']), 'Client name mapped');
    assert($result['rows'][0][0] === 'Gamma Global Ltd');
});

// Test 6: UTF-8 BOM CSV
runTest('Test 6 — UTF-8 BOM with Quotes', function() {
    $csv = "\xEF\xBB\xBF\"COMPANY NAME\",\"Company Number\",\"UTR\",\"VAT NUMBER\"\n\"Delta Services Ltd\",\"44332211\",\"4444444444\",\"GB444444444\"\n";
    $result = parseCsvContent($csv);
    assert(isset($result['mapping']['client_name']), 'Client name recognized despite BOM');
    assert($result['rows'][0][0] === 'Delta Services Ltd');
});

// Test 7: Address containing embedded commas in quotes
runTest('Test 7 — Address containing commas within quotes', function() {
    $csv = "COMPANY NAME,Company Number,UTR,VAT NUMBER,PAYE REF NUMBER,PAYE OFFICE NUMBER,ADDRESS,EMAIL,PHONE\n\"ABC LIMITED\",\"12345678\",\"1234567890\",\"\",\"\",\"\",\"10 King Street, London, Greater London, EC1A 1BB\",\"info@abc.com\",\"02071234567\"\n";
    $result = parseCsvContent($csv);
    assert($result['rows'][0][0] === 'ABC LIMITED');
    assert($result['rows'][0][6] === '10 King Street, London, Greater London, EC1A 1BB', 'Address contains full string with commas');
    assert($result['rows'][0][7] === 'info@abc.com', 'Email not shifted');
    assert($result['rows'][0][8] === '02071234567', 'Phone not shifted');
});

// Test 8: Invalid file missing required columns
runTest('Test 8 — Invalid file rejection', function() {
    $csv = "Random Header 1,Random Header 2,Random Header 3\nValue 1,Value 2,Value 3\n";
    try {
        parseCsvContent($csv);
        throw new \Exception('Expected UserFacingException was not thrown');
    } catch (UserFacingException $e) {
        assert(str_contains($e->getMessage(), 'The CSV header could not be recognized'), 'Correct user-facing error message');
    }
});

echo "\nAll 8 CSV importer tests completed successfully!\n";
