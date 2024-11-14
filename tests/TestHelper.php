<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Entity\Servico;
use App\Entity\Unidade;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Entity\AccessToken as AccessTokenEntity;
use League\Bundle\OAuth2ServerBundle\Entity\Client as ClientEntity;
use League\Bundle\OAuth2ServerBundle\Entity\Scope as ScopeEntity;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\OAuth2\Server\CryptKey;
use Novosga\Entity\ServicoInterface;
use Novosga\Entity\UnidadeInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class TestHelper
{
    private function __construct()
    {
    }

    public static function createUnidade(ContainerInterface $container): UnidadeInterface
    {
        /** @var EntityManagerInterface */
        $em = $container->get(EntityManagerInterface::class);

        $unidade = (new Unidade())
            ->setNome('Test ' . time())
            ->setDescricao('test')
            ->setAtivo(true);
        $em->persist($unidade);
        $em->flush();

        return $unidade;
    }

    public static function createServico(ContainerInterface $container): ServicoInterface
    {
        /** @var EntityManagerInterface */
        $em = $container->get(EntityManagerInterface::class);

        $servico = (new Servico())
            ->setNome('Test ' . time())
            ->setDescricao('test')
            ->setAtivo(true)
            ->setPeso(0);
        $em->persist($servico);
        $em->flush();

        return $servico;
    }

    public static function generateJwtToken(ContainerInterface $container): string
    {
        /** @var ParameterBagInterface */
        $parameters = $container->get(ParameterBagInterface::class);
        $privateKey = $parameters->get('private_key');
        $passphrase = $parameters->get('passphrase');

        /** @var AccessTokenManagerInterface */
        $tokenManager = $container->get(AccessTokenManagerInterface::class);
        $accessToken = $tokenManager->find(AppFixtures::OAUTH2_ACCESS_TOKEN_ID);

        $clientEntity = new ClientEntity();
        $clientEntity->setIdentifier($accessToken->getClient()->getIdentifier());
        $clientEntity->setRedirectUri(array_map('strval', $accessToken->getClient()->getRedirectUris()));

        $accessTokenEntity = new AccessTokenEntity();
        $accessTokenEntity->setPrivateKey(new CryptKey($privateKey, $passphrase, false));
        $accessTokenEntity->setIdentifier($accessToken->getIdentifier());
        $accessTokenEntity->setExpiryDateTime(DateTimeImmutable::createFromInterface($accessToken->getExpiry()));
        $accessTokenEntity->setClient($clientEntity);
        $accessTokenEntity->setUserIdentifier((string) $accessToken->getUserIdentifier());

        foreach ($accessToken->getScopes() as $scope) {
            $scopeEntity = new ScopeEntity();
            $scopeEntity->setIdentifier((string) $scope);

            $accessTokenEntity->addScope($scopeEntity);
        }

        return $accessTokenEntity->toString();
    }
}
