<?php
/**
 * Created by PhpStorm.
 * User: Roman
 * Date: 10. 6. 2019
 * Time: 18:41
 */

namespace App\Presenters;


class ReportsPresenter extends BasePresenter
{
    /**
     * @var \App\Model\CustomerReportsModel
     */
    protected $reportModel;


    /**
     * ReportsPresenterPresenter constructor.
     * @param \App\Model\CustomerReportsModel $reportModel
     */
    public function __construct(\App\Model\CustomerReportsModel $reportModel)
    {
        $this->reportModel = $reportModel;
    }

    public function renderDefault(){
        $this->template->countCustomers = $this->reportModel->getCountCustomers();
        $this->template->countAssignedCart = $this->reportModel->getCountAssignedCart();
    }

    /** Vytvori komponentu dataGridu
     * @param string $name
     * @return \Ublaboo\DataGrid\DataGrid
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    public function createComponentDataGrid(string $name)
    {

        $dateTo = new \DateTime();
        $dateFrom = new \DateTime();
        $dateFrom->setTimestamp($dateTo->getTimestamp()-(60*60*24*30));

        $grid = new \Ublaboo\DataGrid\DataGrid($this, $name);
        $grid->setPrimaryKey('ID_C');
        $grid->setDataSource($this->reportModel->getTopCustomers(10,$dateFrom,$dateTo));

        $grid->addColumnText('C_FIRST_NAME', 'Jméno');
        $grid->addColumnText('C_SURNAME', 'Příjmení');
        $grid->addColumnText('C_EMAIL', 'Email');
        $grid->addColumnText('C_PHONE_NUMBER', 'Telefon');
        $grid->addColumnDateTime('C_CREATEDATE', 'Datum založení');


        return $grid;
    }
}