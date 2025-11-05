<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\MessageBag;

class ValidationErrorResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(MessageBag|array $errors, string $message = 'Validation failed.')
    {
        $normalized = $errors instanceof MessageBag ? $errors->toArray() : $errors;

        parent::__construct([
            'success' => false,
            'message' => $message,
            'errors' => $normalized,
        ]);
    }

    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
