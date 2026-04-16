<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BoardSmokeTest extends WebTestCase
{
    public function testPublicBoardLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Priority Board');
        self::assertSelectorTextContains('.column-header h2', 'WIP');
    }

    public function testPublicApiReturnsBoardPayload(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/board');

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');
        self::assertJson($client->getResponse()->getContent());

        $payload = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('settings', $payload);
        self::assertArrayHasKey('columns', $payload);
        self::assertArrayHasKey('cards', $payload);
        self::assertSame('WIP', $payload['columns']['wip']);
    }
}
