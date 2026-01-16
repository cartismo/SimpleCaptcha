<?php

namespace Modules\SimpleCaptcha\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\HasMultiStoreModuleSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    use HasMultiStoreModuleSettings;

    protected function getModuleSlug(): string
    {
        return 'simple-captcha';
    }

    protected function getDefaultSettings(): array
    {
        return [
            'enabled' => false,
            'type' => 'math',
            'difficulty' => 'easy',
            'case_sensitive' => false,
            'length' => 5,
            'expiry' => 300,
            'protected_forms' => [
                'login' => true,
                'register' => true,
                'checkout_guest' => true,
                'contact' => true,
                'newsletter' => false,
                'reviews' => false,
            ],
            'sort_order' => 1,
        ];
    }

    public function index(): Response
    {
        return Inertia::render('SimpleCaptcha::Admin/Settings', $this->getMultiStoreData());
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'is_enabled' => 'boolean',
            'settings.enabled' => 'boolean',
            'settings.type' => 'required|in:math,image',
            'settings.difficulty' => 'required|in:easy,medium,hard',
            'settings.case_sensitive' => 'boolean',
            'settings.length' => 'integer|min:4|max:8',
            'settings.expiry' => 'integer|min:60|max:900',
            'settings.protected_forms' => 'array',
            'settings.protected_forms.login' => 'boolean',
            'settings.protected_forms.register' => 'boolean',
            'settings.protected_forms.checkout_guest' => 'boolean',
            'settings.protected_forms.contact' => 'boolean',
            'settings.protected_forms.newsletter' => 'boolean',
            'settings.protected_forms.reviews' => 'boolean',
            'settings.sort_order' => 'integer|min:0',
        ]);

        return $this->saveStoreSettings($request);
    }
}