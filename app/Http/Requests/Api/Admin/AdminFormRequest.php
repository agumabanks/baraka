<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Resources\ValidationErrorResource;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class AdminFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        $response = (new ValidationErrorResource($validator->errors()))
            ->response()
            ->setStatusCode(422);

        throw new HttpResponseException($response);
    }
}
