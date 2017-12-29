<?php
namespace Concrete\Package\CommunityStoreOrderHistory;
defined('C5_EXECUTE') or die("Access Denied.");
use Package;
use Core;
use Concrete\Core\Page\Page;
use \Concrete\Core\Page\Single as SinglePage;

class Controller extends Package {
	protected $pkgHandle = 'community_store_order_history';
	protected $appVersionRequired = '5.7.5';
	protected $pkgVersion = '0.1';

	protected $singlePages = array(
		'/account/orders',
	);

	public function getPackageDescription () {
		return t("Community store order history in user profile");
	}

	public function getPackageName () {
		return t("Community Store Order History");
	}

	public function install () {
		$installed = Package::getInstalledHandles();
		if (!(is_array($installed) && in_array('community_store', $installed))) {
			throw new ErrorException(t('This package requires that Community Store be installed'));
		} else {
			$pkg = parent::install();
			$this->singlePages($pkg);
		}
	}

	public function upgrade () {
		parent::upgrade();
		$pkg = Package::getByHandle($this->pkgHandle);
		$this->singlePages($pkg);

	}

	private function singlePages ($pkg) {
		foreach ($this->singlePages as $path) {
			$page = Page::getByPath($path);
			if ($page->getCollectionID() <= 0) {
				$page = SinglePage::add($path, $pkg);
			} else {
				SinglePage::refresh($page);
			}
			$page->update(array('cDescription' => 'Order History'));
		}
	}
}