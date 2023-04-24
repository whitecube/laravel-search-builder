# Search Builder for Laravel
A package to build fast, index-friendly search queries for Laravel.

## What does it do?
The purpose of this package is to allow you to build multi-condition search queries with scores, and make use of covering indexes to be super fast.
This means simply using this package as shown is not everything – you need to have a well designed database, with properly designed indexes for your searches, to get good results. To learn more about this topic, we recommend watching the free [PlanetScale MySQL for Developers course](https://planetscale.com/courses/mysql-for-developers/introduction/course-introduction).

## Installation 

```bash
composer require whitecube/laravel-search-builder
```

## Usage

### How to structure your query

To make your search queries extremely fast, the search builder will pack all of your conditions in a subquery that will aim to hit as many covering indexes as possible, in order to build an aggregated table that only contains ids of the pertinent models (along with a score, more on this later). This aggregated table will then be used to filter down the actual table with an inner join. This means that the processing of your search logic is done entirely on your indexes, and the full table is only accessed at the end, which dramatically speeds everything up.

However, the package can not detect your database structure, so it is your responsibility to create your indexes correctly, and in such a way that your search condition queries will not have to access your main tables' data.

Here's an example of what we're looking to achieve, in raw SQL. Given we have a products table, and we want to search it by reference and by name, and prioritise the reference over the name: 

```sql
with id_and_total_score as (
    select id, sum(score) as score from (
        -- This query makes use of a covering index on the ref column
        select id, 100 as score
        from products
        where ref = 'SEARCH_STRING'
       
        union all
   
        -- This query makes use of a covering index on the name column
        select id, 50 as score
        from products
        where name = 'SEARCH_STRING'
    )
    as ids_and_scores 
    group by id
)

select * from products 
inner join id_and_total_score on id_and_total_score.id = products.id
order by score desc;
```

### The search builder instance

You can get a search builder instance just by passing it the model you want to search.

```php
use \App\Models\Product;
use \Whitecube\SearchBuilder\SearchBuilder;

$builder = new SearchBuilder(Product::class); // You can also pass it an instance of your model
```

Or, if your model uses the `HasSearchBuilder` trait, you can easily get a search builder instance this way, which allows you to cleanly chain your condition methods later.

```php
use Whitecube\SearchBuilder\HasSearchBuilder;

class Product extends Model
{
    use HasSearchBuilder;
}
```

```php
$builder = Product::searchBuilder();
```

### Defining search conditions

Once you have a search builder instance, you can use it to define your search conditions, by passing eloquent builder instances to the `search` method. 

```php
Product::searchBuilder()
    ->search(Product::select('id')->where('ref', 'SEARCH_STRING'), score: 100)
    ->search(Product::select('id')->where('name', 'SEARCH_STRING'), score: 50);
```

The score is optional and will be automatically computed if missing, using the order in which the conditions are defined, with the highest score on top. 

```php
Product::searchBuilder()
    ->search(Product::select('id')->where('ref', 'SEARCH_STRING'), score: 100) // score = 100
    ->search(Product::select('id')->where('name', 'SEARCH_STRING')) // score = 3
    ->search(Product::select('id')->where('description', 'SEARCH_STRING')) // score = 2
    ->search(Product::select('id')->where('content', 'SEARCH_STRING')); // score = 1
```

You can easily search on related tables. Remember to only select the column that references the id of the table you're searching.

```php
Product::searchBuilder()
    // Search on a related table
    ->search(Lot::select('product_id')->where('barcode', 'SEARCH_STRING'))
    // Search on a relation of a related table
    ->search(Lot::select('product_id')->whereHas('delivery', function ($query) {
        $query->where('address', 'SEARCH_STRING');
    }))
```

If you wish to split the search terms on spaces, dashes, dots and underscores, and perform individual queries on each term, you can call the `splitTerms` method.

```php
$terms = 'foo bar baz';

Product::searchBuilder()
    ->splitTerms($terms, function (SearchBuilder $searchBuilder, string $term) {
        // Called once with $term = foo, once with $term = bar, and once with $term = baz
        return $searchBuilder->search(Product::select('id')->where('ref', $term));
    });
```

### Getting the results

After defining your conditions, you can get the collection of results by calling the `get` method.

```php
$results = Product::searchBuilder()
    ->search(Product::select('id')->where('ref', 'SEARCH_STRING'), score: 100)
    ->search(Product::select('id')->where('name', 'SEARCH_STRING'), score: 50)
    ->get();
```

Or, if you need to do more work on the query yourself, you can get the query builder instance.

```php
$query = Product::searchBuilder()
    ->search(Product::select('id')->where('ref', 'SEARCH_STRING'), score: 100)
    ->search(Product::select('id')->where('name', 'SEARCH_STRING'), score: 50)
    ->getQuery();
```

## 💖 Sponsorships

If you are reliant on this package in your production applications, consider [sponsoring us](https://github.com/sponsors/whitecube)! It is the best way to help us keep doing what we love to do: making great open source software.

## Contributing

Feel free to suggest changes, ask for new features or fix bugs yourself. We're sure there are still a lot of improvements that could be made, and we would be very happy to merge useful pull requests.

Thanks!

### Unit tests

When adding a new feature or fixing a bug, please add corresponding unit tests. The current set of tests is limited, but every unit test added will improve the quality of the package.

Run PHPUnit by calling `composer test`.

## Made with ❤️ for open source

At [Whitecube](https://www.whitecube.be) we use a lot of open source software as part of our daily work.
So when we have an opportunity to give something back, we're super excited!

We hope you will enjoy this small contribution from us and would love to [hear from you](mailto:hello@whitecube.be) if you find it useful in your projects. Follow us on [Twitter](https://twitter.com/whitecube_be) for more updates!
