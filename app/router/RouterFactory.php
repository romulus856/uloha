<?php

declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;

	public static function createRouter(): RouteList
	{
		$router = new RouteList;
		$router->addRoute('', 'Homepage:default');
		$router->addRoute('customer[/<action>]','Customer:default');
        $router->addRoute('reports','Reports:default');
		return $router;
	}
}
