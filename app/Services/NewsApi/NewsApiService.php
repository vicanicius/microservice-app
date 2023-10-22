<?php

namespace App\Services\NewsApi;

use App\Services\NewsApi\Contracts\ApiClientContract;
use App\Services\NewsApi\Contracts\NewsApiServiceContract;
use Elastic\Elasticsearch\ClientBuilder;
use Psr\Http\Message\ResponseInterface;

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
        return $this->getResults(config('news-api.v2.everything.get'), $query, 100);
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
        $totalPerPage = $perPage;

        $currentPage = 1;

        $response = $this->client->request('GET', $uri . $query);

        return json_decode($response->getBody(), true)['articles'];
    }
}
