<?php

namespace App\Services;

use LogicException;

/**
 * @deprecated K87.3 FIX switched the shop to Furgonetka
 * Universal E-commerce Integration.
 *
 * Furgonetka now pulls orders from this application.
 * The shop no longer creates shipments through OAuth2 REST API.
 */
class FurgonetkaApiService
{
    public function __construct()
    {
        throw new LogicException(
            'Legacy Furgonetka OAuth2 integration is disabled. '
            . 'Use Universal E-commerce Integration.'
        );
    }
}
