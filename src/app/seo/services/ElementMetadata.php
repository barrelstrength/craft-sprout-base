<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutbase\app\seo\services;


use barrelstrength\sproutbase\app\seo\fields\ElementMetadata as ElementMetadataField;
use barrelstrength\sproutbase\app\seo\models\Metadata;
use barrelstrength\sproutbase\SproutBase;
use Craft;
use craft\base\Element;
use craft\base\Field;
use craft\db\Query;
use craft\errors\SiteNotFoundException;
use craft\events\FieldLayoutEvent;
use craft\models\FieldLayout;
use Throwable;
use yii\base\Component;
use yii\base\Exception;

class ElementMetadata extends Component
{
    /**
     * Returns the metadata for an Element's Element Metadata as a Metadata model
     *
     * @param Element|null $element
     *
     * @return array
     * @throws Throwable
     * @throws Exception
     */
    public function getRawMetadataFromElement(Element $element = null): array
    {
        if (!$element) {
            return [];
        }

        $fieldHandle = $this->getElementMetadataFieldHandle($element);

        if (isset($element->{$fieldHandle})) {
            /** @var Metadata $metadata */
            $metadata = $element->{$fieldHandle};

            if ($metadata) {
                return $metadata->getRawData();
            }
        }

        return [];
    }

    /**
     * Returns the Field handle of the first Element Metadata field found in an Element Field Layout
     *
     * @param Element|null $element
     *
     * @return null|string
     */
    public function getElementMetadataFieldHandle(Element $element = null)
    {
        if (!$element) {
            return null;
        }

        /** @var FieldLayout $fieldLayout */
        $fieldLayout = $element->getFieldLayout();
        $fields = $fieldLayout->getFields();

        /**
         * Get our ElementMetadata Field
         *
         * @var Field $field
         */
        foreach ($fields as $field) {
            if ((get_class($field) == ElementMetadataField::class) && isset($element->{$field->handle})) {
                return $field->handle;
            }
        }

        return null;
    }

    /**
     * @param FieldLayoutEvent $event
     *
     * @throws SiteNotFoundException
     */
    public function handleResaveElementsAfterFieldLayoutIsSaved(FieldLayoutEvent $event)
    {
        $seoSettings = SproutBase::$app->settings->getSettingsByKey('seo');

        if (!$seoSettings->getIsEnabled()) {
            return;
        }

        $this->resaveElementsAfterFieldLayoutIsSaved($event->layout);
    }

    /**
     * Re-save Elements after a field layout or Element Metadata field is updated
     *
     * This is necessary when an Element Metadata field is added to a Field Layout
     * in a Section that Elements already exist, or if any changes are made to the
     * Element Metadata field type.
     *
     * @param FieldLayout $fieldLayout
     *
     * @throws SiteNotFoundException
     */
    public function resaveElementsAfterFieldLayoutIsSaved(FieldLayout $fieldLayout)
    {
        /**
         * The Field Layout event identifies the Element Type
         * that the layout is for:
         * Category, Entry, Commerce_Product, etc.
         */
        $elementType = $fieldLayout->type;
        $fieldLayoutFields = $fieldLayout->getFields();
        $hasElementMetadataField = false;

        foreach ($fieldLayoutFields as $field) {
            if (get_class($field) === ElementMetadataField::class) {
                $hasElementMetadataField = true;
                break;
            }
        }

        if ($hasElementMetadataField) {
            // Some Elements, like Commerce_Products
            // also need to save the related Variant field layout which returns as an array
            $this->resaveElementsByUrlEnabledSection($elementType, true, $fieldLayout);
        }
    }

    /**
     * @param $fieldId
     *
     * @throws SiteNotFoundException
     */
    public function resaveElementsIfUsingElementMetadataField($fieldId)
    {
        //Get all layoutIds where this field is used from craft_fieldlayoutfields.layoutId
        $fieldLayoutIds = (new Query())
            ->select('[[layoutId]]')
            ->from(['{{%fieldlayoutfields}}'])
            ->where(['[[fieldId]]' => $fieldId])
            ->all();

        $fieldLayoutIds = array_column($fieldLayoutIds, 'layoutId');

        $elementTypes = [];

        foreach ($fieldLayoutIds as $fieldLayoutId) {
            //Use that id to get the Element Type of each layout via the craft_fieldlayouts.type column
            $fieldLayout = (new Query())
                ->select('type')
                ->from(['{{%fieldlayouts}}'])
                ->where(['id' => $fieldLayoutId])
                ->one();

            $elementTypes[] = $fieldLayout['type'];
        }

        $elementTypes = array_unique($elementTypes);

        foreach ($elementTypes as $elementType) {
            $this->resaveElementsByUrlEnabledSection($elementType);
        }
    }

    public function getSeoBadgeInfo($settings): array
    {
        $targetSettings = [
            [
                'type' => 'optimizedTitleField',
                'value' => $settings['optimizedTitleField'],
                'badgeClass' => 'sproutseo-metatitle-info',
            ], [
                'type' => 'optimizedDescriptionField',
                'value' => $settings['optimizedDescriptionField'],
                'badgeClass' => 'sproutseo-metadescription-info',
            ], [
                'type' => 'optimizedImageField',
                'value' => $settings['optimizedImageField'],
                'badgeClass' => 'sproutseo-metaimage-info',
            ],
        ];

        $seoFieldHandles = [];
        foreach ($targetSettings as $targetSetting) {

            $handles = $this->getFieldHandles($targetSetting['value']);

            if (is_array($handles)) {
                foreach ($handles as $handle) {
                    if (isset($seoFieldHandles[$handle])) {
                        continue;
                    }
                    $seoFieldHandles[$handle] = [
                        'type' => $targetSetting['type'],
                        'handle' => $handle,
                        'badgeClass' => $targetSetting['badgeClass'],
                    ];
                }
            } else {
                if (isset($seoFieldHandles[$handles])) {
                    continue;
                }
                $seoFieldHandles[$handles] = [
                    'type' => $targetSetting['type'],
                    'handle' => $handles,
                    'badgeClass' => $targetSetting['badgeClass'],
                ];
            }
        }

        return $seoFieldHandles;
    }

    public function getFieldHandles($targetFieldSetting)
    {
        $targetField = $targetFieldSetting ?? null;

        if (!$targetField) {
            return [];
        }

        // Return the handle of the selected field and make into an array
        $existingFieldHandle = [$this->getExistingFieldHandle($targetField)];

        // Parse a custom setting and return an array or empty array
        $customSettingFieldHandles = $this->getCustomSettingFieldHandles($targetField);

        $fieldHandles = array_filter(array_merge($existingFieldHandle, $customSettingFieldHandles));

        if (count($fieldHandles) <= 0) {
            if ($targetField === 'elementTitle') {
                return 'title';
            }

            // resolve 'manually' settings to specific default field ids...

            return $targetField;
        }

        return $fieldHandles;
    }

    public function getExistingFieldHandle($fieldId)
    {
        // A number represents a specific image field selected
        if (!preg_match('/^\\d+$/', $fieldId)) {
            return '';
        }

        /** @var Field $optimizedImageFieldModel */
        $optimizedImageFieldModel = Craft::$app->fields->getFieldById($fieldId);

        return $optimizedImageFieldModel ? $optimizedImageFieldModel->handle : '';
    }

    /**
     * Parses a custom Element Metadata field setting and returns tags used as an array of names
     *
     * @param $value
     *
     * @return array
     */
    public function getCustomSettingFieldHandles($value): array
    {
        // If there are no dynamic tags, just return the template
        if (strpos($value, '{') === false) {
            return [];
        }

        /**
         *  {           - our pattern starts with an open bracket
         *  <space>?    - zero or one space
         *  (object\.)? - zero or one characters that spell "object."
         *  (?<handles> - begin capture pattern and name it 'handles'
         *  [a-zA-Z_]*  - any number of characters in Craft field handles
         *  )           - end capture pattern named 'handles'
         */
        preg_match_all('/{ ?(object\.)?(?<handles>[a-zA-Z_]*)/', $value, $matches);

        if (count($matches['handles'])) {
            // Remove empty array items and make sure we only return each value once
            return array_filter(array_unique($matches['handles']));
        }

        return [];
    }

    /**
     * @return int
     */
    public function getDescriptionLength(): int
    {
        $settings = SproutBase::$app->settings->getSettingsByKey('seo');

        return $settings->maxMetaDescriptionLength ?: 160;
    }

    /**
     * @return int|string
     */
    public function getMetadataFieldCount()
    {
        $totalFields = (new Query())
            ->select(['id'])
            ->from(['{{%fields}}'])
            ->where(['type' => ElementMetadataField::class])
            ->count();

        return $totalFields;
    }

    /**
     * Triggers a Resave Elements job for each Url-Enabled Section with an Element Metadata field
     *
     * @param                  $elementType
     * @param bool $afterFieldLayout
     * @param FieldLayout|null $fieldLayout
     *
     * @return bool
     * @throws SiteNotFoundException
     */
    protected function resaveElementsByUrlEnabledSection($elementType, $afterFieldLayout = false, FieldLayout $fieldLayout = null): bool
    {
        $urlEnabledSectionType = SproutBase::$app->urlEnabledSections->getUrlEnabledSectionTypeByElementType($elementType);

        if ($urlEnabledSectionType === null) {
            return false;
        }

        if ($afterFieldLayout && !$urlEnabledSectionType->resaveElementsAfterFieldLayoutSaved()) {
            return false;
        }

        if ($urlEnabledSectionType) {
            foreach ($urlEnabledSectionType->urlEnabledSections as $urlEnabledSection) {
                if ($afterFieldLayout && $fieldLayout !== null) {
                    if ($urlEnabledSection->hasFieldLayoutId($fieldLayout->id)) {
                        // Need to figure out where to grab sectionId, entryTypeId, categoryGroupId, etc.
                        $elementGroupId = $urlEnabledSection->id;
                        $urlEnabledSectionType->resaveElements($elementGroupId);

                        break;
                    }
                } else if ($urlEnabledSection->hasElementMetadataField(false)) {
                    $elementGroupId = $urlEnabledSection->id;
                    $urlEnabledSectionType->resaveElements($elementGroupId);
                }
            }
        }

        return true;
    }
}
