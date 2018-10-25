<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com/
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   http://sprout.barrelstrengthdesign.com/license
 */

namespace barrelstrength\sproutbase\app\fields\base;

use barrelstrength\sproutbase\app\fields\helpers\AddressHelper;
use craft\base\ElementInterface;
use Craft;
use barrelstrength\sproutbase\SproutBase;
use craft\base\Field;
use barrelstrength\sproutbase\app\fields\models\Address as AddressModel;
use CommerceGuys\Intl\Country\CountryRepository;

trait AddressFieldTrait
{
    /**
     * @return null|string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function getSettingsHtml()
    {
        $addressHelper = $this->addressHelper;
        /**
         * @var $addressHelper AddressHelper
         */
        $countries = $addressHelper->getCountries();
        $settings = $this->getSettings();

        if ($settings !== null && !isset($settings['defaultCountry']))
        {
            $settings['defaultCountry'] = 'US';
            $settings['country'] = 'US';
        }

        return Craft::$app->getView()->renderTemplate(
            'sprout-base-fields/_components/fields/formfields/address/settings',
            [
                'settings' => $settings,
                'countries' => $countries
            ]
        );
    }
    /**
     * @param                       $value
     * @param ElementInterface|null $element
     *
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        /** @var $this Field */
        $name = $this->handle;
        $inputId = Craft::$app->getView()->formatInputId($name);
        $namespaceInputName = Craft::$app->getView()->namespaceInputName($inputId);
        $namespaceInputId = Craft::$app->getView()->namespaceInputId($inputId);

        /** @var $this Field */
        $settings = $this->getSettings();

        $defaultCountryCode = $settings['defaultCountry'] ?? null;
        $hideCountryDropdown = $settings['hideCountryDropdown'] ?? null;

        $addressId = null;

        if (is_object($value)) {
            $addressId = $value->id;
        } elseif (is_array($value)) {
            $addressId = $value['id'];
        }

        $addressInfoModel = SproutBase::$app->addressField->getAddressById($addressId);

        $countryCode = $addressInfoModel->countryCode ?? $defaultCountryCode;

        $addressHelper = $this->addressHelper;

        /**
         * @var $addressHelper AddressHelper
         */
        $addressHelper->setParams($countryCode, $name, $addressInfoModel);

        $addressFormat = "";
        if ($addressId) {
            $addressFormat = $addressHelper->getAddressWithFormat($addressInfoModel);
        }

        $countryInput = $addressHelper->countryInput($hideCountryDropdown);

        $addressForm = $addressHelper->getAddressFormHtml();

        return Craft::$app->getView()->renderTemplate(
            'sprout-base-fields/_components/fields/formfields/address/input',
            [
                'namespaceInputId' => $namespaceInputId,
                'namespaceInputName' => $namespaceInputName,
                'field' => $this,
                'addressId' => $addressId,
                'defaultCountryCode' => $defaultCountryCode,
                'addressFormat' => $addressFormat,
                'countryInput' => $countryInput,
                'addressForm' => $addressForm,
                'hideCountryDropdown' => $hideCountryDropdown
            ]
        );
    }

    /**
     * Prepare our Address for use as an AddressModel
     * @param                       $value
     * @param ElementInterface|null $element
     *
     * @return array|AddressModel|int|mixed|string
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        $addressModel = new AddressModel();

        // Numeric value when retrieved from db
        if (is_numeric($value)) {
            $addressModel = SproutBase::$app->addressField->getAddressById($value);
        }

        // Array value from post data
        if (is_array($value)) {

            if (!empty($value['delete'])) {
                SproutBase::$app->addressField->deleteAddressById($value['id']);
            } else {
                $value['fieldId'] = $this->id ?? null;
                $addressModel = new AddressModel();
                $addressModel->setAttributes($value, false);
            }
        }

        // Adds country property that return country name
        if ($addressModel->countryCode) {
            $countryRepository = new CountryRepository();

            $country = $countryRepository->get($addressModel->countryCode);
            $addressModel->country = $country->getName();
        }

        // return null when clearing address to save null value on content table
        if (!$addressModel->validate(null, false)) {
            return $value;
        }

        return $addressModel;
    }

    /**
     *
     * Prepare the field value for the database.
     *
     * We store the Address ID in the content column.
     *
     * @param mixed                 $value
     * @param ElementInterface|null $element
     *
     * @return array|bool|mixed|null|string
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        if (empty($value)) {
            return false;
        }

        $addressId = null;

        // When loading a Field Layout with an Address Field
        if (is_object($value) && get_class($value) == AddressModel::class) {
            $addressId = $value->id;
        }

        // For the ResaveElements task $value is the id
        if (is_int($value)) {
            $addressId = $value;
        }

        // When the field is saved by post request the id an attribute on $value
        if (isset($value['id']) && $value['id']) {
            $addressId = $value['id'];
        }

        // Save the addressId in the content table
        return $addressId;
    }

    /**
     * Save our Address Field a first time and assign the Address Record ID back to the Address field model
     * We'll save our Address Field a second time in afterElementSave to capture the Element ID for new entries.
     *
     * @param ElementInterface $element
     * @param bool             $isNew
     *
     * @return bool
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    public function beforeElementSave(ElementInterface $element, bool $isNew) : bool
    {
        $address = $element->getFieldValue($this->handle);

        if ($address instanceof AddressModel)
        {
            $address->elementId = $element->id;
            $address->siteId = $element->siteId;
            $address->fieldId = $this->id;

            SproutBase::$app->addressField->saveAddress($address);
        }

        return true;
    }

    /**
     * Save our Address Field a second time for New Entries to ensure we have the Element ID.
     *
     * @param ElementInterface $element
     * @param bool             $isNew
     *
     * @return bool|void
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    public function afterElementSave(ElementInterface $element, bool $isNew)
    {
        if ($isNew)
        {
            $address = $element->getFieldValue($this->handle);

            if ($address instanceof AddressModel)
            {
                $address->elementId = $element->id;
                SproutBase::$app->addressField->saveAddress($address);
            }
        }
    }
}