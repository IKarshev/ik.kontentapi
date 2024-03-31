<?
namespace ik\Kontentapi\Orm;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\BooleanField;

use ik\Kontentapi\Helper;

/**
 * ORM-класс, описывающий таблицу, которая хранит инфу по свойствам.
 * 
 * Такая необходимость возникла из-за того, что в битриксе есть ограничение по длине
 * символьного кода свойства, а в выгрузке есть свойства гораздо длиньше.
 */
Class ProductsPropertysNameTable extends DataManager
{
    private const TABLE_NAME = 'ikKontentapi_ProductsPropertysName';

    public static function getTableName()
    {
        return self::TABLE_NAME;
    }

    public static function getMap()
    {
        return [
            new IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
            ]),
            new StringField('Name', [
                'required' => true,
            ]),
            new StringField('FullCode', [
                'required' => true,
            ]),
            new StringField('ShortCode', [
                'required' => true,
            ]),
        ];
    }

    /**
     * @param string $ShortCode — Полный символьный код
     * 
     * @return mixed — Укороченный символьный код
     */
    public static function GetShortCode( string $FullCode ){
        $result = self::getList([
            'select' => ['*'],
            'filter' => [
                '=FullCode' => $FullCode,
            ],
        ])->fetchAll()[0]['ShortCode'];
        
        return (!empty($result)) ? $result : false;
    }

    /**
     * @param string $ShortCode — Укороченный символьный код
     * 
     * @return mixed — Полный символьный код
     */
    public static function GetFullCode( string $ShortCode ){
        $result = self::getList([
            'select' => ['*'],
            'filter' => [
                '=ShortCode' => $ShortCode,
            ],
        ])->fetchAll()[0]['FullCode'];
        
        return (!empty($result)) ? $result : false;
    }


    /**
     * @param string $Substring — Приписка в начале кода свойства
     * 
     * @return string Укороченный код свойства
     */
    public static function CreatePropertyShortName( string $Substring = '' ){
        $RandomString = Helper::randomString(8, '0123456789');
        $String = $Substring.$RandomString;

        // Если такой код свойства уже существует, то перегенирируем его.
        if( self::GetFullCode( $String ) ) self::CreatePropertyShortName( $Substring );
        
        return $String;
    }


}
?>