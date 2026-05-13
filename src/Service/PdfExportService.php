<?php

namespace App\Service;

use App\Entity\Intervention;
use App\Entity\Urgence;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;

class PdfExportService
{
    private Dompdf $dompdf;

    public function __construct()
    {
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $this->dompdf = new Dompdf($options);
    }

    /**
     * Export urgences to PDF (matching Java PDFExporter)
     */
    public function exportUrgencesToPDF(array $urgences, string $title): Response
    {
        $html = $this->generateUrgencesHTML($urgences, $title);

        $this->dompdf->loadHtml($html, 'UTF-8');
        $this->dompdf->setPaper('A4', 'landscape');
        $this->dompdf->render();

        return new Response(
            $this->dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="urgences_' . date('Ymd_His') . '.pdf"',
            ]
        );
    }

    /**
     * Export interventions to PDF (matching Java PDFExporter)
     */
    public function exportInterventionsToPDF(array $interventions, string $title): Response
    {
        $html = $this->generateInterventionsHTML($interventions, $title);

        $this->dompdf->loadHtml($html, 'UTF-8');
        $this->dompdf->setPaper('A4', 'landscape');
        $this->dompdf->render();

        return new Response(
            $this->dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="interventions_' . date('Ymd_His') . '.pdf"',
            ]
        );
    }

    /**
     * Generate HTML for urgences PDF (matching Java styling)
     */
    private function generateUrgencesHTML(array $urgences, string $title): string
    {
        $date = date('d/m/Y H:i:s');
        $total = count($urgences);

        $rows = '';
        $evenRow = false;

        foreach ($urgences as $urgence) {
            $bgColor = $evenRow ? '#f8f8f8' : '#ffffff';
            $rows .= $this->generateUrgenceRow($urgence, $bgColor);
            $evenRow = !$evenRow;
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>FeelSafe - {$title}</title>
    <style>
        body {
            font-family: Helvetica, sans-serif;
            margin: 20px;
            font-size: 10px;
        }
        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #2e7d32;
        }
        .date {
            text-align: right;
            font-size: 9px;
            color: #666;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #e6e6e6;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #f0f0f0;
        }
        .summary {
            font-size: 10px;
            font-weight: bold;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            font-size: 8px;
            color: #999;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="title">{$title}</div>
    <div class="date">Généré le: {$date}</div>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Type</th>
                <th>Description</th>
                <th>Gravité</th>
                <th>Statut</th>
                <th>Date/Heure</th>
                <th>ID User</th>
            </tr>
        </thead>
        <tbody>
            {$rows}
        </tbody>
    </table>
    
    <div class="summary">Total des urgences: {$total}</div>
    <div class="footer">FeelSafe - Votre bien-être mental</div>
</body>
</html>
HTML;
    }

    /**
     * Generate HTML for interventions PDF (matching Java styling)
     */
    private function generateInterventionsHTML(array $interventions, string $title): string
    {
        $date = date('d/m/Y H:i:s');
        $total = count($interventions);

        $rows = '';
        $evenRow = false;

        foreach ($interventions as $intervention) {
            $bgColor = $evenRow ? '#f8f8f8' : '#ffffff';
            $rows .= $this->generateInterventionRow($intervention, $bgColor);
            $evenRow = !$evenRow;
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>FeelSafe - {$title}</title>
    <style>
        body {
            font-family: Helvetica, sans-serif;
            margin: 20px;
            font-size: 10px;
        }
        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #2e7d32;
        }
        .date {
            text-align: right;
            font-size: 9px;
            color: #666;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #e6e6e6;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #f0f0f0;
        }
        .summary {
            font-size: 10px;
            font-weight: bold;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            font-size: 8px;
            color: #999;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="title">{$title}</div>
    <div class="date">Généré le: {$date}</div>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>ID Urgence</th>
                <th>Type</th>
                <th>Statut</th>
                <th>Date/Heure</th>
                <th>Notes</th>
                <th>ID Admin</th>
            </tr>
        </thead>
        <tbody>
            {$rows}
        </tbody>
    </table>
    
    <div class="summary">Total des interventions: {$total}</div>
    <div class="footer">FeelSafe - Votre bien-être mental</div>
</body>
</html>
HTML;
    }

    private function generateUrgenceRow(Urgence $urgence, string $bgColor): string
    {
        $description = $urgence->getDescription() ?? '';
        $truncatedDesc = strlen($description) > 50 ? substr($description, 0, 47) . '...' : $description;

        $gravity = $urgence->getNiveauGravite();
        $gravityClass = '';
        if ($gravity >= 5)
            $gravityClass = 'color: #c62828; font-weight: bold;';
        elseif ($gravity >= 4)
            $gravityClass = 'color: #ef6c00; font-weight: bold;';
        elseif ($gravity >= 3)
            $gravityClass = 'color: #f9a825;';
        else
            $gravityClass = 'color: #2e7d32;';

        $status = $urgence->getStatut();
        $statusClass = '';
        if ($status === Urgence::STATUT_EN_ATTENTE)
            $statusClass = 'color: #ef6c00;';
        elseif ($status === Urgence::STATUT_PRISE_EN_CHARGE)
            $statusClass = 'color: #1565c0;';
        else
            $statusClass = 'color: #2e7d32;';

        $date = $urgence->getDateHeure() ? $urgence->getDateHeure()->format('d/m/Y H:i') : '';

        return <<<HTML
        <tr style="background-color: {$bgColor};">
            <td>{$urgence->getId()}</td>
            <td>{$urgence->getTypeUrgence()}</td>
            <td>{$truncatedDesc}</td>
            <td style="{$gravityClass}">{$gravity}</td>
            <td style="{$statusClass}">{$status}</td>
            <td>{$date}</td>
            <td>{$urgence->getIdUtilisateur()}</td>
        </tr>
HTML;
    }

    private function generateInterventionRow(Intervention $intervention, string $bgColor): string
    {
        $notes = $intervention->getNotes() ?? '';
        $truncatedNotes = strlen($notes) > 40 ? substr($notes, 0, 37) . '...' : $notes;

        $date = $intervention->getDateHeure() ? $intervention->getDateHeure()->format('d/m/Y H:i') : '';

        return <<<HTML
        <tr style="background-color: {$bgColor};">
            <td>{$intervention->getId()}</td>
            <td>{$intervention->getIdUrgence()}</td>
            <td>{$intervention->getTypeIntervention()}</td>
            <td>{$intervention->getStatut()}</td>
            <td>{$date}</td>
            <td>{$truncatedNotes}</td>
            <td>{$intervention->getIdAdmin()}</td>
        </tr>
HTML;
    }
}