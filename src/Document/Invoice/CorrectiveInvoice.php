<?php

namespace Weboldalnet\CommerceSzamlazzhu\Document\Invoice;

use Weboldalnet\CommerceSzamlazzhu\Header\CorrectiveInvoiceHeader;

/**
 * Helyesbítő számla kiállításához használható segédosztály
 *
 * @package Weboldalnet\CommerceSzamlazzhu\document
 */
class CorrectiveInvoice extends Invoice {

    /**
     * Helyesbítő számla létrehozása
     *
     * @param int $type számla típusa (papír vagy e-számla), alapértelmezett a papír alapú számla
     *
     * @throws \Weboldalnet\CommerceSzamlazzhu\SzamlaAgentException
     */
    function __construct($type = self::INVOICE_TYPE_P_INVOICE) {
        parent::__construct(null);
        // Alapértelmezett fejléc adatok hozzáadása
        $this->setHeader(new CorrectiveInvoiceHeader($type));
    }
 }