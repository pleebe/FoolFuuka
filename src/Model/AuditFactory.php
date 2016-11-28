<?php

namespace Foolz\FoolFuuka\Model;

use Foolz\FoolFrame\Model\DoctrineConnection;
use Foolz\FoolFrame\Model\Model;
use Foolz\FoolFrame\Model\Preferences;

class AuditFactory extends Model
{
    /**
     * @var DoctrineConnection
     */
    protected $dc;

    /**
     * @var RadixCollection
     */
    protected $radix_coll;

    /**
     * @var Preferences
     */
    protected $preferences;

    public function __construct(\Foolz\FoolFrame\Model\Context $context)
    {
        parent::__construct($context);

        $this->dc = $context->getService('doctrine');
        $this->radix_coll = $context->getService('foolfuuka.radix_collection');
        $this->preferences = $context->getService('preferences');
    }

    /**
     * Converts an array into a Audit object
     *
     * @param   array  $array  The array from database
     *
     * @return  \Foolz\FoolFuuka\Model\Audit
     */
    public function fromArray($array)
    {
        $new = new Audit($this->getContext());
        foreach ($array as $key => $item) {
            $new->$key = $item;
        }

        return $new;
    }

    /**
     * Takes an array of arrays to create Audit objects
     *
     * @param   array  $array  The array from database
     *
     * @return  array  An array of \Foolz\FoolFuuka\Model\Audit
     */
    public function fromArrayDeep($array)
    {
        $new = [];
        foreach ($array as $item) {
            $new[] = $this->fromArray($item);
        }

        return $new;
    }

    public function getPagedBy($order_by, $order, $page, $per_page = 30)
    {
        $result = $this->dc->qb()
            ->select('*')
            ->from($this->dc->p('audit_log'), 'l')
            ->orderBy($order_by, $order)
            ->setMaxResults($per_page)
            ->setFirstResult(($page * $per_page) - $per_page)
            ->execute()
            ->fetchAll();

        return $this->fromArrayDeep($result);
    }

    public function log($type, $data)
    {
        if ($this->preferences->get('foolfuuka.audit.'.$type.'_enabled', true)) {
            $this->dc->getConnection()
                ->insert($this->dc->p('audit_log'), [
                    'timestamp' => time(),
                    'user' => $this->getAuth()->getUser()->getId(),
                    'type' => $type,
                    'data' => json_encode($data),
                ]);
        }
    }
}
