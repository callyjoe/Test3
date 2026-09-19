<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}
include '../config.php';
require_once '../vendor/autoload.php';

use Smalot\PdfParser\Parser;

$resource_id = (int)$_GET['resource_id'];
$query = "SELECT type, url, title FROM learning_resources WHERE resource_id=$resource_id";
$result = mysqli_query($conn, $query);

if ($row = mysqli_fetch_assoc($result)) {
    if ($row['type'] == 'pdf') {
        $clean_url = str_replace('../', '', $row['url']);
        $web_url = '/Test3/' . $clean_url;
        $file_path = '../' . $row['url'];
        $extracted_text = '';

        // Try Smalot first
        if (file_exists($file_path) && class_exists('Smalot\PdfParser\Parser')) {
            try {
                $parser = new Parser();
                $pdf = $parser->parseFile($file_path);
                $extracted_text = trim($pdf->getText());
            } catch (Exception $e) {
                $extracted_text = '';
            }
        }

        // ✅ Fallback: if no text extracted, read raw PDF and send to Mistral vision
        if (empty($extracted_text) && file_exists($file_path)) {
            try {
                $pdf_base64 = base64_encode(file_get_contents($file_path));

                $mistral_payload = [
                    "model" => "mistral-small-latest",
                    "messages" => [
                        [
                            "role" => "user",
                            "content" => [
                                [
                                    "type" => "text",
                                    "text" => "Please extract and return ALL the text content from this PDF document. Return only the raw text, no commentary."
                                ],
                                [
                                    "type" => "document_url",
                                    "document_url" => "data:application/pdf;base64," . $pdf_base64
                                ]
                            ]
                        ]
                    ]
                ];

                $ch = curl_init('https://api.mistral.ai/v1/chat/completions');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . MISTRAL_API_KEY  // use your config constant
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($mistral_payload));
                $mistral_response = curl_exec($ch);
                curl_close($ch);

                $mistral_data = json_decode($mistral_response, true);
                $extracted_text = $mistral_data['choices'][0]['message']['content'] ?? '';
            } catch (Exception $e) {
                $extracted_text = '';
            }
        }

        echo json_encode([
            'type'    => 'pdf',
            'url'     => $web_url,
            'content' => $extracted_text,
            'title'   => $row['title']
        ]);

    } else {
        echo json_encode([
            'type'    => 'youtube',
            'url'     => $row['url'],
            'content' => "YouTube video: " . $row['url'],
            'title'   => $row['title']
        ]);
    }
} else {
    echo json_encode(['error' => 'Resource not found']);
}
?>