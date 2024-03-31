<?
use Bitrix\Main\Loader;

Loader::registerAutoloadClasses(
	"ik.kontentapi",
	array(
		// lib
		"ik\\Kontentapi\\Settings" => "lib/Settings.php",
		"ik\\Kontentapi\\EventHandler" => "lib/EventHandler.php",
		"ik\\Kontentapi\\Helper" => "lib/Helper.php",
		"ik\\Kontentapi\\ApiController" => "lib/ApiController.php",
		"ik\\Kontentapi\\ApiUnloading" => "lib/ApiUnloading.php",
		"ik\\Kontentapi\\CacheController" => "lib/CacheController.php",
		// orm
		"ik\\Kontentapi\\Orm\\UnloadStatusTable" => "lib/orm/UnloadStatusTable.php",
		"ik\\Kontentapi\\Orm\\ProductsPropertysNameTable" => "lib/orm/ProductsPropertysNameTable.php",
	)
);