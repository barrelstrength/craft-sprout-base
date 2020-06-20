<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutbase\app\forms\captchas;

use barrelstrength\sproutbase\app\forms\base\Captcha;
use barrelstrength\sproutbase\app\forms\events\OnBeforeValidateEntryEvent;
use Craft;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;

/**
 * Class HoneypotCaptcha
 *
 * @property string $captchaSettingsHtml
 * @property string $name
 * @property string $description
 * @property string $captchaHtml
 */
class HoneypotCaptcha extends Captcha
{
    const HONEYPOT_CAPTCHA_INPUT_KEY = 'sprout-forms-hc';

    /**
     * @var string
     */
    public $honeypotFieldName;

    /**
     * @var string
     */
    public $honeypotScreenReaderMessage;

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Honeypot Captcha';
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return Craft::t('sprout', 'Block form submissions by robots who auto-fill all of your form fields ');
    }

    /**
     * @inheritdoc
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     * @throws \Exception
     */
    public function getCaptchaSettingsHtml(): string
    {
        $settings = $this->getSettings();

        $html = Craft::$app->getView()->renderTemplate('sprout/forms/_components/captchas/honeypot/settings', [
            'captcha' => $this,
            'settings' => $settings,
            'defaultFieldName' => self::HONEYPOT_CAPTCHA_INPUT_KEY,
        ]);

        return $html;
    }

    /**
     * @return string
     */
    public function getCaptchaHtml(): string
    {
        $this->honeypotFieldName = $this->getHoneypotFieldName();
        $this->honeypotScreenReaderMessage = $this->getHoneypotScreenReaderMessage();

        $uniqueId = uniqid($this->honeypotFieldName, false);

        $html = '
    <div id="'.$uniqueId.'_wrapper" style="display:none;">
        <label for="'.$uniqueId.'">'.$this->honeypotScreenReaderMessage.'</label>
        <input type="text" id="'.$uniqueId.'" name="'.$uniqueId.'" value="" />
    </div>';

        return $html;
    }

    /**
     * @param OnBeforeValidateEntryEvent $event
     *
     * @return bool
     */
    public function verifySubmission(OnBeforeValidateEntryEvent $event): bool
    {
        $honeypotFieldName = $this->getHoneypotFieldName();

        $honeypotValue = null;

        foreach ($_POST as $key => $value) {
            if (strpos($key, $honeypotFieldName) === 0) {
                $honeypotValue = $_POST[$key];
                break;
            }
        }

        // The honeypot field must be left blank
        if ($honeypotValue) {
            $errorMessage = 'Honeypot must be blank. Value submitted: '.$honeypotValue;
            Craft::error($errorMessage, __METHOD__);

            $this->addError(self::CAPTCHA_ERRORS_KEY, $errorMessage);

            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getHoneypotFieldName(): string
    {
        $settings = $this->getSettings();

        return $settings['honeypotFieldName'];
    }

    /**
     * @return string
     */
    public function getHoneypotScreenReaderMessage(): string
    {
        $settings = $this->getSettings();

        return $settings['honeypotScreenReaderMessage'] ?? Craft::t('sprout', 'Leave this field blank');
    }
}



