<?php

namespace Weboldalnet\CommerceSzamlazzhu\Models;

use Illuminate\Database\Eloquent\Model;
use Weboldalnet\CommerceCore\Models\Concerns\UsesCommerceConnection;

class SzamlazzhuSetting extends Model
{
    use UsesCommerceConnection;

    protected $table = 'commerce_szamlazzhu_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
    ];
}
