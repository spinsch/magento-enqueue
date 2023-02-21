<?php
use Enqueue\AmqpExt\AmqpContext as AmqpExtContect;
use Enqueue\AmqpLib\AmqpContext as AmqpLibContect;
use Enqueue\AmqpBunny\AmqpContext as AmqpBunnyContect;
use Enqueue\Stomp\StompContext;
use Enqueue\Fs\FsContext;
use Enqueue\Sqs\SqsContext;
use Enqueue\Redis\RedisContext;
use Enqueue\Dbal\DbalContext;

class Enqueue_Enqueue_Model_Config_Field_Transportdefault extends Mage_Core_Model_Config_Data
{
    /**
     * {@inheritdoc}
     */
    protected function _beforeSave()
    {
        $return = parent::_beforeSave();

        $transport = $this->getValue();

        $amqs = [
            'enqueue/amqp-ext' => AmqpExtContect::class,
            'enqueue/amqp-lib' => AmqpLibContect::class,
            'enqueue/amqp-bunny' => AmqpBunnyContect::class
        ];

        $data = [
            'amqp' => [
                'name' => 'AMQP (like RabbitMQ)',
                'package' => implode(' or ', array_keys($amqs)),
                'class' => array_values($amqs),
            ],
            'rabbitmq_stomp' => [
                'name' => 'RabbitMQ STOMP',
                'package' => 'enqueue/stomp',
                'class' => StompContext::class,
            ],
            'stomp' => [
                'name' => 'STOMP',
                'package' => 'enqueue/stomp',
                'class' => StompContext::class,
            ],
            'fs' => [
                'name' => 'Filesystem',
                'package' => 'enqueue/fs',
                'class' => FsContext::class,
            ],
            'sqs' => [
                'name' => 'Amazon AWS SQS',
                'package' => 'enqueue/sqs',
                'class' => SqsContext::class,
            ],
            'redis' => [
                'name' => 'Redis',
                'package' => 'enqueue/redis',
                'class' => RedisContext::class,
            ],
            'dbal' => [
                'name' => 'Doctrine DBAL',
                'package' => 'enqueue/dbal',
                'class' => DbalContext::class,
            ],
        ];

        if (false == isset($data[$transport])) {
            throw new \LogicException(sprintf('Unknown transport: "%s"', $transport));
        }

        if (false == $this->isClassExists($data[$transport]['class'])) {
            Mage::throwException(sprintf('%s transport requires package "%s". Please install it via composer. #> php composer.php require %s',
                $data[$transport]['name'], $data[$transport]['package'], $data[$transport]['package']
            ));
        }

        return $return;
    }

    /**
     * @param string|array $class
     *
     * @return bool
     */
    private function isClassExists($class)
    {
        if (is_array($class)) {
            foreach ($class as $declaration) {
                if ($this->isClassExists($declaration)) {
                    return true;
                }
            }

            return false;
        }

        try {
            return class_exists($class);
        } catch (\Exception $e) { // in dev mode error handler throws exception
            return false;
        }
    }
}
