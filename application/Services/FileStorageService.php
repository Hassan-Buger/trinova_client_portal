<?php

namespace Application\Services;

use Application\Config\App;
use Exception;

final class FileStorageService
{
    private static array $allowedTypes = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/x-zip-compressed'],
        'xls'  => ['application/vnd.ms-excel', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/x-zip-compressed'],
        'csv'  => ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'],
        'png'  => ['image/png'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'zip'  => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
        'txt'  => ['text/plain'],
    ];

    private static int $maxSizeBytes = 26214400; // 25 MB

    public static function store(array $fileArray): array
    {
        $uploadError = (int)($fileArray['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError !== UPLOAD_ERR_OK) {
            throw new Exception("File upload failed with error code: {$uploadError}");
        }

        if ((int)($fileArray['size'] ?? 0) <= 0) {
            throw new Exception('The selected file is empty.');
        }

        if ((int)$fileArray['size'] > self::$maxSizeBytes) {
            throw new Exception("File exceeds the maximum allowed size of 25MB.");
        }

        $originalName = basename((string)($fileArray['name'] ?? ''));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($originalName === '' || !isset(self::$allowedTypes[$extension])) {
            throw new Exception('Disallowed file extension. Allowed formats: PDF, Word, Excel, CSV, images, ZIP and text.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) throw new Exception('Server file validation is unavailable.');
        $mimeType = finfo_file($finfo, (string)$fileArray['tmp_name']);
        finfo_close($finfo);

        if (!is_string($mimeType) || !in_array(strtolower($mimeType), self::$allowedTypes[$extension], true)) {
            throw new Exception('The file content does not match its extension or is not an allowed document type.');
        }

        $randomName = bin2hex(random_bytes(16)) . '.' . $extension;

        $uploadDir = App::get('storage_dir') . '/uploads';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir))
            throw new Exception('The secure upload directory is unavailable.');

        $destination = $uploadDir . '/' . $randomName;
        if (!move_uploaded_file($fileArray['tmp_name'], $destination)) {
            throw new Exception("Failed to move uploaded file to secure storage directory.");
        }

        return [
            'original_filename' => $originalName,
            'stored_path'       => $randomName,
            'mime_type'         => $mimeType,
            'file_size'         => $fileArray['size']
        ];
    }

    public static function remove(string $storedPath): void
    {
        $safeName = basename($storedPath);
        if ($safeName === '' || $safeName !== $storedPath) return;
        $path = App::get('storage_dir') . '/uploads/' . $safeName;
        if (is_file($path)) @unlink($path);
    }
}
