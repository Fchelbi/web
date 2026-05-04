<?php
namespace App\Service;

use TCPDF;

class CertificateService
{
    /**
     * Generate a PDF certificate and return the file content as a string.
     */
    public function generateCertificate(
        string $studentName,
        string $formationTitle,
        int $score,
        int $totalPoints,
        \DateTimeInterface $completedAt
    ): string {
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->AddPage();

        $w = 297; // A4 landscape width
        $h = 210; // A4 landscape height

        // === Background ===
        // Gradient background: navy blue
        $pdf->SetFillColor(10, 22, 40);
        $pdf->Rect(0, 0, $w, $h, 'F');

        // Decorative border
        $pdf->SetDrawColor(56, 189, 248); // Cyan border
        $pdf->SetLineWidth(1.5);
        $pdf->Rect(12, 12, $w - 24, $h - 24, 'D');

        // Inner border
        $pdf->SetDrawColor(232, 115, 74); // Orange accent
        $pdf->SetLineWidth(0.5);
        $pdf->Rect(16, 16, $w - 32, $h - 32, 'D');

        // Corner decorations (small circles)
        $pdf->SetFillColor(232, 115, 74);
        $pdf->Circle(20, 20, 3, 0, 360, 'F');
        $pdf->Circle($w - 20, 20, 3, 0, 360, 'F');
        $pdf->Circle(20, $h - 20, 3, 0, 360, 'F');
        $pdf->Circle($w - 20, $h - 20, 3, 0, 360, 'F');

        // === Logo ===
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetXY(0, 25);
        $pdf->Cell($w, 8, 'EchoCare', 0, 1, 'C');

        // === Title ===
        $pdf->SetTextColor(56, 189, 248);
        $pdf->SetFont('helvetica', 'B', 32);
        $pdf->SetXY(0, 42);
        $pdf->Cell($w, 16, 'CERTIFICAT DE RÉUSSITE', 0, 1, 'C');

        // === Divider line ===
        $pdf->SetDrawColor(232, 115, 74);
        $pdf->SetLineWidth(1);
        $pdf->Line($w / 2 - 50, 62, $w / 2 + 50, 62);

        // === "Décerné à" ===
        $pdf->SetTextColor(148, 163, 184);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->SetXY(0, 68);
        $pdf->Cell($w, 8, 'Ce certificat est décerné à', 0, 1, 'C');

        // === Student Name ===
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 28);
        $pdf->SetXY(0, 80);
        $pdf->Cell($w, 14, $studentName, 0, 1, 'C');

        // === "Pour avoir complété" ===
        $pdf->SetTextColor(148, 163, 184);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->SetXY(0, 100);
        $pdf->Cell($w, 8, 'Pour avoir complété avec succès la formation', 0, 1, 'C');

        // === Formation Title ===
        $pdf->SetTextColor(232, 115, 74);
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetXY(20, 112);
        $pdf->Cell($w - 40, 12, $formationTitle, 0, 1, 'C');

        // === Score ===
        $percentage = $totalPoints > 0 ? round(($score / $totalPoints) * 100) : 0;
        $pdf->SetTextColor(52, 211, 153); // Emerald
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetXY(0, 132);
        $pdf->Cell($w, 10, 'Score : ' . $score . '/' . $totalPoints . ' (' . $percentage . '%)', 0, 1, 'C');

        // === Date ===
        $pdf->SetTextColor(148, 163, 184);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetXY(0, 148);
        $pdf->Cell($w, 8, 'Date : ' . $completedAt->format('d/m/Y'), 0, 1, 'C');

        // === Signatures area ===
        $pdf->SetDrawColor(148, 163, 184);
        $pdf->SetLineWidth(0.3);

        // Left signature
        $pdf->Line(50, 175, 120, 175);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetXY(50, 176);
        $pdf->Cell(70, 6, 'Le Coach', 0, 0, 'C');

        // Right signature
        $pdf->Line($w - 120, 175, $w - 50, 175);
        $pdf->SetXY($w - 120, 176);
        $pdf->Cell(70, 6, 'EchoCare Platform', 0, 0, 'C');

        // === Certificate ID ===
        $certId = 'EC-' . strtoupper(substr(md5($studentName . $formationTitle . $completedAt->format('Y-m-d')), 0, 8));
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY(0, $h - 25);
        $pdf->Cell($w, 6, 'Certificat N° ' . $certId, 0, 1, 'C');

        return $pdf->Output('', 'S'); // Return as string
    }
}