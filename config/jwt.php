<?php
return [
    'secret' => env('JWT_SECRET'),
    'ttl' => 60 * 24, // Thời gian token có hiệu lực (phút)
];