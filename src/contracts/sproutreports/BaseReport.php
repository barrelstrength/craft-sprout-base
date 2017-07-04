<?php
namespace barrelstrength\sproutcore\contracts\sproutreports;

/**
 * Class BaseReport
 *
 * @package barrelstrength\sproutcore\contracts\sproutreports
 */
abstract class BaseReport
{
	/**
	 * @return string
	 */
	abstract public function getName();

	/**
	 * @return string
	 */
	abstract public function getHandle();

	/**
	 * @return string
	 */
	abstract public function getGroupName();

	/**
	 * @return string
	 */
	abstract public function getDescription();

	/**
	 * @return array
	 */
	abstract public function getOptions();

	/**
	 * @return BaseDataSource
	 */
	abstract public function getDataSource();
}
