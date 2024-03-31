<?
// пространство имен для подключений ланговых файлов
use Bitrix\Main\Localization\Loc;
// подключение ланговых файлов
Loc::loadMessages(__FILE__);
// метод возвращает объект класса CApplicationException, содержащий последнее исключение
if ($errorException = $APPLICATION->getException()) {
    // вывод сообщения об ошибке при удалении модуля
    CAdminMessage::showMessage(
        "Произошла ошибка, модуль не удалось удалить" . ': ' . $errorException->GetString()
    );
} else {
    // вывод уведомления при успешном удалении модуля
    CAdminMessage::showNote(
        "Модуль успешно удален."
    );
}
?>
<!-- Кнопка возврата к списку модулей -->
<form action="<?= $APPLICATION->getCurPage(); ?>">
    <input type="submit" value="<?="Вернуться в список модулей";?>">
</form>