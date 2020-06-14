<?php

namespace barrelstrength\sproutbase\app\fields\fields;

use barrelstrength\sproutbase\app\fields\data\PredefinedFieldData;
use Craft;
use craft\base\ElementInterface;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;
use yii\db\Schema;

/**
 *
 * @property string $contentColumnType
 * @property mixed $settingsHtml
 */
class Predefined extends BasePredefinedField
{
    /**
     * The method to output the Predefined Field's value in the Field Layout
     *
     * singleline
     * textarea
     * html
     *
     * @var string
     */
    public $outputFormat;

    /**
     * Whether the Field Format setting should be processed each time the Field Layout is loaded
     *
     * @var bool
     */
    public $renderDynamically;

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('sprout', 'Predefined (Sprout Fields)');
    }

    /**
     * @inheritDoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_TEXT;
    }

    /**
     * @inheritDoc
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('sprout/fields/_components/fields/formfields/predefined/settings',
            [
                'field' => $this
            ]);
    }

    /**
     * @inheritDoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if ($value instanceof PredefinedFieldData) {
            return $value;
        }

        $predefinedFieldData = new PredefinedFieldData();
        $predefinedFieldData->fieldFormat = $this->fieldFormat;
        $predefinedFieldData->value = $value;
        $predefinedFieldData->setElement($element);

        return $predefinedFieldData;
    }

    /**
     * @inheritDoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        if ($value instanceof PredefinedFieldData) {
            return $value->value;
        }

        return parent::serializeValue($value, $element);
    }

    /**
     * @inheritDoc
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Throwable
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        if ($this->showField && $this->renderDynamically) {
            $value = Craft::$app->view->renderObjectTemplate($this->fieldFormat, $element);
        }

        return Craft::$app->getView()->renderTemplate('sprout/fields/_components/fields/formfields/predefined/input',
            [
                'field' => $this,
                'id' => $this->handle,
                'name' => $this->handle,
                'value' => $value,
            ]);
    }
}
