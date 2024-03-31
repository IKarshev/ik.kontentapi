<?require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
session_start();
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Config\Option;

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;

use \Bitrix\Main\Application;
use \Bitrix\Iblock\SectionTable;
use \Bitrix\Iblock\ElementTable;
use \Bitrix\Iblock\PropertyTable;

use \ik\Kontentapi\Orm\UnloadStatusTable;


session_start();

Loader::includeModule('iblock');
Loader::includeModule('ik.kontentapi');

class ApiUnloadComponent extends CBitrixComponent implements Controllerable{

    public function configureActions(){
        // сбрасываем фильтры по-умолчанию
        return [
            'Send_Form' => [
                'prefilters' => [],
                'postfilters' => []
            ]
        ];
    }

    public function executeComponent(){// подключение модулей (метод подключается автоматически)
        try{
            // Проверка подключения модулей
            $this->checkModules();
            // формируем arResult
            $this->getResult();
            // подключение шаблона компонента
            $this->includeComponentTemplate();
        }
        catch (SystemException $e){
            ShowError($e->getMessage());
        }
    }

    protected function checkModules(){// если модуль не подключен выводим сообщение в catch (метод подключается внутри класса try...catch)
        if (!Loader::includeModule('iblock')){
            throw new SystemException(Loc::getMessage('IBLOCK_MODULE_NOT_INSTALLED'));
        }
    }


    public function onPrepareComponentParams($arParams){//обработка $arParams (метод подключается автоматически)
        // $arParams["ERROR_MESSAGES"] = array(
        //     "FILE" => Loc::getMessage('ERROR_FILE'),
        //     "STRING" => Loc::getMessage('ERROR_STRING'),
        //     "CHECKBOX" => Loc::getMessage('ERROR_CHECKBOX'),
        //     "LIST" => Loc::getMessage('ERROR_LIST'),
        //     "TEXT_AREA" => Loc::getMessage('ERROR_TEXT_AREA'),
        //     "EMAIL_VALIDATE" => Loc::getMessage('EMAIL_VALIDATE'),
        // );
        
        return $arParams;
    }

    public function CanUnload(array $StatusList):bool{
        foreach ($StatusList as $key => $value) {
            if( $value['STATUS'] != 'WAITING' ) return false;
        };

        $ForcedUnloading = ( \Bitrix\Main\Config\Option::get('ik.kontentapi', 'forced_unloading', '', 's1') && \Bitrix\Main\Config\Option::get('ik.kontentapi', 'forced_unloading', '', 's1') == 'Y' ) ? true : false;
        $RegularUnload = ( \Bitrix\Main\Config\Option::get('ik.kontentapi', 'StartRegularUnload', '', 's1') && \Bitrix\Main\Config\Option::get('ik.kontentapi', 'StartRegularUnload', '', 's1') == 'Y' ) ? true : false;

        if( $ForcedUnloading || $RegularUnload ) return false;

        return true;
    }

    protected function getResult(){ // подготовка массива $arResult (метод подключается внутри класса try...catch)
        // Формируем массив arResult
        $status = UnloadStatusTable::GetStatus();
        foreach ($status as &$value) {
            switch ($value['TYPE']) {
                case 'SECTION':
                    $value['NAME'] = 'Выгрузка разделов';
                    break;
                case 'BRANDS':
                    $value['NAME'] = 'Выгрузка брендов';
                    break;
                case 'PRODUCTS':
                    $value['NAME'] = 'Выгрузка товаров';
                    break;
            };

            switch ($value['STATUS']) {
                case 'WAITING':
                    $value['STATUS_NAME'] = 'Ожидание';
                    break;
                case 'IN_PROGRESS':
                    $value['STATUS_NAME'] = 'Выгрузка';
                    break;
                case 'COMPLETED':
                    $value['STATUS_NAME'] = 'Завершено';
                    break;
            };
        };
        $this->arResult['STATUS'] = $status;

        $this->arResult['CAN_UNLOAD'] = $this->CanUnload( $status );
        
        // Передаем параметры в сессию, чтобы получить иметь доступ в ajax
        $_SESSION['arParams'] = $this->arParams;

        return $this->arResult;
    }

    public function Start_UnloadAction(){
        $request = Application::getInstance()->getContext()->getRequest();
        // получаем файлы, post
        $post = $request->getPostList();
        $files = $request->getFileList()->toArray();
        // Получаем параметры компонента из сессии

        // возвращаем false, если выгрузка не возможна
        if( !self::CanUnload(UnloadStatusTable::GetStatus()) ) return false;

        // Устанавливаем принудительную выгрузку
		\Bitrix\Main\Config\Option::set('ik.kontentapi', 'forced_unloading', 'Y');

        return true;
    } 

}