<?php

declare(strict_types=1);

/*
 * This file is part of the Novo SGA project.
 *
 * (c) Rogerio Lino <rogeriolino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Api;

use App\Tests\TestHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TriagemControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface */
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->getConnection()->executeQuery('DELETE FROM unidades');
    }

    public function testDistribuiSenhaWithoutAccessToken(): void
    {
        $client = static::getClient();

        $client->request('POST', '/api/distribui');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDistribuiSenhaWithInvalidAccessToken(): void
    {
        $client = static::getClient();

        $client->request('POST', '/api/distribui', server: [
            'HTTP_AUTHORIZATION' => 'Bearer test',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testDistribuiSenhaWithoutPayload(): void
    {
        $client = static::getClient();
        $accessToken = TestHelper::generateJwtToken(static::getContainer());

        $client->jsonRequest('POST', '/api/distribui', server: [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $accessToken),
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testDistribuiSenhaWithEmptyPayload(): void
    {
        $client = static::getClient();
        $accessToken = TestHelper::generateJwtToken(static::getContainer());

        $client->jsonRequest('POST', '/api/distribui', parameters: [], server: [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $accessToken),
        ]);

        $this->assertResponseStatusCodeSame(422);
        $response = $client->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertIsArray($result['error']);
        $this->assertEquals($result['error'], [
            'unidade' => 'Este valor não deve ser nulo.',
            'prioridade' => 'Este valor não deve ser nulo.',
            'servico' => 'Este valor não deve ser nulo.',
        ]);
    }
}
