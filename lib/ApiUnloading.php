<?php
namespace ik\Kontentapi;
use ik\Kontentapi\Helper;
use ik\Kontentapi\Orm\UnloadStatusTable;
use ik\Kontentapi\Orm\ProductsPropertysNameTable;

use Bitrix\Main\Loader;
use CFile;
use CIBlockSection;
use CIBlockElement;
use CUtil;
use CCatalogProduct;
use CPrice;
use CIBlockProperty;
use CIBlockPropertyEnum;

Loader::includeModule('ik.kontentapi');
Loader::includeModule('iblock');
Loader::includeModule('main');  

class ApiUnloading{

    private bool $StartRegularUnload;

    public function __construct( bool $StartRegularUnload = false ) {
        $this->Settings = new Settings();
        $this->ApiController = new ApiController();
        $this->CacheController = new CacheController();
        $this->CIBlockSection = new CIBlockSection();
        $this->CIBlockElement = new CIBlockElement();
        $this->IblockID = $this->Settings->get_option()['IBLOCK'];
        $this->BrandIblockID = $this->Settings->get_option()['BRANDS_IBLOCK'];
        
        $this->UpdatedSectionPerOneTime = (isset($this->Settings->get_option()['UPDATE_SECTION_PER_ONE_TIME']) && $this->Settings->get_option()['UPDATE_SECTION_PER_ONE_TIME']!="") ? $this->Settings->get_option()['UPDATE_SECTION_PER_ONE_TIME'] : 50;
        $this->UpdatedProductsPerOneTime = (isset($this->Settings->get_option()['UPDATE_PRODUCTS_PER_ONE_TIME']) && $this->Settings->get_option()['UPDATE_PRODUCTS_PER_ONE_TIME']!="") ? $this->Settings->get_option()['UPDATE_PRODUCTS_PER_ONE_TIME'] : 20;
        
        // Устанавливаем статус регулярной выгрузки
        if( $StartRegularUnload ) \Bitrix\Main\Config\Option::set('ik.kontentapi', 'StartRegularUnload', 'Y');

        $this->ForcedUnloading = ( isset($this->Settings->get_option()['forced_unloading']) && $this->Settings->get_option()['forced_unloading'] == 'Y') ? true : false;
        $this->StartRegularUnload = ( isset($this->Settings->get_option()['StartRegularUnload']) && $this->Settings->get_option()['StartRegularUnload'] == 'Y') ? true : false;
        $this->FullUnload = ( isset($this->Settings->get_option()['FULL_UNLOAD']) && $this->Settings->get_option()['FULL_UNLOAD'] == 'Y') ? true : false;

        // символы, которые нужно удалить из названия свойства
        $this->PropertyToDeleteFromProps = [
            '&amp;le;',
        ];

    }

    /**
     * Запуск всей выгрузки
     */
    public function StartUnload(){

        $UnloadStatus = UnloadStatusTable::GetStatus();
        if (!$this->Settings->get_option()['MODULE_ACTIVITY']) return 'Модуль не активен';
        if( $this->ForcedUnloading || $this->StartRegularUnload ){

            if( $UnloadStatus['SECTION']['STATUS']!='COMPLETED' ) $this->UnloadSectionsController();
            if( $UnloadStatus['BRANDS']['STATUS']!='COMPLETED' ) $this->UnloadBrandController();
            if( $UnloadStatus['PRODUCTS']['STATUS']!='COMPLETED' ) $this->UnloadProductsController();

            // Завершаем принудительную выгрузку
            \Bitrix\Main\Config\Option::set('ik.kontentapi', 'forced_unloading', 'N');

            // Завершаем регулярную выгрузку
            \Bitrix\Main\Config\Option::set('ik.kontentapi', 'StartRegularUnload', 'N');

            // Сбрасываем статусы
            UnloadStatusTable::ResetStatuses();
        };
    }

    /**
     * Контролирует выгрузку разделов
     */
    private function UnloadSectionsController(){
        // Если статус завершен, то выгрузку не продолжаем.
        if( UnloadStatusTable::GetStatus('SECTION') == 'COMPLETED' ) return false;

        // Текущая итерация
        $CurrentIteration = $this->GetCurrentSectionIteration('SECTION');

        // Получаем разделы
        $SavedUnloadData = $this->ApiController->ReadUnloadFromFile('categories_save.log');
        if( !$SavedUnloadData || $CurrentIteration==1 ){
            $SavedUnloadData = $this->ApiController->GetCategories();
            $this->ApiController->SaveUnloadInFile('categories_save.log', $SavedUnloadData);
        };

        $SectionCount = count($SavedUnloadData); // Всего разделов
        $IterationCount = ceil($SectionCount / $this->UpdatedSectionPerOneTime); // Кол-во итераций для выгрузки разделов

        while( $CurrentIteration <= $IterationCount ){
            
            // Выгружаем часть разделов
            $DataSlice = Helper::sliceArray( $SavedUnloadData, $CurrentIteration, $this->UpdatedSectionPerOneTime);
            $this->UnloadSections($DataSlice);

            // Диактивируем API-разделы, которых не было в выгрузке
            $this->DiactivateNotParticipateSection( array_keys($SavedUnloadData) );

            // Если выгрузка завершилась, то обновляем итерацию
            UnloadStatusTable::update(1 ,[
                'ITERATION'=>($CurrentIteration >= $IterationCount) ? '1' : $CurrentIteration+1,
            ]);

            // Устанавливаем статус завершенной выгрузки
            if( $CurrentIteration == $IterationCount ){
                UnloadStatusTable::update(1 ,[
                    'ITERATION'=>'1',
                    'STATUS' => 'COMPLETED',
                ]);
                return $Log;
            };

            $CurrentIteration = $this->GetCurrentSectionIteration('SECTION');
        };
    }

    /**
     * Контролирует выгрузку брендов
     */
    private function UnloadBrandController(){
        // Если статус завершен, то выгрузку не продолжаем.
        if( UnloadStatusTable::GetStatus('BRANDS') == 'COMPLETED' ) return false;

        // Текущая итерация
        $CurrentIteration = $this->GetCurrentSectionIteration('BRANDS');

        // Получаем разделы
        $SavedUnloadData = $this->ApiController->ReadUnloadFromFile('brands_save.log');
        if( !$SavedUnloadData || $CurrentIteration==1 ){
            $SavedUnloadData = $this->ApiController->GetBrands();
            $this->ApiController->SaveUnloadInFile('brands_save.log', $SavedUnloadData);
        };

        $SectionCount = count($SavedUnloadData); // Всего разделов
        $IterationCount = ceil($SectionCount / $this->UpdatedSectionPerOneTime); // Кол-во итераций для выгрузки разделов

        while( $CurrentIteration <= $IterationCount ){
            
            // Выгружаем часть разделов
            $DataSlice = Helper::sliceArray( $SavedUnloadData, $CurrentIteration, $this->UpdatedSectionPerOneTime);
            $this->UnloadBrands($DataSlice);

            // Диактивируем бренды, которые есть на сайте, но не учавствовали в выгрузке
            $this->DiactivateNotParticipateBrands( array_keys($SavedUnloadData) );

            // Если выгрузка завершилась, то обновляем итерацию
            UnloadStatusTable::update(2 ,[
                'ITERATION'=>($CurrentIteration >= $IterationCount) ? '1' : $CurrentIteration+1,
            ]);

            // Устанавливаем статус завершенной выгрузки
            if( $CurrentIteration == $IterationCount ){
                UnloadStatusTable::update(2 ,[
                    'ITERATION'=>'1',
                    'STATUS' => 'COMPLETED',
                ]);
                return $Log;
            };

            $CurrentIteration = $this->GetCurrentSectionIteration('BRANDS');
        };
    }

    /**
     * Контролирует выгрузку товаров
     */
    private function UnloadProductsController(){

        // Если статус завершен, то выгрузку не продолжаем.
        if( UnloadStatusTable::GetStatus('PRODUCTS') == 'COMPLETED' ) return false;

        // Текущая итерация
        $CurrentIteration = $this->GetCurrentSectionIteration('PRODUCTS');

        // Получаем разделы
        $SavedUnloadData = $this->ApiController->ReadUnloadFromFile('products_save.log');
        if( !$SavedUnloadData || $CurrentIteration==1 ){
            $SavedUnloadData = $this->ApiController->GetProducts();
            $this->ApiController->SaveUnloadInFile('products_save.log', $SavedUnloadData);
        };

        $SectionCount = count($SavedUnloadData); // Всего товаров
        $IterationCount = ceil($SectionCount / $this->UpdatedProductsPerOneTime); // Кол-во итераций для выгрузки товаров


        while( $CurrentIteration <= $IterationCount ){
            
            // Выгружаем часть товаров
            $DataSlice = Helper::sliceArray( $SavedUnloadData, $CurrentIteration, $this->UpdatedProductsPerOneTime);
            $this->UnloadProducts($DataSlice);

            // Диактивация товаров
            $this->DiactivateNotParticipateProducts( array_keys($SavedUnloadData) );

            // Если выгрузка завершилась, то обновляем итерацию
            UnloadStatusTable::update(3 ,[
                'ITERATION'=>($CurrentIteration >= $IterationCount) ? '1' : $CurrentIteration+1,
            ]);

            // Устанавливаем статус завершенной выгрузки
            if( $CurrentIteration == $IterationCount ){
                UnloadStatusTable::update(3 ,[
                    'ITERATION'=>'1',
                    'STATUS' => 'COMPLETED',
                ]);
                return $Log;
            };

            $CurrentIteration = $this->GetCurrentSectionIteration('PRODUCTS');
        };
    }

    /**
     * Выгружает разделы
     * 
     * @param array $Sections
     */
    private function UnloadSections(array $Sections){
        // Проверяем кэш и наличие каждого раздела. Если нужно обновить, то обновляем.
        foreach ($Sections as $SectionKey => $SectionValue) {
            if( $this->CacheController->CheckSectionCache( $SectionValue['chpu'] ) ){
                
                // Подготавливаем поля
                $arFields = array(
                    "ACTIVE" => 'Y',    
                    "IBLOCK_ID" => $this->IblockID,
                    "NAME" => $SectionValue['title'],
                    "CODE" => $SectionValue['chpu'],
                    'SORT' => $SectionValue['order'],
                );
                if( isset($SectionValue['level']) && $SectionValue['level'] != 0 ){
                    $IblockSectionId = $this->SearchParentSectionID( $SectionValue['level'] );
                    if( $IblockSectionId ) $arFields['IBLOCK_SECTION_ID'] = $IblockSectionId;
                };

                // Если раздел найден, обновляем инфу. Иначе создаем новый.
                if( $SectionID = $this->SearchExistingApiSection($SectionValue['chpu']) ){
                    $this->CIBlockSection->Update($SectionID, $arFields);

                }else{
                    $SectionID = $this->CIBlockSection->Add($arFields);
                    if ($SectionID){
                        Helper::SetSectionProp($SectionID, 'UF_APISECTIONID', $SectionKey);
                        Helper::SetSectionProp($SectionID, 'UF_IS_API', true);
                    };
                };
            }
        }
    }

    /**
     * Выгружает Бренды
     * 
     * @param array $Brands
     */
    private function UnloadBrands(array $Brands){
        // Проверяем кэш и наличие каждого раздела. Если нужно обновить, то обновляем.
        foreach ($Brands as $BrandsKey => $BrandsValue) {
            if( $this->CacheController->CheckBrandsCache( $BrandsValue['chpu'] ) ){
                
                // Подготавливаем поля
                $arFields = array(
                    "MODIFIED_BY" => "1",
                    "IBLOCK_SECTION_ID" => false,
                    "IBLOCK_ID" => $this->BrandIblockID,
                    "ACTIVE" => 'Y',
                    "NAME" => $BrandsValue['title'],
                    "CODE" => $BrandsValue['chpu'],
                    "SORT" => $BrandsValue['order'],
                    "PROPERTY_VALUES" => array(
                        "API_ID" => $BrandsKey,
                        "IS_API" => 'Y',
                        "URL" => $BrandsValue['url'],
                    ),
                );

                $BrandID = $this->SearchExistingApiBrand( $BrandsKey );
                if( $BrandID || $this->FullUnload  ){
                    $arFields['DETAIL_PICTURE'] = CFile::MakeFileArray($BrandsValue['image']);
                };

                if( $BrandID ){
                    $this->CIBlockElement->Update($BrandID, $arFields);
                }else{
                    $el = new CIBlockElement;
                    if($newElement = $el->Add($arFields)){
                        $BrandID = $newElement;
                    }
                };
            }
        }
    }

    /**
     * Выгружает товары
     * 
     * @param array $Products
     */
    public function UnloadProducts(array $Products){
        // Проверяем кэш и наличие каждого товара. Если нужно обновить, то обновляем.
        foreach ($Products as $ProductsKey => $ProductsValue) {
            if( $this->CacheController->CheckProductsCache( $ProductsValue['articul'] ) ){
                $Section = $this->SearchParentSectionID( $ProductsValue['category_id'] );

                // Подготавливаем поля
                $arFields = array(
                    "MODIFIED_BY" => "1",
                    "IBLOCK_SECTION_ID" => ($Section) ? $Section : false,
                    "IBLOCK_ID" => $this->IblockID,
                    "ACTIVE" => 'Y',
                    "NAME" => $ProductsValue['title'],
                    "CODE" => $this->Translit($ProductsValue['title']),
                    "DETAIL_TEXT" => $ProductsValue['utp'],

                    "PROPERTY_VALUES" => array(
                        "API_ID" => $ProductsKey,
                        "IS_API" => 'Y',
                        "NC" => $ProductsValue['nc'],
                        "NC_VNUTR" => $ProductsValue['nc_vnutr'],
                        "NC_NARUJ" => $ProductsValue['nc_naruj'],
                        "NC_ACCESSORY" => $ProductsValue['nc_accessory'],
                        "CML2_ARTICLE" => $ProductsValue['articul'],
                        "SERIES" => $ProductsValue['series'],
                        "VIDEO_YOUTUBE" => $ProductsValue['video_youtube'],
                        "BOOKLET" => $ProductsValue['booklet'],
                        "MANUAL" => $ProductsValue['manual'],
                        "BIM_MODEL" => $ProductsValue['bim_model'],
                    ),
                );

                // Поставляем бренд (привязка к элементу инфоблока)
                $SearchBrand = $this->SearchExistingApiBrand($ProductsValue['brand']);
                if( $SearchBrand ) $arFields['PROPERTY_VALUES']['BRAND'] = $SearchBrand;

                // Получаем значение тех.характеристик, добавляем значения.
                $TechPropertList = $this->ApiController->GetProductTech( $ProductsKey );
                if( $TechPropertList ){
                    foreach ($TechPropertList as $TechPropertyValue) {
                        // Создаем свойство, если оно не создано
                        $FullCode = 'API_'.$this->Translit($TechPropertyValue['title']);

                        // Не выгружаем бренд т.к. он заполняется в свойство, которое создается руками.
                        if( in_array($this->Translit($FullCode), ['brand']) ) continue;

                        $ShortCode = ProductsPropertysNameTable::GetShortCode($FullCode);
                        if( !in_array($ShortCode, Helper::GetIblockPropertys($this->IblockID)) ){

                            $ShortCode = ProductsPropertysNameTable::CreatePropertyShortName('API_');

                            $Mandatory = ($TechPropertyValue['type']==1) ? true : false;
                            $NewPropInfo = Helper::CreateIblockProperty( 
                                $this->IblockID, 
                                $ShortCode, 
                                $TechPropertyValue['title'], 
                                $TechPropertyValue['type'],
                                false,
                                $Mandatory,
                            );

                            // Добавляем свойство в таблицу
                            ProductsPropertysNameTable::add([
                                "Name" => $TechPropertyValue['title'],
                                "FullCode" => $FullCode,
                                "ShortCode" => $ShortCode,
                            ]);
                        };

                        // Добавляем значение
                        $PropValueID = Helper::GetListPropValueID( $this->IblockID, $ShortCode, $TechPropertyValue['value'] )[0];
                        if( empty($PropValueID) ){
                            // Создаем новое значение
                            $property = CIBlockProperty::GetByID($ShortCode, $this->IblockID)->GetNext();
                            $ibpenum = new CIBlockPropertyEnum;
                            
                            // Удаляем лишние символы из названия свойства
                            $TechPropertyValueCorrect = str_replace($this->PropertyToDeleteFromProps, '', $TechPropertyValue['value']);

                            if($PropValueID = $ibpenum->Add([
                                'PROPERTY_ID' => $property['ID'],
                                "VALUE" => $TechPropertyValueCorrect, // имя
                                // "XML_ID" => "", // XML_ID
                                "DEF" => "", // по-умолчанию Y/N
                                "SORT" => "100" // сортировка
                            ]));
                        };

                        $arFields['PROPERTY_VALUES'][$ShortCode] = $PropValueID;

                    };
                };
                
                $ProductID = $this->SearchExistingApiProduct( $ProductsKey );
                $NewProduct = ( $ProductID ) ? true : false;
                if( $NewProduct || $this->FullUnload ){
                    $Images = (function( $array ){
                        unset( $array[0] );
                        foreach ($array as $value) {
                            $result[] = CFile::MakeFileArray( $value );  
                        };
                        return $result;
                    })( $ProductsValue['images'] );
                    if( !empty($Images) ) $arFields['PROPERTY_VALUES']['MORE_PHOTO'] = $Images;
                    $arFields['DETAIL_PICTURE'] = CFile::MakeFileArray($ProductsValue['images'][0]);
                };

                if( $ProductID ){
                    foreach ($arFields['PROPERTY_VALUES'] as $key => $value) {
                        $CurrentValues = $this->GetElementListPropValues($ProductID, $key);
                        $arFields['PROPERTY_VALUES'][$key] = array_merge($CurrentValues, [$value]);    
                    };

                    $this->CIBlockElement->Update($ProductID, $arFields);
                }else{
                    $el = new CIBlockElement;
                    if($newElement = $el->Add($arFields)){
                        $ProductID = $newElement['ID'];
                    }
                };

                // Устанавливаем доступное кол-во 0
                $CountProduct =[
                    'ID' => $ProductID,
                    'QUANTITY' => 1,
                ];

                $existProduct = \Bitrix\Catalog\Model\Product::getCacheItem($CountProduct['ID'],true);
                if(!empty($existProduct)){
                    \Bitrix\Catalog\Model\Product::update(intval($CountProduct['ID']),$CountProduct);
                } else {
                    \Bitrix\Catalog\Model\Product::add($CountProduct);
                };

                // Устанавливаем цены
                if( isset($ProductsValue['price']['rrc']) && $ProductsValue['price']['rrc']!="" ){
                    $arPriceFields = Array(
                        "PRODUCT_ID" => $ProductID,
                        "CATALOG_GROUP_ID" => 1,
                        "PRICE" => $ProductsValue['price']['rrc'],
                        "CURRENCY" => $ProductsValue['price']['rrc_currency'],
                    );

                    // Проверяем существует ли уже цена для этого товара
                    $res = CPrice::GetList(array(), array("PRODUCT_ID" => $ProductID, "CATALOG_GROUP_ID" => 1));
                    if ($arr = $res->Fetch()) {
                        // Если цена уже существует, обновляем ее
                        CPrice::Update($arr["ID"], $arPriceFields);
                    } else {
                        // Если цены еще нет, добавляем ее
                        CPrice::Add($arPriceFields);
                    }
                };
            };
        };
    }

    /**
     * Ищет родительский раздел
     * 
     * @param int $ApiSectionID — ID секции (Передеанный по api)
     * 
     * @return int ID секции из структуры bitrix
     * @return false Если родительского раздела не найдено
     */
    private function SearchParentSectionID( int $ApiSectionID ){

        $dbSections = CIBlockSection::GetList(array(), array(
            'IBLOCK_ID' => $this->IblockID,
            '=UF_APISECTIONID' => $ApiSectionID,
        ), false, array('ID'), false);

        if ($arSection = $dbSections->Fetch()){
            return $arSection['ID'];
        }else{
            return false;
        };
    }

    /**
     * Ищет api раздел
     * 
     * @param string $Code — символьный код
     * 
     * @return int ID раздела
     * @return false Раздел не найден
     */
    private function SearchExistingApiSection( string $Code ){
        
        $dbSections = $this->CIBlockSection::GetList(array(), array(
            'IBLOCK_ID' => $this->IblockID,
            'CODE' => $Code,
            '=UF_IS_API' => '1',
        ), false, array('ID','CODE'), false);

        if ($arSection = $dbSections->Fetch()){
            return $arSection['ID'];
        }else{
            return false;
        };
    }

    /**
     * Ищет api товар
     * 
     * @param string $ApiID — API-id товара
     * 
     * @return int ID товара
     * @return false Товар не найден
     */
    private function SearchExistingApiProduct( string $ApiID ){
        
        $dbSections = $this->CIBlockElement::GetList(array(), array(
            'IBLOCK_ID' => $this->IblockID,
            'PROPERTY_IS_API' => 'Y',
            'PROPERTY_API_ID' => $ApiID,
        ), false, array('ID','PROPERTY_API_ID'), false);

        if ($arSection = $dbSections->Fetch()){
            return $arSection['ID'];
        }else{
            return false;
        };
    }

    /**
     * Ищет api бренд
     * 
     * @param string $ApiID — API-id бренда
     * 
     * @return int ID бренда
     * @return false бренд не найден
     */
    private function SearchExistingApiBrand( string $ApiID ){
        
        $dbBrands = $this->CIBlockElement::GetList(array(), array(
            'IBLOCK_ID' => $this->BrandIblockID,
            'PROPERTY_IS_API' => 'Y',
            'PROPERTY_API_ID' => $ApiID,
        ), false, array('ID','PROPERTY_API_ID'), false);

        if ($arBrand = $dbBrands->Fetch()){
            return $arBrand['ID'];
        }else{
            return false;
        };
    }    

    /**
     * Диактивирует api разделы, 
     * которые присутствуют на сайте, но не участвовали в выгрузке.
     * 
     * @param array $ParticipateSectionList — массив id разделов api, 
     *      которые не нужно диактивировать
     * 
     */
    private function DiactivateNotParticipateSection( array $ParticipateSectionList ){
        $dbSections = CIBlockSection::GetList(array(), array(
            'IBLOCK_ID' => $this->IblockID,
            'UF_IS_API' => true,
        ), false, array('ID', "UF_APISECTIONID",), false);

        while($arSection = $dbSections->GetNext()){
            if( !in_array($arSection['UF_APISECTIONID'], $ParticipateSectionList) ){
                $result[] = $arSection['ID'];
            };
        };

        if( !empty($result) ) Helper::ChangeSectionActivity($result, false);
    }

    /**
     * Диактивирует api-бренды, 
     * которые присутствуют на сайте, но не участвовали в выгрузке.
     * 
     * @param array $ParticipateSectionList — массив id элеменов инфоблока с брендами api, 
     *      которые не нужно диактивировать
     * 
     */
    private function DiactivateNotParticipateBrands( array $ParticipateBrandsList ){

        $IblockClass = \Bitrix\Iblock\Iblock::wakeUp($this->BrandIblockID)->getEntityDataClass();

        if (class_exists($IblockClass)){
            $element = call_user_func([$IblockClass, 'getList'], [
                'select' => ['ID', 'IS_API_'=>'IS_API', 'API_ID_' => 'API_ID'],
                'filter' => [
                    '=IS_API.VALUE' => 'Y',
                ],
            ])->fetchAll();

            foreach ($element as $value) {
                if( !in_array( $value['API_ID_VALUE'], $ParticipateBrandsList ) ){
                    $result[] = $value['ID'];
                };
            };
        };

        if( !empty($result) ) Helper::ChangeIblockElementActivity($result, $this->BrandIblockID, false);
    }

    /**
     * Диактивирует api-товары, 
     * которые присутствуют на сайте, но не участвовали в выгрузке.
     * 
     * @param array $ParticipateSectionList — массив id элеменов инфоблока с товарами api, 
     *      которые не нужно диактивировать
     * 
     */
    private function DiactivateNotParticipateProducts( array $ParticipateProductsList ){
        $IblockClass = \Bitrix\Iblock\Iblock::wakeUp($this->IblockID)->getEntityDataClass();

        if (class_exists($IblockClass)){
            $element = call_user_func([$IblockClass, 'getList'], [
                'select' => ['ID', 'IS_API_'=>'IS_API', 'API_ID_' => 'API_ID'],
                'filter' => [
                    '=IS_API.VALUE' => 'Y',
                ],
            ])->fetchAll();

            foreach ($element as $value) {
                if( !in_array( $value['API_ID_VALUE'], $ParticipateProductsList ) ){
                    $result[] = $value['ID'];
                };
            };
        };

        if( !empty($result) ) Helper::ChangeIblockElementActivity($result, $this->IblockID, false);

    }

    /**
     * Рассчитываем текущую итерацию разделов и возвращает её.
     * 
     * @return int $CurrentIteration — Итерация разделов
     */
    private function GetCurrentSectionIteration( string $Type ){
        $CurrentIteration = UnloadStatusTable::getList([
            'select' => ['ITERATION'],
            'filter' => ['=TYPE'=>$Type],
        ])->fetchAll();
        if( empty($CurrentIteration) || $CurrentIteration[0]['ITERATION'] == 0 ){
            $CurrentIteration = 1;
            UnloadStatusTable::update(1 ,['ITERATION'=>'1']);
        }else{
            $CurrentIteration = $CurrentIteration[0]['ITERATION'];
        };

        return $CurrentIteration;
    }

    private function Translit(string $str):string{
        return \Cutil::translit($str,"ru",["replace_space"=>"_","replace_other"=>"_"]);
    }

    /**
     * Получаем список выбранных значений свойства типа список для элемента
     * 
     * @param int $ElementID — id-элемента
     * @param string $PropertyCode — символьный код свойства
     * 
     * @return array массив id выбранных значений свойства типа список у элемента инфоблока
     */
    private function GetElementListPropValues( int $ElementID, string $PropertyCode ):array{
        $Values = array_column(\Bitrix\Iblock\Elements\ElementAPICatalogTable::getList([
            'select' => ['ID', 'NAME', $PropertyCode.'_'=>$PropertyCode],
            'filter' => [
                'ID' => $ElementID,
            ],
        ])->fetchAll(), $PropertyCode.'_VALUE');

        return $Values;
    }
}
?>