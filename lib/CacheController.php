<?
namespace ik\Kontentapi;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

use ik\Kontentapi\Settings;
use ik\Kontentapi\Helper;

Loader::includeModule('ik.kontentapi');

/**
 * Класс, для работы с кэшем
 */
Class CacheController{

    /**
     * Проверяет нужно ли обновлять инфу по разделу
     * 
     * @param string $SectionCode — Символьный код раздела
     * 
     * @return true Инфа по разделу устарела, нужно сделать выгрузку
     * @return false Инфа по разделу актуальна, выгрузка не нужна
     */
    public function CheckSectionCache(string $SectionCode): bool{
        // TODO - написать метод
        return true;
    }

    /**
     * Проверяет нужно ли обновлять инфу по бренду
     * 
     * @param string $articul — Артикул бренда
     * 
     * @return true Инфа по бренду устарела, нужно сделать выгрузку
     * @return false Инфа по бренду актуальна, выгрузка не нужна
     */
    public function CheckBrandsCache(string $articul): bool{
        // TODO - написать метод
        return true;
    }

    /**
     * Проверяет нужно ли обновлять инфу по товару
     * 
     * @param string $articul — Артикул товара
     * 
     * @return true Инфа по товару устарела, нужно сделать выгрузку
     * @return false Инфа по товару актуальна, выгрузка не нужна
     */
    public function CheckProductsCache(string $articul): bool{
        // TODO - написать метод
        return true;
    }
}