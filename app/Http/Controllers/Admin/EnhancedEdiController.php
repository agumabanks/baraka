<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EdiMapping;
use App\Models\EdiTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnhancedEdiController extends Controller
{
    /**
     * Enhanced EDI controller with transformation pipelines and acknowledgments
     */
    public function receiveDocs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'edi_type' => 'required|in:850,856,997',
            'document' => 'required|string',
            'sender_code' => 'required|string',
            'receiver_code' => 'required|string',
            'reference' => 'required|string',
        ]);

        try {
            // Store raw document
            $transaction = EdiTransaction::create([
                'edi_type' => $validated['edi_type'],
                'sender_code' => $validated['sender_code'],
                'receiver_code' => $validated['receiver_code'],
                'reference' => $validated['reference'],
                'raw_document' => $validated['document'],
                'status' => 'received',
            ]);

            // Transform document based on mapping
            $mapping = EdiMapping::where('edi_type', $validated['edi_type'])
                ->where('sender_code', $validated['sender_code'])
                ->firstOrFail();

            $transformed = $this->transformDocument(
                $validated['document'],
                $mapping
            );

            // Process transformation
            $this->processTransformation($transaction, $transformed);

            // Send acknowledgment
            $ack = $this->generateAcknowledgment($transaction);

            Log::info('EDI document received and processed', [
                'type' => $validated['edi_type'],
                'sender' => $validated['sender_code'],
                'transaction_id' => $transaction->id,
            ]);

            return response()->json([
                'success' => true,
                'transaction_id' => $transaction->id,
                'ack' => $ack,
            ]);
        } catch (\Throwable $e) {
            Log::error('EDI document processing failed', [
                'error' => $e->getMessage(),
                'sender' => $validated['sender_code'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Transform EDI document using configurable mapping
     */
    private function transformDocument(string $document, EdiMapping $mapping): array
    {
        // Parse EDI document (850, 856, etc.)
        $segments = explode("\n", $document);
        $data = [];

        foreach ($segments as $segment) {
            $fields = explode('*', rtrim($segment, '~'));
            $segmentId = $fields[0];

            // Apply mapping transformation
            if (isset($mapping->transformations[$segmentId])) {
                $transformation = $mapping->transformations[$segmentId];
                $data[$segmentId] = $this->applyTransformation($fields, $transformation);
            }
        }

        return $data;
    }

    /**
     * Apply field-level transformation rules
     */
    private function applyTransformation(array $fields, array $rules): array
    {
        $result = [];

        foreach ($rules as $field => $rule) {
            if (isset($fields[$rule['position']])) {
                $value = $fields[$rule['position']];

                // Apply validation/transformation
                if (isset($rule['type'])) {
                    $value = $this->typecast($value, $rule['type']);
                }

                if (isset($rule['mapping'])) {
                    $value = $rule['mapping'][$value] ?? $value;
                }

                $result[$field] = $value;
            }
        }

        return $result;
    }

    /**
     * Process transformed data and create business objects
     */
    private function processTransformation(EdiTransaction $transaction, array $transformed): void
    {
        // Handle 850 (Purchase Order) → Create shipment booking
        if ($transaction->edi_type === '850') {
            $this->createShipmentFromOrder($transaction, $transformed);
        }

        // Handle 856 (Shipment Notice) → Update tracking
        if ($transaction->edi_type === '856') {
            $this->updateTrackingFromNotice($transaction, $transformed);
        }

        $transaction->update(['status' => 'processed']);
    }

    /**
     * Generate EDI 997 Functional Acknowledgment
     */
    private function generateAcknowledgment(EdiTransaction $transaction): string
    {
        $ack = "ISA*00*          *00*          *ZZ*BARAKA         *ZZ*SENDER         *200101*0000*U*00401*000000001*0*P*:\n";
        $ack .= "GS*FA*BARAKA*SENDER*20200101*000000*1*X*004010\n";
        $ack .= "ST*997*1*005010\n";
        $ack .= "AK1*" . $transaction->edi_type . "*1\n";
        $ack .= "AK2*" . $transaction->reference . "*1\n";
        $ack .= "AK5*A\n"; // Accepted
        $ack .= "AK9*1*1*1\n";
        $ack .= "SE*7*1\n";
        $ack .= "GE*1*1\n";
        $ack .= "IEA*1*1\n";

        return $ack;
    }

    private function createShipmentFromOrder(EdiTransaction $transaction, array $transformed): void
    {
        // Stub: Parse transformed order and create shipment
    }

    private function updateTrackingFromNotice(EdiTransaction $transaction, array $transformed): void
    {
        // Stub: Parse transformed notice and update tracking
    }

    private function typecast(string $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'float' => (float) $value,
            'date' => \Carbon\Carbon::createFromFormat('ymd', $value),
            default => $value,
        };
    }

    /**
     * Get EDI transaction status
     */
    public function getTransactionStatus(int $transactionId): JsonResponse
    {
        $transaction = EdiTransaction::findOrFail($transactionId);

        return response()->json([
            'id' => $transaction->id,
            'type' => $transaction->edi_type,
            'status' => $transaction->status,
            'reference' => $transaction->reference,
            'created_at' => $transaction->created_at,
            'updated_at' => $transaction->updated_at,
        ]);
    }

    /**
     * List EDI mappings
     */
    public function listMappings(): JsonResponse
    {
        $mappings = EdiMapping::all();

        return response()->json([
            'mappings' => $mappings,
        ]);
    }

    /**
     * Create or update EDI mapping
     */
    public function saveMappings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'edi_type' => 'required|in:850,856,997',
            'sender_code' => 'required|string',
            'transformations' => 'required|array',
        ]);

        $mapping = EdiMapping::updateOrCreate(
            ['edi_type' => $validated['edi_type'], 'sender_code' => $validated['sender_code']],
            ['transformations' => $validated['transformations']]
        );

        return response()->json([
            'success' => true,
            'mapping' => $mapping,
        ]);
    }
}
