<?php

namespace App\Services\NewsApi;

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
    private const QUEUE_WITH_SUMMARY = 'news_summary_made';
    private const QUEUE = 'summary_news';
    private const DIRECT_EXCHANGE = 'amq.direct';
    private const BIND_QUEUE = 'summary_news_queue';

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
                $news = $this->newsRepository->updateOrCreate(
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

                $this->sendMessageToQueueAmqp($news);
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
        $topScore = $this->newsRepository->searchTopScore($dataRequest['search']);

        return $topScore->toArray();
    }

    public function getTopHeadlinesInTheCountryInElastic(array $dataRequest): array
    {
        $topScore = $this->topHeadlinesRepository->searchTopScore($dataRequest['search']);

        return $topScore->toArray();
    }

    public function getSummaryNews(): void
    {
        $this->receiveMessageToQueueAmqp();
    }

    private function receiveMessageToQueueAmqp()
    {
        $connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST'),
            env('RABBITMQ_PORT'),
            env('RABBITMQ_USERNAME'),
            env('RABBITMQ_PASSWORD')
        );

        $channel = $connection->channel();

        $channel->basic_consume(self::QUEUE_WITH_SUMMARY, '', false, false, false, false, function ($message) {
            $messageDecode = json_decode($message->body);
            $this->newsRepository->update(
                $messageDecode->news_id,
                [
                    'summary' => $messageDecode->summary,
                ]
            );
        });

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }

    private function sendMessageToQueueAmqp($news)
    {
        $amqp = $this->getAmqpConnectionAndChannel();

        $this->amqpQueueDeclare($amqp['channel'], self::QUEUE);

        $this->amqpQueueBind($amqp['channel'], self::QUEUE, self::DIRECT_EXCHANGE, self::BIND_QUEUE);

        $message = [
            'news' => $news,
            'news_id' => $news->id,
        ];

        $amqp['channel']->basic_publish(
            new AMQPMessage(json_encode($message)),
            self::DIRECT_EXCHANGE,
            self::BIND_QUEUE
        );

        $this->closeAmqpConnections($amqp);
    }

    private function getAmqpConnectionAndChannel(): array
    {
        $connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST'),
            env('RABBITMQ_PORT'),
            env('RABBITMQ_USERNAME'),
            env('RABBITMQ_PASSWORD')
        );

        $channel = $connection->channel();

        return [
            'connection' => $connection,
            'channel' => $channel,
        ];
    }

    private function amqpQueueDeclare($channel, $queue)
    {
        $channel->queue_declare($queue, false, true, false, false);
    }

    private function amqpQueueBind($channel, $queue, $exchange, $bindName)
    {
        $channel->queue_bind($queue, $exchange, $bindName);
    }

    private function closeAmqpConnections($amqp)
    {
        $amqp['channel']->close();
        $amqp['connection']->close();
    }
}
