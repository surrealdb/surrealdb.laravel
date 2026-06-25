<?php

declare(strict_types=1);

namespace SurrealDB\Laravel\Testing;

use PHPUnit\Framework\Assert;
use SurrealDB\SDK\Contracts\QueryExecutor;
use SurrealDB\SDK\Query\BoundQuery;

final class FakeQueryExecutor implements QueryExecutor
{
    /** @var list<BoundQuery> */
    public array $queries = [];

    /**
     * @param list<mixed> $result
     */
    public function __construct(
        private readonly array $result = [],
    ) {}

    /**
     * @return list<mixed>
     */
    public function query(BoundQuery $query): array
    {
        $this->queries[] = $query;

        return $this->result;
    }

    public function assertQuerySent(string $expected): void
    {
        Assert::assertContains(
            $expected,
            array_map(static fn (BoundQuery $query): string => $query->query, $this->queries),
            sprintf('Failed asserting that SurrealDB query "%s" was sent.', $expected),
        );
    }

    public function assertNothingSent(): void
    {
        Assert::assertSame([], $this->queries, 'Failed asserting that no SurrealDB queries were sent.');
    }
}
