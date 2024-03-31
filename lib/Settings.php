<?
namespace ik\Kontentapi;

use ik\Kontentapi\DataTable;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Entity;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;

/**
 * Класс для манипуляции с настройками модуля
 */
class Settings{
	
	function __construct() {
		$this->options = new \Bitrix\Main\Config\Option();
		$this->module_id = "ik.kontentapi";
	}

	/**
	 * Возвращает настройки
	 */
	public function get_option(){
		return $this->options::getForModule( $this->module_id );
	}

	/**
	 * Сохраняет/изменяет настройки на отправленные в формате:
	 * array(
	 * 	"property_code1"=>"value1",
	 *	"property_code2"=>"value2",
	 * );
	 */
	public function save_option( array $new_settings ){

		$settings = array_merge($this->get_option() , $new_settings );


        foreach ($settings as $arkey => $arItem) {

            // delete old
            if ( !isset($new_settings[$arkey]) ){
                $this->options::delete( $this->module_id, array("name"=>$arkey) );
            }else{
                $this->options::set($this->module_id, $arkey, is_array($arItem) ? implode(",", $arItem):$arItem);
            };
            
        };
	}

    public function fill_params( array $aTabs ){
        $new_settings = $aTabs;

        foreach ($aTabs as $Tabskey => $TabItem) {
            
            foreach ($TabItem["OPTIONS"] as $optionskey => $optionsItem) {
                if ( is_array($optionsItem) ){
                
                    $option_type = $new_settings[$Tabskey]["OPTIONS"][$optionskey][3][0];
                    $option_id = $new_settings[$Tabskey]["OPTIONS"][$optionskey][0];
                
                    if ( !isset($this->get_option()[$option_id]) ){
                        switch ($option_type) {
                            case 'checkbox':
                                $default_type_value = "N";
                                break;
                            case 'text':
                                $default_type_value = "";
                                break;
                        };
                        // set value
                        $new_settings[$Tabskey]["OPTIONS"][$optionskey][2] = $default_type_value;
                    };
                };
            };
        };

        return $new_settings;
    }



}
