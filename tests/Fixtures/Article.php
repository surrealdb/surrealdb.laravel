<?php

declare(strict_types=1);

namespace SurrealDB\Laravel\Tests\Fixtures;

use Surqlize\Attributes\Id;
use Surqlize\Attributes\Schema;
use Surqlize\Attributes\Table;
use Surqlize\Model\Model;
use SurrealDB\SDK\Types\RecordId;

#[Table('article')]
#[Schema(ArticleSchema::class)]
final class Article extends Model
{
    /** @var RecordId<'article'> */
    #[Id]
    public RecordId $id;

    public string $title;
}
