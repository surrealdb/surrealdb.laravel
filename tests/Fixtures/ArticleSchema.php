<?php

declare(strict_types=1);

namespace SurrealDB\Laravel\Tests\Fixtures;

use Surqlize\Model\SchemaContract;

final class ArticleSchema implements SchemaContract
{
    /**
     * @return list<string>
     */
    public function definitions(): array
    {
        return [
            'DEFINE TABLE article SCHEMAFULL;',
            'DEFINE FIELD title ON article TYPE string;',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
