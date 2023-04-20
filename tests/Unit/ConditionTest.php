<?php

use Whitecube\SearchBuilder\Tests\Fixtures\SoftDeleteModel;
use Whitecube\SearchBuilder\Condition;
use Whitecube\SearchBuilder\Tests\Fixtures\FooModel;

test('the condition removes global scopes from its query', function () {
    $condition = new Condition();

    $condition->query(SoftDeleteModel::where('foo', '=', 'bar'));

    expect($condition->getQuery()->toSql())
        ->not()->toContain('`soft_delete_models`.`deleted_at` is null');
});

test('the condition can apply its score to its query', function () {
    $condition = new Condition();

    $condition->query(FooModel::where('foo', '=', 'bar'));
    $condition->score(100);

    $condition->applyScore(fallbackScore: 1);

    expect($condition->getQuery()->toSql())
        ->toContain('select 100 as score');
});

test('the condition can use a fallback score if a score was not previously set', function () {
    $condition = new Condition();

    $condition->query(FooModel::where('foo', '=', 'bar'));

    $condition->applyScore(fallbackScore: 10);

    expect($condition->getQuery()->toSql())
        ->toContain('select 10 as score');
});
