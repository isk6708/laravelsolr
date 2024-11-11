<?php

namespace HaiderJabbar\LaravelSolr;

class LaravelSolr
{
    protected $solrUrl;

    public function __construct()
    {
        $this->solrUrl = config('solr.url');
    }

    public function query($params)
    {
        // Example of how you might interact with Solr
        $url = $this->solrUrl . '/select?' . http_build_query($params);

        // Perform the request and return the results
        $response = file_get_contents($url);
        return json_decode($response, true);
    }
}
