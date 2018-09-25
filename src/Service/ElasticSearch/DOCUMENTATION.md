# ElasticSearch Query Library

An abstraction layer over the official ElasticSearch PHP SDK.

## ElasticSearch CLI Commands

Make sure ElasticSearch is running, if it fails due to memory then make sure the JavaMemory and the: `/etc/elasticsearch/jvm.options` memory values are the same.

Delete all documents of a type:

- `curl -XDELETE 'http://localhost:9200/playground/?pretty=true'`

Other service commands:

- Restart: `sudo service elasticsearch restart`
- Stop: `sudo service elasticsearch stop`
- Start: `sudo service elasticsearch start`
- Test: `curl -X GET 'http://localhost:9200'`

---

## Documentation


Get using:
```php
$this->get('elasticsearch');
```


### ADD DOCUMENT
```php
$elastic->addDocument($index, $type, $id, $body);

// example:
$res = $elastic->addDocument('content', 'item', 1675, [
    'name' => 'Curtama',
    'type' => 'Relic'
]);
```


### BULK DOCUMENTS
```php
$elastic->bulkDocuments($index, $type, $list[]);

// example:
$res = $elastic->bulkDocuments('content', 'item', [
    1675 => [
        'name' => 'Curtana',
        'type' => 'Relic'
    ],
    1676 => [
        'name' => 'Fists',
        'type' => 'Relic'
    ]
]);
```


### GET DOCUMENT
```php
$elastic->getDocument($index, $type, $id, $future|null);

// If $future = true
$res = $elastic->getDocument($index, $type, $id, true);
$res = $elastic->source($res);
```

### DELETE DOCUMENT
```php
$elastic->deleteDocument($index, $type, $id);
```


### ADD INDEX
```php
$elastic->addIndex($index, $shards, $replicas);
```


### DELETE INDEX
```php
$elastic->deleteIndex($index);
```

### SEARCH

Run: `$res = $ela->search('content', 'item');` after building the query.

```php
$ela = $this->get('elasticsearch');

// Match
$ela->QueryBuilder
    ->match('name', 'curtana');
    
    
// Match Phrase
$ela->QueryBuilder
    ->match('name', 'curtana', 'match_phrase');


// Match Phrase Prefix (poor mans auto-complete)
$ela->QueryBuilder
    ->match('name', 'curt', 'match_phrase_prefix');


            
// Multi-match
// Provide a type and fields, 
// Type can be: best_fields(default), most_fields, cross_fields, phrase, phrase_prefix
// https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html
$ela->QueryBuilder
    ->match('name', 'curt', 'multi_match', [
        'type' => 'phrase_prefix',
        'fields' => ['name']
    ]);
  
  
// Query string
// A more SQL like search
// default_operator = OR, can be AND
// https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html
$ela->QueryBuilder
    ->match('name', 'curt', 'query_string', [
        'default_field' => 'name',
        'query' => 'curtana OR fists'
    ]);
    
    
// Query string multiple fields
$ela->QueryBuilder
    ->match('name', 'curt', 'query_string', [
        'fields' => ['name', 'description'],
        'query' => 'curtana OR fists'
    ]);
    
    
// Query string multiple fields via query
$ela->QueryBuilder
    ->match('name', 'curt', 'query_string', [
        'default_field' => 'name,
        'query' => 'curtana AND relic:1 OR name:(allagan OR "of maiming") OR name = qu?ck bro*'
    ]);
    
    
// Query string fuzzy search via query
$ela->QueryBuilder
    ->match('name', 'curt', 'query_string', [
        'default_field' => 'name,
        'query' => 'quikc~ brwn~ foks~'
    ]);
    
    
// Query string ranges
$ela->QueryBuilder
    ->match('name', 'curt', 'query_string', [
        'default_field' => 'name,
        'query' => 'date:[x TO y]'
    ]);
    
    // in query. [ means include { means exclude, eg:
    $query = 'count:[1 TO 5}'
    // = Numbers from 1 up to but NOT INCLUDING 5
    
    
    // Numbers between x and Y
    $query = 'count:{x TO y}';
    
    // Numbers from x and above
    $query = 'count:[x TO *]';
    
    // Dates before y
    $query = 'date:{* TO y}';
    
    // Operators
    $query = 'level_item:>10'
    $query = 'level_item:>=10'
    $query = 'level_item:<10'
    $query = 'level_item:<=10'
    
    // Combined operators
    $query = 'level_item:(>=10 AND <20)'

    // Boost a param, eg we really want to find Maiming gear, especially those that start with Allagan
    $query = 'allagan^2 of maiming 
    
// Term
// Note: Term is case sensitive, it provides no analyzers.
$ela->QueryBuilder
    ->term('name', 'curtana');

// Multiple Term
$ela->QueryBuilder
    ->term('name', 'curtana')
    ->term('name', 'fists');

// Find anything above a specific range
$ela->QueryBuilder
    ->range('level', [
        'gte' => 50
    ]);
    
// Range (with a match)
// find items named "item xxx" greater than or equal to level 50.
$ela->QueryBuilder
    ->match('name', 'curtana', 'match_phrase_prefix')
    ->range('level', [
        'gte' => 50
    ]);

// Prefix
$ela->QueryBuilder
    ->prefix('name', 'it');
    
// Wilcard
$ela->QueryBuilder
    ->wildcard('name', 'curt*');
    
    
// Fuzzy
$ela->QueryBuilder
    ->fuzzy('name', 'em', [
        'boost' => 1,
        'fuzziness' => 2,
        'prefix_length' => 0,
        'max_expansions' => 100,
    ]);
    
// Sort and limit
$ela->QueryBuilder
    ->match('name', 'item')
    ->sort('level', 'asc')
    ->limit(0, 3);

```

