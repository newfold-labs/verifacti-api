<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Transport;

use Bluehost\VerifactiApi\Config\VerifactiConfig;

interface HttpTransportInterface
{
    public function send(HttpRequest $request, VerifactiConfig $config): HttpResponse;
}
