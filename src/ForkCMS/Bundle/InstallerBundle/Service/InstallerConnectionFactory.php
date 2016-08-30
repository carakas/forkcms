<?php

namespace ForkCMS\Bundle\InstallerBundle\Service;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use ForkCMS\Bundle\InstallerBundle\DBAL\InstallerConnection;
use ForkCMS\Bundle\InstallerBundle\Entity\InstallationData;
use Symfony\Component\HttpFoundation\Session\Session;

class InstallerConnectionFactory extends ConnectionFactory
{
    /**
     * @param array $params
     * @param Configuration|null $config
     * @param EventManager|null $eventManager
     * @param array $mappingTypes
     *
     * @return Connection
     */
    public function createConnection(
        array $params,
        Configuration $config = null,
        EventManager $eventManager = null,
        array $mappingTypes = array()
    ) {
        // there is no other way since we don't have access to the container, but it works yeey, but I'm still sorry
        $session = new Session();

        if (!$session->has('installation_data') || $session->get('installation_data')->getDbHostname() === null) {
            return $this->getInstallerConnection($params, $config, $eventManager);
        }

        /** @var InstallationData $installationData */
        $installationData = $session->get('installation_data');
        $params['host'] = $installationData->getDbHostname();
        $params['port'] = $installationData->getDbPort();
        $params['dbname'] = $installationData->getDbDatabase();
        $params['user'] = $installationData->getDbUsername();
        $params['password'] = $installationData->getDbPassword();

        //continue with regular connection creation using new params
        return parent::createConnection($params, $config, $eventManager, $mappingTypes);
    }

    /**
     * @param array $params
     * @param Configuration|null $config
     * @param EventManager|null $eventManager
     *
     * @return InstallerConnection
     */
    private function getInstallerConnection(
        array $params,
        Configuration $config = null,
        EventManager $eventManager = null
    ) {
        $normalConnection = DriverManager::getConnection($params, $config, $eventManager);

        return new InstallerConnection($params, $normalConnection->getDriver(), $config, $eventManager);
    }
}
