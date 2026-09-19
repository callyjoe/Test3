<?php
// Turn off error display — errors corrupt the file output
error_reporting(0);
ini_set('display_errors', 0);

if (ob_get_length()) ob_end_clean();
ob_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;

$data    = json_decode(file_get_contents("php://input"), true);
$content = $data['content'] ?? '';

if (trim($content) === '') {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(["error" => "No content provided."]);
    exit;
}

try {
    $phpWord = new PhpWord();

    // ── Default font ──────────────────────────────────────────────
    $phpWord->setDefaultFontName('Arial');
    $phpWord->setDefaultFontSize(12);

    // ── Heading styles ────────────────────────────────────────────
    $phpWord->addTitleStyle(1, [
        'name'  => 'Arial',
        'size'  => 22,
        'bold'  => true,
        'color' => '1F3864',
    ], [
        'spaceAfter'  => 160,
        'spaceBefore' => 240,
    ]);

    $phpWord->addTitleStyle(2, [
        'name'  => 'Arial',
        'size'  => 16,
        'bold'  => true,
        'color' => '2E5496',
    ], [
        'spaceAfter'        => 120,
        'spaceBefore'       => 200,
        'borderBottomSize'  => 6,
        'borderBottomColor' => '2E5496',
    ]);

    $phpWord->addTitleStyle(3, [
        'name'  => 'Arial',
        'size'  => 13,
        'bold'  => true,
        'color' => '17375E',
    ], [
        'spaceAfter'  => 80,
        'spaceBefore' => 160,
    ]);

    // ── Section ───────────────────────────────────────────────────
    $section = $phpWord->addSection([
        'marginTop'    => Converter::cmToTwip(2.5),
        'marginBottom' => Converter::cmToTwip(2.5),
        'marginLeft'   => Converter::cmToTwip(3),
        'marginRight'  => Converter::cmToTwip(3),
    ]);

    // ── Inline styles ─────────────────────────────────────────────
    $normalStyle = ['name' => 'Arial', 'size' => 12];
    $codeStyle   = ['name' => 'Courier New', 'size' => 10, 'color' => '2B6A2B'];
    $paraStyle   = ['spaceAfter' => 100, 'spaceBefore' => 0];

    $bulletStyle = [
        'spaceAfter'  => 60,
        'spaceBefore' => 0,
        'indentation' => ['left' => 720, 'hanging' => 360],
    ];

    $subBulletStyle = [
        'spaceAfter'  => 40,
        'spaceBefore' => 0,
        'indentation' => ['left' => 1440, 'hanging' => 360],
    ];

    // ── List numbering styles ─────────────────────────────────────
    $phpWord->addNumberingStyle('bulletList', [
        'type'   => 'singleLevel',
        'levels' => [[
            'start'     => 1,
            'format'    => 'bullet',
            'text'      => '',
            'alignment' => Jc::START,
            'tabPos'    => 720,
            'left'      => 720,
            'hanging'   => 360,
            'font'      => 'Symbol',
        ]],
    ]);

    $phpWord->addNumberingStyle('subBulletList', [
        'type'   => 'singleLevel',
        'levels' => [[
            'start'     => 1,
            'format'    => 'bullet',
            'text'      => 'o',
            'alignment' => Jc::START,
            'tabPos'    => 1440,
            'left'      => 1440,
            'hanging'   => 360,
            'font'      => 'Courier New',
        ]],
    ]);

    $phpWord->addNumberingStyle('numberedList', [
        'type'   => 'singleLevel',
        'levels' => [[
            'start'     => 1,
            'format'    => 'decimal',
            'text'      => '%1.',
            'alignment' => Jc::START,
            'tabPos'    => 720,
            'left'      => 720,
            'hanging'   => 360,
        ]],
    ]);

    $phpWord->addNumberingStyle('subNumberedList', [
        'type'   => 'singleLevel',
        'levels' => [[
            'start'     => 1,
            'format'    => 'lowerLetter',
            'text'      => '%1.',
            'alignment' => Jc::START,
            'tabPos'    => 1440,
            'left'      => 1440,
            'hanging'   => 360,
        ]],
    ]);

    // ═══════════════════════════════════════════════════════════════
    // HELPER: strip markdown bold/italic markers from plain text
    // Used for headings which are already styled bold
    // ═══════════════════════════════════════════════════════════════
    function stripMarkdown($text) {
        // Remove **bold** and *italic* markers
        $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
        $text = preg_replace('/\*(.+?)\*/', '$1', $text);
        // Remove inline code backticks
        $text = preg_replace('/`([^`]+)`/', '$1', $text);
        return trim($text);
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPER: parse inline markdown into Word runs
    // Handles **bold**, *italic*, `inline code`, plain text
    // ═══════════════════════════════════════════════════════════════
    function addFormattedText($textRun, $text, $baseFont) {
        // Match **bold**, *italic*, `code`, or plain text segments
        $pattern = '/\*\*(.+?)\*\*|\*(.+?)\*|`([^`]+)`/';
        $offset  = 0;
        $len     = strlen($text);

        preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $i => $match) {
            $matchStr   = $match[0];
            $matchStart = $match[1];

            // Add any plain text before this match
            if ($matchStart > $offset) {
                $plain = substr($text, $offset, $matchStart - $offset);
                if ($plain !== '') {
                    $textRun->addText(htmlspecialchars($plain), $baseFont);
                }
            }

            if ($matches[1][$i][0] !== '') {
                // **bold**
                $textRun->addText(
                    htmlspecialchars($matches[1][$i][0]),
                    array_merge($baseFont, ['bold' => true])
                );
            } elseif ($matches[2][$i][0] !== '') {
                // *italic*
                $textRun->addText(
                    htmlspecialchars($matches[2][$i][0]),
                    array_merge($baseFont, ['italic' => true])
                );
            } elseif ($matches[3][$i][0] !== '') {
                // `inline code`
                $textRun->addText(
                    htmlspecialchars($matches[3][$i][0]),
                    ['name' => 'Courier New', 'size' => 10, 'color' => '2B6A2B', 'highlight' => 'yellow']
                );
            }

            $offset = $matchStart + strlen($matchStr);
        }

        // Add any remaining plain text after last match
        if ($offset < $len) {
            $remaining = substr($text, $offset);
            if ($remaining !== '') {
                $textRun->addText(htmlspecialchars($remaining), $baseFont);
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // PARSE LINES
    // ═══════════════════════════════════════════════════════════════
    $lines       = explode("\n", $content);
    $inCodeBlock = false;
    $codeLines   = [];

    foreach ($lines as $line) {
        $trimmed = rtrim($line);

        // ── Code block fence ───────────────────────────────────
        if (preg_match('/^```/', $trimmed)) {
            if ($inCodeBlock) {
                // End code block — render collected lines
                $inCodeBlock = false;
                if (!empty($codeLines)) {
                    foreach ($codeLines as $codeLine) {
                        $cp = $section->addTextRun([
                            'spaceBefore' => 0,
                            'spaceAfter'  => 0,
                            'indentation' => ['left' => 720],
                        ]);
                        $cp->addText(
                            htmlspecialchars($codeLine === '' ? ' ' : $codeLine),
                            $codeStyle
                        );
                    }
                    $section->addTextBreak(1);
                    $codeLines = [];
                }
            } else {
                $inCodeBlock = true;
                $codeLines   = [];
            }
            continue;
        }

        if ($inCodeBlock) {
            $codeLines[] = $trimmed;
            continue;
        }

        // ── Horizontal rule ────────────────────────────────────
        if (preg_match('/^[-*]{3,}$/', $trimmed)) {
            $section->addTextBreak(1);
            continue;
        }

        // ── H1: # Heading ──────────────────────────────────────
        if (preg_match('/^# (.+)$/', $trimmed, $m)) {
            $section->addTitle(htmlspecialchars(stripMarkdown($m[1])), 1);
            continue;
        }

        // ── H2: ## Heading ─────────────────────────────────────
        if (preg_match('/^## (.+)$/', $trimmed, $m)) {
            $section->addTitle(htmlspecialchars(stripMarkdown($m[1])), 2);
            continue;
        }

        // ── H3: ### Heading ────────────────────────────────────
        if (preg_match('/^### (.+)$/', $trimmed, $m)) {
            $section->addTitle(htmlspecialchars(stripMarkdown($m[1])), 3);
            continue;
        }

        // ── Indented bullet: 2+ spaces then - or * ─────────────
        // Must come BEFORE the top-level bullet check
        if (preg_match('/^ {2,}[-*] (.+)$/', $line, $m)) {
            $tr = $section->addTextRun(
                array_merge($subBulletStyle, ['numStyle' => 'subBulletList', 'numLevel' => 0])
            );
            addFormattedText($tr, trim($m[1]), $normalStyle);
            continue;
        }

        // ── Indented numbered: 2+ spaces then 1. ───────────────
        if (preg_match('/^ {2,}\d+\. (.+)$/', $line, $m)) {
            $tr = $section->addTextRun(
                array_merge($subBulletStyle, ['numStyle' => 'subNumberedList', 'numLevel' => 0])
            );
            addFormattedText($tr, trim($m[1]), $normalStyle);
            continue;
        }

        // ── Top-level bullet: - item or * item ─────────────────
        if (preg_match('/^[-*] (.+)$/', $trimmed, $m)) {
            $tr = $section->addTextRun(
                array_merge($bulletStyle, ['numStyle' => 'bulletList', 'numLevel' => 0])
            );
            addFormattedText($tr, trim($m[1]), $normalStyle);
            continue;
        }

        // ── Top-level numbered list: 1. item ───────────────────
        if (preg_match('/^\d+\. (.+)$/', $trimmed, $m)) {
            $tr = $section->addTextRun(
                array_merge($bulletStyle, ['numStyle' => 'numberedList', 'numLevel' => 0])
            );
            addFormattedText($tr, trim($m[1]), $normalStyle);
            continue;
        }

        // ── Blank line ─────────────────────────────────────────
        if ($trimmed === '') {
            $section->addTextBreak(1);
            continue;
        }

        // ── Normal paragraph ───────────────────────────────────
        $tr = $section->addTextRun($paraStyle);
        addFormattedText($tr, $trimmed, $normalStyle);
    }

    // ── Save & stream ─────────────────────────────────────────────
    $tempFile = sys_get_temp_dir() . '/converted_notes_' . time() . '.docx';
    $writer   = IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($tempFile);

    ob_end_clean();

    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="converted_notes.docx"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($tempFile));

    readfile($tempFile);
    unlink($tempFile);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(["error" => "Word generation error: " . $e->getMessage()]);
    exit;
}
?>