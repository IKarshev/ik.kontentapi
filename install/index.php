<?
use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Config\Option;
IncludeModuleLangFile(__FILE__);

// Orm
use ik\Kontentapi\Orm\UnloadStatusTable;
use ik\Kontentapi\Orm\ProductsPropertysNameTable;

Class ik_Kontentapi extends CModule
{

    var $MODULE_ID = "ik.kontentapi";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $errors;

    function __construct(){
        //$arModuleVersion = array();
        $this->MODULE_VERSION = "0.0.1";
        $this->MODULE_VERSION_DATE = "04.03.2024";
        $this->MODULE_NAME = "Контентный-API";
        $this->MODULE_DESCRIPTION = "Модуль контентного API — https://api.breez.ru/api";
    }

    function DoInstall(){
        global $APPLICATION;

        RegisterModule($this->MODULE_ID);

        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();


        $APPLICATION->includeAdminFile(
            "Установочное сообщение",
            __DIR__ . '/instalInfo.php'
        );
        return true;
    }

    function DoUninstall(){
        global $APPLICATION;

        $this->UnInstallDB();
        $this->UnInstallEvents();
        $this->UnInstallFiles();

        UnRegisterModule($this->MODULE_ID);
        $APPLICATION->includeAdminFile(
            "Сообщение деинсталяции",
            __DIR__ . '/deInstalInfo.php'
        );
        return true;
    }

    function InstallDB(){
        Loader::includeModule($this->MODULE_ID);

        if (!Application::getConnection()->isTableExists(UnloadStatusTable::getTableName())) {
            UnloadStatusTable::getEntity()->createDbTable();
            UnloadStatusTable::add([
                'TYPE'=>'SECTION',
                'ITERATION'=>'1',
                'STATUS'=> 'WAITING',
            ]);
            UnloadStatusTable::add([
                'TYPE'=>'BRANDS',
                'ITERATION'=>'1',
                'STATUS'=> 'WAITING',
            ]);
            UnloadStatusTable::add([
                'TYPE'=>'PRODUCTS',
                'ITERATION'=>'1',
                'STATUS'=> 'WAITING',
            ]);
        };

        if (!Application::getConnection()->isTableExists(ProductsPropertysNameTable::getTableName())) {
            ProductsPropertysNameTable::getEntity()->createDbTable();
        };

        return true;
    }
    
    function UnInstallDB(){
        Loader::includeModule($this->MODULE_ID);

        if (Application::getConnection()->isTableExists(UnloadStatusTable::getTableName())) {
            Application::getConnection()->dropTable(UnloadStatusTable::getTableName());
        };

        if (Application::getConnection()->isTableExists(ProductsPropertysNameTable::getTableName())) {
            Application::getConnection()->dropTable(ProductsPropertysNameTable::getTableName());
        };
        return true;
    }

    function InstallEvents(){
        
        // Добавляем меню
        EventManager::getInstance()->registerEventHandler(
            'main',
            'OnBuildGlobalMenu',
            $this->MODULE_ID,
            'ik\kontentapi\EventHandler',
            'OnBuildGlobalMenuHandler'
        );
        // обработка добавления нового товара
        EventManager::getInstance()->registerEventHandler(
            "iblock",
            "OnBeforeIBlockElementAdd",
            $this->MODULE_ID,
            'ik\kontentapi\EventHandler',
            'OnBeforeIBlockElementAddHandler'
        );
        // обработка обновления нового товара
        EventManager::getInstance()->registerEventHandler(
            "iblock",
            "OnBeforeIBlockElementUpdate",
            $this->MODULE_ID,
            'ik\kontentapi\EventHandler',
            'OnBeforeIBlockElementUpdateHandler'
        );
        /*
        EventManager::getInstance()->registerEventHandler(
            'main',
            'OnBeforeEndBufferContent',
            $this->MODULE_ID,
            'ik\multiregional\EventHandler',
            'OnBeforeEndBufferContentHandler'
        );
        */
    }

    function UnInstallEvents(){
        
        // Добавляем меню
        EventManager::getInstance()->unRegisterEventHandler(
            "main",
            "OnBuildGlobalMenu",
            $this->MODULE_ID,
            'ik\kontentapi\EventHandler',
            'OnBuildGlobalMenuHandler'
        );
        // обработка добавления нового товара
        EventManager::getInstance()->unRegisterEventHandler(
            "iblock",
            "OnBeforeIBlockElementAdd",
            $this->MODULE_ID,
            'ik\kontentapi\EventHandler',
            'OnBeforeIBlockElementAddHandler'
        );
        // обработка обновления нового товара
        EventManager::getInstance()->unRegisterEventHandler(
            "iblock",
            "OnBeforeIBlockElementUpdate",
            $this->MODULE_ID,
            'ik\kontentapi\EventHandler',
            'OnBeforeIBlockElementUpdateHandler'
        );
        /*
        EventManager::getInstance()->unRegisterEventHandler(
            'main',
            'OnBeforeEndBufferContent',
            $this->MODULE_ID,
            'ik\multiregional\EventHandler',
            'OnBeforeEndBufferContentHandler'
        );
        */
    }

    function InstallFiles(){
        CopyDirFiles(
            __DIR__ . '/admin/settings',
            Application::getDocumentRoot() . '/bitrix/admin',
            true,
            true
        );
        CopyDirFiles(
            __DIR__ . '/components',
            Application::getDocumentRoot() . '/bitrix/components',
            true,
            true
        );
        return true;
    }

    function UnInstallFiles(){
        Directory::deleteDirectory(
            Application::getDocumentRoot() . '/bitrix/admin/APIUnloadGui.php',
        );
        Directory::deleteDirectory(
            Application::getDocumentRoot() . '/bitrix/components/ik',
        );
        return true;
    }
}