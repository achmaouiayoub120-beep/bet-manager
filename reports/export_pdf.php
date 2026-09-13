<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../lib/fpdf/fpdf.php';

$pdo = getConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT r.*, pr.name AS project_name, pr.project_code, u.full_name AS creator_name
                       FROM reports r
                       LEFT JOIN projects pr ON pr.id = r.project_id
                       LEFT JOIN users u ON u.id = r.created_by
                       WHERE r.id = ?");
$stmt->execute([$id]);
$report = $stmt->fetch();

if (!$report) {
    setFlash('danger', 'Rapport introuvable.');
    header('Location: /reports/index.php');
    exit();
}

$typeLabels = ['technical'=>'Technique','progress'=>'Avancement','inspection'=>'Inspection','calculation'=>'Calcul','other'=>'Autre'];
$statusLabels = ['draft'=>'Brouillon','submitted'=>'Soumis','approved'=>'Approuvé','rejected'=>'Rejeté'];

class PDF extends FPDF {
    public $reportTitle = '';
    public $reportCode = '';

    function Header() {
        $this->SetFillColor(26, 42, 58);
        $this->Rect(0, 0, 210, 25, 'F');
        
        $this->SetFillColor(52, 152, 219);
        $this->Rect(10, 6, 13, 13, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 11);
        $this->SetXY(10, 9);
        $this->Cell(13, 7, 'BC', 0, 0, 'C');
        
        $this->SetFont('Arial', 'B', 14);
        $this->SetXY(27, 9);
        $this->Cell(100, 7, 'BET Manager', 0, 0, 'L');
        
        $this->SetFont('Arial', '', 9);
        $this->SetXY(150, 10);
        $this->Cell(50, 6, 'Edite le ' . date('d/m/Y'), 0, 0, 'R');
        
        $this->SetY(35);
        $this->SetTextColor(26, 42, 58);
        $this->SetFont('Arial', 'B', 16);
        $this->MultiCell(0, 8, utf8_decode($this->reportTitle), 0, 'L');
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(108, 117, 125);
        $this->Cell(0, 5, 'Code : ' . $this->reportCode, 0, 1, 'L');
        $this->Ln(3);
        
        $this->SetDrawColor(52, 152, 219);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-18);
        $this->SetDrawColor(224, 224, 224);
        $this->SetLineWidth(0.2);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->SetY(-14);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(108, 117, 125);
        $this->Cell(0, 10, utf8_decode('BET Manager - Bureau d\'etudes | Page ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->reportTitle = $report['title'];
$pdf->reportCode = $report['report_code'];
$pdf->AddPage();

$pdf->SetFillColor(245, 245, 240);
$pdf->SetTextColor(26, 42, 58);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(50, 7, 'Projet :', 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 7, utf8_decode(($report['project_code'] ? $report['project_code'] . ' - ' . $report['project_name'] : 'Aucun')), 0, 1, 'L');

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(50, 7, 'Type :', 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(60, 7, utf8_decode($typeLabels[$report['report_type']] ?? $report['report_type']), 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(30, 7, 'Statut :', 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 7, utf8_decode($statusLabels[$report['status']] ?? $report['status']), 0, 1, 'L');

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(50, 7, 'Auteur :', 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(60, 7, utf8_decode($report['creator_name'] ?? '-'), 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(30, 7, 'Date :', 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 7, formatDateFr($report['created_at']), 0, 1, 'L');

$pdf->Ln(5);
$pdf->SetDrawColor(224, 224, 224);
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(26, 42, 58);
$pdf->Cell(0, 8, 'Contenu du rapport', 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(40, 40, 40);
$pdf->MultiCell(0, 6, utf8_decode($report['content'] ?: 'Aucun contenu.'), 0, 'L');

logActivity($pdo, 'export', 'report', $id, "Export PDF du rapport : {$report['title']}");

$pdf->Output('I', 'rapport_' . $report['report_code'] . '.pdf');
exit();
?>