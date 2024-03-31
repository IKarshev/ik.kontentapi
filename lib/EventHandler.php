<?
namespace ik\Kontentapi;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;

use \Bitrix\Main\Loader;
use ik\Kontentapi\ApiController;
use ik\Kontentapi\Settings;
use ik\Kontentapi\Helper;
use ik\Kontentapi\ApiUnloading;
use ik\Kontentapi\Orm\ProductsPropertysNameTable;

Loader::includeModule('ik.kontentapi');

/**
 * Класс-обработчик событий
 * 
 * @category Class 
 */
Class EventHandler{

    /**
     * Определяем, что используется инфоблока связанный с API
     */
    private static function GetApiSettings(){
        $APIUnloadSettingsClass = new Settings;
        $APIUnloadSettings = $APIUnloadSettingsClass->get_option();

        return $APIUnloadSettings;
    }

    /**
     * Вывод меню в админку
     */
    public static function OnBuildGlobalMenuHandler(&$arGlobalMenu, &$arModuleMenu){
        global $USER;
        if(!$USER->IsAdmin()) return;
    
        $arGlobalMenu["global_menu_ikkontentapi"] = [
            'menu_id' => 'ik',
            'text' => 'ik',
            'title' => 'ik',
            'url' => 'settingss.php?lang=ru',
            'sort' => 1000,
            'items_id' => 'GlobalMenu_ikkontentapi',
            'help_section' => 'custom',
            'items' => [
                [
                    'parent_menu' => 'GlobalMenu_ikkontentapi',
                    'sort'        => 10,
                    'url'         => '/bitrix/admin/APIUnloadGui.php',
                    'text'        => Loc::getMessage('MULTIREGION_SETTINGS_TAB'),
                    'title'       => Loc::getMessage('MULTIREGION_SETTINGS_TAB'),
                    'icon'        => 'fav_menu_icon',
                    'page_icon'   => 'fav_menu_icon',
                    'items_id'    => 'menu_custom',
                ],
            ],
        ];
    }

    /**
     * Обработчик 'перед выводом буферизированного контента'
     */
    public static function OnBeforeEndBufferContentHandler(){
        $module_id = pathinfo(dirname(__DIR__))["basename"];

        /**
         * Подключение js/css файлов модуля в административную часть
         */
        if(defined("ADMIN_SECTION") && ADMIN_SECTION === true){
            Asset::getInstance()->addJs("/bitrix/js/".$module_id."/admin.js");
        };
    }

    public static function OnBeforeIBlockElementAddHandler(&$arParams){
        $ApiSettings =  self::GetApiSettings();
        if( $ApiSettings['IBLOCK'] == $arParams['IBLOCK_ID'] ) self::BeforeApiIblockUnload( $arParams, $ApiSettings );
    }

    public static function OnBeforeIBlockElementUpdateHandler(&$arParams){
        $ApiSettings =  self::GetApiSettings();
        if( $ApiSettings['IBLOCK'] == $arParams['IBLOCK_ID'] ) self::BeforeApiIblockUnload( $arParams, $ApiSettings );
    }

    public static function BeforeApiIblockUnload( &$arParams, $ApiSettings ){

        // Форматируем превью текст
        $arParams['PREVIEW_TEXT'] = Helper::formatToHtmlText($arParams['PREVIEW_TEXT']);
        $arParams['PREVIEW_TEXT_TYPE'] = 'html';

        // Форматируем детальный текст
        $arParams['DETAIL_TEXT'] = Helper::formatToHtmlText($arParams['DETAIL_TEXT']);
        $arParams['DETAIL_TEXT_TYPE'] = 'html';
    }
}
?>