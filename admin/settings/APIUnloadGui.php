<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php"); // первый общий пролог
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php"); // второй общий пролог
// require Classes
use \Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;


CJSCore::Init(array("jquery"));

// require modules
Loader::includeModule('ik.kontentapi');

// settings/filter
$APPLICATION->SetTitle( Loc::getMessage('GLOBAL_MENU_TAB_NAME') );
?>
 
<?$APPLICATION->IncludeComponent(
	"ik:APIUnload",
	"",
Array()
);?>
 
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>