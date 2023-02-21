#!/usr/bin/env php
<?php

use Enqueue\Symfony\Client\SimpleConsumeCommand;
use Enqueue\Symfony\Client\SimpleProduceCommand;
use Enqueue\Symfony\Client\SimpleRoutesCommand;
use Enqueue\Symfony\Client\SimpleSetupBrokerCommand;
use Enqueue\Symfony\Client\SetupBrokerCommand;
use Symfony\Component\Console\Application;


set_time_limit(0);

$dir = realpath(dirname($_SERVER['PHP_SELF']));
$loader = require $dir . '/../app/Mage.php';

// init
Mage::app('admin', 'store');

/** @var \Enqueue_Enqueue_Helper_Data $enqueue */
$enqueue = Mage::helper('enqueue');
$enqueue->bindProcessors();

/** @var \Enqueue\SimpleClient\SimpleClient $client */
$client = $enqueue->getClient();

$application = new Application();
$application->add(new SimpleSetupBrokerCommand($client->getDriver()));
$application->add(new SimpleRoutesCommand($client->getDriver()));
$application->add(new SimpleProduceCommand($client->getProducer()));
$application->add(new SimpleConsumeCommand(
    $client->getQueueConsumer(),
    $client->getDriver(),
    $client->getDelegateProcessor()
));

$application->run();