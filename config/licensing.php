<?php

/**
 * Öffentlicher Ed25519-Schlüssel zur PRÜFUNG von Lizenzschlüsseln, unabhängig
 * von APP_KEY. Die Erzeugung von Lizenzen (privater Schlüssel) lebt
 * ausschließlich im separaten Tool "cis-requests-license".
 */
return [
    'public_key' => env('LICENSE_PUBLIC_KEY', '4KWPO9/pZuYpBnvzeytSi0nRWWlYUcrxhBFQDLtBaJo='),
];
