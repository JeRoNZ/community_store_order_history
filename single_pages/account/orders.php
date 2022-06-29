<?php
defined('C5_EXECUTE') or die("Access Denied.");
$dh = Core::make('helper/date');
use \Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Price as Price;
use Concrete\Core\Page\Page;

$th = Core::make('helper/text');
/* @var $th \Concrete\Core\Utility\Service\Text */
?>

<?php if ($controller->getTask() == 'order'){
	$form = Core::make('helper/form');

	?>

	<div class="ccm-dashboard-header-buttons">
		<form action="<?=URL::to('/account/orders/slip')?>" method="post" target="_blank">
			<input type="hidden" name="oID" value="<?= $order->getOrderID()?>">
			<button class="btn btn-primary"><?= t("Print Order Slip")?></button>
		</form>
	</div>

	<div class="row">
		<div class="col-sm-8">
			<p><strong><?= t('Order placed'); ?>:</strong> <?= $dh->formatDateTime($order->getOrderDate())?></p>
		</div>
		<div class="col-sm-4">
			<?php
			$refunded = $order->getRefunded();
			$paid = $order->getPaid();
			$cancelled = $order->getCancelled();

			if ($cancelled) {
				echo '<p class="alert alert-danger text-center"><strong>' . t('Cancelled') . '</strong></p>';
			} else {
				if ($refunded) {
					$refundreason = $order->getRefundReason();
					echo '<p class="alert alert-warning text-center"><strong>' . t('Refunded') . ($refundreason ? ' - ' .$refundreason : '') . '</strong></p>';
				} elseif ($paid) {
					echo '<p class="alert alert-success text-center"><strong>' . t('Paid') . '</strong></p>';
				} elseif ($order->getTotal() > 0) {
					echo '<p class="alert alert-danger text-center"><strong>' . t('Unpaid') . '</strong></p>';
				} else {
					echo '<p class="alert alert-default text-center"><strong>' . t('Free Order') . '</strong></p>';
				}
			}
			?>
		</div>
	</div>

	<fieldset>
		<legend><?= t("Customer Details")?></legend>

		<div class="row">
			<div class="col-sm-4">
				<?php $orderemail = $order->getAttribute("email"); ?>

				<h4><?= t("Name")?></h4>
				<p><?= $order->getAttribute("billing_first_name"). " " . $order->getAttribute("billing_last_name")?></p>

				<?php if ($orderemail) { ?>
					<h4><?= t("Email")?></h4>
					<p><a href="mailto:<?= $order->getAttribute("email"); ?>"><?= $order->getAttribute("email"); ?></a></p>
				<?php } ?>

				<?php
				$phone = $order->getAttribute("billing_phone");
				if ($phone) {
					?>
					<h4><?= t('Phone'); ?></h4>
					<p><?= $phone; ?></p>
				<?php } ?>

				<?php if (Config::get('community_store.vat_number')) { ?>
					<?php $vat_number = $order->getAttribute('vat_number'); ?>
					<h4><?= t('VAT Number')?></h4>
					<p><?=$vat_number?></p>
				<?php } ?>
			</div>

			<div class="col-sm-4">
				<h4><?= t("Billing Address")?></h4>
				<p>
					<?= $order->getAttribute('billing_first_name'). " " . $order->getAttribute('billing_last_name')?><br>
					<?php
					$billingaddress = $order->getAttributeValueObject('billing_address');

					if ($billingaddress) {
						echo $billingaddress->getValue('displaySanitized', 'display');
					}
					?>
				</p>
			</div>
			<?php if ($order->isShippable()) { ?>
				<div class="col-sm-4">
					<?php if ($order->getAttribute('shipping_address')->address1) { ?>
						<h4><?= t('Shipping Address')?></h4>
						<p>
							<?= $order->getAttribute('shipping_first_name'). " " . $order->getAttribute('shipping_last_name')?><br>
							<?php
							$shippingaddress = $order->getAttributeValueObject('shipping_address');

							if ($shippingaddress) {
								echo $shippingaddress->getValue('displaySanitized', 'display');
							}
							?>
						</p>
					<?php } ?>
				</div>
			<?php } ?>
		</div>
	</fieldset>

	<fieldset>
		<legend><?= t('Order Items')?></legend>
		<table class="table table-striped">
			<thead>
			<tr>
				<th><strong><?= t('Product Name')?></strong></th>
				<th><?= t('Product Options')?></th>
				<th><?= t('Price')?></th>
				<th><?= t('Quantity')?></th>
				<th><?= t('Subtotal')?></th>
			</tr>
			</thead>
			<tbody>
			<?php
			$items = $order->getOrderItems();

			if($items){
				foreach($items as $item){
					?>
					<tr>
						<td><?= $item->getProductName()?>
							<?php if ($sku = $item->getSKU()) {
								echo '(' .  $sku . ')';
							} ?>
						</td>
						<td>
							<?php
							$options = $item->getProductOptions();
							if($options){
								echo "<ul class='list-unstyled'>";
								foreach($options as $option){
									echo "<li>";
									echo '<strong>' .$option['oioKey']. ': </strong>';
									echo ($option['oioValue'] ?: '<em>' .t('None') . '</em>');
									echo "</li>";
								}
								echo "</ul>";
							}
							?>
						</td>
						<td><?=Price::format($item->getPricePaid())?></td>
						<td><?= $item->getQty()?></td>
						<td><?=Price::format($item->getSubTotal())?></td>
					</tr>
				<?php
				}
			}
			?>
			</tbody>
			<tfoot>
			<tr>
				<td colspan="4" class="text-right"><strong><?= t('Items Subtotal')?>:</strong></td>
				<td colspan="1" ><?=Price::format($order->getSubTotal())?></td>
			</tr>
			</tfoot>
		</table>


		<?php $applieddiscounts = $order->getAppliedDiscounts();

		if (!empty($applieddiscounts)) { ?>
			<h4><?= t('Discounts Applied')?></h4>
			<hr />
			<table class="table table-striped">
				<thead>
				<tr>
					<th><strong><?= t('Name')?></strong></th>
					<th><?= t('Displayed')?></th>
					<th><?= t('Discount')?></th>
					<th><?= t('Amount')?></th>
					<th><?= t('Triggered')?></th>
				</tr>

				</thead>
				<tbody>
				<?php foreach($applieddiscounts as $discount) { ?>
					<tr>
						<td><?= h($discount['odName']); ?></td>
						<td><?= h($discount['odDisplay']); ?></td>
						<td>
							<?php
							$deducttype = $discount['odDeductType'];
							$deductfrom = $discount['odDeductFrom'];

							$discountRuleDeduct = $deductfrom;

							if ($deducttype === 'percentage') {
								$discountRuleDeduct = t('from products');
							}

							if ($deducttype === 'value_all') {
								$discountRuleDeduct = t('from each product');
							}

							if ($deducttype === 'percentage' && $deductfrom === 'shipping' ) {
								$discountRuleDeduct = t('from shipping');
							}

							if (($deducttype === 'value_all' || $deducttype === 'value') && $deductfrom === 'shipping') {
								$discountRuleDeduct = t('from shipping');
							}

							if ($deducttype === 'fixed' ) {
								$discountRuleDeduct = t('set as price');
							}

							if ($deducttype === 'fixed' && $deductfrom === 'shipping') {
								$discountRuleDeduct = t('set as price for shipping');
							}
							?>
							<?= $discountRuleDeduct; ?>
						</td>
						<td><?= ($discount['odValue'] > 0 ? Price::format($discount['odValue']) : $discount['odPercentage'] . '%' ); ?></td>
						<td><?= ($discount['odCode'] ? t('by code'). ' <em>' .$discount['odCode'] .'</em>': t('Automatically') ); ?></td>
					</tr>
				<?php } ?>

				</tbody>
			</table>

		<?php } ?>

		<?php if ($order->isShippable()) { ?>
			<p>
				<strong><?= t("Shipping")?>: </strong><?=Price::format($order->getShippingTotal())?>
			</p>
		<?php } ?>

		<?php $taxes = $order->getTaxes();

		if (!empty($taxes)) { ?>
			<p>
				<?php foreach ($order->getTaxes() as $tax) { ?>
					<strong><?= $tax['label'] ?>
						:</strong> <?= Price::format($tax['amount'] ?: $tax['amountIncluded']) ?><br>
				<?php } ?>
			</p>
		<?php } ?>

		<p>
			<strong><?= t('Grand Total') ?>: </strong><?= Price::format($order->getTotal()) ?>
		</p>
		<p>
			<strong><?= t('Payment Method') ?>: </strong><?= t($order->getPaymentMethodName()) ?><br>
			<?php $transactionReference = $order->getTransactionReference();
			if ($transactionReference) { ?>
				<strong><?= t('Transaction Reference') ?>: </strong><?= $transactionReference ?><br>
			<?php } ?>
		</p>

		<?php if ($order->isShippable()) { ?>
			<br /><p>
				<strong><?= t('Shipping Method') ?>: </strong><?= $order->getShippingMethodName() ?>
			</p>



			<?php
			$trackingURL = $order->getTrackingURL();
			$trackingCode = $order->getTrackingCode();
			$carrier = $order->getCarrier();

			if ($carrier) { ?>
				<p><strong><?= t('Carrier') ?>: </strong><?= $carrier ?></p>
			<?php }

			if ($trackingCode) { ?>
				<p><strong><?= t('Tracking Code') ?>: </strong><?= $trackingCode ?> </p>
			<?php }

			if ($trackingURL) { ?>
				<p><a target="_blank" href="<?= $trackingURL; ?>"><?= t('View shipment tracking');?></a></p>
			<?php } ?>

			<?php
			$shippingInstructions = $order->getShippingInstructions();
			if ($shippingInstructions) { ?>
				<p><strong><?= t('Delivery Instructions') ?>: </strong><?= $shippingInstructions ?></p>
			<?php } ?>

		<?php } ?>

		<div class="row">
			<?php if (!empty($orderChoicesAttList)) { ?>
				<div class="col-sm-12">
					<h4><?= t('Other Choices')?></h4>
					<?php foreach ($orderChoicesAttList as $ak) {
						$attValue = $order->getAttributeValueObject(StoreOrderKey::getByHandle($ak->getAttributeKeyHandle()));
						if ($attValue) {  ?>
							<label><?= $ak->getAttributeKeyDisplayName()?></label>
							<p><?= str_replace("\r\n", "<br>", $attValue->getValue('displaySanitized', 'display')); ?></p>
						<?php } ?>
					<?php } ?>
				</div>
			<?php } ?>
		</div>


	</fieldset>
	<br />

	<div class="form-actions">
		<a href="<?php echo URL::to('/account/orders')?>" class="btn btn-default" /><?php echo t('Back to Orders')?></a>
	</div>


<?php } else { ?>

	<div>
		<form role="form" class="form-inline ccm-search-fields">
			<div class="ccm-search-fields-row">
				<?php if($statuses){?>
					<ul id="group-filters" class="nav nav-pills">
						<li <?= (!$status ? 'class="active"' : ''); ?>><a href="<?= URL::to('/account/orders/')?>"><?= t('All Statuses')?></a></li>

						<?php foreach($statuses as $statusoption){ ?>
							<li <?= ($status == $statusoption->getHandle() ? 'class="active"' : ''); ?>><a href="<?= URL::to('/account/orders/', $statusoption->getHandle())?>"><?= t($statusoption->getName());?></a></li>
						<?php } ?>
					</ul>
				<?php } ?>
			</div>
		</form>

		<?php if (!empty($orderList)) {
			/* @var $orderListObject \Concrete\Package\CommunityStoreOrderHistory\Src\OrderList */
			?>
			<div class="table-responsive">
			<table class="table ccm-search-results">
				<thead>
				<tr>
					<th><a href="<?= $orderListObject->getSortURL('oID')?>"><?= t('Order %s',"#")?></a></th>
					<th><?= t('Products')?></th>
					<th><a href="<?= $orderListObject->getSortURL('oDate')?>"><?= t('Order Date')?></a></th>
					<th><a href="<?= $orderListObject->getSortURL('oTotal')?>"><?= t('Total')?></a></th>
					<th><a href="<?= $orderListObject->getSortURL('payment')?>"><?= t('Payment')?></a></th>
					<th><?= t('Fulfilment')?></th>
					<th><?= t('Print')?></th>
				</tr>
				</thead>
				<tbody>
				<?php
				foreach($orderList as $order){
					$cancelled = $order->getCancelled();
					$canstart = '';
					$canend = '';
					if ($cancelled) {
						$canstart = '<del>';
						$canend = '</del>';
					}
					?>
					<tr>
						<td><?= $canstart; ?>
							<a target="_blank" href="<?=URL::to('/account/orders/order/',$order->getOrderID())?>"><?= $order->getOrderID()?></a><?= $canend; ?>
						</td>
						<td>
							<?= $canstart; ?>
							<?php
							$items = $order->getOrderItems();
							$ix = 0;
							$max = 3;
							if ($items) {
								?>
								<ul style="list-style: none; padding:0"><?php
								foreach ($items as $item) {
									$urla = $url2 = '';
									/* @var $item \Concrete\Package\CommunityStore\Src\CommunityStore\Order\OrderItem */
									$product = $item->getProductObject();
									if ($product) {
										/* @var $product \Concrete\Package\CommunityStore\Src\CommunityStore\Product\Product */
										$id = $product->getPageID();
										$page = Page::getByID($id);
										if ($page) {
											$url1 = '<a href="' . Url::to($page->getCollectionLink()). '" target="_blank">';
											$url2 = '</a>';
										}
									}

									if (++$ix > $max) // Show first $max items
										break;
									?>
									<li><?= $url1 . $th->wordSafeShortText($item->getProductName(),50) ?>
										<?php if ($sku = $item->getSKU()) {
											echo '(' . $sku . ')';
										} ?>
										<?= $url2 ?>
									</li>
								<?php
								}
								?></ul><?php
								if (count($items) > $max) {
									echo t('+%s more',count($items)-$max);
								}
							}
							?><?= $canend; ?>
						</td>
						<td><?= $canstart; ?><?= $dh->formatDateTime($order->getOrderDate())?><?= $canend; ?></td>
						<td><?= $canstart; ?><?=Price::format($order->getTotal())?><?= $canend; ?></td>
						<td>
							<?php
							$refunded = $order->getRefunded();
							$paid = $order->getPaid();

							if ($cancelled)  {
								echo '<span class="label label-danger">' . t('Cancelled') . '</span>';
							} elseif ($refunded) {
								echo '<span class="label label-warning">' . t('Refunded') . '</span>';
							} elseif ($paid) {
								echo '<span class="label label-success">' . t('Paid') . '</span>';
							} elseif ($order->getTotal() > 0) {
								echo '<span class="label label-danger">' . t('Unpaid') . '</span>';
							} else {
								echo '<span class="label label-default">' . t('Free Order') . '</span>';
							}
							?>
						</td>
						<td><?= $canstart; ?><?=t(ucwords($order->getStatus()))?><?= $canend; ?></td>
						<td>
							<form action="<?=URL::to('/account/orders/slip')?>" method="post" target="_blank">
								<input type="hidden" name="oID" value="<?= $order->getOrderID()?>">
								<button class="btn btn-primary"><?= t("Print")?></button>
							</form>
						</td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
			</div>
		<?php } ?>
	</div>

	<?php if (empty($orderList)) { ?>
		<br /><p class="alert alert-info"><?= t('No Orders Found');?></p>
	<?php } ?>

	<?php if ($paginator->getTotalPages() > 1) { ?>
		<?= $pagination ?>
	<?php } ?>

	<div class="form-actions">
		<a href="<?php echo URL::to('/account')?>" class="btn btn-default" /><?php echo t('Back to Account')?></a>
	</div>

<?php }