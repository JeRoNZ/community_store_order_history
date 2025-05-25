<?php
namespace  Concrete\Package\CommunityStoreOrderHistory\Src;
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\OrderList as OL;
use Concrete\Core\Support\Facade\Application;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use Illuminate\Contracts\Container\BindingResolutionException;

class OrderList extends OL
{
	protected $autoSortColumns = array('oID','cID','oDate','oTotal');
    protected $limit;


	/**
	 * @var int|null
	 */
	protected $cID = null;


	/**
	 * @param int|null $cID
	 */
	public function setCustomerID($cID)
	{
		$this->cID = is_numeric($cID) ? (int) $cID : null;
	}



	// Copy of community store Concrete\Package\CommunityStore\Src\CommunityStore\Order\OrderList,
	// but without the fixed sort by
    /**
     * @throws Exception
     * @throws BindingResolutionException
     * @throws \Doctrine\DBAL\Exception
     */
    public function finalizeQuery(QueryBuilder $query)
	{
		$paramcount = 0;

		if (isset($this->search)) {
			$this->query->where('oID like ?')->setParameter($paramcount++, '%'. $this->search. '%');

			$app = Application::getFacadeApplication();
            /**
             * @var Connection $db
             */
			$db = $app->make('database')->connection();
			$matchingOrders = $db->query('SELECT DISTINCT(oID) FROM CommunityStoreOrderAttributeValues csoav INNER JOIN atDefault av ON csoav.avID = av.avID WHERE av.value LIKE ?', array('%'.$this->search.'%'));

			$orderIDs = array();
			while ($value = $matchingOrders->fetchAssociative()) {
				$orderIDs[] = $value['oID'];
			}

			if (!empty($orderIDs)) {
				$this->query->orWhere('o.oID in ('.implode(',', $orderIDs).')');
			}
		}

		if (isset($this->status)) {
			$app = Application::getFacadeApplication();
            /**
             * @var Connection $db
             */
			$db = $app->make('database')->connection();
			$matchingOrders = $db->query('SELECT oID FROM CommunityStoreOrderStatusHistories t1
                                            WHERE oshStatus = ? and
                                                t1.oshDate = (SELECT MAX(t2.oshDate)
                                                             FROM CommunityStoreOrderStatusHistories t2
                                                             WHERE t2.oID = t1.oID)', array($this->status));
			$orderIDs = array();

			while ($value = $matchingOrders->fetchAssociative()) {
				$orderIDs[] = $value['oID'];
			}

			if (!empty($orderIDs)) {
				if ($paramcount > 0) {
					$this->query->addWhere('o.oID in ('.implode(',', $orderIDs).')');
				} else {
					$this->query->where('o.oID in ('.implode(',', $orderIDs).')');
				}
			} else {
				$this->query->where('1 = 0');
			}
		}

		if (isset($this->fromDate)) {
			$this->query->andWhere('DATE(oDate) >= DATE(?)')->setParameter($paramcount++, $this->fromDate);
		}
		if (isset($this->toDate)) {
			$this->query->andWhere('DATE(oDate) <= DATE(?)')->setParameter($paramcount++, $this->toDate);
		}
		if (isset($this->paid)) {
			$this->query->andWhere('o.oPaid is not null');
			$this->query->andWhere('o.oRefunded is null');
		}

		if (isset($this->cancelled)) {
			if ($this->cancelled) {
				$this->query->andWhere('o.oCancelled is not null');
			} else {
				$this->query->andWhere('o.oCancelled is null');
			}
		}

		if (isset($this->shippable)) {
			if ($this->shippable) {
				$this->query->andWhere('o.smName <> ""');
			} else {
				$this->query->andWhere('o.smName = ""');
			}
		}

		if (isset($this->refunded)) {
			if ($this->refunded) {
				$this->query->andWhere('o.oRefunded is not null');
			} else {
				$this->query->andWhere('o.oRefunded is null');
			}
		}

		if (isset($this->refunded)) {
			if ($this->limit > 0) {
				$this->query->setMaxResults($this->limit);
			}
		}

		if (isset($this->externalPaymentRequested) && $this->externalPaymentRequested) {
		} else {
			$this->query->andWhere('o.externalPaymentRequested is null');
		}

		if (isset($this->cID)) {
			$this->query->andWhere('cID = ?')->setParameter($paramcount++, $this->cID);
		}

		if (! $this->sortBy)
			$this->query->orderBy('oID', 'DESC');

		// This is a bit icky - it's not a real field, and the ones below are in fact dates.
		// cancelled and refunded are likely null, paid will be too if the order is not paid for.
		if ($this->sortBy === 'payment') {
			$dir = strtoupper($this->getActiveSortDirection());
			if ($dir !== 'DESC' && $dir !== 'ASC') {
				$dir='ASC';
			}
			$this->query->orderBy('oCancelled '.$dir.',oRefunded '. $dir.',oPaid '.$dir.' ,oTotal', $dir);
		}

		//$query = $this->query;
		/* @var $query QueryBuilder */
		//echo $query->getSQL(); die();

		return $this->query;
	}
}