<?php

namespace Whitecube\SearchBuilder;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SearchBuilder
{
    /**
     * The model we're basing our queries on
     */
    protected Model $model;

    /**
     * The query builder instance
     */
    protected QueryBuilder|EloquentBuilder|null $query = null;

    /**
     * All the search conditions to apply to the query
     */
    protected array $conditions = [];

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Set the query builder instance to add the search conditions to
     */
    public function setQuery(QueryBuilder|EloquentBuilder $query): static
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Register a search condition subquery with an optional score/weight
     */
    public function search(QueryBuilder|EloquentBuilder $query, ?int $score = null): static
    {
        $this->conditions[] = (new Condition())
            ->query($query)
            ->score($score);

        return $this;
    }

    /**
     * Split the search terms and execute the callback to allow
     * adding search conditions for each one separately
     */
    public function splitTerms(string $terms, callable $callback): static
    {
        $split = array_unique(array_filter(explode(' ', str_replace(['-', '_', '.'], ' ', $terms))));

        foreach ($split as $term) {
            $callback($this, $term);
        }

        return $this;
    }

    /**
     * Get the results
     */
    public function get(): Collection
    {
        return $this->getQuery()->get();
    }

    /**
     * Build and get the search query
     */
    public function getQuery(): EloquentBuilder
    {
        if (is_null($this->query)) {
            $this->setQuery($this->model->query());
        }

        $table = $this->query->getQuery()->from;

        return $this->query
            ->withExpression('id_and_total_score', $this->getScoreQuery())
            ->leftJoin('id_and_total_score', 'id_and_total_score.id', $table.'.id')
            ->whereRaw($table.'.id in (select id from id_and_total_score)')
            ->orderBy('score', 'desc');
    }

    /**
     * Get the score-calculating sub query
     */
    protected function getScoreQuery(): QueryBuilder
    {
        $subquery = DB::query()->selectRaw('null as id, 0 as score');

        foreach ($this->conditions as $index => $condition) {
            $condition->applyScore(fallbackScore: count($this->conditions) - $index);

            $subquery->unionAll($condition->getQuery());
        }

        return DB::query()
            ->fromSub($subquery, as: 'ids_and_scores')
            ->selectRaw('ids_and_scores.id as id, sum(ids_and_scores.score) as score')
            ->groupBy('id');
    }
}
