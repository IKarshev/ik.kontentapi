<?require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
use \Bitrix\Main\Loader;



$arComponentParameters = array(
    "GROUPS" => array(
        "BASE" => array(
            "NAME" => "основные настройки",
        ),
    ),
    "PARAMETERS" => array(
        /*
        "FORM_TITLE" => array(
            "PARENT" => "BASE",
            "NAME" => "Название формы",
            "TYPE" => "STRING",
        ),
        "EMAIL_MASK" => array(
            "NAME" => "Свойства с маской почты",
            "TYPE" => "LIST",
            "PARENT" => "BASE",
            "MULTIPLE" => "Y",
            "VALUES" => [
                'test1'=>'test11',
                'test2'=>'test22',
            ],
            "REFRESH" => "Y",
        ),
        "ADD_FORM" => array(
            "PARENT" => "BASE",
            "NAME" => "Добавлять результат в инфоблок?",
            "TYPE" => "CHECKBOX",
        ),
        */
    ),
);
// if ( $arCurrentValues["POPUP"] == "Y" ){

// };
?>