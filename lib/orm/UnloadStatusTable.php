<?
namespace ik\Kontentapi\Orm;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\BooleanField;

/**
 * ORM-класс, описывающий таблицу, которая хранит статус выгрузки
 * 
 * STATUS => [
 *      'WAITING', - Ожидание, готов к выгрузке
 *      'IN_PROGRESS', - Выгрузка происходит в данный момент
 *      'COMPLETED' - Выгрузка завершена, этап пропускается
 * ];
 */
Class UnloadStatusTable extends DataManager
{
    private const TABLE_NAME = 'ikKontentapi_UnloadStatus';

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
            new StringField('TYPE', [
                'required' => true,
            ]),
            new IntegerField('ITERATION', [
                'required' => true,
            ]),
            new StringField('STATUS', [
                'required' => true,
            ]),
        ];
    }
    public static function GetStatus(){
        $result = array();
        
        $query = self::getList([
            'select' => ['*'],
        ])->fetchAll();
        
        foreach ($query as $value) {
            $result[$value['TYPE']] = $value;
        };
        
        return $result;
    }
    public static function ResetStatuses(){
        self::update(1,[
            'ITERATION'=>'1',
            'STATUS' => 'WAITING',
        ]);
        self::update(2,[
            'ITERATION'=>'1',
            'STATUS' => 'WAITING',
        ]);
        self::update(3,[
            'ITERATION'=>'1',
            'STATUS' => 'WAITING',
        ]);
    }

}
?>