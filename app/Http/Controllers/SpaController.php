<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SpaController extends Controller
{
    /**
     * Serve the compiled React single page application.
     */
    public function __invoke(): Response|BinaryFileResponse
    {
        $spaEntry = $this->resolveSpaEntry();

        if (!$spaEntry) {
            return response('SPA assets are not built yet. Run the React build to generate public/app/index.html.', 503);
        }

        return response()->file($spaEntry);
    }

    private function resolveSpaEntry(): ?string
    {
        $candidates = [
            public_path('app/index.html'),
            public_path('index.html'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
