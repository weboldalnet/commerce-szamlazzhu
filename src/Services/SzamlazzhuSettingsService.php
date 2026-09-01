<?php

namespace Weboldalnet\CommerceSzamlazzhu\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Weboldalnet\CommerceSzamlazzhu\Models\SzamlazzhuSetting;

class SzamlazzhuSettingsService
{
    protected static $cacheKey = 'commerce_szamlazzhu_settings';
    protected static $typeCacheKey = 'commerce_szamlazzhu_setting_types';

    /**
     * A lapos beállítás-kulcsok leképezése a beágyazott config útvonalakra.
     * Enélkül a config (és így az .env) sosem tudna alapértelmezést adni,
     * mert a config szerkezete csoportosított, a beállítás-kulcsok viszont laposak.
     */
    protected const CONFIG_PATH_MAP = [
        'agent_api_key'          => 'authentication.agent_api_key',
        'username'               => 'authentication.username',
        'password'               => 'authentication.password',
        'invoice_type'           => 'defaults.invoice_type',
        'language'               => 'defaults.language',
        'currency'               => 'defaults.currency',
        'vat_key'                => 'defaults.vat_key',
        'payment_deadline_days'  => 'defaults.payment_deadline_days',
        'invoice_prefix'         => 'defaults.invoice_prefix',
        'unit_of_measure'        => 'defaults.unit_of_measure',
        'auto_pay_online'        => 'defaults.auto_pay_online',
        'send_email_to_customer' => 'defaults.send_email_to_customer',
        'bank_name'              => 'seller.bank_name',
        'bank_account_number'    => 'seller.bank_account_number',
        'email_from_name'        => 'seller.email_from_name',
        'email_from_address'     => 'seller.email_from_address',
        'email_subject'          => 'seller.email_subject',
        'email_content'          => 'seller.email_content',
        'use_signature'          => 'seller.use_signature',
    ];

    /**
     * Az admin beállítófelületen szerkeszthető kulcsok.
     * Ezekre a DB érték hiányában is mutatni kell a tényleges (config/.env) értéket.
     */
    public static function viewKeys(): array
    {
        return [
            'enabled', 'billing_mode',
            'agent_api_key', 'username', 'password',
            'invoice_type', 'language', 'currency', 'vat_key',
            'payment_deadline_days', 'invoice_prefix', 'unit_of_measure',
            'download_pdf', 'auto_pay_online', 'send_email_to_customer',
            'bank_name', 'bank_account_number',
            'email_from_address', 'use_signature',
        ];
    }

    public static function all()
    {
        try {
            return Cache::rememberForever(self::$cacheKey, function () {
                return SzamlazzhuSetting::all()->pluck('value', 'key')->toArray();
            });
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Kulcs => típus térkép. Egy lekérdezésből, cache-elve – korábban minden
     * egyes get() hívás külön SELECT-et indított.
     */
    protected static function types(): array
    {
        try {
            return Cache::rememberForever(self::$typeCacheKey, function () {
                return SzamlazzhuSetting::all()->pluck('type', 'key')->toArray();
            });
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Config alapértelmezés egy lapos beállítás-kulcshoz.
     */
    protected static function configDefault($key, $default = null)
    {
        // Előbb lapos kulcs (pl. enabled, billing_mode), utána a leképezett útvonal.
        $flat = config('commerce-szamlazzhu.' . $key);
        if ($flat !== null) {
            return $flat;
        }

        if (isset(self::CONFIG_PATH_MAP[$key])) {
            $mapped = config('commerce-szamlazzhu.' . self::CONFIG_PATH_MAP[$key]);
            if ($mapped !== null && $mapped !== '') {
                return $mapped;
            }
        }

        return $default;
    }

    public static function get($key, $default = null)
    {
        $settings = self::all();
        $hasDbValue = array_key_exists($key, $settings) && $settings[$key] !== null && $settings[$key] !== '';
        $value = $hasDbValue ? $settings[$key] : self::configDefault($key, $default);

        $type = self::types()[$key] ?? null;

        // A titkosítás csak a DB-ben tárolt értékre vonatkozik, a config/.env értéke nyers.
        if ($hasDbValue && $type === 'encrypted' && $value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Throwable $e) {
                return $value;
            }
        }

        if ($type === 'boolean') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return $value;
    }

    public static function getBool($key, $default = false)
    {
        return filter_var(self::get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    public static function save($key, $value, $type = 'string', $group = 'general')
    {
        if ($type === 'encrypted' && $value) {
            $value = Crypt::encryptString($value);
        }

        SzamlazzhuSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'group' => $group]
        );

        self::clearCache();
    }

    public static function clearCache()
    {
        Cache::forget(self::$cacheKey);
        Cache::forget(self::$typeCacheKey);
    }
}
