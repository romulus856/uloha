<?php
/**
 * Created by PhpStorm.
 * User: Roman
 * Date: 10. 6. 2019
 * Time: 18:41
 */

namespace App\Presenters;

use \App\Entity\BaseCustomer;
use App\Entity\CustomerAddress;
use Nette\Application\UI\Form;
use Tracy\Debugger;

class CustomerPresenter extends BasePresenter
{
    /**
     * @var \App\Model\CustomerModel
     */
    protected $customerModel;
    /**
     * @var \App\Model\CartModel
     */
    protected $cartModel;
    /**
     * @var \App\Factory\FormFactory
     */
    protected $formFactory;

    /**
     * CustomerPresenter constructor.
     * @param \App\Model\CustomerModel $customerModel
     * @param \App\Model\CartModel $cartModel
     * @param \App\Factory\FormFactory $formFactory
     */
    public function __construct(\App\Model\CustomerModel $customerModel, \App\Model\CartModel $cartModel, \App\Factory\FormFactory $formFactory)
    {
        $this->customerModel = $customerModel;
        $this->cartModel = $cartModel;
        $this->formFactory = $formFactory;
    }

    /** Vytvori komponentu dataGridu
     * @param string $name
     * @return \Ublaboo\DataGrid\DataGrid
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    public function createComponentDataGrid(string $name)
    {
        Debugger::log('Vytvarim data grid');
        $grid = new \Ublaboo\DataGrid\DataGrid($this, $name);
        $grid->setPrimaryKey('ID_C');
        $grid->setDataSource($this->customerModel->getCustomers());

        $grid->addColumnText('C_FIRST_NAME', 'Jméno');
        $grid->addColumnText('C_SURNAME', 'Příjmení');
        $grid->addColumnText('C_EMAIL', 'Email');
        $grid->addColumnText('C_PHONE_NUMBER', 'Telefon');
        $grid->addColumnText('CA_STREET', 'Ulice');
        $grid->addColumnText('CA_NUMBER_OF_DESCRIPTIVE', 'Číslo popisné');
        $grid->addColumnText('CA_ORIENTATION_NUMBER', 'Číslo orientační');
        $grid->addColumnText('CA_CITY', 'Město');
        $grid->addColumnText('CA_ZIP_CODE', 'PCČ');
        $grid->addColumnText('CA_COUNTRY', 'Stát');
        $grid->addColumnText('CC_NUMBER', 'Číslo karty');
        $grid->addColumnText('CT_CART_TYPE', 'Typ karty');
        $grid->addColumnDateTime('C_CREATEDATE', 'Datum založení');

        $grid->addFilterText('C_FIRST_NAME', 'Jméno');
        $grid->addFilterText('C_SURNAME', 'Příjmení');
        $grid->addFilterText('CC_NUMBER', 'Číslo karty');

        return $grid;
    }

    /** vytvoreni formulare pro zalozeni zakaznika
     * @return \Nette\Forms\Form
     */
    public function createComponentCustomerForm(string $name)
    {
        $form = $this->formFactory->createForm($name);

        $cartList = $this->cartModel->getFreeCartsForCustomer();
        Debugger::log('Nacetl jsem vsechny dostupne karty '.var_export($cartList,true));

        $form->addGroup('Základní údaje:');
        $form->addText('firstname', 'Jméno:*')->setRequired('Nejsou vyplněné všechny povinné položky!')->setMaxLength(80);
        $form->addText('surname', 'Příjmení:*')->setRequired('Nejsou vyplněné všechny povinné položky!')->setMaxLength(80);
        $form->addText('email', 'Email:*')->setRequired('Nejsou vyplněné všechny povinné položky!')->setMaxLength(250)
            ->setDefaultValue('@')->addRule(Form::EMAIL, 'Email není validní!');
        $form->addText('phone', 'Telefon:')->setMaxLength(16)->setDefaultValue('+420');

        $form->addSelect('cart', 'Věrnostní karta:', $cartList);

        $form->addGroup('Adresa:');
        $form->addText('street', 'Ulice:*')->setRequired('Nejsou vyplněné všechny povinné položky!')->setMaxLength(80);
        $form->addText('orientationNumber', 'Číslo orientační:')->setMaxLength(10);
        $form->addText('numberOfDescriptive', 'Číslo popisné:*')->setRequired('Nejsou vyplněné všechny povinné položky!')
            ->setMaxLength(10)->addRule(Form::INTEGER, 'Číslo popisné musí být číslo!');
        $form->addText('city', 'Obec:*')->setRequired('Nejsou vyplněné všechny povinné položky!')->setMaxLength(120);
        $form->addText('zipCode', 'PSČ:*')->setRequired('Nejsou vyplněné všechny povinné položky!')->setMaxLength(50);
        $form->addText('country', 'Stát:*')->setRequired('Nejsou vyplněné všechny povinné položky!');

        $form->addHidden('idCustomer');
        $form->addHidden('idAddress');
        $form->addSubmit('save', 'Uložit');

        $form->onSuccess[] = $this->saveCustomer($form);
        return $form;
    }

    /** Ulozi data zakaznika
     * @param \Nette\Forms\Form $form
     * @throws \Nette\Application\AbortException
     */
    public function saveCustomer(\Nette\Forms\Form $form)
    {
        if ($form->isSubmitted()) {
            Debugger::log('zpracovavam data z formulare');
            $formValues = $form->getValues();

            $cartFieldComponent = null;
            $valid = true;
            foreach ($form->getComponents(true, \Nette\Forms\IControl::class) as $control) {
                if ($control->getName() == 'cart') {
                    $cartFieldComponent = $control;
                }

                if (!$control->getRules()->validate()) {
                    $valid = false;
                    $control->addError('Položka je chybně vyplněná!');
                    Debugger::log('Polozka '.$control->getName() .' neni spravne vyplnena');
                    break;
                }
            }

            if (!is_null($formValues['cart']) && trim($formValues['cart']) != '' && $this->customerModel->checkCard(intval($formValues['cart'])) === false) {
                Debugger::log('vybrana karta byla jiz obsazena, nebudeme ukladat data do DB');
                $cartFieldComponent->addError('Vybraná karta je již obsazená!');
                $valid = false;
            }

            if ($valid === true) {
                Debugger::log('Vsechny validace probehli v poradku.');
                try {
                    $customerData = new BaseCustomer();
                    $customerAddress = new CustomerAddress();

                    $customerData->firstName = $formValues['firstname'];
                    $customerData->surname = $formValues['surname'];
                    $customerData->email = $formValues['email'];
                    $customerData->phone = $formValues['phone'];
                    $customerData->idCart = $formValues['cart'];
                    $customerData->idCustomer = !is_null($formValues['idCustomer']) && trim($formValues['idCustomer']) != '' ? $formValues['idCustomer'] : null;

                    $customerAddress->street = $formValues['street'];
                    $customerAddress->orientationNumber = $formValues['orientationNumber'];
                    $customerAddress->numberOfDescriptive = $formValues['numberOfDescriptive'];
                    $customerAddress->city = $formValues['city'];
                    $customerAddress->zipCode = $formValues['zipCode'];
                    $customerAddress->country = $formValues['country'];
                    $customerAddress->idAddress = !is_null($formValues['idAddress']) && trim($formValues['idAddress']) != '' ? $formValues['idAddress'] : null;
                    $this->customerModel->saveAllCustomerData($customerData, $customerAddress);
                    Debugger::log('Ulozil jsem data o zakaznikovi');
                    $this->flashMessage('Zpracování proběhlo v pořádku.', 'sucess');
                    $this->redirect('Customer:new');
                } catch (\Exception $ex) {
                    if ($ex instanceof \Nette\Application\AbortException) {
                        Debugger::log($ex);
                        throw $ex;
                    } else {
                        Debugger::log($ex);
                        $this->flashMessage('Při zpracování došlo k chybě!', 'danger');
                    }
                }

            } else {
                $this->flashMessage('Některé položky jsou chybně vyplněné!', 'danger');
            }
        }
    }

}