<?php

use Whitecube\SearchBuilder\SearchBuilder;
use Whitecube\SearchBuilder\Tests\Fixtures\FooModel;

test('a search builder can be instanciated', function () {
    $builder = new SearchBuilder(new FooModel);

    expect($builder)->toBeInstanceOf(SearchBuilder::class);
});

test('a model with the trait can get a configured search builder', function () {
    $builder = FooModel::searchBuilder();

    expect($builder)->toBeInstanceOf(SearchBuilder::class);
});

test('the search builder can insert condition sub-queries', function () {
    $query = FooModel::searchBuilder()
        ->search(FooModel::select('id')->where('foo', '=', 'bar'))
        ->getQuery();

    expect($query->toSql())
        ->toContain('select `id`, 1 as score from `foo_models` where `foo` = ?');
});

test('the search builder can insert condition sub-queries with a specified score', function () {
    $query = FooModel::searchBuilder()
        ->search(FooModel::select('id')->where('foo', '=', 'bar'), score: 100)
        ->getQuery();

    expect($query->toSql())
        ->toContain('select `id`, 100 as score from `foo_models` where `foo` = ?');
});

test('the search builder can have multiple condition sub-queries', function () {
    $query = FooModel::searchBuilder()
        ->search(FooModel::select('id')->where('foo', '=', 'bar'))
        ->search(FooModel::select('id')->where('bar', '=', 'baz'))
        ->getQuery();

    expect($query->toSql())
        ->toContain('(select `id`, 2 as score from `foo_models` where `foo` = ?) union all (select `id`, 1 as score from `foo_models` where `bar` = ?)');
});
