<?
namespace ik\Kontentapi;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

use ik\Kontentapi\Settings;

Loader::includeModule('ik.kontentapi');

/**
 * Класс для отправки запросов API 
 */
Class ApiController{

	function __construct() {
        $this->ApiUrl = 'https://api.breez.ru/v1/';
        $this->SettingsClass = new Settings();
        $this->ModuleSettings = $this->SettingsClass->get_option();
    }

    /**
     * @return string Заголовок для авторизации в сервисе
     */
    public function GetControlLine(){
        return "Authorization: Basic " . base64_encode( $this->ModuleSettings['LOGIN'].":".$this->ModuleSettings['PASSWORD'] );
    }

    /**
     * @param string $QueryAddress — Url для запроса, после https://api.breez.ru/v1/
     * @param array @params — массив параметров, 
     *      где ключ это название get переменной, 
     *      а значение это значение
     * 
     * @return array Ответ от сервиса
     */
    public function ApiRequest( string $QueryAddress, array $params = array() ){

        $QueryUrl = $this->ApiUrl.$QueryAddress;
        $QueryUrl = (!empty($params)) ? $QueryUrl.'/?' . http_build_query($params) : $QueryUrl.'/';

        $headers = array(
            $this->GetControlLine(),
        );

        $ch = curl_init($QueryUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }

    /**
     * @param string $id — id категории. Если не передан,
     *      результатом будет список всех категорий.
     * 
     * @return array Найденные категории
     */
    public function GetCategories( string $id = '' ){
        if( $id == '' ){
            $response = $this->ApiRequest('categories');
        }else{
            $response = $this->ApiRequest('categories', ['id' => $id]);
        };

        return $response;
    }

    /**
     * @param string $id — id бренда. Если не передан,
     *      результатом будет список всех брендов.
     * 
     * @return array Найденные бренды
     */
    public function GetBrands( string $id = '' ){
        if( $id == '' ){
            $response = $this->ApiRequest('brands');
        }else{
            $response = $this->ApiRequest('brands', ['id' => $id]);
        };

        return $response;
    }

    /**
     * @param string $id — id товара. Если не передан,
     *      результатом будет список всех товаров.
     * 
     * @return array Найденные товары
     */
    public function GetProducts(string $id = ''){
        if( $id == '' ){
            $response = $this->ApiRequest('products');
        }else{
            $response = $this->ApiRequest('products', ['id' => $id]);
        };

        return $response;
    }

    /**
     * @param int $id — ID-раздела
     * 
     * @return array Технические характеристики категории
     */
    public function GetCategoryTech( string $id ){
        if( $id == '' ){
            $response = $this->ApiRequest('tech');
        }else{
            $response = $this->ApiRequest('tech', ['category' => $id]);
        };

        if( isset($response['error']) ) return $response;
        return $response['techs'];
    }

    /**
     * @param int $id — ID-товара
     * 
     * @return array Технические характеристики товара
     */
    public function GetProductTech( string $id ){
        $response = $this->ApiRequest('tech', ['id' => $id]);
        if( isset($response['error']) ) return false;

        return array_shift( $response )['techs'];
    }

    /**
     * Сохраняем выгрузку в файл
     * 
     * @param string $FileName — Название файла
     * @param array $Data — Данные для сохранения
     */
    public function SaveUnloadInFile( string $FileName, array $Data){
        // Запись
        if( !file_exists($FileName) ){
            file_put_contents($FileName, serialize($Data));
        };
    }

    /**
     * Сохраняем выгрузку в файл
     * 
     * @param string $FileName — Название файла
     */
    public function ReadUnloadFromFile( string $FileName){
        // Чтение
        if( file_exists($FileName) ){
            return unserialize( file_get_contents($FileName) );
        }else{
            return false;
        };
    }

}
?>