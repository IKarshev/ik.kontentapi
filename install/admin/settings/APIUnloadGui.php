<?
if( file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ik.kontentapi/admin/settings/APIUnloadGui.php") ){
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ik.kontentapi/admin/settings/APIUnloadGui.php");
} elseif( file_exists($_SERVER["DOCUMENT_ROOT"]."/local/modules/ik.kontentapi/admin/settings/APIUnloadGui.php") ){
    require($_SERVER["DOCUMENT_ROOT"]."/local/modules/ik.kontentapi/admin/settings/APIUnloadGui.php");
};
?>