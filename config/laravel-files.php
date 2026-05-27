<?php

declare(strict_types=1);

return [
    'table' => 'files',
    'disk' => 'local',
    'image_cache_disk' => 'public',
    'image_cache_quality' => 90,
    'max_file_size_bytes' => env('FILES_MAX_FILE_SIZE_BYTES', 25 * 1024 * 1024),
    'max_upload_file_size_bytes' => env('FILES_MAX_UPLOAD_FILE_SIZE_BYTES', 50 * 1024 * 1024),
    'max_image_side_pixels' => env('FILES_MAX_IMAGE_SIDE_PIXELS', 2048),
    'max_upload_image_side_pixels' => env('FILES_MAX_UPLOAD_IMAGE_SIDE_PIXELS', 8192),
    'max_file_size' => ceil(env('FILES_MAX_UPLOAD_FILE_SIZE_BYTES', 50 * 1024 * 1024) / 1024),
    'max_image_dimension' => env('FILES_MAX_UPLOAD_IMAGE_SIDE_PIXELS', 8192),
    'accept_image_mimes' => 'apng,avif,gif,jpg,jpeg,jfif,pjpeg,pjp,png,svg,webp',
    'accept_file_mimes' => 'pdf',
];
