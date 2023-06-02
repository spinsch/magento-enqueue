<?php

use Enqueue\SimpleClient\SimpleClient;
use Interop\Queue\Processor;

class Enqueue_Enqueue_Helper_Data extends Mage_Core_Helper_Data
{
    /**
     * @var SimpleClient
     */
    private $client;

    public function bindProcessors()
    {
        if (false == $processors = Mage::getStoreConfig('enqueue/processors')) {
            return;
        }

        foreach ($processors as $name => $config) {
            if (empty($config['topic'])) {
                throw new \LogicException(sprintf('Topic name is not set for processor: "%s"', $name));
            }

            if (empty($config['helper'])) {
                throw new \LogicException(sprintf('Helper name is not set for processor: "%s"', $name));
            }

            $this->getClient()->bindTopic($config['topic'], function () use ($config) {
                $processor = Mage::helper($config['helper']);

                if (false == $processor instanceof Processor) {
                    throw new \LogicException(sprintf('Expects processor is instance of: "%s"', Processor::class));
                }

                return call_user_func_array([$processor, 'process'], func_get_args());
            }, $name);
        }
    }

    /**
     * @param string               $topic
     * @param string|array|Message $message
     */
    public function send($topic, $message)
    {
        $this->getProducer()->sendEvent($topic, $message);
    }

    /**
     * @return \Enqueue\Client\ProducerInterface
     */
    public function getProducer()
    {
        return $this->getClient()->getProducer();
    }

    /**
     * @return SimpleClient
     */
    public function getClient()
    {
        if (null === $this->client) {
            $this->client = new SimpleClient($this->buildConfig());
        }

        return $this->client;
    }

    /**
     * @return array
     */
    public function buildConfig()
    {
        $config = $this->getClientConfig();
        $config['transport'] = [];

        switch ($name = Mage::getStoreConfig('enqueue/transport/default')) {
            case 'amqp':
                $config['transport'] = $this->getAmqpConfig();
                break;
            case 'stomp':
                $config['transport'] = $this->getStompConfig();
                break;
            case 'rabbitmq_stomp':
                $config['transport'] = $this->getRabbitMqStompConfig();
                break;
            case 'fs':
                $config['transport'] = $this->getFsConfig();
                break;
            case 'sqs':
                $config['transport'] = $this->getSqsConfig();
                break;
            case 'redis':
                $config['transport'] = $this->getRedisConfig();
                break;
            case 'dbal':
                $config['transport'] = $this->getDbalConfig();
                break;
            default:
                throw new \LogicException(sprintf('Unknown transport: "%s"', $name));
        }

        return $config;
    }

    /**
     * @return array
     */
    public function getClientConfig()
    {
        return ['client' => [
            'prefix' => Mage::getStoreConfig('enqueue/client/prefix'),
            'app_name' => Mage::getStoreConfig('enqueue/client/app_name'),
            'router_topic' => Mage::getStoreConfig('enqueue/client/router_topic'),
            'router_queue' => Mage::getStoreConfig('enqueue/client/router_queue'),
            'default_queue' => Mage::getStoreConfig('enqueue/client/default_processor_queue'),
            'redelivered_delay_time' => (int) Mage::getStoreConfig('enqueue/client/redelivered_delay_time'),
        ]];
    }

    /**
     * @return array
     */
    public function getAmqpConfig()
    {
        return [
            'dsn' => Mage::getStoreConfig('enqueue/amqp/host')
        ];

        return ['amqp' => [
            'host' => Mage::getStoreConfig('enqueue/amqp/host'),
            'port' => (int) Mage::getStoreConfig('enqueue/amqp/port'),
            'user' => Mage::getStoreConfig('enqueue/amqp/user'),
            'pass' => Mage::getStoreConfig('enqueue/amqp/pass'),
            'vhost' => Mage::getStoreConfig('enqueue/amqp/vhost'),
            'lazy' => (bool) Mage::getStoreConfig('enqueue/amqp/lazy'),
        ]];
    }

    /**
     * @return array
     */
    public function getStompConfig()
    {
        return ['stomp' => [
            'host' => Mage::getStoreConfig('enqueue/stomp/host'),
            'port' => (int) Mage::getStoreConfig('enqueue/stomp/port'),
            'login' => Mage::getStoreConfig('enqueue/stomp/login'),
            'password' => Mage::getStoreConfig('enqueue/stomp/password'),
            'vhost' => Mage::getStoreConfig('enqueue/stomp/vhost'),
            'lazy' => (bool) Mage::getStoreConfig('enqueue/stomp/lazy'),
        ]];
    }

    /**
     * @return array
     */
    public function getRabbitMqStompConfig()
    {
        return ['rabbitmq_stomp' => [
            'host' => Mage::getStoreConfig('enqueue/rabbitmq_stomp/host'),
            'port' => (int) Mage::getStoreConfig('enqueue/rabbitmq_stomp/port'),
            'login' => Mage::getStoreConfig('enqueue/rabbitmq_stomp/login'),
            'password' => Mage::getStoreConfig('enqueue/rabbitmq_stomp/password'),
            'vhost' =>  Mage::getStoreConfig('enqueue/rabbitmq_stomp/vhost'),
            'lazy' => (bool) Mage::getStoreConfig('enqueue/rabbitmq_stomp/lazy'),
            'delay_plugin_installed' => (bool) Mage::getStoreConfig('enqueue/rabbitmq_stomp/delay_plugin_installed'),
            'management_plugin_installed' => (bool) Mage::getStoreConfig('enqueue/rabbitmq_stomp/management_plugin_installed'),
            'management_plugin_port' => (int) Mage::getStoreConfig('enqueue/rabbitmq_stomp/management_plugin_port'),
        ]];
    }

    /**
     * @return array
     */
    public function getFsConfig()
    {
        return ['fs' => [
            'store_dir' => Mage::getStoreConfig('enqueue/fs/store_dir'),
            'pre_fetch_count' => (int) Mage::getStoreConfig('enqueue/fs/pre_fetch_count'),
            'chmod' => intval(Mage::getStoreConfig('enqueue/fs/chmod'), 8),
        ]];
    }

    /**
     * @return array
     */
    public function getSqsConfig()
    {
        return ['sqs' => [
            'key' => Mage::getStoreConfig('enqueue/sqs/key'),
            'secret' => Mage::getStoreConfig('enqueue/sqs/secret'),
            'token' => Mage::getStoreConfig('enqueue/sqs/token'),
            'region' => Mage::getStoreConfig('enqueue/sqs/region'),
            'retries' => (int) Mage::getStoreConfig('enqueue/sqs/retries'),
            'lazy' => (bool) Mage::getStoreConfig('enqueue/sqs/lazy'),
        ]];
    }

    /**
     * @return array
     */
    public function getRedisConfig()
    {
        return ['redis' => [
            'host' => Mage::getStoreConfig('enqueue/redis/host'),
            'port' => (int) Mage::getStoreConfig('enqueue/redis/port'),
            'vendor' => Mage::getStoreConfig('enqueue/redis/vendor'),
            'lazy' => (bool) Mage::getStoreConfig('enqueue/redis/lazy'),
        ]];
    }

    /**
     * @return array
     */
    public function getDbalConfig()
    {
        return ['dbal' => [
            'connection' => [
                'url' => Mage::getStoreConfig('enqueue/dbal/url'),
            ],
            'table_name' => Mage::getStoreConfig('enqueue/dbal/table_name'),
            'polling_interval' => (int) Mage::getStoreConfig('enqueue/dbal/polling_interval'),
            'lazy' => (bool) Mage::getStoreConfig('enqueue/dbal/lazy'),
        ]];
    }
}
