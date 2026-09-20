<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ingestion Configuration
    |--------------------------------------------------------------------------
    |
    | Centralized configuration for ingestion pipeline.
    | All values are locked per AGENTS.md §2 — do not change without approval.
    |
    */

    // Maximum items per batch request (EC-8)
    'max_batch' => (int) env('INGEST_MAX_BATCH', 500),

    // Chunk size for bulk insert (one transaction per chunk)
    'chunk_size' => (int) env('INGEST_CHUNK_SIZE', 100),

    // Clock future tolerance in seconds (EC-1: ts > now + tolerance → reject)
    'clock_future_tolerance' => (int) env('CLOCK_FUTURE_TOLERANCE_SEC', 300),

    // Rain counter conversion: 1 tip = 0.2 mm
    'rain_mm_per_tip' => (float) env('RAIN_MM_PER_TIP', 0.2),

];
