<?php

namespace App\Services\NewsApi;

use App\Services\NewsApi\Contracts\ApiClientContract;
use App\Services\NewsApi\Contracts\NewsApiServiceContract;

class NewsApiService implements NewsApiServiceContract
{
    /**
     * @param  ApiClientContract  $client
     */
    public function __construct(private ApiClientContract $client)
    {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function getAllArticlesAbout(string $query): array
    {
        $result = $this->getResults(config('news-api.v2.everything.get'), $query, 100);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getTopHeadlinesInTheCountry(string $country): array
    {
        return $this->getResults(config('news-api.v2.top-headlines.get'), $country, 20);
    }

    private function getResults(string $uri, string $query, int $perPage)
    {
        $response = $this->client->request('GET', $uri . $query);

        return json_decode($response->getBody(), true)['articles'];
    }
}
