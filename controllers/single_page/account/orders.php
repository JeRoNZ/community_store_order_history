<?php

namespace Concrete\Package\CommunityStoreOrderHistory\Controller\SinglePage\Account;
defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Core\Http\Response;
use Concrete\Core\Routing\RedirectResponse;
use Concrete\Package\CommunityStore\Entity\Attribute\Key\StoreOrderKey;
use Concrete\Package\CommunityStoreOrderHistory\Src\OrderList as StoreOrderList;;
use Concrete\Core\Page\Controller\PageController;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\OrderStatus\OrderStatus as StoreOrderStatus;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order as StoreOrder;
#use Config;
use Concrete\Core\User\User;
use Illuminate\Filesystem\Filesystem;
use View;
use Request;
use Package;

class Orders extends PageController {
	public function view ($status = '') {
		$orderList = new StoreOrderList();

		$u = new User();
		if (!$u || ! $u->isRegistered()) {
			return new RedirectResponse('/');
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
		if (!$oID) {
			return new RedirectResponse('/');
		}

		if (!ctype_digit($oID)) {
			return new RedirectResponse('/');
		}

		$u = new User();
		if (!$u || ! $u->isRegistered()) {
			return new RedirectResponse('/');
		}

		$order = StoreOrder::getByID($oID);
		/* @var $order StoreOrder */

		if ($order) {
			// Thou shalt not covert thy neighbour's order
			if ($u->getUserID() != $order->getCustomerID()) {
				return new RedirectResponse('/');
			}

			$this->set('order', $order);
			$this->set('orderStatuses', StoreOrderStatus::getList());
			$orderChoicesAttList = StoreOrderKey::getAttributeListBySet('order_choices');
			if (is_array($orderChoicesAttList) && !empty($orderChoicesAttList)) {
				$this->set('orderChoicesAttList', $orderChoicesAttList);
			} else {
				$this->set('orderChoicesAttList', array());
			}
		} else {
			return new RedirectResponse('/');
		}

		$this->set('pageTitle', t('Order #') . $order->getOrderID());
	}

	public function slip() {
		$u = new User();
		if (!$u || ! $u->isRegistered()) {
			return new RedirectResponse('/');
		}

// If we're not a post request, get out otherwise we get an exception
		if (! Request::isPost()) {
			return new RedirectResponse('/');
		}

		/** @var StoreOrder $o */
		$o = StoreOrder::getByID($this->post('oID'));
		if (! $o) {
			return new RedirectResponse('/');
		}

		// Thou shalt not covert thy neighbour's order
		if ($o->getCustomerID() != $u->getUserID()) {
			return new RedirectResponse('/');
		}

		$orderChoicesAttList = StoreOrderKey::getAttributeListBySet('order_choices', $u);
		$orderChoicesEnabled = count($orderChoicesAttList)? true : false;

		$filesystem = new \Illuminate\Filesystem\Filesystem();

		ob_start();
		if ($filesystem->exists(DIR_BASE. '/application/elements/customer_order_slip.php')) {
			View::element('customer_order_slip', array('order' => $o, 'orderChoicesEnabled' => $orderChoicesEnabled, 'orderChoicesAttList' => $orderChoicesAttList));
		} else {
			View::element('customer_order_slip', array('order' => $o, 'orderChoicesEnabled' => $orderChoicesEnabled, 'orderChoicesAttList' => $orderChoicesAttList), 'community_store_order_history');
		}
		return new Response(ob_get_clean());
	}


}
