<?php

namespace barrelstrength\sproutbase\app\import\importers\fields;

use barrelstrength\sproutbase\app\import\base\FieldImporter;
use barrelstrength\sproutbase\SproutBase;
use craft\elements\User;
use craft\fields\Users as UsersField;
use Craft;

class Users extends FieldImporter
{
    /**
     * @return string
     */
    public function getModelName()
    {
        return UsersField::class;
    }

    /**
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function getSeedSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-base-import/settings/seed-defaults/users/settings', [
            'settings' => $this->seedSettings['fields']['users'] ?? []
        ]);
    }

    /**
     * @return array|bool|mixed
     */
    public function getMockData()
    {
        $settings = $this->model->settings;

        $relatedMin = 1;
        $relatedMax = 3;

        if (isset($this->seedSettings['fields']))
        {
            $relatedMin = $this->seedSettings['fields']['assets']['relatedMin'] ?: $relatedMin;
            $relatedMax = $this->seedSettings['fields']['assets']['relatedMax'] ?: $relatedMax;
        }

        $relatedMax = SproutBase::$app->fieldImporter->getLimit($settings['limit'], $relatedMax);

        $mockDataSettings = [
            'fieldName' => $this->model->name,
            'required' => $this->model->required,
            'relatedMin' => $relatedMin,
            'relatedMax' => $relatedMax
        ];

        if (!isset($settings['sources'])) {
            SproutBase::info(Craft::t('sprout-base', 'Unable to generate Mock Data for relations field: {fieldName}. No Sources found.', [
                'fieldName' => $this->model->name
            ]));
            return null;
        }

        $sources = $settings['sources'];

        $groupIds = SproutBase::$app->fieldImporter->getElementGroupIds($sources);
        $attributes = null;

        if ($sources != '*') {
            $attributes = [
                'groupIds' => $groupIds
            ];
        }

        $userElement = new User();

        $elementIds = SproutBase::$app->fieldImporter->getMockRelations($userElement, $attributes, $mockDataSettings);

        return $elementIds;
    }
}
