<?php

/*
| Synonym tables used by TenderIngestService to resolve raw category/location
| values from external sources (e.g. NeST) to existing Category/Location names
| in the database. Keys are lowercased.
*/

return [
    'categories' => [
        'small works'                       => 'Construction',
        'works'                             => 'Construction',
        'ict equipment installation'        => 'Construction',
        'general goods'                     => 'Supplies & Equipment',
        'goods'                             => 'Supplies & Equipment',
        'ifad request for bids – goods'     => 'Supplies & Equipment',
        'ifad request for bids - goods'     => 'Supplies & Equipment',
        'health sector goods'               => 'Healthcare',
        'consultancy'                       => 'Consultancy',
        'standard request for proposal'     => 'Consultancy',
        'general non-consultancy'           => 'Non-Consultancy Services',
        'non-consultancy'                   => 'Non-Consultancy Services',
        'non-consultancy services'          => 'Non-Consultancy Services',
    ],

    'locations' => [
        'ilala'      => 'Dar es Salaam',
        'ilala cbd'  => 'Dar es Salaam',
        'kinondoni'  => 'Dar es Salaam',
        'temeke'     => 'Dar es Salaam',
        'ubungo'     => 'Dar es Salaam',
        'kigamboni'  => 'Dar es Salaam',
    ],

    'defaults' => [
        'category'                   => 'Supplies & Equipment',
        'location'                   => 'Dar es Salaam',
        'deadline_days_from_publish' => 30,
        'type'                       => 'government',
        'status'                     => 'open',
        'documents_url'              => 'https://nest.go.tz/tenders/published-tenders',
    ],
];
