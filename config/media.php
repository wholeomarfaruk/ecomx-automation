<?php

return [
    // Defaults only — the live values are admin-editable and stored in the
    // `settings` table under the 'media' group (see App\Models\Setting).
    // ThumbnailService reads Setting::get(..., 'media') with these as fallback.
    'thumbnail' => [
        'enabled' => true,
        'width' => 200,
        'format' => 'avif',
        'quality' => 65,
    ],
];
