<?require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");?>

<!-- <pre><?//print_r( $arResult['STATUS'] );?></pre> -->

<div id="APIUnload">
    <h2>Статус выгрузки</h2>
    <ul class="status-list">
        <?foreach ($arResult['STATUS'] as $status):?>
            <li>
                <span class="Name"><?=$status['NAME']?></span>
                <div class="dashed"></div>
                <span class="status <?=$status['STATUS']?>"><?=$status['STATUS_NAME']?></span>
            </li>
        <?endforeach;?>
    </ul>

    <a href="" class="start-unload <?=($arResult['CAN_UNLOAD']) ? 'active' : ''?>">Запустить принудительную выгрузку</a>
    <span class="cant-unload <?=($arResult['CAN_UNLOAD']) ? '' : 'active'?>">Выгрузка уже запущена</span>
</div>

<script>
    <?// импорт переменных в js?>
	// var form_id = <?//=CUtil::PhpToJSObject($arResult["form_id"])?>;
</script>