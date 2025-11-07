<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V10\GeneralSettingsUpdateRequest;
use App\Repositories\Currency\CurrencyInterface;
use App\Repositories\GeneralSettings\GeneralSettingsInterface;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\JsonResponse;
use Throwable;

class GeneralSettingCotroller extends Controller
{
    use ApiReturnFormatTrait;

    protected $repo;

    protected $currencies;

    public function __construct(GeneralSettingsInterface $repo, CurrencyInterface $currencies)
    {
        $this->repo = $repo;
        $this->currencies = $currencies;
    }

    public function index(): JsonResponse
    {
        $generalSettings = $this->repo->all();
        $generalSettings->append(['logo_image', 'light_logo_image', 'favicon_image']);
        $generalSettings->setAttribute('preferences', $this->repo->preferences($generalSettings));

        return $this->responseWithSuccess('General settings information.', $generalSettings, 200);
    }

    public function update(GeneralSettingsUpdateRequest $request): JsonResponse
    {
        try {
            $settings = $this->repo->update($request);
            $settings->append(['logo_image', 'light_logo_image', 'favicon_image']);
            $settings->setAttribute('preferences', $this->repo->preferences($settings));

            return $this->responseWithSuccess('General settings updated successfully.', $settings, 200);
        } catch (Throwable $exception) {
            report($exception);

            return $this->responseWithError('Unable to update general settings.', [
                'error' => $exception->getMessage(),
            ], 422);
        }
    }

    public function currencies()
    {
        $currencies = $this->currencies->getActive();

        return $this->responseWithSuccess('All Currency.', $currencies, 200);
    }
}
