<?php

namespace Weboldalnet\CommerceSzamlazzhu\Header;

use Weboldalnet\CommerceSzamlazzhu\Document\Invoice\Invoice;

/**
 * Előlegszámla fejléc
 *
 * @package Weboldalnet\CommerceSzamlazzhu\Header
 */
class PrePaymentInvoiceHeader extends InvoiceHeader {

    /**
     * @param int $type
     *
     * @throws \Weboldalnet\CommerceSzamlazzhu\SzamlaAgentException
     */
    function __construct($type = Invoice::INVOICE_TYPE_P_INVOICE) {
        parent::__construct($type);
        $this->setPrePayment(true);
        $this->setPaid(false);
    }
}