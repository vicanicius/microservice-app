<?php

namespace App\Services\NewsApi;

use App\Services\NewsApi\Contracts\ApiClientContract;
use App\Services\NewsApi\Contracts\NewsApiServiceContract;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Http\Message\ResponseInterface;

class NewsApiService implements NewsApiServiceContract
{

    private const QUEUE = 'summary_news';
    private const DIRECT_EXCHANGE = 'amq.direct';
    private const BIND_QUEUE = 'summary_news_queue';

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
        $this->sendMessageToQueueAmqp($result);

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

    private function sendMessageToQueueAmqp($messages)
    {
        $amqp = $this->getAmqpConnectionAndChannel();

        $this->amqpQueueDeclare($amqp['channel'], self::QUEUE);

        $this->amqpQueueBind($amqp['channel'], self::QUEUE, self::DIRECT_EXCHANGE, self::BIND_QUEUE);

        foreach ($messages as $message) {
            $amqp['channel']->basic_publish(
                new AMQPMessage($message['description']),
                self::DIRECT_EXCHANGE,
                self::BIND_QUEUE
            );
        }

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
