<?php

namespace App\Http\Controllers\Web\Backend\Settings;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\RedirectResponse;

class AppleController extends Controller
{
    public function __construct()
    {
        Config::set('services.apple.client_id', env('APPLE_CLIENT_ID'));
        Config::set('services.apple.team_id', env('APPLE_TEAM_ID'));
        Config::set('services.apple.key_id', env('APPLE_KEY_ID'));
        Config::set('services.apple.private_key', env('APPLE_PRIVATE_KEY'));
        Config::set('services.apple.redirect', env('APPLE_REDIRECT_URL'));
    }

    public function index(): View
    {
        $settings = [
            'apple_client_id'    => env('APPLE_CLIENT_ID', ''),
            'apple_team_id'      => env('APPLE_TEAM_ID', ''),
            'apple_key_id'       => env('APPLE_KEY_ID', ''),
            'apple_private_key'  => env('APPLE_PRIVATE_KEY', ''),
            'apple_redirect_url' => env('APPLE_REDIRECT_URL', ''),
        ];

 

        return view('backend.layouts.settings.apple_settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'apple_client_id'    => 'nullable|string',
            'apple_team_id'      => 'nullable|string',
            'apple_key_id'       => 'nullable|string',
            'apple_private_key'  => 'nullable|string',
            'apple_redirect_url' => 'nullable|string',
        ]);

        try {
            $envContent = File::get(base_path('.env'));
            $lineBreak  = "\n";
            $envContent = preg_replace([
                '/APPLE_CLIENT_ID=(.*)\s*/',
                '/APPLE_TEAM_ID=(.*)\s*/',
                '/APPLE_KEY_ID=(.*)\s*/',
                '/APPLE_PRIVATE_KEY=(.*)\s*/',
                '/APPLE_REDIRECT_URL=(.*)\s*/',
            ], [
                'APPLE_CLIENT_ID=' . $request->apple_client_id . $lineBreak,
                'APPLE_TEAM_ID=' . $request->apple_team_id . $lineBreak,
                'APPLE_KEY_ID=' . $request->apple_key_id . $lineBreak,
                'APPLE_PRIVATE_KEY=' . $request->apple_private_key . $lineBreak,
                'APPLE_REDIRECT_URL=' . $request->apple_redirect_url . $lineBreak,
            ], $envContent);

            File::put(base_path('.env'), $envContent);

            return back()->with('t-success', 'Apple settings updated successfully');
        } catch (Exception) {
            return back()->with('t-error', 'Failed to update Apple settings');
        }
    }
}
