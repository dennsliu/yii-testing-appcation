<?php

namespace console\controllers;

use Dennsliu\RSMQ\ExecutorInterface;
use Dennsliu\RSMQ\Message;
use Dennsliu\RSMQ\QueueWorker;
use Dennsliu\RSMQ\WorkerSleepProvider;
use yii\console\Controller;
use yii\console\ExitCode;
use Predis\Client;
use Dennsliu\RSMQ\RSMQClient;

class RequeueController extends Controller
{
    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    /**
     * php ./yii requeue/consumer
     *
     * @return void
     */
    public function actionConsumer()
    {

        $predis = new Client(
            [
                'host' => '127.0.0.1',
                'port' => 6379
            ]
        );
        $rsmq = new RSMQClient($predis);

        $executor = new class() implements ExecutorInterface {
            public function __invoke(Message $message): bool
            {
                //@todo: do some work, true will ack/delete the message, false will allow the queue's config to "re-publish"
                var_dump($message);
                echo '-----$message-------';
                return true;
            }
        };

        $sleepProvider = new class() implements WorkerSleepProvider {
            public function getSleep(): ?int
            {
                /**
                 * This allows you to return null to stop the worker, which can be used with something like redis to mark.
                 *
                 * Note that this method is called _before_ we poll for a message, and therefore if it returns null we'll eject
                 * before we process a message.
                 */
                return 1;
            }
        };
        $worker = new QueueWorker($rsmq, $executor, $sleepProvider, 'myqueue');
        $worker->work();

        return ExitCode::OK;
    }
}
