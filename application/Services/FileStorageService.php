<?php

namespace Application\Services;

use Application\Config\App;
use Exception;

class FileStorageService
{
    private static array $allowedMimeTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/jpeg',
        'image/png',
        'application/zip',
        'application/x-zip-compressed'
    ];

    private static int $maxSizeBytes = 26214400; // 25 MB

    public static function store(array $fileArray): array
    {
        if ($fileArray['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload failed with error code: " . $fileArray['error']);
        }

        if ($fileArray['size'] > self::$maxSizeBytes) {
            throw new Exception("File exceeds the maximum allowed size of 25MB.");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileArray['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::$allowedMimeTypes)) {
            throw new Exception("Invalid file type: {$mimeType}. Allowed formats: PDF, Word, Excel, Images, ZIP.");
        }

        $extension = pathinfo($fileArray['name'], PATHINFO_EXTENSION);
        $randomName = bin2hex(random_bytes(16)) . '.' . strtolower($extension);

        $uploadDir = App::get('storage_dir') . '/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . '/' . $randomName;
        if (!move_uploaded_file($fileArray['tmp_name'], $destination)) {
            throw new Exception("Failed to move uploaded file to secure storage directory.");
        }

        return [
            'original_filename' => basename($fileArray['name']),
            'stored_path'       => $randomName,
            'mime_type'         => $mimeType,
            'file_size'         => $fileArray['size']
        ];
    }
}
