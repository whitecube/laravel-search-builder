<?php

namespace Whitecube\SearchBuilder;

use Illuminate\Database\Eloquent\Builder;

class Condition
{
    /**
     * The condition query
     */
    protected Builder $query;

    /**
     * The score/weight to apply for this condition
     */
    protected ?int $score = null;

    /**
     * Set the condition query
     */
    public function query(Builder $query): static
    {
        // Remove global scopes like deleted_at etc, these prevent us from
        // using covering indexes so we'll just need to add them later,
        // outside of the score-calculating CTE
        $this->query = $query->withoutGlobalScopes();

        return $this;
    }

    /**
     * Get the query instance
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }

    /**
     * Set the score/weight to apply for this condition
     */
    public function score(?int $score): static
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Set the fallback score and apply it on the query
     */
    public function applyScore(int $fallbackScore): static
    {
        if (is_null($this->score)) {
            $this->score($fallbackScore);
        }

        $this->query->selectRaw($this->score.' as score');

        return $this;
    }
}
