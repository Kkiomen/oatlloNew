<?php

declare(strict_types=1);

namespace App\Aidevs;

use Faker\Provider\Uuid;
use Qdrant\Qdrant;
use Qdrant\Config;
use Qdrant\Http\Builder;
use Qdrant\Endpoints\Collections;
use Qdrant\Models\Request\CreateCollection;
use Qdrant\Models\Request\VectorParams;
use Qdrant\Models\PointsStruct;
use Qdrant\Models\PointStruct;
use Qdrant\Models\Filter\Condition\MatchString;
use Qdrant\Models\Filter\Filter;
use Qdrant\Models\Request\SearchRequest;
use Qdrant\Models\VectorStruct;

class QdrantHelper
{

    public static function createCollection(string $collectionName): void
    {
        $client = self::initClient();
        $createCollection = new CreateCollection();
        $createCollection->addVector(new VectorParams(3072, VectorParams::DISTANCE_COSINE), 'content');
        $client->collections($collectionName)->create($createCollection);
    }

    public static function addPoint(string $text, string $collectionName): void
    {
        $uuid = Uuid::uuid();
        $embedding = OpenAiHelper::embedding($text);
        $points = new PointsStruct();
        $points->addPoint(
            new PointStruct(
                $uuid,
                new VectorStruct($embedding, 'content'),
                [
                    'id' => $uuid,
                    'meta' => $text
                ]
            )
        );

        self::initClient()->collections($collectionName)->points()->upsert($points);
    }

    public static function search(string $query, string $collectionName): array
    {
        $embedding = OpenAiHelper::embedding($query);

        $searchRequest = (new SearchRequest(new VectorStruct($embedding, 'content')))
            ->setLimit(20)
            ->setParams([
                'hnsw_ef' => 128,
                'exact' => false,
            ])
            ->setWithPayload(true);

        $response =  self::initClient()->collections($collectionName)->points()->search($searchRequest);

        return $response['result'];
    }

    public static function initClient(): Qdrant
    {
        $config = new Config('https://422fc1dc-477a-45ef-8f30-117a13056737.us-east4-0.gcp.cloud.qdrant.io');
        $config->setApiKey($_ENV['QDRANT_API_KEY']);

        $transport = (new Builder())->build($config);
        return new Qdrant($transport);
    }
}
