<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutbase\app\forms\elements\actions;

use barrelstrength\sproutbase\SproutBase;
use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;
use Throwable;

/**
 *
 * @property string $triggerLabel
 */
class Delete extends ElementAction
{
    /**
     * @var string|null The confirmation message that should be shown before the elements get deleted
     */
    public $confirmationMessage;

    /**
     * @var string|null The message that should be shown after the elements get deleted
     */
    public $successMessage;

    public static function isDestructive(): bool
    {
        return true;
    }

    public function getTriggerLabel(): string
    {
        return Craft::t('sprout', 'Delete…');
    }

    public function getConfirmationMessage()
    {
        return Craft::t('sprout', 'Are you sure you want to delete the selected forms?');
    }

    /**
     * @inheritDoc
     *
     * @throws Throwable
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $message = null;

        $response = SproutBase::$app->forms->deleteForms($query->all());

        if ($response) {
            $message = Craft::t('sprout', 'Forms Deleted.');
        } else {
            $message = Craft::t('sprout', 'Failed to delete forms.');
        }

        $this->setMessage($message);

        return $response;
    }
}
