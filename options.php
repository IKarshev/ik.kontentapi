<?
use Bitrix\Main\Loader;
use ik\Kontentapi\Settings;
use ik\Kontentapi\Helper;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Config\Option;

\Bitrix\Main\Loader::IncludeModule("iblock");

Loc::loadMessages(__FILE__);
$request = HttpApplication::getInstance()->getContext()->getRequest();

$module_id = htmlspecialcharsbx($request["mid"] != "" ? $request["mid"] : $request["id"]);
Loader::includeModule($module_id);


$Main = new ik\Kontentapi\Settings();
if ( $request->isPost() ){//save settings
    $Main->save_option( $_POST );
};
$current_options = $Main->get_option();

// multiselectbox, textarea, statictext, statichtml, checkbox, text, password, selectbox
$aTabs = array(
    array(
        "DIV" => "edit",
        "TAB"=> Loc::getMessage("FALBAR_TOTOP_OPTIONS_TAB_NAME"),
        "TITLE" => Loc::getMessage("FALBAR_TOTOP_OPTIONS_TAB_NAME"),
        "OPTIONS" => array(
            array(
                "MODULE_ACTIVITY",
                Loc::getMessage("MODULE_ACTIVITY"),
                "",
                array("checkbox")
            ),
            array(
                "FULL_UNLOAD",
                Loc::getMessage("FULL_UNLOAD"),
                "",
                array("checkbox")
            ),
            // Доступы к API
            Loc::getMessage("API_SETTINGS"),
            array(
                "LOGIN",
                Loc::getMessage("LOGIN"),
                "",
                array("text")
            ),
            array(
                "PASSWORD",
                Loc::getMessage("PASSWORD"),
                "",
                array("text")
            ),
            // Кол-во разделов для выгрузки за одну итерацию
            array(
                "UPDATE_SECTION_PER_ONE_TIME",
                Loc::getMessage("UPDATE_SECTION_PER_ONE_TIME"),
                "",
                array("text")
            ),
            // Кол-во товаров для выгрузки за одну итерацию
            array(
                "UPDATE_PRODUCTS_PER_ONE_TIME",
                Loc::getMessage("UPDATE_PRODUCTS_PER_ONE_TIME"),
                "",
                array("text")
            ),


            // Инфоблок для выгрузки
            Loc::getMessage("IBLOCK_SETTINGS"),
            array(
                "IBLOCK",
                Loc::getMessage("IBLOCK"),
                "",
                array("selectbox", Helper::GetIblockList() )
            ),
            // инфоблок для выгрузки разделов
            array(
                "BRANDS_IBLOCK",
                Loc::getMessage("BRANDS_IBLOCK"),
                "",
                array("selectbox", Helper::GetIblockList() )
            ),

            // Периодичность выгрузки

        )
    ),
);


// формируем табы
$aTabs = $Main->fill_params( $aTabs );
$tabControl = new CAdminTabControl(
    "tabControl",
    $aTabs
);  
$tabControl->Begin();
?>

<form id="ik_Kontentapi" action="<? echo($APPLICATION->GetCurPage()); ?>?mid=<? echo($module_id); ?>&lang=<? echo(LANG); ?>" method="post">

<?
foreach($aTabs as $aTab){

    if($aTab["OPTIONS"]){

        $tabControl->BeginNextTab();

        __AdmSettingsDrawList($module_id, $aTab["OPTIONS"]);
    }
}

$tabControl->Buttons();
?>

<input type="submit" name="apply_" value="<? echo(Loc::GetMessage("FALBAR_TOTOP_OPTIONS_INPUT_APPLY")); ?>" class="adm-btn-save" />
<input type="submit" name="default" value="<? echo(Loc::GetMessage("FALBAR_TOTOP_OPTIONS_INPUT_DEFAULT")); ?>" />

<?
echo(bitrix_sessid_post());
?>
</form>
<?$tabControl->End();?>