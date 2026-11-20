<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class FullTextSearch
{
    /**
     * SQLite has no fulltext search; callers skip the fulltext branch there.
     *
     * @param  Builder<covariant Model>  $query
     */
    public static function isSupported(Builder $query): bool
    {
        return $query->getConnection()->getDriverName() !== 'sqlite';
    }

    /**
     * Laravel's PostgreSQL grammar joins one to_tsvector() per column with ||,
     * and a single NULL column turns the whole vector NULL. PostgreSQL therefore
     * coalesces each column; other drivers use Laravel's whereFullText().
     *
     * @template TBuilder of Builder<covariant Model>
     *
     * @param  TBuilder  $query
     * @param  list<string>  $columns
     * @return TBuilder
     */
    public static function where(Builder $query, array $columns, string $keyword, string $boolean = 'and'): Builder
    {
        if ($query->getConnection()->getDriverName() !== 'pgsql') {
            return $query->whereFullText($columns, $keyword, [], $boolean);
        }

        $grammar = $query->getQuery()->getGrammar();
        $vector = collect($columns)
            ->map(fn (string $column): string => "to_tsvector('english', coalesce({$grammar->wrap($column)}, ''))")
            ->implode(' || ');

        return $query->whereRaw("({$vector}) @@ plainto_tsquery('english', ?)", [$keyword], $boolean);
    }
}
