<?php

namespace Weboldalnet\CommerceSzamlazzhu\Models;

use Illuminate\Database\Eloquent\Model;

class SzamlazzhuSetting extends Model
{
    protected $table = 'commerce_szamlazzhu_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
    ];
}
