<?
namespace ik\Kontentapi;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use CIBlockSection;
use CUserTypeEntity;
use CIBlockProperty;
use CIBlockElement;


Loader::includeModule('ik.kontentapi');
Loader::includeModule('iblock');
Loader::IncludeModule("main");

/**
 * Вспомогательный класс
 */
Class Helper{

    /**
     * @return string Дирректория ( bitrix || local ), где находится модуль
     */
    public static function GetModuleDirrectory():string{
        $modulePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', realpath(__DIR__));
        if (strpos($modulePath, DIRECTORY_SEPARATOR . 'bitrix' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR) !== false) {
            // Модуль в /bitrix/modules/
            return "bitrix";
        } elseif (strpos($modulePath, DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR) !== false) {
            // Модуль в /local/modules/
            return "local";
        };
    }

    /**
     * @return array Список Типов инфоблоков
     */
    public static function GetIblockTypeList(){
        // Тип инфоблока
        $infoblock_type = \Bitrix\Iblock\TypeTable::getList( [
            'select' => [
                'ID',
                'NAME' => 'LANG_MESSAGE.NAME',
            ],
            'filter' => ['=LANG_MESSAGE.LANGUAGE_ID' => 'ru'],
        ] );
        while ($row = $infoblock_type->fetch()) {
            $iblockTypes[$row['ID']] = $row['NAME'];
        }

        return $iblockTypes;
    }

    /**
     * @return array Инфоблоки
     */
    public static function GetIblockList(){
        $iblockTypes = self::GetIblockTypeList();

        $infoblock = \Bitrix\Iblock\IblockTable::getList( [
            'select' => ['ID', 'NAME'],
            'filter' => ['IBLOCK_TYPE_ID' => array_keys($iblockTypes)],
        ] );
        while ($row = $infoblock->fetch()) {
            $iblocks[$row['ID']] = '['.$row['ID'].'] '.$row['NAME'];
        }
        return $iblocks;
    }

    /**
     * Устанавливает значение в пользовательское поле раздела
     * 
     * @param int $sectionId — id раздела
     * @param string $fieldName — Символьный код пользовательского поля
     * @param $fieldValue — Значение
     */
    public static function SetSectionProp( int $sectionId, string $fieldName, $fieldValue) {
        $arFields = array(
            $fieldName => $fieldValue,
        );

        $section = new CIBlockSection();
        $section->Update($sectionId, $arFields);
    }

    /**
     * Меняет активность раздела
     * 
     * @param array,int $SectionID — ID раздела
     * @param bool $activity — true чтобы включить активность, false чтобы выключить
     */
    public static function ChangeSectionActivity($SectionID, bool $activity = true){
        if( !is_array($SectionID) ) $SectionID = [$SectionID];

        $CIBlockSection = new CIBlockSection;
        foreach ($SectionID as $value) {
            $CIBlockSection->Update($value, array(
                "ACTIVE" => ($activity) ? 'Y' : 'N',
            ));
        };
    }

    /**
     * Меняет активность элемента инфоблока
     * 
     * @param array,int $ElementID — ID элемента инфоблока
     * @param bool $activity — true чтобы включить активность, false чтобы выключить
     */
    public static function ChangeIblockElementActivity($ElementID, $IblockID, bool $activity = true){
        if( !is_array($ElementID) ) $ElementID = [$ElementID];
        $activity = ($activity) ? 'Y' : 'N';

        foreach ($ElementID as $element) {
            $CIBlockElement = new CIBlockElement;
            $CIBlockElement->Update($element, array(
                'IBLOCK_ID' => $IblockID,
                'ID' => $element,
                'ACTIVE' => $activity,
            ));
        };
    }

    /**
     * Аналог array_slice, но не переиндексирует массив
     * 
     * @param array $array — Массив
     * @param int $currentIteration — номер итерации
     * @param int $elementsToShow — кол-во элементов в одной итерации
     * 
     * @return array Часть массива
     */
    public static function sliceArray(array $array, int $currentIteration, int $elementsToShow){
        // Вычисляем начальный индекс для обрезки массива
        $startIndex = ($currentIteration - 1) * $elementsToShow;
        
        // Проверяем, не выходит ли начальный индекс за пределы массива
        if ($startIndex >= count($array)) {
            return array(); // Возвращаем пустой массив, если начальный индекс превышает длину массива
        }
        
        // Создаем новый массив для хранения результатов с сохранением старых индексов
        $slicedArray = array();
        
        // Перебираем исходный массив и сохраняем элементы с их старыми индексами
        $i = 1;
        foreach ($array as $key => $value) {

            if( $i > $startIndex && $i < $startIndex + 1 + $elementsToShow ){
                $slicedArray[$key] = $value;
            }
            $i++;
        }
        
        return $slicedArray;
    }

    /**
     * Получает список CODE пользовательских полей
     * 
     * @param int $IblockID — id-инфоблока
     * @return array массив CODE пользовательских полей
     */
    public static function GetSectionUF_FieldList( int $IblockID ):array{
        $entity = \Bitrix\Iblock\Model\Section::compileEntityByIblock($IblockID);
        $dbSect = $entity::getList(array("select" => ["UF_*"],));
        if ($arSect = $dbSect->fetch()) {
            return array_keys($arSect);
        };
        return array();
    }

    /**
     * Создает новое пользовательское поле в разделе
     * 
     * @param int $IblockID — id-инфоблока
     * @param string $FieldCode — Code поля
     * @param string $fieldName — Имя поля
     * @param string $FieldType — Тип поля
     * @param bool $multiple — Множественное
     * @param bool $Mandatory — Обязательное
     * @param array $DefaultValue — Массив значений по-умолчанию
     */
    public static function CreateSectionUF_Field(
        int $IblockID, 
        string $FieldCode,
        string $fieldName,
        string $FieldType,
        bool $multiple, 
        bool $Mandatory, 
        array $DefaultValue = array()
        ){

        switch ($FieldType) {
            case 'string':
                $FieldType = 'string';
                break;
            case 'int':
                $FieldType = 'string';
                break;
            case 'float':
                $FieldType = 'string';
                break;
        };

        $oUserTypeEntity = new CUserTypeEntity();
        $aUserFields = array(
            'ENTITY_ID' => 'IBLOCK_'.$IblockID.'_SECTION',
            'FIELD_NAME' => 'UF_API_PROP_'.$FieldCode,
            'USER_TYPE_ID' => $FieldType,
            'MULTIPLE' => ($multiple) ? 'Y' : 'N',
            'MANDATORY' => ($Mandatory) ? 'Y' : 'N',
            'DEFAULT_VALUE' => $DefaultValue,
            'LIST_COLUMN_LABEL' => array(
                'ru' => $fieldName,
            ),
        );
        
        $iUserFieldId = $oUserTypeEntity->Add( $aUserFields );
        return $iUserFieldId;
    }

    /**
     * Возвращает список кодов свойств инфоблока
     * 
     * @param int $IblockID — id-инфоблока
     * @return array список кодов свойств инфоблока
     */
    public static function GetIblockPropertys( int $IblockID ):array{
        $result = array();
        $properties = CIBlockProperty::GetList(array(), array('IBLOCK_ID'=>$IblockID));
        while ($prop_fields = $properties->GetNext()){
            $result[] = $prop_fields['CODE'];
        };
        return $result;
    }

    /**
     * Создает новое свойство для инфоблока
     * 
     * @param int $IblockID — id-инфоблока
     * @param string $FieldCode — Code поля
     * @param string $fieldName — Имя поля
     * @param string $FieldType — Тип поля
     * @param bool $multiple — Множественное
     * @param bool $Mandatory — Обязательное
     * @param array $ListProps - массив значений для типа список:
     *  array(
     *      "VALUE" => "", // имя
     *      "XML_ID" => "", // XML_ID
     *      "DEF" => "", // по-умолчанию Y/N
     *      "SORT" => "100" // сортировка
     *  );
     */
    public static function CreateIblockProperty(
        int $IblockID, 
        string $FieldCode,
        string $fieldName,
        string $FieldType,
        bool $multiple = false, 
        bool $Mandatory = false, 
        array $ListProps = array()
        ){
        // Все свойства передаются как строки, а для использования их в фильтре необходим тип "список"
        $FieldTyleList = array(
            'string' => 'L', 
        );

        $arFields = array(
            'IBLOCK_ID' => $IblockID,
            'NAME' => $fieldName,
            "ACTIVE" => "Y",
            "SORT" => "500",
            'CODE' => $FieldCode,
            'PROPERTY_TYPE' => 'L',
            'MULTIPLE' => ($multiple) ? 'Y' : 'N',
            'MANDATORY' => ($Mandatory) ? 'Y' : 'N',
        );
        if( !empty($ListProps) ) $arFields['VALUES'] = $ListProps;

        $ibp = new CIBlockProperty;
        $PropID = $ibp->Add($arFields);

        return array(
            "PropID" => $PropID,
            "PropType" => $FieldTyleList[$FieldType],
        );
    }

    /**
     * @param int $lenght — длина возвращаемой строки
     * @param string $characters — символы
     * @return string рандомные символы
     */
    public static function randomString(int $length = 8, string $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' ):string{ 
        $charactersLength = strlen($characters); 
        $randomString = ''; 
        for ($i = 0; $i < $length; $i++) { 
            $randomString .= $characters[rand(0, $charactersLength - 1)]; 
        } 
        return $randomString; 
    }

    /**
     * Получаем ID значений свойства тип список
     * 
     * @param int $IblockID — id-инфоблока
     * @param string $PropertyCode — символьный код свойства
     * @param string $ValueName — массив названий элементов списка
     * 
     * @return array массив с id элементов свойства типа 'список' 
     */
    public static function GetListPropValueID( int $IblockID, string $PropertyCode, string $ValueName):array{
        $result = array_column(\Bitrix\Iblock\PropertyEnumerationTable::getList([
            "select" => ["ID"],
            "filter" => [
                "PROPERTY.IBLOCK_ID" => $IblockID,
                "PROPERTY.CODE" => $PropertyCode,
                "VALUE" => $ValueName
            ],
        ])->fetchAll(), 'ID');

        return $result;
    }

    /**
     * Форматирует текст
     */
    public static function formatToHtmlText($text) {
        // Заменяем &quot; на "
        $text = str_replace('&quot;', '"', $text);
        
        // Разделяем текст на строки
        $lines = explode("\n", $text);
        
        $formattedText = '';
        $inList = false;
        
        foreach ($lines as $line) {
            // Если строка начинается с &amp;#9679;
            if (strpos($line, '&amp;#9679;') === 0) {
                // Удаляем эти символы и обрамляем строку в тег <li>
                $line = '<li>' . substr($line, 11) . '</li>';
                
                // Удаляем теги &lt;br&gt из пунктов списка
                $line = str_replace('&lt;br&gt;', '', $line);
                
                // Если мы не находимся в списке, открываем новый список
                if (!$inList) {
                    $formattedText .= '<ul>';
                    $inList = true;
                }
            } else {
                // Если строка не начинается с &amp;#9679; и мы находимся в списке, закрываем список
                if ($inList) {
                    $formattedText .= '</ul>';
                    $inList = false;
                }
            }
            
            // Добавляем отформатированную строку к итоговому тексту
            $formattedText .= $line;
        }
        
        // Если в конце текста остался открытый список, закрываем его
        if ($inList) {
            $formattedText .= '</ul>';
        }
        
        return $formattedText;
    }

}
?>