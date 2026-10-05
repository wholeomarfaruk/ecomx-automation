<?php

namespace Tests\Unit\OrderIntake;

use App\OrderIntake\OrderIntakeService;
use Tests\TestCase;

/** A PDF with a text layer is read by the parser, not sent to the AI. */
class PdfTextTest extends TestCase
{
    /** A one-page PDF with these lines as real text. */
    private function pdf(array $lines): string
    {
        $stream = "BT /F1 12 Tf 50 750 Td 14 TL\n";
        foreach ($lines as $l) {
            $stream .= '(' . addcslashes($l, '()\\') . ") Tj T*\n";
        }
        $stream .= 'ET';

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length ' . strlen($stream) . " >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $out = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $o) {
            $offsets[] = strlen($out);
            $out .= ($i + 1) . " 0 obj\n{$o}\nendobj\n";
        }
        $xref = strlen($out);
        $out .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $out .= sprintf("%010d 00000 n \n", $off);
        }

        return $out . "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function pdfText(string $contents): ?string
    {
        return (new \ReflectionMethod(OrderIntakeService::class, 'pdfText'))->invoke(app(OrderIntakeService::class), $contents);
    }

    public function test_text_pdf_is_read(): void
    {
        $text = $this->pdfText($this->pdf(['Name: Rahima Akter', 'Phone: 01711-223344', 'Address: House 12, Dhanmondi', 'Product: SF-0156 x2']));

        $this->assertNotNull($text);
        $this->assertStringContainsString('Rahima Akter', $text);
        $this->assertStringContainsString('01711-223344', $text);
    }

    public function test_pdf_without_text_goes_to_ai(): void
    {
        $this->assertNull($this->pdfText($this->pdf([])));
        $this->assertNull($this->pdfText('not a pdf at all'));
    }
}
