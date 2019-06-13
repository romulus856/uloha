<?php
/**
 * Created by PhpStorm.
 * User: Roman
 * Date: 9. 6. 2019
 * Time: 21:28
 */

namespace App\Model;


class CartModel
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

    /** Vraci seznam volnych karet pro zakaznika pro ciselnik. Pokud je vyplneno id karty zakanika, varci i tuto
     * @param int|null $idCustomerCart
     * @return array
     */
    public function getFreeCartsForCustomer(int $idCustomerCart = null): array
    {
        $qb = $this->conn->createQueryBuilder();
        $qb->select('ID_CC', 'CC_NUMBER', 'CT_CART_TYPE')
            ->from('CUSTOMER_CART')
            ->innerJoin('CUSTOMER_CART', 'CUSTOMER_CART_TYPE', 'CUSTOMER_CART_TYPE', 'ID_CT=CC_ID_CT')
            ->where('ID_CC NOT IN(SELECT C_ID_CC FROM CUSTOMER) AND CT_DELDATE IS NULL')
            ->orderBy('CC_ID_CT', 'ASC');

        if (!is_null($idCustomerCart)) {
            $qb->orWhere('ID_CC=:idCart')
                ->setParameter(':idCart', $idCustomerCart, \Doctrine\DBAL\Types\IntegerType::INTEGER);
        }

        $data = array();
        $res = $qb->execute();
        while ($resultData = $res->fetch()) {
            $data[$resultData['ID_CC']] = $resultData['CC_NUMBER'] . ' (' . $resultData['CT_CART_TYPE'] . ')';
        }
        $res->closeCursor();

        return $data;
    }
}