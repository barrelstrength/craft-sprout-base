<?php
namespace barrelstrength\sproutcore\records\sproutreports;

use craft\db\ActiveRecord;

/**
 * Class SproutReports_ReportRecord
 *
 * @package Craft
 * --
 * @property int $id               ID
 * @property string $name          Name
 * @property string $handle        Handle
 * @property string $description   Description
 * @property array $options        Options
 * @property string $dataSourceId  Data Source ID
 * @property int $groupId          Group ID
 * @property int $enabled          Enabled
 */
class DataSource extends ActiveRecord
{
	/**
	 * @return string
	 */
	public static function tableName(): string
	{
		return '{{%sproutreports_datasources}}';
	}
}