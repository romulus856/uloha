<?php
/**
 * Created by PhpStorm.
 * User: Roman
 * Date: 9. 6. 2019
 * Time: 19:05
 */

namespace App\Factory;


class DoctrineFactory
{

    /**
     * @var \Doctrine\DBAL\Configuration
     */
    protected static $configurator;
    /**
     * @var \Doctrine\Common\EventManager
     */
    protected static $eventManager;

    /**
     * DoctrineFactory constructor.
     * @param \Doctrine\DBAL\Driver $driver
     * @param \Doctrine\DBAL\Configuration|null $config
     * @param \Doctrine\Common\EventManager|null $eventManager
     */
    public function __construct(\Doctrine\DBAL\Configuration $config = null, \Doctrine\Common\EventManager $eventManager = null)
    {
        self::$configurator = $config;
        self::$eventManager = $eventManager;
    }

    /**
     * @return \Doctrine\DBAL\Connection
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function createConnection($params)
    {
        return \Doctrine\DBAL\DriverManager::getConnection($params, self::$configurator);
    }

}