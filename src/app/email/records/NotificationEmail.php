<?php

namespace barrelstrength\sproutbase\app\email\records;

use craft\base\Element;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 *
 * @property ActiveQueryInterface $element
 */
class NotificationEmail extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%sprout_notification_emails}}';
    }

    /**
     * Returns the entry’s element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
