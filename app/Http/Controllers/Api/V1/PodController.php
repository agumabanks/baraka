<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\ShipmentStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePodRequest;
use App\Http\Requests\Api\V1\VerifyPodRequest;
use App\Http\Resources\Api\V1\PodResource;
use App\Models\PodProof;
use App\Models\Shipment;
use App\Models\DeliveryMan;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Proof of Delivery",
 *     description="API Endpoints for proof of delivery management"
 * )
 */
class PodController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * @OA\Post(
     *     path="/api/v1/tasks/{task}/pod",
     *     summary="Submit proof of delivery",
     *     description="Submit POD with signature, photo, and OTP for verification",
     *     operationId="submitPod",
     *     tags={"Proof of Delivery"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="task",
     *         in="path",
     *         description="Task ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StorePodRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="POD submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="POD submitted"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="pod", ref="#/components/schemas/PodProof")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - not assigned driver"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(StorePodRequest $request, $taskId)
    {
        // TODO: Get shipment from task
        // For now, assume task ID relates to shipment
        $shipment = Shipment::findOrFail($taskId);
        $driver = DeliveryMan::where('user_id', auth()->id())->first();

        if (!$driver || $shipment->driver_id !== $driver->id) {
            return $this->responseWithError('Not authorized to submit POD for this shipment', [], 403);
        }

        // Generate OTP for verification
        $otpCode = Str::random(6);

        // Store signature and photo
        $signaturePath = null;
        $photoPath = null;

        if ($request->signature) {
            $signaturePath = $request->signature->store('pod/signatures', 'public');
        }

        if ($request->photo) {
            $photoPath = $request->photo->store('pod/photos', 'public');
        }

        $pod = PodProof::create([
            'shipment_id' => $shipment->id,
            'driver_id' => $driver->id,
            'signature' => $signaturePath,
            'photo' => $photoPath,
            'otp_code' => $otpCode,
            'notes' => $request->notes,
        ]);

        // TODO: Send OTP to customer for verification

        return $this->responseWithSuccess('POD submitted successfully', [
            'pod' => new PodResource($pod),
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/pod/{pod}/verify",
     *     summary="Verify proof of delivery",
     *     description="Verify POD using OTP code",
     *     operationId="verifyPod",
     *     tags={"Proof of Delivery"},
     *     @OA\Parameter(
     *         name="pod",
     *         in="path",
     *         description="POD ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"otp"},
     *             @OA\Property(property="otp", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="POD verified successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="POD verified")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid OTP or already verified"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="POD not found"
     *     )
     * )
     */
    public function verify(VerifyPodRequest $request, PodProof $pod)
    {
        if ($pod->verify($request->otp)) {
            // Update shipment status to delivered
            $pod->shipment->update(['current_status' => 'delivered']);

            // Broadcast status change
            broadcast(new ShipmentStatusChanged($pod->shipment, null));

            return $this->responseWithSuccess('POD verified successfully');
        }

        return $this->responseWithError('Invalid OTP or POD already verified', [], 400);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/shipments/{shipment}/pod",
     *     summary="Get POD for shipment",
     *     description="Retrieve POD information for a shipment",
     *     operationId="getShipmentPod",
     *     tags={"Proof of Delivery"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="shipment",
     *         in="path",
     *         description="Shipment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="POD retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="pod", ref="#/components/schemas/PodProof")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="POD not found"
     *     )
     * )
     */
    public function show(Shipment $shipment)
    {
        $pod = $shipment->podProof;

        if (!$pod) {
            return $this->responseWithError('POD not found for this shipment', [], 404);
        }

        return $this->responseWithSuccess('POD retrieved successfully', [
            'pod' => new PodResource($pod),
        ]);
    }
}