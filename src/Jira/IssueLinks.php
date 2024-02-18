<?php

namespace David\PmUtils\Jira;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Traversable;

// implements Array interfaces
class IssueLinks implements ArrayAccess, IteratorAggregate
{
    /**
     * @var IssueLink[]
     */
    private array $links;

    public function __construct($links)
    {
        $this->links = array_map(function($link) {
            return new IssueLink($link);
        }, $links);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->links);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->links[$offset]);
    }

    public function offsetGet(mixed $offset): IssueLink
    {
        return $this->links[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->links[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->links[$offset]);
    }

    /**
     * @param mixed $property Attribute to filter
     * @param mixed $value Attribute value to filter (optional)
     * @return array|IssueLink[]
     */
    public function having($property, $value= null): array
    {
        $filtered = array_filter($this->links, function($link) use ($property, $value) {
            if (is_null($value)) {
                return isset($link->$property);
            }
            return isset($link->$property) && $link->$property === $value;
        });
        return array_values($filtered);
    }
}
