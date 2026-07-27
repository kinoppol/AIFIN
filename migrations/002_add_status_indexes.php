<?php
/**
 * Add indexes that speed up the admin dashboard/queue filters (status lookups
 * and date-range scans). Example of a follow-up migration applied via the
 * Admin > Migrations menu.
 */
return [
    'name' => '002_add_status_indexes',
    'up'   => [
        "ALTER TABLE contracts   ADD INDEX idx_contracts_status (status)",
        "ALTER TABLE contracts   ADD INDEX idx_contracts_end (end_date)",
        "ALTER TABLE redemptions ADD INDEX idx_redemptions_status (status)",
        "ALTER TABLE extension_requests ADD INDEX idx_ext_status (status)",
    ],
];
