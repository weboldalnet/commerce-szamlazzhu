<?php

namespace Weboldalnet\CommerceSzamlazzhu\Document;

use Weboldalnet\CommerceSzamlazzhu\Document\Invoice\Invoice;
use Weboldalnet\CommerceSzamlazzhu\Header\ProformaHeader;

/**
 * Díjbekérő segédosztály
 *
 * @package Weboldalnet\CommerceSzamlazzhu\document
 */
class Proforma extends Invoice {

    /**
     * Díjbekérő létrehozása
     *
     * @throws \Exception
     */
    function __construct() {
        parent::__construct(null);
        // Alapértelmezett fejléc adatok hozzáadása a díjbekérőhöz
        $this->setHeader(new ProformaHeader());
    }
 }