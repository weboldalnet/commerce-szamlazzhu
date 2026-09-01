<?php

namespace Weboldalnet\CommerceSzamlazzhu\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Weboldalnet\CommerceSzamlazzhu\Services\SzamlazzhuSettingsService;
use Weboldalnet\CommerceSzamlazzhu\Services\SzamlazzhuService;
use Illuminate\Support\Facades\Log;

class SzamlazzhuSettingController extends Controller
{
    public function index()
    {
        // FIGYELEM: a változó neve nem lehet $settings – a platform admin layoutja
        // egy globálisan megosztott $settings modellt használ, azt felülírnánk.
        $szSettings = SzamlazzhuSettingsService::all();

        // A view a DB-ben még nem szereplő mezőknél is a tényleges (config/.env) értéket mutassa
        foreach (SzamlazzhuSettingsService::viewKeys() as $key) {
            if (!array_key_exists($key, $szSettings)) {
                $szSettings[$key] = SzamlazzhuSettingsService::get($key);
            }
        }

        // Titkosított mezők maszkolása a view-nak
        if (!empty($szSettings['agent_api_key'])) $szSettings['agent_api_key'] = '********';
        if (!empty($szSettings['password'])) $szSettings['password'] = '********';

        return view('commerce-szamlazzhu::admin.settings', compact('szSettings'));
    }

    public function update(Request $request)
    {
        $data = $request->all();
        
        $checkboxKeys = [
            'enabled', 'download_pdf', 'auto_pay_online', 
            'send_email_to_customer', 'use_signature'
        ];

        foreach ($data as $key => $value) {
            if ($key === '_token') continue;
            
            $type = 'string';
            if (in_array($key, $checkboxKeys)) {
                $type = 'boolean';
                $value = $value === 'on' || $value === '1' || $value === true;
            } elseif (in_array($key, ['agent_api_key', 'password'])) {
                $type = 'encrypted';
                // Csak akkor mentsük, ha nem a maszkolt értéket küldték vissza
                if ($value === '********') continue;
            }

            SzamlazzhuSettingsService::save($key, $value, $type);
        }

        // Checkboxok, amik nem jöttek át (kikapcsolva)
        foreach ($checkboxKeys as $key) {
            if (!isset($data[$key])) {
                SzamlazzhuSettingsService::save($key, false, 'boolean');
            }
        }

        return redirect()->back()->with('success', 'Beállítások sikeresen mentve.');
    }

    public function testConnection(SzamlazzhuService $service)
    {
        try {
            $result = $service->testConnection();
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a kapcsolódáskor: ' . $e->getMessage()
            ]);
        }
    }
}
