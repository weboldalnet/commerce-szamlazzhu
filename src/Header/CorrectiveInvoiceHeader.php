<?php

namespace Weboldalnet\CommerceSzamlazzhu\Header;

use Weboldalnet\CommerceSzamlazzhu\Document\Invoice\Invoice;

/**
 * Helyesbítő számla fejléc
 *
 * @package Weboldalnet\CommerceSzamlazzhu\Header
 */
class CorrectiveInvoiceHeader extends InvoiceHeader {

    /**
     * @param int $type
     *
     * @throws \Weboldalnet\CommerceSzamlazzhu\SzamlaAgentException
     */
    function __construct($type = Invoice::INVOICE_TYPE_P_INVOICE) {
        parent::__construct($type);
        $this->setCorrective(true);
    }
}