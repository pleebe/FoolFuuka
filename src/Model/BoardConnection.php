<?php

namespace Foolz\FoolFuuka\Model;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Foolz\FoolFrame\Model\Model;
use Foolz\FoolFrame\Model\Context;

class BoardConnection extends Model
{
    /**
     * @var string
     */
    public $prefix = '';

    /**
     * @var \Doctrine\DBAL\Connection
     */
    public $connection;

    /**
     * Creates a new connection to the external database
     *
     * @param Context $context
     * @param array|Radix $radix
     *
     */
    public function __construct(\Foolz\FoolFrame\Model\Context $context, $radix = null)
    {
        parent::__construct($context);

        $config = new Configuration();

        $config->setSQLLogger(new \Foolz\FoolFrame\Model\DoctrineLogger($context));

        $data = [
            'dbname' => $radix->getValue('db_name'),
            'user' => $radix->getValue('db_username'),
            'password' => $radix->getValue('db_password'),
            'host' => $radix->getValue('db_hostname'),
            'port' => $radix->getValue('db_port'),
            'driver' => $radix->getValue('db_driver'),
        ];

        if ($radix->getValue('db_driver') == 'pdo_mysql') {
            $data['charset'] = $radix->getValue('db_charset');
        }

        $this->prefix = $radix->getValue('db_prefix');

        $this->connection = DriverManager::getConnection($data, $config);
    }

    /**
     * Get rid of the connection on serialization
     *
     * @return array
     */
    public function __sleep()
    {
        return [];
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Returns a query builder
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function qb()
    {
        return $this->connection->createQueryBuilder();
    }

    /**
     * Returns the prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Adds a prefix to the table name
     *
     * @param $table
     * @return string
     */
    public function p($table = '')
    {
        return $this->prefix.$table;
    }
}
