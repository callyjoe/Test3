<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    $uploadDir = "uploads/";

    if (!is_dir($uploadDir)) mkdir($uploadDir);

    $originalPath = $uploadDir . basename($file["name"]);
    move_uploaded_file($file["tmp_name"], $originalPath);

    // Preprocess: Convert to grayscale + increase contrast
    $image = imagecreatefromstring(file_get_contents($originalPath));
    imagefilter($image, IMG_FILTER_GRAYSCALE);
    imagefilter($image, IMG_FILTER_CONTRAST, -50);

    $processedPath = $uploadDir . "processed_" . basename($file["name"]) . ".png";
    imagepng($image, $processedPath);
    imagedestroy($image);

    // ✅ Full path to Tesseract on Windows
    $tesseract = "C:\\Program Files\\Tesseract-OCR\\tesseract.exe";
    $absProcessedPath = realpath($processedPath);

    $cmd = '"' . $tesseract . '" ' . escapeshellarg($absProcessedPath) . ' stdout --psm 6 --oem 1 2>&1';
    $output = shell_exec($cmd);

    // Debug: remove this after confirming it works
    if (empty($output)) {
        echo json_encode(["error" => "Tesseract returned no output. CMD: " . $cmd]);
        exit;
    }

    // Clean extracted text
    $cleanText = preg_replace("/[^\x20-\x7E\n]/", "", $output);
    $cleanText = preg_replace("/[ \t]{2,}/", " ", $cleanText);
    $cleanText = trim($cleanText);

    // Cleanup uploaded files
    unlink($originalPath);
    unlink($processedPath);

    echo json_encode(["text" => $cleanText]);
}
?>