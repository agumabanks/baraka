<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Branch\Concerns\ResolvesBranch;
use App\Http\Controllers\Controller;
use App\Support\BranchCache;
use App\Support\SystemSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchSettingsController extends Controller
{
    use ResolvesBranch;

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $supportedLocales = translation_supported_languages();
        $settings = $branch->metadata['settings'] ?? [];

        return view('branch.settings', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'supportedLocales' => $supportedLocales,
            'systemLocale' => SystemSettings::defaultLocale(),
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $supportedLocales = translation_supported_languages();

        $payload = $request->validate([
            'preferred_language' => 'nullable|string|in:'.implode(',', $supportedLocales),
            'timezone' => 'nullable|string|max:120',
            'display_name' => 'nullable|string|max:120',
            'contact_email' => 'nullable|email',
            'sla_threshold' => 'nullable|integer|min:0|max:100',
            'operating_notes' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:8',
        ]);

        $metadata = $branch->metadata ?? [];
        $metadata['settings'] = array_merge($metadata['settings'] ?? [], $payload);

        if (Schema::hasColumn('branches', 'settings')) {
            $branch->settings = array_merge($branch->settings ?? [], $payload);
        }

        $branch->metadata = $metadata;
        $branch->save();

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Branch settings updated.');
    }
}
