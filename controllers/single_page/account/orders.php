<?php

namespace Concrete\Package\CommunityStoreOrderHistory\Controller\SinglePage\Account;
defined('C5_EXECUTE') or die("Access Denied.");
use Concrete\Package\CommunityStoreOrderHistory\Src\OrderList as StoreOrderList;;
use Concrete\Core\Page\Controller\PageController;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\OrderStatus\OrderStatus as StoreOrderStatus;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order as StoreOrder;
use Concrete\Package\CommunityStore\Src\Attribute\Key\StoreOrderKey as StoreOrderKey;
#use Config;
use Concrete\Core\User\User;
use Illuminate\Filesystem\Filesystem;
use View;

defined("C5_EXECUTE") or die("Access Denied.");

class Orders extends PageController {
	public function view ($status = '') {
		$orderList = new StoreOrderList();

		$u = new User();
		if (!$u || ! $u->isLoggedIn()) {
			$this->redirect('/');
		}

		if ($this->get('keywords')) {
			$orderList->setSearch($this->get('keywords'));
		}

		if ($status) {
			$orderList->setStatus($status);
		}

		$orderList->setCustomerID($u->getUserID());

		$orderList->setItemsPerPage(20);

		$paginator = $orderList->getPagination();
		$pagination = $paginator->renderDefaultView();
		$this->set('orderList', $paginator->getCurrentPageResults());
		$this->set('orderListObject', $orderList);
		$this->set('pagination', $pagination);
		$this->set('paginator', $paginator);
		$this->set('orderStatuses', StoreOrderStatus::getList());
		$this->set('status', $status);
		$this->set('statuses', StoreOrderStatus::getAll());

		$this->set('pageTitle', t('Orders'));
	}

	public function order ($oID = false) {
		if (!$oID)
			$this->redirect('/');

		if (!ctype_digit($oID))
			$this->redirect('/');

		$u = new User();
		if (!$u || ! $u->isLoggedIn())
			$this->redirect('/');

		$order = StoreOrder::getByID($oID);
		/* @var $order \Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order */

		if ($order) {
			// Thou shalt not covert thy neighbour's order
			if ($u->getUserID() != $order->getCustomerID())
				$this->redirect('/');

			$this->set("order", $order);
			$this->set('orderStatuses', StoreOrderStatus::getList());
			$orderChoicesAttList = StoreOrderKey::getAttributeListBySet('order_choices');
			if (is_array($orderChoicesAttList) && !empty($orderChoicesAttList)) {
				$this->set("orderChoicesAttList", $orderChoicesAttList);
			} else {
				$this->set("orderChoicesAttList", array());
			}
			##$this->requireAsset('javascript', 'communityStoreFunctions');
		} else {
			$this->redirect('/');
		}

		$this->set('pageTitle', t("Order #") . $order->getOrderID());
	}

	public function slip() {
		$u = new User();
		if (!$u || ! $u->isLoggedIn()) {
			$this->redirect('/');
		}

		$o = StoreOrder::getByID($this->post('oID'));
		if (! $o) {
			$this->redirect('/');
		}

		// Thou shalt not covert thy neighbour's order
		if ($o->getCustomerID() != $u->getUserID()) {
			$this->redirect('/');
		}

		$orderChoicesAttList = StoreOrderKey::getAttributeListBySet('order_choices', $u);
		$orderChoicesEnabled = count($orderChoicesAttList)? true : false;

		if (Filesystem::exists(DIR_BASE."/application/elements/customer_order_slip.php")) {
			View::element("customer_order_slip", array('order' => $o, 'orderChoicesEnabled' => $orderChoicesEnabled, 'orderChoicesAttList' => $orderChoicesAttList));
		} else {
			View::element("customer_order_slip", array('order' => $o, 'orderChoicesEnabled' => $orderChoicesEnabled, 'orderChoicesAttList' => $orderChoicesAttList), "community_store_order_history");
		}
		die(); // this is a non-themed page
	}


}