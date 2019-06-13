<?php
/**
 * Created by PhpStorm.
 * User: Roman
 * Date: 9. 6. 2019
 * Time: 22:00
 */

namespace App\Entity;


class CustomerAddress
{
    /**
     * @var int|null
     */
    public $idAddress;
    /** Id zakaznika
     * @var int
     */
    public $idCustomer;
    /**
     * @var string
     */
    public $street;
    /**
     * @var string|null
     */
    public $orientationNumber;
    /**
     * @var string
     */
    public $numberOfDescriptive;
    /**
     * @var string
     */
    public $city;
    /**
     * @var string
     */
    public $zipCode;
    /**
     * @var string
     */
    public $country;
}