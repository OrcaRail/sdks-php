<?php

declare(strict_types=1);

namespace OrcaRail\Service;

use OrcaRail\OrcaRailObject;

final class ProductService extends AbstractService
{
    /** @return list<OrcaRailObject> */
    public function all(string $organizationId): array
    {
        $envelope = $this->requestObject(
            'GET',
            'organizations/' . $this->segment($organizationId) . '/products',
        );
        $data = $envelope->data;
        if (!is_array($data)) {
            throw new \UnexpectedValueException('Expected a catalog list envelope.');
        }

        /** @var list<OrcaRailObject> $data */
        return $data;
    }

    /** @param array<string, mixed> $params */
    public function create(string $organizationId, array $params): OrcaRailObject
    {
        return $this->requestObject(
            'POST',
            'organizations/' . $this->segment($organizationId) . '/products',
            $params,
        );
    }

    /** @param array<string, mixed> $params */
    public function update(string $organizationId, string $productId, array $params): OrcaRailObject
    {
        return $this->requestObject(
            'PATCH',
            'organizations/' . $this->segment($organizationId) . '/products/' . $this->segment($productId),
            $params,
        );
    }

    public function delete(string $organizationId, string $productId): OrcaRailObject
    {
        return $this->requestObject(
            'DELETE',
            'organizations/' . $this->segment($organizationId) . '/products/' . $this->segment($productId),
        );
    }
}
