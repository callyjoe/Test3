<?php
require 'vendor/autoload.php';
use Smalot\PdfParser\Parser;

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $file = $_FILES['document'];
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $tmpName = $file['tmp_name'];

    // 🧩 1. Basic validation
    if ($file['size'] > 10 * 1024 * 1024) {
        exit("File too large. Max 10MB allowed.");
    }

    if (!in_array($ext, ['pdf', 'docx', 'pptx'])) {
        exit("Unsupported file format. Please upload PDF, DOCX, or PPTX.");
    }

    // 🧩 2. Move file to unique location
    $uploadDir = __DIR__ . '/uploads/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

    $uniquePath = $uploadDir . uniqid('doc_', true) . '.' . $ext;
    move_uploaded_file($tmpName, $uniquePath);

    // 🧩 3. Extract text
    $text = "";

    if ($ext === 'pdf') {
        $parser = new Parser();
        try {
            $pdf = $parser->parseFile($uniquePath);
            $text = $pdf->getText();
        } catch (Exception $e) {
            exit("Failed to parse PDF: " . $e->getMessage());
        }
    } elseif ($ext === 'docx') {
        $zip = new ZipArchive;
        if ($zip->open($uniquePath) === TRUE) {
            $data = $zip->getFromName("word/document.xml");
            $zip->close();
            $text = strip_tags($data);
        } else {
            exit("Failed to read DOCX.");
        }
    } elseif ($ext === 'pptx') {
        $zip = new ZipArchive;
        if ($zip->open($uniquePath)) {
            for ($i = 1; $i < 200; $i++) {
                $slide = "ppt/slides/slide{$i}.xml";
                if (!$zip->locateName($slide)) break;
                $data = $zip->getFromName($slide);
                $text .= strip_tags($data) . "\n";
            }
            $zip->close();
        } else {
            exit("Failed to read PPTX.");
        }
    }

    // 🧩 4. Return extracted text (sanitized)
    echo nl2br(htmlspecialchars($text));
} else {
    echo "No file uploaded.";
}
?>
