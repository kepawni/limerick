<?php declare(strict_types=1);
namespace Kepawni\Limerick;

use Iterator;
use Predis\Client;

class Hexastore
{
    /** @var Client */
    private $client;
    /** @var string */
    private $escapeChar;
    /** @var string */
    private $key;
    /** @var string */
    private $separator;

    public function __construct(
        Client $client,
        string $keyForSortedSetOfTriples,
        string $tripleSeparator = ':',
        string $tripleEscapeChar = '#'
    )
    {
        $this->client = $client;
        $this->key = $keyForSortedSetOfTriples;
        $this->separator = $tripleSeparator;
        $this->escapeChar = $tripleEscapeChar;
    }

    public function delete(string $subject, string $predicate, string $object): void
    {
        $subject = $this->escape($subject);
        $predicate = $this->escape($predicate);
        $object = $this->escape($object);
        $this->client->zrem($this->key, implode($this->separator, ['spo', $subject, $predicate, $object]));
        $this->client->zrem($this->key, implode($this->separator, ['sop', $subject, $object, $predicate]));
        $this->client->zrem($this->key, implode($this->separator, ['pso', $predicate, $subject, $object]));
        $this->client->zrem($this->key, implode($this->separator, ['pos', $predicate, $object, $subject]));
        $this->client->zrem($this->key, implode($this->separator, ['osp', $object, $subject, $predicate]));
        $this->client->zrem($this->key, implode($this->separator, ['ops', $object, $predicate, $subject]));
    }

    /**
     * @param string|null $subject
     * @param string|null $predicate
     * @param string|null $object
     *
     * @return Iterator Iterates matching triplets which are returned as [$subject, $predicate, $object] arrays.
     */
    public function find(?string $subject = null, ?string $predicate = null, ?string $object = null): Iterator
    {
        $order = 'spo';
        $noCriteria = 3;
        if (!isset($subject)) {
            $order = str_replace('s', '', $order) . 's';
            $noCriteria--;
        }
        if (!isset($predicate)) {
            $order = str_replace('p', '', $order) . 'p';
            $noCriteria--;
        }
        if (!isset($object)) {
            $order = str_replace('o', '', $order) . 'o';
            $noCriteria--;
        }
        $begin = sprintf(
            "[%s%s%s%s",
            $order,
            $this->separator,
            implode(
                $this->separator,
                array_map(
                    [$this, 'escape'],
                    array_filter(
                        [$subject, $predicate, $object],
                        function ($item) {
                            return isset($item);
                        }
                    )
                )
            ),
            $noCriteria === 3 ? '' : $this->separator
        );
        $end = sprintf("%s%s", $begin, $noCriteria === 3 ? '' : "\xff");
        foreach ($this->client->zrangebylex($this->key, $begin, $end) as $item) {
            [, $result[$order[0]], $result[$order[1]], $result[$order[2]]] = array_map(
                [$this, 'restore'],
                explode($this->separator, $item)
            );
            yield [$result['s'], $result['p'], $result['o']];
        }
    }

    public function store(string $subject, string $predicate, string $object): void
    {
        $subject = $this->escape($subject);
        $predicate = $this->escape($predicate);
        $object = $this->escape($object);
        $this->client->zadd(
            $this->key,
            [
                implode($this->separator, ['spo', $subject, $predicate, $object]) => 0,
                implode($this->separator, ['sop', $subject, $object, $predicate]) => 0,
                implode($this->separator, ['pso', $predicate, $subject, $object]) => 0,
                implode($this->separator, ['pos', $predicate, $object, $subject]) => 0,
                implode($this->separator, ['osp', $object, $subject, $predicate]) => 0,
                implode($this->separator, ['ops', $object, $predicate, $subject]) => 0,
            ]
        );
    }

    private function escape(string $value): string
    {
        return str_replace(
            [$this->escapeChar, $this->separator],
            [$this->escapeChar . 'E', $this->escapeChar . 'S'],
            $value
        );
    }

    private function restore(string $value): string
    {
        return str_replace(
            [$this->escapeChar . 'S', $this->escapeChar . 'E'],
            [$this->separator, $this->escapeChar],
            $value
        );
    }
}
