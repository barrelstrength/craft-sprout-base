<?php
namespace barrelstrength\sproutcore\services;

use barrelstrength\sproutcore\services\sproutreports\DataSources;
use barrelstrength\sproutcore\services\sproutreports\Exports;
use barrelstrength\sproutcore\migrations\sproutreports\Migration;
use barrelstrength\sproutcore\services\sproutreports\Reports;
use barrelstrength\sproutcore\services\sproutfields\Utilities;
use barrelstrength\sproutcore\services\sproutfields\Link;
use barrelstrength\sproutcore\services\sproutfields\Phone;
use barrelstrength\sproutcore\services\sproutfields\RegularExpression;
use barrelstrength\sproutcore\services\sproutfields\Email;
use barrelstrength\sproutcore\services\sproutfields\EmailSelect;
use barrelstrength\sproutcore\services\Address;
use craft\base\Component;

class App extends Component
{
	public $phone;
	public $utilities;
	public $link;
	public $email;
	public $regularExpression;
	public $emailSelect;
	public $exports;
	public $address;

	/**
	 * @var Reports
	 */
	public $reports;

	/**
	 * @var Migration
	 */
	public $reportsMigration;

	/**
	 * @var DataSources
	 */
	public $dataSources;

	public function init()
	{
		$this->phone             = new Phone();
		$this->utilities         = new Utilities();
		$this->link              = new Link();
		$this->email             = new Email();
		$this->regularExpression = new RegularExpression();
		$this->emailSelect       = new EmailSelect();
		$this->reports           = new Reports();
		$this->dataSources       = new DataSources();
		$this->exports           = new Exports();
		$this->reportsMigration  = new Migration();
		$this->address           = new Address();
	}
}