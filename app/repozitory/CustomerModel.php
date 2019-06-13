<?php
/**
 * Created by PhpStorm.
 * User: Roman
 * Date: 9. 6. 2019
 * Time: 21:23
 */

namespace App\Model;


class CustomerModel
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

    /** Ulozi zakaznika a vrati jeho ID
     * @param \App\Entity\BaseCustomer $customer
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function saveCustomerBase(\App\Entity\BaseCustomer $customer)
    {
        $qb = $this->conn->createQueryBuilder();

        if (is_null($customer->idCustomer)) {     //zakladame noveho
            $qb->insert('CUSTOMER')
                ->values(array(
                    'C_CREATEDATE' => ':C_CREATEDATE',
                    'C_EMAIL' => ':C_EMAIL',
                    'C_FIRST_NAME' => ':C_FIRST_NAME',
                    'C_ID_CC' => ':C_ID_CC',
                    'C_PHONE_NUMBER' => ':C_PHONE_NUMBER',
                    'C_SURNAME' => ':C_SURNAME'
                ))
                ->setParameter(':C_CREATEDATE', new \DateTime(), \Doctrine\DBAL\Types\DateTimeType::DATETIME);
        } else {      //editujeme existujiciho
            $qb->update('CUSTOMER')
                ->set('C_EMAIL', ':C_EMAIL')
                ->set('C_FIRST_NAME', ':C_FIRST_NAME')
                ->set('C_ID_CC', ':C_ID_CC')
                ->set('C_PHONE_NUMBER', ':C_PHONE_NUMBER')
                ->set('C_SURNAME', ':C_SURNAME')
                ->where('ID_C=:ID_C')
                ->setParameter(':ID_C', $customer->idCustomer, \Doctrine\DBAL\Types\IntegerType::INTEGER);
        }

        $qb->setParameter(':C_EMAIL', $customer->email)
            ->setParameter(':C_FIRST_NAME', $customer->firstName, \Doctrine\DBAL\Types\StringType::STRING)
            ->setParameter(':C_ID_CC', $customer->idCart, \Doctrine\DBAL\Types\IntegerType::INTEGER)
            ->setParameter(':C_PHONE_NUMBER', $customer->phone, \Doctrine\DBAL\Types\StringType::STRING)
            ->setParameter(':C_SURNAME', $customer->surname, \Doctrine\DBAL\Types\StringType::STRING);

        $qb->execute();
        $idCustomer = is_null($customer->idCustomer) ? $this->conn->lastInsertId('ID_C_CUSTOMER') : $customer->idCustomer;

        return $idCustomer;
    }

    /** Ulozi adresu zakaznika
     * @param \App\Entity\CustomerAddress $address
     */
    public function saveCustomerAddress(\App\Entity\CustomerAddress $address)
    {
        $qb = $this->conn->createQueryBuilder();
        if (is_null($address->idAddress)) {     //zakladame novou adresu
            $qb->insert('CUSTOMER_ADDRESS')
                ->values(array(
                    'CA_CITY' => ':CA_CITY',
                    'CA_COUNTRY' => ':CA_COUNTRY',
                    'CA_ID_C' => ':CA_ID_C',
                    'CA_NUMBER_OF_DESCRIPTIVE' => ':CA_NUMBER_OF_DESCRIPTIVE',
                    'CA_ORIENTATION_NUMBER' => ':CA_ORIENTATION_NUMBER',
                    'CA_STREET' => ':CA_STREET',
                    'CA_ZIP_CODE' => ':CA_ZIP_CODE',
                ));
        } else {    //editujeme stavajici
            $qb->update('CUSTOMER_ADDRESS')
                ->set('CA_CITY', ':CA_CITY')
                ->set('CA_COUNTRY', ':CA_COUNTRY')
                ->set('CA_ID_C', ':CA_ID_C')
                ->set('CA_NUMBER_OF_DESCRIPTIVE', ':CA_NUMBER_OF_DESCRIPTIVE')
                ->set('CA_ORIENTATION_NUMBER', ':CA_ORIENTATION_NUMBER')
                ->set('CA_STREET', ':CA_STREET')
                ->set('CA_ZIP_CODE', ':CA_ZIP_CODE')
                ->where('ID_CA=:ID_CA')
                ->setParameter(':ID_CA', $address->idAddress, \Doctrine\DBAL\Types\IntegerType::INTEGER);
        }
        $qb->setParameter(':CA_CITY', $address->city, \Doctrine\DBAL\Types\StringType::STRING)
            ->setParameter(':CA_COUNTRY', $address->country, \Doctrine\DBAL\Types\StringType::STRING)
            ->setParameter(':CA_ID_C', $address->idCustomer, \Doctrine\DBAL\Types\IntegerType::INTEGER)
            ->setParameter(':CA_NUMBER_OF_DESCRIPTIVE', $address->numberOfDescriptive, \Doctrine\DBAL\Types\StringType::STRING)
            ->setParameter(':CA_ORIENTATION_NUMBER', $address->orientationNumber, \Doctrine\DBAL\Types\StringType::STRING)
            ->setParameter(':CA_STREET', $address->street, \Doctrine\DBAL\Types\StringType::STRING)
            ->setParameter(':CA_ZIP_CODE', $address->zipCode, \Doctrine\DBAL\Types\StringType::STRING);
        $qb->execute();
    }

    /** Ulozi zakaznika a adresu zakaznika v jedne fazi
     * @param \App\Entity\BaseCustomer $customer
     * @param \App\Entity\CustomerAddress $address
     */
    public function saveAllCustomerData(\App\Entity\BaseCustomer $customer, \App\Entity\CustomerAddress $address)
    {
        $this->conn->beginTransaction();
        try {
            if (is_null($customer->idCustomer)) {
                $address->idCustomer = $this->saveCustomerBase($customer);
            }
            $this->saveCustomerAddress($address);
        } catch (\Exception $ex) {
            $this->conn->rollBack();
        }
        $this->conn->commit();
    }

    /** Vraci seznam zakazniku vcetne adresy a karty
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getCustomers()
    {
        $qb = $this->conn->createQueryBuilder();
        $qb->select('*')
            ->from('CUSTOMER')
            ->innerJoin('CUSTOMER', 'CUSTOMER_ADDRESS', 'CUSTOMER_ADDRESS', 'CUSTOMER.ID_C=CUSTOMER_ADDRESS.CA_ID_C')
            ->leftJoin('CUSTOMER', 'CUSTOMER_CART', 'CUSTOMER_CART', 'CUSTOMER_CART.ID_CC=CUSTOMER.C_ID_CC')
            ->leftJoin('CUSTOMER_CART', 'CUSTOMER_CART_TYPE', 'CUSTOMER_CART_TYPE', 'ID_CT=CC_ID_CT');

        return $qb;
    }

    /** Zkontrolujeme, zda neni karta jiz obsazena behem editace
     * @param int $idCard
     * @return bool
     */
    public function checkCard(int $idCard){
        $qb = $this->conn->createQueryBuilder();
        $qb->select('COUNT(*) AS COUNT_ROWS')
            ->from('CUSTOMER')
            ->where('C_ID_CC=:idCart')
            ->setParameter(':idCart',$idCard,\Doctrine\DBAL\Types\IntegerType::INTEGER);

        $res = $qb->execute();
        $data = $res->fetch();
        $res->closeCursor();
        if($data['COUNT_ROWS']==0){
            return true;
        }else{
            return false;
        }
    }
}