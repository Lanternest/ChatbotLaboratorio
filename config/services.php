<?php

// Agregar este bloque dentro del array return de config/services.php

return [

    // ... (configuraciones existentes de Laravel)

    /*
    |--------------------------------------------------------------------------
    | Anthropic API
    |--------------------------------------------------------------------------
    */
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

];