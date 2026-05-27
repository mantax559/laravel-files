<?php

declare(strict_types=1);

return [
    'table' => 'files',
    'disk' => 'local',
    'image_cache_disk' => 'public',
    'image_cache_quality' => 90,
    'max_file_size_bytes' => 25 * 1024 * 1024,
    'max_upload_file_size_bytes' => 50 * 1024 * 1024,
    'max_image_side_pixels' => 2048,
    'max_upload_image_side_pixels' => 8192,
];
