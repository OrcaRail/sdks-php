<?php

declare(strict_types=1);

namespace OrcaRail;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @implements ArrayAccess<array-key, mixed>
 * @implements IteratorAggregate<string, mixed>
 */
final class OrcaRailObject implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /** @var array<string, mixed> */
    private array $values;

    /** @param array<string, mixed> $values */
    public function __construct(array $values = [])
    {
        $this->values = array_map([self::class, 'convert'], $values);
    }

    public static function convert(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map([self::class, 'convert'], $value);
        }

        return new self($value);
    }

    public function __get(string $name): mixed
    {
        return $this->values[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->values[$name]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && array_key_exists($offset, $this->values);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return is_string($offset) ? ($this->values[$offset] ?? null) : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!is_string($offset)) {
            throw new \InvalidArgumentException('OrcaRailObject keys must be strings.');
        }

        $this->values[$offset] = self::convert($value);
    }

    public function offsetUnset(mixed $offset): void
    {
        if (is_string($offset)) {
            unset($this->values[$offset]);
        }
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->values);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $convert = static function (mixed $value) use (&$convert): mixed {
            if ($value instanceof self) {
                return array_map($convert, $value->values);
            }

            return is_array($value) ? array_map($convert, $value) : $value;
        };

        return array_map($convert, $this->values);
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
