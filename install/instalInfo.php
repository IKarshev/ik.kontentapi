<?
// пространство имен для подключений ланговых файлов
use Bitrix\Main\Localization\Loc;
// подключение ланговых файлов
Loc::loadMessages(__FILE__);
// метод возвращает объект класса CApplicationException, содержащий последнее исключение
if ($errorException = $APPLICATION->getException()) {
    // вывод сообщения об ошибке при установке модуля
    CAdminMessage::showMessage(
        "В процессе установки произошла ошибка" . ': ' . $errorException->GetString()
    );
} else {
    // вывод уведомления при успешной установке модуля
    CAdminMessage::showNote(
        "Модуль успешно установлен!"
    );
}
?>
<!-- Кнопка возврата к списку модулей -->
<form action="<?= $APPLICATION->getCurPage(); ?>">
    <input type="submit" value="<?="Вернуться в список модулей"?>">
</form>