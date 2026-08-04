<?php

namespace Weboldalnet\CommerceSzamlazzhu\Document\Invoice;

use Weboldalnet\CommerceSzamlazzhu\Header\FinalInvoiceHeader;

/**
 * Végszámla kiállításához használható segédosztály
 *
 * @package Weboldalnet\CommerceSzamlazzhu\document\invoice
 */
class FinalInvoice extends Invoice {

    /**
     * Végszámla létrehozása
     *
     * @param int $type végszámla típusa (papír vagy e-számla), alapértelmezett a papír alapú számla
     *
     * @throws \Weboldalnet\CommerceSzamlazzhu\SzamlaAgentException
     */
    function __construct($type = self::INVOICE_TYPE_P_INVOICE) {
        parent::__construct(null);
        // Alapértelmezett fejléc adatok hozzáadása
        $this->setHeader(new FinalInvoiceHeader($type));
    }
 }