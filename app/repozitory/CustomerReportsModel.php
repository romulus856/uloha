<?php
/**
 * Created by PhpStorm.
 * User: Roman
 * Date: 9. 6. 2019
 * Time: 21:46
 */

namespace App\Model;
use Tracy\Debugger;

class CustomerReportsModel
{
    use \Nette\SmartObject;
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    public function __construct(\Doctrine\DBAL\Connection $conn)
    {
        $this->conn = $conn;
    }

    /** Vrati aktuali pocet zakazniku
     *
     * @return int
     */
    public function getCountCustomers()
    {
        $qb = $this->conn->createQueryBuilder();
        $qb->select('COUNT(*) AS COUNT_ROWS')
            ->from('CUSTOMER');
        $res = $qb->execute();
        $data = $res->fetch();
        $res->closeCursor();
        return (int )$data['COUNT_ROWS'];
    }

    /** vrati pocet prirazenych karet
     * @return int
     */
    public function getCountAssignedCart()
    {
        $qb = $this->conn->createQueryBuilder();
        $qb->select('COUNT(*) AS COUNT_ROWS')
            ->from('CUSTOMER')
            ->where('C_ID_CC IS NOT NULL');
        $res = $qb->execute();
        $data = $res->fetch();
        $res->closeCursor();
        return (int)$data['COUNT_ROWS'];
    }

    /** vrati qb pro vyber top zakazniku za vybranou dobu
     * @param int $top
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getTopCustomers(int $top,\DateTime $dateFrom,\DateTime $dateTo){
        $qb = $this->conn->createQueryBuilder();
        $qbSub = $this->conn->createQueryBuilder();
        $qbSub->select('SUM(custom_order_item.COI_ITEM_PRICE*custom_order_item.COI_ITEM_COUNT) AS PRICE', 'custom_order.CO_ID_C')
            ->from('custom_order')
            ->innerJoin('custom_order','custom_order_item','custom_order_item','custom_order_item.COI_ID_CO=custom_order.ID_CO')
            ->where('CO_CREATE_DATE>=:dateFrom AND CO_CREATE_DATE<=:dateTo')
            ->groupBy('CO_ID_C')
            ->orderBy('PRICE','DESC')
            ->setMaxResults($top);

        $qb->select('*')
            ->from('CUSTOMER')
            ->innerJoin('CUSTOMER','('.$qbSub->getSQL().')','tab','tab.CO_ID_C=ID_C')
            ->setParameter(':dateFrom',$dateFrom,\Doctrine\DBAL\Types\DateTimeType::DATETIME)
            ->setParameter(':dateTo',$dateTo,\Doctrine\DBAL\Types\DateTimeType::DATETIME);
        Debugger::log('Sestavil jsem SQL dotaz pro top zakazniky: '.$qb->getSQL().' s parametry:'.var_export($qb->getParameters(),true));

        return $qb;
    }

}