<?php

return [
    // Odoo master truc tiep (sale.channel API), khac voi legacy connector :8090.
    'url' => env('ODOO_URL', 'http://localhost:8069'),
    'channel_key' => env('ODOO_CHANNEL_KEY', ''),
    'channel_code' => env('ODOO_CHANNEL_CODE', 'wibu'),
    'timeout' => (int) env('ODOO_TIMEOUT', 10),
];
