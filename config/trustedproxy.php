<?php

return [
    // Trust only the connector IPs/CIDRs reaching the origin, including Docker's
    // gateway when cloudflared runs on the host. Never trust arbitrary clients.
    'proxies' => env('TRUSTED_PROXIES', '127.0.0.1,::1'),
];
