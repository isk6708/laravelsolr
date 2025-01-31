<?php

namespace HaiderJabbar\LaravelSolr\Services;

use Illuminate\Support\Facades\Http;

class SolrQueryBuilder
{
    protected $solrUrl;
    protected $searchQueries = [];    // For main query (q parameter)
    public $filterQueries = [];    // For filter query (fq parameter)
    public $sort = '';
    public $queryOperator = 'AND';    // Changed default to AND
    public $facet = false;
    public $facetFields = [];
    public $start = 0;
    public $rows = 10;
    public $fields = '*';
    public $returnMode = '';  // Default to returning both parent and child
    protected $crossCollectionJoins = [];
    public $mainQuery = '*:*';

    public function __construct($coreName)
    {
        // dump($coreName);
        // RKM
        $this->solrUrl = (env('SOLR_URL') ?? "http://localhost:8983/solr") . '/' . $coreName . '/select';
        // $this->solrUrl = (env('SOLR_URL') ?? "http://localhost:8983/solr") . '/#/' . $coreName . '/query';

    }

    /**
     * Add a basic where clause.
     */
    public function where($field, $operator, $value, $priority = null)
    {
        $condition = $this->buildCondition($field, $operator, $value, $priority);
        $this->filterQueries[] = ['condition' => $condition, 'connector' => 'AND'];
        return $this;
    }

    /**
     * Add an orWhere clause.
     */
    public function orWhere($field, $operator, $value, $priority = null)
    {
        $condition = $this->buildCondition($field, $operator, $value, $priority);
        $this->filterQueries[] = ['condition' => $condition, 'connector' => 'OR'];
        return $this;
    }

    /**
     * Add sorting.
     */
    public function sort($sort)
    {
        $this->sort = $sort;
        return $this;
    }

    /**
     * Set pagination (start).
     */
    public function start($start)
    {
        $this->start = $start;
        return $this;
    }

    /**
     * Set number of rows to return.
     */
    public function rows($rows)
    {
        $this->rows = $rows;
        return $this;
    }

    /**
     * Select specific fields.
     */
    public function fl(array $fields)
    {
        $this->fields = implode(',', $fields);
        return $this;
    }

    /**
     * Enable faceting.
     */
    public function facet($enable = true)
    {
        $this->facet = $enable;
        return $this;
    }

    /**
     * Set facet fields.
     */
    public function facetFields(array $facetFields)
    {
        $this->facetFields = $facetFields;
        return $this;
    }

    /**
     * Add where clause for parent document with scoring.
     */
    public function whereParent($field, $operator, $value, $boost = null)
    {

        // Build the base condition with boost if provided
        // Add the condition to filter queries with parent query parser
//----------------------------------------
        $condition = $this->buildCondition($field, $operator, $value, $boost);
        $this->filterQueries[] = ['condition' => $condition, 'connector' => 'AND'];
//----------------------------------------
        $this->filterQueries[] = [
            'condition' => "{!parent which='*:* -_nest_path_:*' score=max}",
            'connector' => 'AND'
        ];

        return $this;
    }

    /**
     * Add where clause for child document with scoring.
     */
    public function whereChild($field, $operator, $value, $boost = null)
    {

        //----------------------------------------
        $condition = $this->buildCondition($field, $operator, $value, $boost);
        //----------------------------------------
        $this->filterQueries[] = [
            'condition' => "{!parent which='*:* -_nest_path_:*' score=max}$condition",
            'connector' => 'AND'
        ];
//-------------------------------------------------------------------------------------------------
        return $this;
    }


    /**
     * Builds and adds a join filter query with multiple conditions using a closure.
     *
     * @param string $fromIndex Index to join from.
     * @param string $fromField Field in the source index.
     * @param string $toField Field in the target index.
     * @param callable $callback Callback function to add multiple conditions.
     *
     * @return self
     */
    public function whereJoin(string $fromIndex, string $fromField, string $toField, callable $callback): self
    {
        $conditions = [];

        // Define a temporary query builder for building conditions within the callback.
        $queryBuilder = new class {
            public array $conditions = [];

            public function where(string $field, string $operator, $value, ?float $boost = null): self
            {
                $this->conditions[] = compact('field', 'operator', 'value', 'boost');
                return $this;
            }
        };

        // Execute the callback to populate conditions.
        $callback($queryBuilder);

        // Build each condition using `buildCondition` and join them with 'AND'.
        foreach ($queryBuilder->conditions as $condition) {
            $conditions[] = $this->buildCondition(
                $condition['field'],
                $condition['operator'],
                $condition['value'],
                $condition['boost']
            );
        }

        // Combine conditions into a single join query
        $combinedCondition = implode(' AND ', $conditions);
        $join = sprintf("{!join from=%s fromIndex=%s to=%s v='(%s)'}", $fromField, $fromIndex, $toField, $combinedCondition);

        $this->filterQueries[] = ['condition' => $join, 'connector' => 'AND'];
        return $this;
    }

    /**
     * Return only parent documents.
     */
    public function returnOnlyParent()
    {
        $this->returnMode = 'parent';
        return $this;
    }

    /**
     * Return only child documents.
     */
    public function returnOnlyChild()
    {
        $this->returnMode = 'child';

        return $this;
    }

    /**
     * Return both parent and child documents (default).
     */
    public function returnBothParentAndChild()
    {
        $this->returnMode = 'both';
        $this->fields = "*,[child]";
        return $this;
    }

    /**
     * Build and execute the query.
     */
    /**
     * Build and execute the query, including cross-collection joins.
     */

    /**
     * Build and execute the query.
     */
    /**
     * Build and execute the query.
     */
    public function get()
    {

        $filterQuery = $this->buildFilterQueryString();

        // Base query parameters
        $query = [
            'q.op' => $this->queryOperator,
            'sort' => $this->sort,
            'start' => $this->start,
            'rows' => $this->rows,
            'fl' => $this->fields,
        ];

        // Add main query with appropriate wrapper based on return mode
        switch ($this->returnMode) {
            case 'parent':
                $this->searchQueries[] = ['condition' => "{!parent which='*:* -_nest_path_:*' score=max}", 'connector' => 'AND'];
                break;

            case 'child':
//                $this->searchQueries[] = ['condition' => "{!child of='-_nest_path_:* *:*'}", 'connector' => 'AND'];
                $query["fl"] = $query['fl'] . ",[child]";
                break;

            case 'both':
                //booth
                $this->searchQueries[] = ['condition' => "{!parent which='*:* -_nest_path_:*' score=max}", 'connector' => 'AND'];
                break;
        }

        $query['q'] = $this->buildMainQueryString();

        // Add filter query if exists
        if ($filterQuery) {
            $query['fq'] = $filterQuery;
            // $query['fq'] = 'last_modified:'.$filterQuery;
        }
        // RKM        
        // $query['df'] = 'resourcename';
        // $query['df'] = 'content';
        $query['indent'] = 'true';
        $query['debug'] = 'results';
        // dd($query);
        // Add faceting if enabled
        if ($this->facet) {
            $query['facet'] = 'true';
            $query['facet.field'] = implode(",",$this->facetFields);
        }

        // dump($query);
        // dump($this->solrUrl);
        // dump(Http::get($this->solrUrl, $query));
        $response = \Illuminate\Support\Facades\Http::get($this->solrUrl, $query);
        // $response = Http::get('http://localhost:8983/solr/techproducts/query?q=(name:*Black*)&debug=results');
        // $response = Http::get('http://localhost:8983/solr/secondLocalDocs/select?q=(resourcename:*email*)&debug=results');
        // dd($response);

        if (isset($response["response"]) || $response->successful()) {
            return $response;
        }

        return [];
    }

    /**
     * Format the Solr condition based on the operator.
     */
    protected function formatCondition($operator, $value)
    {
        switch ($operator) {
            case '=':
                return '"' . addslashes($value) . '"';
            case '!=':
                return "[* TO *] -\"" . addslashes($value) . "\"";
            case '<':
                return "[* TO " . addslashes($value) . "]";
            case '>':
                return "[" . addslashes($value) . " TO *]";
            case '<=':
                return "[* TO " . addslashes($value) . "]";
            case '>=':
                // return "[" . addslashes($value) . " TO *]";
                return "[" . addslashes($value) . " TO NOW]";
            case 'like':
                return "*" . addslashes($value) . "*";
            case 'RKM':
                    return addslashes($value);
            case 'in':
                if (is_array($value)) {
                    return '(' . implode(' OR ', array_map(function ($v) {
                            return '"' . addslashes($v) . '"';
                        }, $value)) . ')';
                }
                return '"' . addslashes($value) . '"';
            default:
                return '"' . addslashes($value) . '"';
        }
    }

    /**
     * Build and execute the query, returning both results and facets.
     */
    public function getWithFacets()
    {
        $query = [
            'q' => '*:*', // Default to match all
            'q.op' => $this->queryOperator,
            'fq' => $this->filterQueries,
            'sort' => $this->sort,
            'start' => $this->start,
            'rows' => $this->rows,
            'fl' => $this->fields,
            'facet' => 'true', // Ensure faceting is enabled
            'facet.field' => $this->facetFields, // Add facet fields
        ];

        // Handling return modes for parent-child queries
        switch ($this->returnMode) {
            case 'parent':
                $query['q'] = "{!parent which='parent:true'}*:*";
                break;
            case 'child':
                $query['q'] = "{!child of='-_nest_path_:* *:*'}";
                break;
            default:
                // Default mode: fetch both parent and child
                break;
        }

        $response = Http::get($this->solrUrl, $query);

        if ($response->successful()) {
            $data = $response->json();

            // Return results and facets separately
            return [
                'results' => $data['response']['docs'] ?? [],
                'facets' => $data['facet_counts']['facet_fields'] ?? [],
            ];
        }

        return ['results' => [], 'facets' => []];
    }

    /**
     * Retrieves documents with joined data from a specified core based on matching IDs.
     *
     * @param string $core The core to join data from.
     * @param string $fromId The ID field to match in the current core.
     * @param string $toId The ID field to match in the target core.
     * @return array|null The documents with joined data, or null if the request fails.
     */
    public function getWithJoinedDocuments(string $core, string $fromId, string $toId): ?array
    {
        // Fetch initial response
        $response = $this->get();
        if (!isset($response['response']) || !$response->successful()) {
            return null;
        }

        // Collect unique IDs from the initial response documents
        $docs = $response['response']['docs'];
        $idsArray = array_unique(array_column($docs, $fromId));
        $idsString = implode(' ', $idsArray);

        // Construct the joined core URL and query
        $joinedSolrUrl = (env('SOLR_URL') ?? 'http://localhost:8983/solr') . '/' . $core . '/select';
        $joinedQuery = ['q' => "$toId:($idsString)"];

        // Execute the query on the joined core and check the response
        $joinedResponse = Http::get($joinedSolrUrl, $joinedQuery);
        if (!isset($joinedResponse['response']) || !$joinedResponse->successful()) {
            return null;
        }

        // Map joined documents by their toId for quick lookup
        $joinedDocs = [];
        foreach ($joinedResponse['response']['docs'] as $joinedDoc) {
            $joinedDocs[$joinedDoc[$toId]] = $joinedDoc;
        }

        // Combine main documents with joined data
        $result = [];
        foreach ($docs as $doc) {
            $doc["$core"] = $joinedDocs[$doc[$fromId]] ?? null;  // Assign joined doc if available
            $result[] = $doc;
        }
        return $result;
    }

    /**
     * Add a cross-collection join.
     *
     * @param string $fromIndex The name of the collection to join from
     * @param string $fromField The field in the fromIndex to join on
     * @param string $toField The field in the current collection to join on
     * @param string $query The query to apply on the fromIndex (default '*:*')
     * @return $this
     */
    public function crossCollectionJoin($fromIndex, $fromField, $toField, $query = '*:*')
    {
        $this->crossCollectionJoins[] = [
            'fromIndex' => $fromIndex,
            'fromField' => $fromField,
            'toField' => $toField,
            'query' => $query
        ];

        return $this;
    }

    public function whereIn($field, array $values)
    {
        return $this->where($field, 'in', $values);
    }

    protected function buildCondition($field, $operator, $value, $boost = null)
    {
        // $baseCondition = "{$field}:{$this->formatCondition($operator, $value)}";
        // RKM
        $baseCondition = "{$this->formatCondition($operator, $value)}";

        if ($boost !== null) {
            $baseCondition .= "^{$boost}";
        }

        return $baseCondition;
    }

    /**
     * Build filter query string from filter queries array.
     */
    protected function buildFilterQueryString()
    {

        if (empty($this->filterQueries)) {
            return null;
        }

        $fqParts = [];
        foreach ($this->filterQueries as $index => $fq) {
            if ($index > 0) {
                $fqParts[] = $fq['connector'];
            }
            $fqParts[] = $fq['condition'];
        }
        return implode(' ', $fqParts);
    }

    /**
     * Build the main query string from search queries array.
     */
    protected function buildMainQueryString()
    {
        if (empty($this->searchQueries)) {
            return '*:*';
            // return 'RKM';
            // return 'Zakat';
        }

        $queryParts = [];
        foreach ($this->searchQueries as $index => $query) {
            if ($index > 0) {
                $queryParts[] = $query['connector'];
            }
            $queryParts[] = $query['condition'];
        }
        // return '(' . implode(' ', $queryParts) . ')';
        // RKM
        return implode(' ', $queryParts);
    }

    /**
     * Add a filter condition (fq parameter).
     */
    public function filter($field, $operator, $value)
    {
        $condition = $this->buildCondition($field, $operator, $value);
        $this->filterQueries[] = ['condition' => $condition, 'connector' => 'AND'];
        return $this;
    }

    /**
     * Add an OR search condition to the main query.
     */
    public function orSearch($field, $value, $boost = null)
    {
        $condition = $this->buildCondition($field, '=', $value, $boost);
        $this->searchQueries[] = ['condition' => $condition, 'connector' => 'OR'];
        return $this;
    }

    /**
     * Add a search condition to the main query.
     */
    public function search($field, $operator = "=", $value, $boost = null)
    {
        $condition = $this->buildCondition($field, $operator, $value, $boost);
        $this->searchQueries[] = ['condition' => $condition, 'connector' => 'AND'];
        return $this;
    }
}
