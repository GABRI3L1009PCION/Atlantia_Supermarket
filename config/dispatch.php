<?php

return [
    'enabled' => env('DISPATCH_AUTO_ENABLED', true),
    'offer_ttl_seconds' => (int) env('DISPATCH_OFFER_TTL_SECONDS', 90),
    'gps_max_age_seconds' => (int) env('DISPATCH_GPS_MAX_AGE_SECONDS', 180),
    'gps_max_accuracy_meters' => (float) env('DISPATCH_GPS_MAX_ACCURACY_METERS', 100),
    'max_pickup_distance_km' => (float) env('DISPATCH_MAX_PICKUP_DISTANCE_KM', 20),
    'max_active_deliveries' => (int) env('DISPATCH_MAX_ACTIVE_DELIVERIES', 1),
    'retry_courier_after_minutes' => (int) env('DISPATCH_RETRY_COURIER_AFTER_MINUTES', 15),
    'batch_size' => (int) env('DISPATCH_BATCH_SIZE', 100),
    'distance_weight' => (float) env('DISPATCH_DISTANCE_WEIGHT', 10),
    'load_weight' => (float) env('DISPATCH_LOAD_WEIGHT', 25),
    'rating_weight' => (float) env('DISPATCH_RATING_WEIGHT', 0.5),
    'completion_weight' => (float) env('DISPATCH_COMPLETION_WEIGHT', 0.02),
];
