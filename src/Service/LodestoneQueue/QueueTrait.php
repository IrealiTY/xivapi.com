<?php

namespace App\Service\LodestoneQueue;

use App\Entity\Character;
use App\Entity\Entity;
use App\Entity\LodestoneStatistic;
use Doctrine\ORM\EntityManagerInterface;
use Lodestone\Exceptions\AchievementsPrivateException;
use Lodestone\Exceptions\ForbiddenException;
use Lodestone\Exceptions\GenericException;
use Lodestone\Exceptions\NotFoundException;
use Ramsey\Uuid\Uuid;

trait QueueTrait
{
    /**
     * Immediately save an entity
     *
     * @param EntityManagerInterface $em
     * @param $entity
     */
    protected static function save(EntityManagerInterface $em, $entity)
    {
        $em->persist($entity);
        $em->flush();
    }

    /**
     * Queue multiple existing entries
     *
     * @param array $entries
     * @param string $queue
     */
    public static function queue(array $entries, string $queue)
    {
        if (empty($entries)) {
            return;
        }

        $ids = [];
        foreach ($entries as $obj) {
            $ids[] = $obj->getId();
        }

        self::request($ids, $queue);
    }

    /**
     * Request an id to be parsed
     */
    public static function request($ids, string $queue)
    {
        $ids = is_string($ids) ? [ $ids ] : $ids;

        $rabbit = new RabbitMQ();
        $rabbit->connect($queue .'_request');

        foreach ($ids as $id) {
            $rabbit->batchMessage([
                'queue'         => $queue,
                'added'         => date('Y-m-d H:i:s'),
                'requestId'     => Uuid::uuid4()->toString(),
                'method'        => self::METHOD,
                'arguments'     => [ $id ],
            ]);
        }

        $rabbit->sendBatch()->close();
    }

    /**
     * Handle a response from rabbitmq
     */
    public static function response(EntityManagerInterface $em, $response): void
    {
        //
        // Record stats
        //
        // Stats
        $stat = new LodestoneStatistic();
        $stat
            ->setQueue($response->queue)
            ->setMethod($response->method)
            ->setArguments(implode(',', $response->arguments))
            ->setStatus($response->health ? 'good' : 'bad')
            ->setDuration(round($response->finished - $response->updated, 3))
            ->setResponse(is_string($response->response) ? $response->response : get_class($response));
        $em->persist($stat);

        //
        // Process response
        //

        // grab the characters lodestone id from the first argument
        $lodestoneId = $response->arguments[0];

        /** @var Character $entity */
        $entity = self::getEntity($em, $lodestoneId);

        // handle response state
        // if there was an error
        if (!$response->health) {
            switch($response->response) {
                // unknown error
                default: break;

                // todo - not sure what to do here
                case GenericException::class:
                    break;

                // register as not found
                case NotFoundException::class:
                    $entity->setStateNotFound()->incrementNotFoundChecks();
                    $em->persist($entity);
                    break;

                // register as private
                case AchievementsPrivateException::class:
                case ForbiddenException::class:
                    $entity->setStatePrivate()->incrementAchievementsPrivateChecks();
                    $em->persist($entity);
                    break;
            }

            $em->flush();
            return;
        }

        // send response to be handled
        self::handle($em, $entity, $response->response);
        $em->flush();
    }
}
