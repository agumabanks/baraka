<?php

namespace App\Services;

use App\Models\Backend\Parcel;
use Barryvdh\DomPDF\Facade\Pdf;
use DNS1D;

class Gs1LabelGenerator
{
    /**
     * Generate GS1 Logistics Label PDF (A6/4x6 format)
     */
    public static function generateLabel(Parcel $parcel): string
    {
        $data = self::prepareLabelData($parcel);
        $barcode = self::generateGs1Barcode($parcel->sscc);

        $html = self::getLabelTemplate($data, $barcode);

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a6', 'portrait')
            ->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'dpi' => 300,
            ]);

        return $pdf->output();
    }

    /**
     * Generate bulk labels for multiple parcels
     */
    public static function generateBulkLabels(Collection $parcels): string
    {
        $html = '<div style="display: flex; flex-wrap: wrap;">';

        foreach ($parcels as $parcel) {
            $data = self::prepareLabelData($parcel);
            $barcode = self::generateGs1Barcode($parcel->sscc);
            $html .= '<div style="page-break-after: always;">' .
                     self::getLabelTemplate($data, $barcode) .
                     '</div>';
        }

        $html .= '</div>';

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a6', 'portrait')
            ->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'dpi' => 300,
            ]);

        return $pdf->output();
    }

    /**
     * Prepare data for label template
     */
    private static function prepareLabelData(Parcel $parcel): array
    {
        return [
            'sscc' => $parcel->sscc,
            'tracking_id' => $parcel->tracking_id,
            'customer_name' => $parcel->customer_name,
            'customer_address' => $parcel->customer_address,
            'weight' => $parcel->weight . ' kg',
            'dimensions' => sprintf('%dx%dx%d cm',
                $parcel->length ?? 0,
                $parcel->width ?? 0,
                $parcel->height ?? 0
            ),
            'contents' => $parcel->contents ?? 'General Goods',
            'origin_branch' => $parcel->shipment->originBranch->name ?? 'N/A',
            'dest_branch' => $parcel->shipment->destBranch->name ?? 'N/A',
            'service_level' => $parcel->shipment->service_level ?? 'STANDARD',
            'declared_value' => $parcel->declared_value ? '€' . number_format($parcel->declared_value, 2) : 'N/A',
            'created_date' => $parcel->created_at->format('d/m/Y'),
        ];
    }

    /**
     * Generate GS1-128 barcode for SSCC
     */
    private static function generateGs1Barcode(string $sscc): string
    {
        // GS1-128 barcode with SSCC (Application Identifier 00)
        $barcodeData = '(00)' . SsccGenerator::clean($sscc);

        return DNS1D::getBarcodeHTML($barcodeData, 'C128', 2, 60, 'black', true);
    }

    /**
     * Get HTML template for GS1 Logistics Label
     */
    private static function getLabelTemplate(array $data, string $barcode): string
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    font-size: 8px;
                    margin: 0;
                    padding: 5px;
                    width: 105mm;
                    height: 148mm;
                }
                .header {
                    text-align: center;
                    font-size: 12px;
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                .barcode-section {
                    text-align: center;
                    margin: 10px 0;
                }
                .info-section {
                    display: flex;
                    justify-content: space-between;
                    margin: 5px 0;
                }
                .info-left, .info-right {
                    width: 48%;
                }
                .field {
                    margin: 2px 0;
                }
                .field-label {
                    font-weight: bold;
                    display: inline-block;
                    width: 35px;
                }
                .field-value {
                    display: inline-block;
                }
                .footer {
                    text-align: center;
                    font-size: 6px;
                    margin-top: 10px;
                    border-top: 1px solid #000;
                    padding-top: 5px;
                }
                .large-text {
                    font-size: 14px;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <div class="header">
                GS1 LOGISTICS LABEL
            </div>

            <div class="barcode-section">
                ' . $barcode . '
                <div style="font-size: 10px; margin-top: 5px;">
                    (00) ' . $data['sscc'] . '
                </div>
            </div>

            <div class="info-section">
                <div class="info-left">
                    <div class="field">
                        <span class="field-label">Track:</span>
                        <span class="field-value large-text">' . $data['tracking_id'] . '</span>
                    </div>
                    <div class="field">
                        <span class="field-label">To:</span>
                        <span class="field-value">' . $data['customer_name'] . '</span>
                    </div>
                    <div class="field">
                        <span class="field-label">Addr:</span>
                        <span class="field-value">' . substr($data['customer_address'], 0, 30) . '</span>
                    </div>
                    <div class="field">
                        <span class="field-label">Weight:</span>
                        <span class="field-value">' . $data['weight'] . '</span>
                    </div>
                    <div class="field">
                        <span class="field-label">Dims:</span>
                        <span class="field-value">' . $data['dimensions'] . '</span>
                    </div>
                </div>

                <div class="info-right">
                    <div class="field">
                        <span class="field-label">From:</span>
                        <span class="field-value">' . $data['origin_branch'] . '</span>
                    </div>
                    <div class="field">
                        <span class="field-label">To:</span>
                        <span class="field-value">' . $data['dest_branch'] . '</span>
                    </div>
                    <div class="field">
                        <span class="field-label">Service:</span>
                        <span class="field-value">' . $data['service_level'] . '</span>
                    </div>
                    <div class="field">
                        <span class="field-label">Value:</span>
                        <span class="field-value">' . $data['declared_value'] . '</span>
                    </div>
                    <div class="field">
                        <span class="field-label">Date:</span>
                        <span class="field-value">' . $data['created_date'] . '</span>
                    </div>
                </div>
            </div>

            <div class="field" style="margin-top: 10px;">
                <span class="field-label">Contents:</span>
                <span class="field-value">' . $data['contents'] . '</span>
            </div>

            <div class="footer">
                Generated by Baraka ERP • GS1 Compliant
            </div>
        </body>
        </html>';
    }

    /**
     * Save label to file
     */
    public static function saveLabelToFile(Parcel $parcel, string $path): bool
    {
        $pdfContent = self::generateLabel($parcel);

        return file_put_contents($path, $pdfContent) !== false;
    }

    /**
     * Get label as base64 string
     */
    public static function getLabelAsBase64(Parcel $parcel): string
    {
        $pdfContent = self::generateLabel($parcel);
        return base64_encode($pdfContent);
    }
}