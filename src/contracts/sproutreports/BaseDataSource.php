<?php
namespace barrelstrength\sproutcore\contracts\sproutreports;

use barrelstrength\sproutcore\SproutCore;
use Craft;
use barrelstrength\sproutcore\records\sproutreports\DataSource;
use barrelstrength\sproutcore\models\sproutreports\Report as ReportModel;
use craft\helpers\UrlHelper;

/**
 * Class BaseDataSource
 *
 * @package Craft
 */
abstract class BaseDataSource
{
	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $pluginName;

	/**
	 * @var string
	 */
	protected $pluginHandle;

	/**
	 * @var ReportModel()
	 */
	protected $report;

	/**
	 * @param string $pluginHandle
	 */
	public function __construct()
	{
		$namespaces = explode('\\', get_class($this));

		$class = basename(get_class($this));

		// get plugin name on second array
		$dataSourceClass = $namespaces[1] . '.' . $class;

		$this->id = strtolower($dataSourceClass);
	}

	/**
	 * Returns a fully qualified string that uniquely identifies the given data source
	 *
	 * @format {plugin}.{source}
	 * 1. {plugin} should be the lower case version of the plugin handle
	 * 3. {source} should be the lower case version of your data source without prefixes or suffixes
	 *
	 * @example
	 * - SproutFormsSubmissionsDataSource   > sproutforms.submissions
	 * - CustomQuery > sproutreports.customquery
	 *
	 * @return string
	 */
	final public function getId()
	{
		return $this->id;
	}

	/**
	 * Set a ReportModel on our data source.
	 *
	 * @param ReportModel|null $report
	 */
	public function setReport(ReportModel $report = null)
	{
		if (is_null($report))
		{
			$report = new ReportModel();
		}

		$this->report = $report;
	}

	/**
	 * Returns the CP URL for the given data source with the option to append to it once composed
	 *
	 * @legend
	 * Breaks apart the data source id and transforms its components into a URL friendly string
	 *
	 * @example
	 * sproutReports.customQuery > sproutreports/customquery
	 * sproutreports.customquery > sproutreports/customquery
	 *
	 * @see getId()
	 *
	 * @param string $append
	 *
	 * @return string
	 */
	public function getUrl($append = null)
	{
		$pluginHandle = Craft::$app->getRequest()->getSegment(1);

		return UrlHelper::cpUrl(sprintf($pluginHandle . '/reports/%s/%s', $this->getId(), ltrim($append, '/')));
	}

	/**
	 * Returns the name of the plugin name that the given data source is bundled with
	 *
	 * @param string $name
	 */
	final public function setPluginName($name)
	{
		$this->pluginName = $name;
	}

	/**
	 * @return string
	 */
	public function getPlugin()
	{
		$pluginHandle = $this->getPluginHandle();

		$plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);

		return $plugin;
	}

	public function getPluginName()
	{
		$plugin = $this->getPlugin();

		return $plugin->name;
	}

	/**
	 * @return string
	 */
	public function getPluginHandle()
	{
		return 'sprout-reports';
	}

	public function getLowerPluginHandle()
	{
		return strtolower($this->getPluginHandle());
	}

	/**
	 * Returns the total count of reports created based on the given data source
	 *
	 * @return [type] [description]
	 */
	final public function getReportCount()
	{
		return SproutCore::$app->reports->getCountByDataSourceId($this->getId());
	}

	/**
	 * Should return a human readable name for your data source
	 *
	 * @return string
	 */
	abstract public function getName();

	/**
	 * Should return an string containing the necessary HTML to capture user input
	 *
	 * @return null|string
	 */
	public function getOptionsHtml()
	{
		return null;
	}

	/**
	 * Should return an array of strings to be used as column headings in display/output
	 *
	 * @return array
	 */
	public function getDefaultLabels(ReportModel &$report, $options = array())
	{
		return array();
	}

	/**
	 * Should return an array of records to use in the report
	 *
	 * @param ReportModel $report
	 *
	 * @return null|array
	 */
	public function getResults(ReportModel &$report, $options = array())
	{
		return array();
	}

	/**
	 * Validate the data sources options
	 *
	 * @return boolean
	 */
	public function validateOptions(array $options = array(), array &$errors = array())
	{
		return true;
	}

	/**
	 * Allows a user to disable a Data Source from displaying in the New Report dropdown
	 *
	 * @return bool|mixed
	 */
	public function allowNew()
	{
		$record = DataSource::findOne(['dataSourceId' => $this->id]);

		// $record->allowNew != null
		if ($record != null)
		{
			return $record->allowNew;
		}

		return true;
	}

	/**
	 * Allow a user to toggle the Allow Html setting.
	 *
	 * @return bool
	 */
	public function isAllowHtmlEditable()
	{
		return false;
	}

	/**
	 * Define the default value for the Allow HTML setting. Setting Allow HTML
	 * to true enables a report to output HTML on the Results page.
	 *
	 * @return bool
	 */
	public function getDefaultAllowHtml()
	{
		return false;
	}
}
