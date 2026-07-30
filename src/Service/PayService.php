<?php

declare(strict_types=1);

namespace OrcaRail\Service;

use OrcaRail\OrcaRailObject;

final class PayService extends AbstractService
{
    public function retrieve(string $slug): OrcaRailObject
    {
        return $this->requestObject('GET', 'pay/' . $this->segment($slug));
    }

    public function get(string $slug): OrcaRailObject
    {
        return $this->retrieve($slug);
    }

    public function cancel(string $slug): OrcaRailObject
    {
        return $this->requestObject('POST', 'pay/' . $this->segment($slug) . '/cancel', []);
    }
}
