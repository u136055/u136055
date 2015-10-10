<?php
namespace Bitrix\Crm\Widget\Data;

use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\DB\SqlExpression;

use Bitrix\Crm\History\Entity\DealStageHistoryTable;
use Bitrix\Crm\Statistics\Entity\DealActivityStatisticsTable;
use Bitrix\Crm\Statistics\Entity\DealInvoiceStatisticsTable;
use Bitrix\Crm\Widget\Filter;

class DealInWork extends DealDataSource
{
	const TYPE_NAME = 'DEAL_IN_WORK';
	const GROUP_BY_DATE = 'DATE';
	private static $messagesLoaded = false;
	/**
	* @return string
	*/
	public function getTypeName()
	{
		return self::TYPE_NAME;
	}
	/**
	 * @return array
	 */
	public function getList(array $params)
	{
		/** @var Filter $filter */
		$filter = isset($params['filter']) ? $params['filter'] : null;
		if(!($filter instanceof Filter))
		{
			throw new Main\ObjectNotFoundException("The 'filter' is not found in params.");
		}

		/** @var array $select */
		$select = isset($params['select']) && is_array($params['select']) ? $params['select'] : array();
		$name = '';
		if(!empty($select))
		{
			$selectItem = $select[0];
			if(isset($selectItem['name']))
			{
				$name = $selectItem['name'];
			}
		}

		if($name === '')
		{
			$name = 'COUNT';
		}

		$group = isset($params['group']) ? strtoupper($params['group']) : '';
		if($group !== '' && $group !== self::GROUP_BY_DATE)
		{
			$group = '';
		}
		$enableGroupByDate = $group !== '';

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$queries = array(
			self::prepareHistoryQuery($periodStartDate, $periodEndDate, null, $enableGroupByDate),
			self::prepareActivityQuery($periodStartDate, $periodEndDate, null, $enableGroupByDate),
			self::prepareInvoiceQuery($periodStartDate, $periodEndDate, null, $enableGroupByDate)
		);

		$map = array();
		foreach($queries as $query)
		{
			/** @var  Query $query*/
			$dbResult = $query->exec();
			if($enableGroupByDate)
			{
				while($ary = $dbResult->fetch())
				{
					/** @var Date $date */
					$date =  $ary['DATE'];
					$key = $date->format('Y-m-d');

					if($key === '9999-12-31')
					{
						//Skip empty dates
						continue;
					}

					if(!isset($map[$key]))
					{
						$map[$key] = array();
					}

					$ownerID =  $ary['OWNER_ID'];
					if(!isset($map[$key][$ownerID]))
					{
						$map[$key][$ownerID] = true;
					}
				}
			}
			else
			{
				while($ary = $dbResult->fetch())
				{
					$ownerID =  $ary['OWNER_ID'];
					if(!isset($map[$ownerID]))
					{
						$map[$ownerID] = true;
					}
				}
			}
		}

		$results = array();
		if($enableGroupByDate)
		{
			foreach($map as $k => $v)
			{
				$results[] = array('DATE' => $k, $name => count($v));
			}
		}
		else
		{
			$results[] = array($name => count($map));
		}
		return $results;
	}
	/**
	 * @return array Array of arrays
	 */
	public static function getPresets()
	{
		self::includeModuleFile();
		return array(
			array(
				'title' => GetMessage('CRM_DEAL_IN_WORK_PRESET_OVERALL_COUNT'),
				'name' => self::TYPE_NAME.'::OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'COUNT'),
				'context' => DataContext::ENTITY
			)
		);
	}
	protected static function includeModuleFile()
	{
		if(self::$messagesLoaded)
		{
			return;
		}

		Main\Localization\Loc::loadMessages(__FILE__);
		self::$messagesLoaded = true;
	}
	/**
	 * @return Query
	 */
	protected static function prepareHistoryQuery($startDate, $endDate, $responsibleIDs = null, $groupByDate = true)
	{
		$query = new Query(DealStageHistoryTable::getEntity());
		$query->addSelect('OWNER_ID');
		$query->addFilter('=IS_LOST', false);
		$query->addFilter('>=CREATED_DATE', $startDate);
		$query->addFilter('<=CREATED_DATE', $endDate);
		$query->addGroup('OWNER_ID');

		if(is_array($responsibleIDs) && !empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		if($groupByDate)
		{
			$query->addSelect('CREATED_DATE', 'DATE');
			$query->addGroup('CREATED_DATE');
			$query->addOrder('CREATED_DATE', 'ASC');
		}

		return $query;
	}
	/**
	 * @return Query
	 */
	protected static function prepareActivityQuery($startDate, $endDate, $responsibleIDs = null, $groupByDate = true)
	{
		$query = new Query(DealActivityStatisticsTable::getEntity());
		$query->addSelect('OWNER_ID');
		$query->addFilter('=IS_LOST', false);
		$query->addFilter('>=DEADLINE_DATE', $startDate);
		$query->addFilter('<=DEADLINE_DATE', $endDate);
		$query->addGroup('OWNER_ID');

		if(is_array($responsibleIDs) && !empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		if($groupByDate)
		{
			$query->addSelect('DEADLINE_DATE', 'DATE');
			$query->addGroup('DEADLINE_DATE');
			$query->addOrder('DEADLINE_DATE', 'ASC');
		}

		return $query;
	}
	/**
	 * @return Query
	 */
	protected static function prepareInvoiceQuery($startDate, $endDate, $responsibleIDs = null, $groupByDate = true)
	{
		$query = new Query(DealInvoiceStatisticsTable::getEntity());
		$query->addSelect('OWNER_ID');
		$query->addFilter('=IS_LOST', false);
		$query->addFilter('>=CREATED_DATE', $startDate);
		$query->addFilter('<=CREATED_DATE', $endDate);
		$query->addGroup('OWNER_ID');

		if(is_array($responsibleIDs) && !empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		if($groupByDate)
		{
			$query->addSelect('CREATED_DATE', 'DATE');
			$query->addGroup('CREATED_DATE');
			$query->addOrder('CREATED_DATE', 'ASC');
		}

		return $query;
	}
	/** @return array */
	public function prepareEntityListFilter(array $filterParams)
	{
		$filter = self::internalizeFilter($filterParams);

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$responsibleIDs = $filter->getResponsibleIDs();

		$queries = array(
			self::prepareHistoryQuery($periodStartDate, $periodEndDate, $responsibleIDs, false)->getQuery(),
			self::prepareActivityQuery($periodStartDate, $periodEndDate, $responsibleIDs, false)->getQuery(),
			self::prepareInvoiceQuery($periodStartDate, $periodEndDate, $responsibleIDs, false)->getQuery()
		);

		return array(
			'__JOINS' => array(
				array(
					'TYPE' => 'INNER',
					'SQL' => 'INNER JOIN('.implode("\nUNION\n", $queries).') DS ON DS.OWNER_ID = L.ID'
				)
			)
		);
	}
}