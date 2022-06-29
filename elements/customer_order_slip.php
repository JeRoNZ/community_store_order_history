<?php
defined('C5_EXECUTE') or die("Access Denied.");
use \Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Price as Price;
use \Concrete\Package\CommunityStore\Src\Attribute\Key\StoreOrderKey as StoreOrderKey;

$dh = Core::make('helper/date');

?>
<!doctype html>
<html>
<head>
    <title><?= t("Order #") . $order->getOrderID() ?></title>
    <link href="<?= str_replace('/index.php/', '/' , \URL::to('/concrete/css/app.css')); ?>" rel="stylesheet" type="text/css" media="all">
    <style>
        td, th {
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="ccm-ui">
    <div class="container">
        <h1><?= t("Order #") . $order->getOrderID() ?></h1>

        <div class="row">
            <div class="col-xs-8">
                <p><strong><?= t('Order placed'); ?>:</strong> <?= $dh->formatDateTime($order->getOrderDate()) ?></p>
            </div>
            <div class="col-xs-4">
                <?php
                $refunded = $order->getRefunded();
                $paid = $order->getPaid();
                $cancelled = $order->getCancelled();

                if ($cancelled) {
                    echo '<p class="alert alert-danger text-center"><strong>' . t('Cancelled') . '</strong></p>';
                } else {
                    if ($refunded) {
                        $refundreason = $order->getRefundReason();
                        echo '<p class="alert alert-warning text-center"><strong>' . t('Refunded') . ' - ' . $dh->formatDateTime($refunded)  . ($refundreason ? ' - ' . $refundreason : '') . '</strong></p>';
                    } elseif ($paid) {
                        echo '<p class="alert alert-success text-center"><strong>' . t('Paid') . ' - ' . $dh->formatDateTime($paid) . '</strong></p>';
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
            <legend><?= t("Customer Details") ?></legend>
            <div class="row">
                <div class="col-xs-4">
                    <h4><?= t("Name")?></h4>
                    <p><?= $order->getAttribute('billing_first_name'). " " . $order->getAttribute('billing_last_name')?></p>

                    <?php $orderemail = $order->getAttribute('email');
                    if ($orderemail) { ?>
                        <h4><?= t("Email") ?></h4>
                        <p><a href="mailto:<?= $order->getAttribute('email'); ?>"><?= $order->getAttribute('email'); ?></a></p>
                    <?php } ?>

                    <?php
                    $phone = $order->getAttribute("billing_phone");
                    if ($phone) {
                    ?>
                        <h4><?= t("Phone") ?></h4>
                        <p><?= $order->getAttribute("billing_phone") ?></p>
                    <?php } ?>

                    <?php
                    $vat_number = $order->getAttribute('vat_number');
                    if (Config::get('community_store.vat_number') && $vat_number) { ?>
                        <h4><?= t('VAT Number')?></h4>
                        <p><?=$vat_number?></p>
                    <?php } ?>

                </div>

                <div class="col-xs-4">
                    <h4><?= t("Billing Address") ?></h4>

                    <p>
                        <?= $order->getAttribute('billing_first_name') . ' ' . $order->getAttribute('billing_last_name') ?>
                        <br>
                        <?php
						$billingaddress = $order->getAttributeValueObject('billing_address');
                        if ($billingaddress) {
                            echo $billingaddress->getValue('displaySanitized', 'display');
                        }?>
                    </p>
                </div>
                <div class="col-xs-4">
                    <?php
                    $billingaddress = $order->getAttributeValueObject('shipping_address');
                    if ($billingaddress) { ?>
                        <h4><?= t('Shipping Address') ?></h4>
                        <p>
                            <?= $order->getAttribute('shipping_first_name') . " " . $order->getAttribute('shipping_last_name') ?>
                            <br>
                            <?= $billingaddress->getValue('displaySanitized', 'display'); ?>
                        </p>
                    <?php } ?>
                </div>

                <?php if ($orderChoicesEnabled) { ?>
                    <div class="col-xs-12">
                        <h4><?= t('Other Choices') ?></h4>
                        <?php foreach ($orderChoicesAttList as $ak) {
							$handle = $ak->getAttributeKeyHandle();
							?>
                            <label><?= $ak->getAttributeKeyDisplayName() ?></label>
                            <p><?= str_replace("\r\n", "<br>", $order->getAttributeValueObject($handle)->getValue('displaySanitized', 'display')); ?></p>
                        <?php } ?>
                    </div>
                <?php } ?>

            </div>
        </fieldset>
        <br/>

        <fieldset>
            <legend><?= t('Order Items') ?></legend>

            <table class="table table-striped table-condensed">
                <thead>
                <tr>
                    <th><strong><?= t('Product Name') ?></strong></th>
                    <th><?= t('Product Options') ?></th>
                    <th><?= t('Price') ?></th>
                    <th><?= t('Quantity') ?></th>
                    <th><?= t('Subtotal') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                $items = $order->getOrderItems();

                if ($items) {
                    foreach ($items as $item) {
                        ?>
                        <tr>
                            <td><?= $item->getProductName() ?></td>
                            <td>
                                <?php
                                $options = $item->getProductOptions();
                                if ($options) {
                                    echo "<ul class='list-unstyled'>";
                                    foreach ($options as $option) {
                                        echo '<li>';
                                        echo '<strong>' . $option['oioKey'] . ': </strong>';
                                        echo $option['oioValue'];
                                        echo '</li>';
                                    }
                                    echo '</ul>';
                                }
                                ?>
                            </td>
                            <td><?= Price::format($item->getPricePaid()) ?></td>
                            <td><?= $item->getQty() ?></td>
                            <td><?= Price::format($item->getSubTotal()) ?></td>
                        </tr>
                        <?php
                    }
                }
                ?>
                </tbody>
            </table>
        </fieldset>

        <p>
            <strong><?= t('Subtotal') ?>: </strong><?= Price::format($order->getSubTotal()) ?><br>
            <?php foreach ($order->getTaxes() as $tax) { ?>
                <strong><?= $tax['label'] ?>
                    :</strong> <?= Price::format($tax['amount'] ? $tax['amount'] : $tax['amountIncluded']) ?><br>
            <?php } ?>
            <strong><?= t('Shipping') ?>: </strong><?= Price::format($order->getShippingTotal()) ?><br>

            <?php $applieddiscounts = $order->getAppliedDiscounts();
            if (!empty($applieddiscounts)) { ?>
                <strong><?= (count($applieddiscounts) > 1 ? t('Discounts') : t('Discount')); ?>: </strong>
                <?php
                $discountsApplied = array();
                foreach ($applieddiscounts as $discount) {
                    $discountsApplied[] = $discount['odDisplay'];
                }
                echo implode(',', $discountsApplied);
                ?>
                <br/>
            <?php } ?>

            <strong><?= t('Grand Total') ?>: </strong><?= Price::format($order->getTotal()) ?>
        </p>

        <p>
            <strong><?= t('Payment Method') ?>: </strong><?= t($order->getPaymentMethodName()) ?><br>
            <?php if ($order->isShippable()) { ?>
            <br><strong><?= t('Shipping Method') ?>: </strong><?= $order->getShippingMethodName() ?>

            <?php
            $shippingInstructions = $order->getShippingInstructions();
            if ($shippingInstructions) { ?>

        </p>
        <p>
            <strong><?= t('Delivery Instructions') ?>: </strong><?= $shippingInstructions ?>
        </p>
        <?php } ?>


        <?php } ?>
    </div>
</div>
</body>
</html>