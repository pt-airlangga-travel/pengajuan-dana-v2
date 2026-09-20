<?php

use Filament\Support\Enums\Size;

return [

    // optional, default is 5
    'length' => 4,

    // optional, default is 'abcdefghijklmnpqrstuvwxyz123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'
    'charset' => '123456789',

    'width' => 195,

    'height' => 65,

    // 'background_color' => [255, 255, 255],
    'background_color' => [240, 243, 247],

    // 'fontColors' => ['#111827'],
    'fontColors' => [
        '#111827', // slate-900 (tajam)
    ],

    'refresh_button' => [
        'icon' => 'heroicon-o-arrow-path',
        'size' => Size::Medium,
    ],

];
