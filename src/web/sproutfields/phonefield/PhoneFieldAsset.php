<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com/
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   http://sprout.barrelstrengthdesign.com/license
 */

namespace barrelstrength\sproutcore\web\sproutfields\phonefield;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class PhoneFieldAsset extends AssetBundle
{
	public function init()
	{
		// define the path that your publishable resources live
		$this->sourcePath = '@sproutcore/web/sproutfields/resources';

		// define the dependencies
		$this->depends = [
			CpAsset::class,
		];

		// define the relative path to CSS/JS files that should be registered with the page
		// when this asset bundle is registered
		$this->js = [
			'js/inputmask.js',
			'js/jquery.inputmask.js',
			'js/PhoneInputMask.js',
			'js/PhoneInputMask.js',
		];

		$this->css = [
			'css/sproutfields.css',
		];

		parent::init();
	}
}