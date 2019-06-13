<?php
/**
 * Created by PhpStorm.
 * User: Roman
 * Date: 9. 6. 2019
 * Time: 21:50
 */

namespace App\Entity;


class BaseCustomer
{
    /** Id zakaznika
     * @var int|null
     */
public $idCustomer;
    /** Jmeno zakaznika
     * @var string
     */
public $firstName;
    /** Prijmeni zakaznika
     * @var
     */
public $surname;
    /** Telefon zakaznika
     * @var string|null
     */
public $phone;
    /** Email zakaznika
     * @var string
     */
public $email;
    /** Id karty zakaznika
     * @var int|null
     */
public $idCart;
    /** Datum zalozeni zakaznika
     * @var \DateTime
     */
public $createDate;
}