# laravelsolr

A Laravel package for seamless integration with Apache Solr, providing easy-to-use commands for core management and a
fluent interface for Solr operations.

## Features

- Create, update, and delete Solr cores via artisan commands
- Manage Solr fields with parent-child relationships
- Fluent query builder interface for Solr searches
- Support for parent-child document relationships
- Cross-collection joins
- Faceted search capabilities
- Complete test coverage

## Installation

```bash
composer require haiderjabbar/laravelsolr
```

Add the service provider to your `config/app.php`:

```php
'providers' => [
    // ...
    haiderjabbar\laravelsolr\LaravelSolrServiceProvider::class,
];
```

You should publish the config/solr.php config file with:

```bash
php artisan vendor:publish --provider="haiderjabbar\laravelsolr\LaravelSolrServiceProvider"
```

## Available Commands

```bash
# Create a new Solr core and migration file
php artisan solr:create-core coreName

# Update a Solr core with a new name
php artisan solr:update-core

# Delete a Solr core and its migration file
php artisan solr:delete-core coreName

# Create new Solr fields with optional parent and fields
php artisan solr:create-fields coreName

# Update existing Solr core fields
php artisan solr:update-fields

# Delete fields from a Solr core
php artisan solr:delete-fields


```

## Basic Usage

### Adding Documents

```php
use haiderjabbar\laravelsolr\Models\SolrModel;

$coreName = 'your_core_name';
$data = [
    'id' => 'unique_id',
    'name' => 'document_name',
    // ... other fields
];

$result = SolrModel::addDocument($coreName, $data);
```

### Updating Documents

```php
$result = SolrModel::updateDocument($coreName, $data);
```

### Deleting Documents

```php
$result = SolrModel::deleteDocumentById($coreName, $id);
```

### Parent-Child Document Operations

```php
// Add child documents to a parent
$result = SolrModel::addChildToParent($coreName, $parentId, "child", $childData);
```

### Controller Example

```php
use haiderjabbar\laravelsolr\Services\SolrQueryBuilder;
use Illuminate\Http\Request;
use haiderjabbar\laravelsolr\Models\SolrModel;


class SolrController extends Controller
{
    public function addDocument(Request $request)
    {
        $coreName = 'testCore';
        $data = [
            'id' => 1,
            'name' => 'name'
        ];
    
        $result = SolrModel::addDocument($coreName, $data);
    
        return $result
            ? response()->json(['message' => 'Document added successfully'])
            : response()->json(['message' => 'Failed to add document'], 500);
    }

    public function addChildDocument(Request $request, $parentId)
    {
        $coreName = 'testCore';
        $childData = $request->input('childData');
        
        $result = SolrModel::addChildToParent($coreName, $parentId, "child", $childData);

        if ($result) {
            return response()->json(['message' => 'Child documents added successfully']);
        }
        return response()->json(['message' => 'Failed to add child documents'], 500);
    }
}
```

## Query Builder

laravelsolr provides a powerful and fluent interface for building Solr queries through the `SolrQueryBuilder` class:

### Basic Usage

```php
use haiderjabbar\laravelsolr\Services\SolrQueryBuilder;

$builder = new SolrQueryBuilder($coreName);
```

### Search Methods

```php
// Basic search
$builder->search('field', '=', 'value', $boost);

// OR search condition
$builder->orSearch('field', 'value', $boost);

// Where clause
$builder->where('field', '=', 'value', $priority);

// OR where clause
$builder->orWhere('field', '=', 'value', $priority);

// WHERE IN clause
$builder->whereIn('field', ['value1', 'value2']);

// Filter (fq parameter)
$builder->filter('field', '=', 'value');
```

Available operators:

- `=` Exact match
- `!=` Not equal
- `<` Less than
- `>` Greater than
- `<=` Less than or equal
- `>=` Greater than or equal
- `like` Contains
- `in` In array of values

### Sorting and Pagination

```php
$builder
    ->sort('field asc')    // Add sorting
    ->start(0)             // Starting offset (pagination)
    ->rows(10);            // Number of rows to return
```

### Field Selection

```php
$builder->fl(['field1', 'field2', 'score']); // Select specific fields to return
```

### Parent-Child Document Queries

```php
// Get parent with child
$builder
    ->whereParent('id', '=', 'parent_id', 20)
    ->returnBothParentAndChild()
    ->get();

// Get only children
$builder
    ->whereParent('id', '=', 'parent_id', 20)
    ->returnOnlyChild()
    ->fl(['*', 'score'])
    ->get();

// Get parent only
$builder
    ->whereParent('id', '=', 'parent_id', 20)
    ->returnOnlyParent()
    ->get();

// Query where parent and child
$builder
    ->whereParent('id', '=', 'parent_id', 20)
    ->whereChild('childId', '=', 'child_id', 20)
    ->get();
```

### Cross-Collection Joins

```php
// Simple join
$builder->crossCollectionJoin(
    'otherCore',           // From index
    'fromField',          // Field in from index
    'toField',            // Field in current index
    'field:value'         // Optional query
);

// Complex join with conditions
$builder->whereJoin('otherCore', 'fromField', 'toField', function($q) {
    $q->where('field1', '=', 'value1')
      ->where('field2', '=', 'value2');
});

// Cross collection join example
$result = $builder
    ->whereParent('id', '=', 'parent_id', 20)
    ->whereJoin('otherCore', 'id', 'id', function ($q) {
        $q->where("id", "=", "value1", 20)
         ->where("id", "=", "value2", 20);
    })
    ->returnBothParentAndChild()
    ->getWithJoinedDocuments("otherCore", "id", "id");
```

### Faceted Search

```php
// Basic faceted search
$builder
    ->facet(true)
    ->facetFields(['field1', 'field2'])
    ->get();

// Complex faceted search
$builder
    ->whereParent('id', '=', 'parent_id', 20)
    ->whereChild('childId', '=', 'child_id', 20)
    ->facetFields(['id'])
    ->facet(true)
    ->fl(['*', 'score'])
    ->get();
```

### Query Execution

```php
// Basic query execution
$results = $builder->get();

// Get results with facets
$results = $builder->getWithFacets();

// Get results with joined documents
$results = $builder->getWithJoinedDocuments('otherCore', 'fromId', 'toId');
```

### Complete Example

```php
$builder = new SolrQueryBuilder($coreName);

$results = $builder
    ->whereParent('category', '=', 'electronics', 20)
    ->whereChild('price', '>=', 100)
    ->facet(true)
    ->facetFields(['brand', 'color'])
    ->sort('price desc')
    ->start(0)
    ->rows(20)
    ->fl(['id', 'name', 'price', 'score'])
    ->returnBothParentAndChild()
    ->get();
```

### Environment Configuration

Make sure to set your Solr URL in your `.env` file:

```env
SOLR_URL=http://localhost:8983/solr
```

## Directory Structure

```
laravelsolr/
├── src/
│   ├── Console/Commands/
│   │   ├── CreateSolrCore.php
│   │   ├── CreateSolrFields.php
│   │   ├── DeleteSolrCore.php
│   │   ├── DeleteSolrFields.php
│   │   ├── UpdateSolrCore.php
│   │   └── UpdateSolrFields.php
│   ├── Models/
│   │   └── SolrModel.php
│   ├── Schema/
│   │   └── SolrSchemaBuilder.php
│   ├── Services/
│   │   ├── CoreSolrService.php
│   │   ├── FieldsSolrService.php
│   │   └── SolrQueryBuilder.php
│   ├── laravelsolr.php
│   ├── LaravelSolrServiceProvider.php
│   └── SolrServiceProvider.php
└── tests/
    └── Unit/
        ├── Console/Commands/
        ├── Models/
        └── Services/
```

## Testing

The package includes comprehensive tests for all features. To run the tests:

```bash
vendor/bin/phpunit
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-source software licensed under the MIT license.
