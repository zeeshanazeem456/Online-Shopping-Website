<?php

class ProductImageUploader
{
    private const MAX_BYTES = 2097152;
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function __construct(private string $uploadDir)
    {
    }

    public function save(string $fieldName, string $productName): string
    {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
            return '';
        }

        if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Image upload failed.');
        }

        if ($_FILES[$fieldName]['size'] > self::MAX_BYTES) {
            throw new Exception('Image size must be 2MB or less.');
        }

        $extension = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new Exception('Only JPG, PNG, WEBP, and GIF images are allowed.');
        }

        if (!getimagesize($_FILES[$fieldName]['tmp_name'])) {
            throw new Exception('Uploaded file is not a valid image.');
        }

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }

        $safeName = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $productName));
        $fileName = trim($safeName, '-') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destination = $this->uploadDir . '/' . $fileName;

        if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $destination)) {
            throw new Exception('Could not save uploaded image.');
        }

        return 'products/' . $fileName;
    }
}
