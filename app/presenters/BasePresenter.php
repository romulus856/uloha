<?php
/**
 * Created by PhpStorm.
 * User: Roman
 * Date: 10. 6. 2019
 * Time: 18:40
 */

namespace App\Presenters;

use Tracy\Debugger;

abstract class BasePresenter extends \Nette\Application\UI\Presenter
{

  public function startup()
  {
      parent::startup();
     // Debugger::$productionMode = true;
  }
}