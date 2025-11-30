<?php

namespace App\Services\Finance;

use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * CurrencyService
 * 
 * Multi-currency support:
 * - Exchange rate management
 * - Currency conversion
 * - Rate updates from external APIs
 */
class CurrencyService
{
    protected array $supportedCurrencies = [
        'USD' => 'US Dollar',
        'EUR' => 'Euro',
        'TRY' => 'Turkish Lira',
        'CDF' => 'Congolese Franc',
        'RWF' => 'Rwandan Franc',
        'UGX' => 'Ugandan Shilling',
        'KES' => 'Kenyan Shilling',
        'TZS' => 'Tanzanian Shilling',
        'GBP' => 'British Pound',
    ];

    protected string $baseCurrency = 'USD';

    /**
     * Get supported currencies
     */
    public function getSupportedCurrencies(): array
    {
        return $this->supportedCurrencies;
    }

    /**
     * Get exchange rate
     */
    public function getRate(string $from, string $to, ?Carbon $date = null): ?float
    {
        $cacheKey = "exchange_rate_{$from}_{$to}_" . ($date?->toDateString() ?? 'current');
        
        return Cache::remember($cacheKey, 3600, function () use ($from, $to, $date) {
            return ExchangeRate::getRate($from, $to, $date);
        });
    }

    /**
     * Convert amount between currencies
     */
    public function convert(float $amount, string $from, string $to, ?Carbon $date = null): array
    {
        if ($from === $to) {
            return [
                'original_amount' => $amount,
                'converted_amount' => $amount,
                'from_currency' => $from,
                'to_currency' => $to,
                'rate' => 1.0,
                'date' => $date?->toDateString() ?? now()->toDateString(),
            ];
        }

        $rate = $this->getRate($from, $to, $date);

        if ($rate === null) {
            throw new \Exception("Exchange rate not available for {$from} to {$to}");
        }

        return [
            'original_amount' => round($amount, 2),
            'converted_amount' => round($amount * $rate, 2),
            'from_currency' => $from,
            'to_currency' => $to,
            'rate' => $rate,
            'date' => $date?->toDateString() ?? now()->toDateString(),
        ];
    }

    /**
     * Set exchange rate manually
     */
    public function setRate(string $from, string $to, float $rate, ?Carbon $date = null): ExchangeRate
    {
        $exchangeRate = ExchangeRate::setRate($from, $to, $rate, $date, 'manual');
        
        // Clear cache
        Cache::forget("exchange_rate_{$from}_{$to}_" . ($date?->toDateString() ?? 'current'));
        
        return $exchangeRate;
    }

    /**
     * Update rates from external API
     */
    public function updateRatesFromApi(): array
    {
        $apiKey = config('services.exchange_rates.api_key');
        $apiUrl = config('services.exchange_rates.api_url', 'https://api.exchangerate-api.com/v4/latest');

        if (!$apiKey && !str_contains($apiUrl, 'exchangerate-api.com')) {
            return ['success' => false, 'message' => 'Exchange rate API not configured'];
        }

        try {
            $response = Http::get("{$apiUrl}/{$this->baseCurrency}");

            if (!$response->successful()) {
                return ['success' => false, 'message' => 'API request failed'];
            }

            $data = $response->json();
            $rates = $data['rates'] ?? [];
            $updatedCount = 0;

            foreach ($this->supportedCurrencies as $currency => $name) {
                if ($currency === $this->baseCurrency) {
                    continue;
                }

                if (isset($rates[$currency])) {
                    ExchangeRate::setRate($this->baseCurrency, $currency, $rates[$currency], now(), 'api');
                    $updatedCount++;
                }
            }

            // Clear all rate caches
            foreach ($this->supportedCurrencies as $currency => $name) {
                Cache::forget("exchange_rate_{$this->baseCurrency}_{$currency}_current");
                Cache::forget("exchange_rate_{$currency}_{$this->baseCurrency}_current");
            }

            return [
                'success' => true,
                'updated_count' => $updatedCount,
                'base_currency' => $this->baseCurrency,
                'updated_at' => now()->toIso8601String(),
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get all current rates
     */
    public function getAllRates(): array
    {
        $rates = [];

        foreach ($this->supportedCurrencies as $currency => $name) {
            if ($currency === $this->baseCurrency) {
                $rates[$currency] = [
                    'currency' => $currency,
                    'name' => $name,
                    'rate' => 1.0,
                    'is_base' => true,
                ];
                continue;
            }

            $rate = $this->getRate($this->baseCurrency, $currency);
            $rates[$currency] = [
                'currency' => $currency,
                'name' => $name,
                'rate' => $rate,
                'is_base' => false,
            ];
        }

        return $rates;
    }

    /**
     * Format amount with currency symbol
     */
    public function format(float $amount, string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'UGX' => 'UGX ',
            'KES' => 'KES ',
            'TZS' => 'TZS ',
            'RWF' => 'RWF ',
            'EUR' => '€',
            'GBP' => '£',
        ];

        $symbol = $symbols[$currency] ?? $currency . ' ';
        
        // Format with appropriate decimal places
        $decimals = in_array($currency, ['UGX', 'KES', 'TZS', 'RWF']) ? 0 : 2;
        
        return $symbol . number_format($amount, $decimals);
    }

    /**
     * Get rate history
     */
    public function getRateHistory(string $from, string $to, int $days = 30): array
    {
        return ExchangeRate::where('base_currency', $from)
            ->where('target_currency', $to)
            ->where('effective_date', '>=', now()->subDays($days))
            ->orderBy('effective_date')
            ->get()
            ->map(fn($rate) => [
                'date' => $rate->effective_date->format('Y-m-d'),
                'rate' => $rate->rate,
                'source' => $rate->source,
            ])
            ->toArray();
    }
}
