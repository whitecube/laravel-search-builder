<?php

namespace Whitecube\SearchBuilder;

trait HasSearchBuilder
{
    /**
     * Get a search builder instance pre-configured for the model
     */
    public static function searchBuilder(): SearchBuilder
    {
        return new SearchBuilder(new static);
    }
}
