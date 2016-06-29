<?php
namespace App\Modules\Setup;

use Sintattica\Atk\Core\Atk;
use Sintattica\Atk\Core\Tools;
use Sintattica\Atk\Core\Menu;

class Module extends \Sintattica\Atk\Core\Module
{
	static $module = 'Setup';

	public function boot()
	{
		$this->registerNode('setup',Setup::class,['admin', 'add', 'edit', 'delete', 'view']);

		$this->addMenuItem('Setup' );
		$this->addNodeToMenu("Run Setup",setup, admin, setup);
	}
}
?>