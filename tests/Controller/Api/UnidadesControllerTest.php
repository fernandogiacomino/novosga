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

class UnidadesControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface */
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->getConnection()->executeQuery('DELETE FROM unidades');
    }

    public function testGetUnidadesWithoutAccessToken(): void
    {
        $client = static::getClient();

        $client->request('GET', '/api/unidades');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetUnidadesWithInvalidAccessToken(): void
    {
        $client = static::getClient();

        $client->request('GET', '/api/unidades', server: [
            'HTTP_AUTHORIZATION' => 'Bearer test',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetUnidadesWithValidAccessToken(): void
    {
        $client = static::getClient();
        $accessToken = TestHelper::generateJwtToken(static::getContainer());

        $unidades = [
            TestHelper::createUnidade(static::getContainer()),
            TestHelper::createUnidade(static::getContainer()),
            TestHelper::createUnidade(static::getContainer())
        ];

        $client->request('GET', '/api/unidades', server: [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $accessToken),
        ]);

        $this->assertResponseStatusCodeSame(200);

        $response = $client->getResponse();
        $result = json_decode($response->getContent(), true);
        $this->assertSameSize($unidades, $result);

        for ($i = 0; $i < count($unidades); $i++) {
            $fromDb = $unidades[$i];
            $fromApi = $result[$i];

            $this->assertSame($fromDb->getId(), $fromApi['id']);
            $this->assertSame($fromDb->getNome(), $fromApi['nome']);
            $this->assertSame($fromDb->getDescricao(), $fromApi['descricao']);
            $this->assertSame($fromDb->isAtivo(), $fromApi['ativo']);
        }
    }

    public function testGetUnidadeByInvalidId(): void
    {
        $client = static::getClient();
        $accessToken = TestHelper::generateJwtToken(static::getContainer());

        $client->request('GET', '/api/unidades/999', server: [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $accessToken),
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetUnidadeByValidId(): void
    {
        $client = static::getClient();
        $accessToken = TestHelper::generateJwtToken(static::getContainer());

        $unidade = TestHelper::createUnidade(static::getContainer());

        $client->request('GET', sprintf('/api/unidades/%s', $unidade->getId()), server: [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $accessToken),
        ]);

        $this->assertResponseIsSuccessful();

        $response = $client->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertSame($unidade->getId(), $result['id']);
        $this->assertSame($unidade->getNome(), $result['nome']);
        $this->assertSame($unidade->getDescricao(), $result['descricao']);
        $this->assertSame($unidade->isAtivo(), $result['ativo']);
    }
}
