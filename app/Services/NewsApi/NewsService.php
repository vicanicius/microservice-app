<?php

namespace App\Services\NewsApi;

use App\Models\News;
use App\Repositories\Contracts\NewsRepositoryContract;
use App\Repositories\Contracts\TopHeadlinesRepositoryContract;
use App\Services\NewsApi\Contracts\NewsApiServiceContract;
use App\Services\NewsApi\Contracts\NewsServiceContract;
use GuzzleHttp\Exception\RequestException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Http\Message\ResponseInterface;

class NewsService implements NewsServiceContract
{
    /**
     * @param  NewsApiServiceContract  $service
     * @param  NewsRepositoryContract  $newsRepository
     * @param  TopHeadlinesRepositoryContract  $topHeadlinesRepository
     */
    public function __construct(
        private NewsApiServiceContract $service,
        private NewsRepositoryContract $newsRepository,
        private TopHeadlinesRepositoryContract $topHeadlinesRepository
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function getAllArticlesAbout(array $dataRequest): array
    {
        try {
            $queryString = http_build_query($dataRequest['query']);

            $response = $this->service->getAllArticlesAbout($queryString);

            foreach ($response as $article) {
                $this->newsRepository->updateOrCreate(
                    [
                        'url' => $article['url']
                    ],
                    [
                        'sourceId' => $article['source']['id'],
                        'sourceName' => $article['source']['name'],
                        'author' => $article['author'],
                        'title' => $article['title'],
                        'description' => $article['author'],
                        'url' => $article['url'],
                        'urlToImage' => $article['urlToImage'],
                        'publishedAt' => $article['publishedAt'],
                        'content' => $article['content'],
                    ]
                );
            }

            return [];
        } catch (RequestException $exception) {
            return $this->formatExceptionResponse($exception->getResponse());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getTopHeadlinesInTheCountry(array $dataRequest): array
    {
        $country = $dataRequest['country'];
        try {
            $queryString = http_build_query($dataRequest);

            $response = $this->service->getTopHeadlinesInTheCountry($queryString);

            foreach ($response as $article) {
                $this->topHeadlinesRepository->updateOrCreate(
                    [
                        'url' => $article['url'],
                        'country' => $country,
                    ],
                    [
                        'country' => $country,
                        'sourceId' => $article['source']['id'],
                        'sourceName' => $article['source']['name'],
                        'author' => $article['author'],
                        'title' => $article['title'],
                        'description' => $article['author'],
                        'url' => $article['url'],
                        'urlToImage' => $article['urlToImage'],
                        'publishedAt' => $article['publishedAt'],
                        'content' => $article['content'],
                    ]
                );
            }

            return [];
        } catch (RequestException $exception) {
            return $this->formatExceptionResponse($exception->getResponse());
        }
    }

    /**
     * @param  ResponseInterface  $response
     * @return array
     */
    private function formatExceptionResponse(ResponseInterface $response): array
    {
        $data = json_decode($response->getBody()->getContents(), true);

        return [
            'success' => false,
            'message' => $data['message'] ?? 'No service response',
            'data' => $data['data'] ?? $data ?? [],
        ];
    }

    public function getAllArticlesAboutInElastic(array $dataRequest): array
    {
        $connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST'),
            env('RABBITMQ_PORT'),
            env('RABBITMQ_USERNAME'),
            env('RABBITMQ_PASSWORD')
        );
        $channel = $connection->channel();

        $queue = 'teste-queue'; // Substitua pelo nome da sua fila

        $channel->basic_consume($queue, '', false, true, false, false, function (AMQPMessage $message) {
            dd('Mensagem recebida: ', $message->getBody(), PHP_EOL);
        });
        return [];
        /*
        $topScore = $this->newsRepository->searchTopScore($dataRequest['search']);

        return $topScore->toArray(); */
    }

    public function getTopHeadlinesInTheCountryInElastic(array $dataRequest): array
    {
        $topScore = $this->topHeadlinesRepository->searchTopScore($dataRequest['search']);

        return $topScore->toArray();
    }
}
