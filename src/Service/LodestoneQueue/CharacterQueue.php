<?php

namespace App\Service\LodestoneQueue;

use App\Entity\Character;
use App\Entity\CharacterAchievements;
use App\Entity\CharacterFriends;
use App\Entity\Entity;
use App\Entity\LodestoneStatistic;
use App\Service\Content\LodestoneData;
use Doctrine\ORM\EntityManagerInterface;
use Lodestone\Exceptions\GenericException;
use Lodestone\Exceptions\NotFoundException;
use Ramsey\Uuid\Uuid;

class CharacterQueue
{
    const AUTO  = 'characters_auto';
    const FAST  = 'characters_fast';
    
    /**
     * Add a series of characters to a queue
     *
     * @param Character[] $characters
     * @param string $queue
     */
    public static function queue(array $characters, string $queue)
    {
        $ids = [];
        foreach ($characters as $character) {
            $ids[] = $character->getId();
        }
        
        self::request($ids, $queue);
    }

    /**
     * Add a character to the queue to be parsed (doesn't matter if it's to add or update)
     */
    public static function request($ids, string $queue)
    {
        $rabbit = new RabbitMQ();

        $ids = is_string($ids) ? [ $ids ] : $ids;

        $rabbit->connect($queue .'_request');

        foreach ($ids as $id) {
            $rabbit->batchMessage([
                'type'          => 'character',
                'queue'         => $queue,
                'added'         => microtime(true),
                'requestId'     => Uuid::uuid4()->toString(),
                'method'        => LodestoneApi::GET_CHARACTER,
                'arguments'     => [ $id ],
            ]);
        }
        
        $rabbit->sendBatch()->close();
    }
    
    /**
     * Handle a character response from the
     * @param EntityManagerInterface $em
     * @param $response
     */
    public static function response(EntityManagerInterface $em, $response): void
    {
        // grab the characters lodestone id from the first argument
        $lodestoneId = $response->arguments[0];
        
        // try find the DB record for this character, otherwise just make one
        $repo   = $em->getRepository(Character::class);
        $entity = $repo->find($lodestoneId) ?: new Character($lodestoneId);
        
        // Stats
        $stat = new LodestoneStatistic();
        $stat->setType($response->type)
            ->setQueue($response->queue)
            ->setMethod($response->method)
            ->setArguments(implode(',', $response->arguments))
            ->setStatus($response->health ? 'good' : 'bad')
            ->setDuration(round($response->finished - $response->added, 3))
            ->setResponse(is_string($response->response) ? $response->response : 'Character Object');
        
        $em->persist($stat);

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
                    $entity->setState(Entity::STATE_NOT_FOUND)->setUpdated(time());
                    $em->persist($entity);
                    break;
            }
            
            $em->flush();
            return;
        }
        
        // if the previous state was "adding" then this response means it's
        // a new character and we can request achievements + friends
        if ($entity->getState() === Entity::STATE_ADDING) {
            $em->persist((new CharacterAchievements($lodestoneId))->setState(Entity::STATE_CACHED));
            $em->persist((new CharacterFriends($lodestoneId))->setState(Entity::STATE_CACHED));
        }
        
        // all good, process the character!
        $character = LodestoneData::convertCharacterData($response->response);
        LodestoneData::save('character', 'data', $lodestoneId, $character);
        $em->persist($entity->setState(Entity::STATE_CACHED)->setUpdated(time()));
        $em->flush();
    }
}
