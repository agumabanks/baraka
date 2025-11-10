<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    /**
     * Handle an incoming registration request.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'phone' => ['sometimes', 'string', 'max:20'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'role_id' => ['sometimes', 'integer', 'exists:roles,id'],
            ], [
                'name.required' => 'Name field is required',
                'name.max' => 'Name cannot exceed 255 characters',
                'email.required' => 'Email address is required',
                'email.email' => 'Please provide a valid email address',
                'email.max' => 'Email cannot exceed 255 characters',
                'email.unique' => 'This email address is already registered',
                'password.required' => 'Password is required',
                'password.min' => 'Password must be at least 8 characters',
                'password.confirmed' => 'Password confirmation does not match',
                'role_id.exists' => 'Invalid role selected',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'type' => 'validation_error',
                    'message' => 'The given data was invalid.',
                    'errors' => $validator->errors(),
                    'timestamp' => now()->toISOString(),
                ], 422);
            }

            // Create the user
            $validated = $validator->validated();
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'role_id' => $validated['role_id'] ?? 3, // Default to user role
                'status' => 1,
                'email_verified_at' => now(), // Auto-verify for API
                'remember_token' => Str::random(10),
            ]);

            // Assign default permissions
            $user->givePermissionTo(['dashboard_read', 'profile_read']);

            // Fire registration event
            event(new Registered($user));

            // Log the registration
            Log::channel('auth')->info('New user registered successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'type' => 'registration.success',
                'message' => 'User registered successfully',
                'data' => [
                    'user' => new UserResource($user),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ],
                'timestamp' => now()->toISOString(),
            ], 201);

        } catch (\Exception $e) {
            // Log the error
            Log::channel('auth')->error('Registration failed: ' . $e->getMessage(), [
                'email' => $request->get('email'),
                'name' => $request->get('name'),
                'ip' => $request->ip(),
                'error_trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'type' => 'registration.error',
                'message' => 'Registration failed: ' . (config('app.debug') ? $e->getMessage() : 'Registration failed'),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Quick register for mobile apps (minimal data required)
     */
    public function quickRegister(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
                'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:6'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'type' => 'validation_error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->get('email'),
                'password' => Hash::make($request->password),
                'role_id' => 3, // Default user
                'status' => 1,
                'email_verified_at' => now(),
            ]);

            $user->givePermissionTo(['dashboard_read', 'profile_read']);

            Log::channel('auth')->info('Quick registration completed', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Quick registration successful',
                'data' => new UserResource($user),
            ], 201);

        } catch (\Exception $e) {
            Log::channel('auth')->error('Quick registration failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Quick registration failed',
            ], 500);
        }
    }

    /**
     * Check email availability
     */
    public function checkEmailAvailability(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'string', 'email', 'max:255'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $exists = User::where('email', $request->email)->exists();

            return response()->json([
                'success' => true,
                'available' => !$exists,
                'message' => $exists ? 'Email is already registered' : 'Email is available',
            ]);

        } catch (\Exception $e) {
            Log::channel('auth')->error('Email availability check failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to check email availability',
            ], 500);
        }
    }

    /**
     * Check phone availability
     */
    public function checkPhoneAvailability(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => ['required', 'string', 'max:20'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $exists = User::where('phone', $request->phone)->exists();

            return response()->json([
                'success' => true,
                'available' => !$exists,
                'message' => $exists ? 'Phone number is already registered' : 'Phone number is available',
            ]);

        } catch (\Exception $e) {
            Log::channel('auth')->error('Phone availability check failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to check phone availability',
            ], 500);
        }
    }
}

class RegisterRequest extends \Illuminate\Foundation\Http\FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['sometimes', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['sometimes', 'integer', 'exists:roles,id'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => trans_db('validation.name_required'),
            'name.max' => trans_db('validation.name_max'),
            'email.required' => trans_db('validation.email_required'),
            'email.email' => trans_db('validation.email_invalid'),
            'email.unique' => trans_db('validation.email_unique'),
            'phone.unique' => trans_db('validation.phone_unique'),
            'password.required' => trans_db('validation.password_required'),
            'password.min' => trans_db('validation.password_min'),
            'password.confirmed' => trans_db('validation.password_confirmed'),
            'role_id.exists' => trans_db('validation.role_exists'),
            'device_name.max' => trans_db('validation.device_name_max'),
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function configureValidator(\Illuminate\Validation\Validator $validator)
    {
        $validator->after(function ($validator) {
            $password = $this->input('password');
            $email = $this->input('email');
            $phone = $this->input('phone');
            $name = $this->input('name');

            // Check password strength
            if ($password) {
                $weakPasswords = ['password', '12345678', 'qwerty123', 'abc12345', 'password123'];
                if (in_array(strtolower($password), $weakPasswords)) {
                    $validator->errors()->add('password', trans_db('validation.password_weak'));
                }

                // Check if password is too similar to email name
                if ($email && str_contains(strtolower($password), strtolower(explode('@', $email)[0] ?? ''))) {
                    $validator->errors()->add('password', trans_db('validation.password_email_similarity'));
                }

                // Check if password contains name
                if ($name && str_contains(strtolower($password), strtolower($name))) {
                    $validator->errors()->add('password', trans_db('validation.password_name_similarity'));
                }
            }

            // Validate phone format if provided
            if ($phone && !$this->isValidPhone($phone)) {
                $validator->errors()->add('phone', trans_db('validation.phone_invalid'));
            }
        });
    }

    /**
     * Validate phone number format.
     */
    private function isValidPhone(string $phone): bool
    {
        // Remove all non-digit characters for validation
        $digits = preg_replace('/\D/', '', $phone);
        
        // Allow phone numbers between 8 and 15 digits
        return strlen($digits) >= 8 && strlen($digits) <= 15;
    }

    /**
     * Custom validation rules.
     */
    protected function beforeValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->input('email')))
            ]);
        }

        if ($this->has('phone')) {
            $this->merge([
                'phone' => preg_replace('/\D/', '', $this->input('phone'))
            ]);
        }
    }
}
