<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutbase\app\forms\elements;

use barrelstrength\sproutbase\app\forms\base\FormField;
use barrelstrength\sproutbase\app\forms\base\FormTemplates;
use barrelstrength\sproutbase\app\forms\elements\actions\Delete;
use barrelstrength\sproutbase\app\forms\elements\db\FormQuery;
use barrelstrength\sproutbase\app\forms\errors\FormTemplatesDirectoryNotFoundException;
use barrelstrength\sproutbase\app\forms\formtemplates\AccessibleTemplates;
use barrelstrength\sproutbase\app\forms\records\Entry as EntryRecord;
use barrelstrength\sproutbase\app\forms\records\Form as FormRecord;
use barrelstrength\sproutbase\app\forms\rules\FieldRule;
use barrelstrength\sproutbase\SproutBase;
use Craft;
use craft\base\Element;
use craft\base\FieldInterface;
use craft\behaviors\FieldLayoutBehavior;
use craft\db\Query;
use craft\elements\db\ElementQueryInterface;
use craft\errors\MissingComponentException;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use yii\base\ErrorHandler;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Form represents a form element.
 *
 * @property FormTemplates $formTemplate
 * @property array $rules
 * @property array $fields
 *
 * @mixin FieldLayoutBehavior
 */
class Form extends Element
{
    /**
     * @var int|null Group ID
     */
    public $groupId;

    /**
     * @var int|null name
     */
    public $name;

    public $handle;

    public $oldHandle;

    public $saveAsNew;

    public $fieldLayoutId;

    public $titleFormat;

    public $displaySectionTitles = false;

    public $redirectUri;

    public $submissionMethod = 'sync';

    public $errorDisplayMethod = 'inline';

    public $successMessage;

    public $errorMessage;

    public $submitButtonText;

    public $saveData = true;

    public $formTemplateId;

    public $enableCaptchas = true;

    private $_fields;

    public static function displayName(): string
    {
        return Craft::t('sprout', 'Form');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('sprout', 'Forms');
    }

    public static function refHandle()
    {
        return 'form';
    }

    /**
     * @inheritdoc
     *
     * @return FormQuery The newly created [[FormQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new FormQuery(static::class);
    }

    protected static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('sprout', 'All Forms'),
            ],
        ];

        $groups = SproutBase::$app->formGroups->getAllFormGroups();

        foreach ($groups as $group) {
            $key = 'group:'.$group->id;

            $sources[] = [
                'key' => $key,
                'label' => Craft::t('sprout', $group->name),
                'data' => ['id' => $group->id],
                'criteria' => ['groupId' => $group->id],
            ];
        }

        return $sources;
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        // Delete
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
        ]);

        return $actions;
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['name', 'handle'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        $attributes = [
            'name' => Craft::t('sprout', 'Form Name'),
            'elements.dateCreated' => Craft::t('sprout', 'Date Created'),
            'elements.dateUpdated' => Craft::t('sprout', 'Date Updated'),
        ];

        return $attributes;
    }

    protected static function defineTableAttributes(): array
    {
        $attributes['name'] = ['label' => Craft::t('sprout', 'Name')];
        $attributes['handle'] = ['label' => Craft::t('sprout', 'Handle')];
        $attributes['numberOfFields'] = ['label' => Craft::t('sprout', 'Number of Fields')];
        $attributes['totalEntries'] = ['label' => Craft::t('sprout', 'Total Entries')];
        $attributes['formSettings'] = ['label' => Craft::t('sprout', 'Settings'), 'icon' => 'settings'];

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return ['name', 'handle', 'numberOfFields', 'totalEntries', 'formSettings'];
    }

    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'fieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => self::class,
            ],
        ]);
    }

    /**
     * Returns the field context this element's content uses.
     *
     * @access protected
     * @return string
     */
    public function getFieldContext(): string
    {
        return 'sproutForms:'.$this->id;
    }

    /**
     * Returns the name of the table this element's content is stored in.
     *
     * @return string
     */
    public function getContentTable(): string
    {
        return SproutBase::$app->forms->getContentTableName($this);
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl(
            'sprout/forms/edit/'.$this->id
        );
    }

    /**
     * Use the name as the string representation.
     *
     * @return string
     * @noinspection PhpInconsistentReturnPointsInspection
     */
    public function __toString()
    {
        try {
            return (string)$this->name;
        } catch (\Exception $e) {
            ErrorHandler::convertExceptionToError($e);
        }
    }

    /**
     * @return FieldLayout
     * @throws InvalidConfigException
     */
    public function getFieldLayout(): FieldLayout
    {
        $behaviors = $this->getBehaviors();

        /** @var FieldLayoutBehavior $fieldLayout */
        $fieldLayout = $behaviors['fieldLayout'];

        return $fieldLayout->getFieldLayout();
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function afterSave(bool $isNew)
    {
        // Get the form record
        if (!$isNew) {
            $record = FormRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid Form ID: '.$this->id);
            }
        } else {
            $record = new FormRecord();
            $record->id = $this->id;
        }

        $record->fieldLayoutId = $this->fieldLayoutId;
        $record->name = $this->name;
        $record->handle = $this->handle;
        $record->titleFormat = $this->titleFormat;
        $record->displaySectionTitles = $this->displaySectionTitles;
        $record->groupId = $this->groupId;
        $record->redirectUri = $this->redirectUri;
        $record->saveData = $this->saveData;
        $record->submissionMethod = $this->submissionMethod;
        $record->errorDisplayMethod = $this->errorDisplayMethod;
        $record->successMessage = $this->successMessage;
        $record->errorMessage = $this->errorMessage;
        $record->submitButtonText = $this->submitButtonText;
        $record->formTemplateId = $this->formTemplateId;
        $record->enableCaptchas = $this->enableCaptchas;

        $record->save(false);

        parent::afterSave($isNew);
    }

    /**
     * Returns the fields associated with this form.
     *
     * @return FormField[]
     * @throws InvalidConfigException
     */
    public function getFields(): array
    {
        if ($this->_fields === null) {
            $this->_fields = [];

            /** @var FormField[] $fields */
            $fields = $this->getFieldLayout()->getFields();

            foreach ($fields as $field) {
                $this->_fields[$field->handle] = $field;
            }
        }

        return $this->_fields;
    }

    /**
     * @param string $handle
     *
     * @return FieldInterface|null
     * @throws InvalidConfigException
     */
    public function getField($handle)
    {
        $fields = $this->getFields();

        if (is_string($handle) && !empty($handle)) {
            return $fields[$handle] ?? null;
        }

        return null;
    }

    /**
     * @param null $cssClasses
     *
     * @return array|array[]
     * @throws FormTemplatesDirectoryNotFoundException
     */
    public function getClassesOptions($cssClasses = null): array
    {
        $classesIds = [];
        $apiOptions = $this->getFormTemplate()->getCssClassDefaults();
        $options = [
            [
                'label' => Craft::t('sprout', 'Select...'),
                'value' => '',
            ],
        ];

        foreach ($apiOptions as $key => $option) {
            $options[] = [
                'label' => $option,
                'value' => $key,
            ];
            $classesIds[] = $key;
        }

        $options[] = [
            'optgroup' => Craft::t('sprout', 'Custom CSS Classes'),
        ];

        if (!in_array($cssClasses, $classesIds, true) && $cssClasses) {
            $options[] = [
                'label' => $cssClasses,
                'value' => $cssClasses,
            ];
        }

        $options[] = [
            'label' => Craft::t('sprout', 'Add Custom'),
            'value' => 'custom',
        ];

        return $options;
    }

    /**
     * Get the global template used by Sprout Forms
     *
     * @return FormTemplates
     * @throws FormTemplatesDirectoryNotFoundException
     */
    public function getFormTemplate(): FormTemplates
    {
        $defaultFormTemplates = new AccessibleTemplates();

        if ($this->formTemplateId) {
            $templatePath = SproutBase::$app->formTemplates->getFormTemplateById($this->formTemplateId);
            if ($templatePath) {
                return $templatePath;
            }
        }

        return $defaultFormTemplates;
    }

    /**
     * Return only enabled rules for this form
     *
     * @return array
     * @throws InvalidConfigException
     * @throws MissingComponentException
     */
    public function getRules(): array
    {
        return SproutBase::$app->formRules->getRulesByFormId($this->id, FieldRule::class, true);
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {

            case 'handle':
            {
                return '<code>'.$this->handle.'</code>';
            }
            case 'numberOfFields':
            {
                $totalFields = (new Query())
                    ->select('COUNT(*)')
                    ->from('{{%fieldlayoutfields}}')
                    ->where(['layoutId' => $this->fieldLayoutId])
                    ->scalar();

                return $totalFields;
            }
            case 'totalEntries':
            {
                $totalEntries = (new Query())
                    ->select('COUNT(*)')
                    ->from(EntryRecord::tableName())
                    ->where(['formId' => $this->id])
                    ->scalar();

                return $totalEntries;
            }
            case 'formSettings':
            {
                return Html::a('', $this->getCpEditUrl().'/settings/general', [
                    'data-icon' => 'settings',
                    'title' => Craft::t('sprout', 'Visit form settings'),
                ]);
            }
        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * @return array
     * @throws InvalidConfigException
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title'],
        ];
        $rules[] = [
            ['name', 'handle'],
            UniqueValidator::class,
            'targetClass' => FormRecord::class,
        ];

        return $rules;
    }
}