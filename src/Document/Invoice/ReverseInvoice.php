<?php

namespace Weboldalnet\CommerceSzamlazzhu\Document\Invoice;

use Weboldalnet\CommerceSzamlazzhu\Header\ReverseInvoiceHeader;

/**
 * Sztornó számla
 *
 * @package Weboldalnet\CommerceSzamlazzhu\document\invoice
 */
class ReverseInvoice extends Invoice {

    /**
     * Sztornó számla létrehozása
     *
     * @param int $type számla típusa (papír vagy e-számla), alapértelmezett a papír alapú számla
     *
     * @throws \Weboldalnet\CommerceSzamlazzhu\SzamlaAgentException
     */
    public function __construct($type = self::INVOICE_TYPE_P_INVOICE) {
        parent::__construct(null);
        // Alapértelmezett fejléc adatok hozzáadása a számlához
        if (!empty($type)) {
            $this->setHeader(new ReverseInvoiceHeader($type));
        }
    }
}