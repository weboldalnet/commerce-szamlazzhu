<?php

namespace Weboldalnet\CommerceSzamlazzhu\Document;

use Weboldalnet\CommerceSzamlazzhu\Document\Invoice\Invoice;
use Weboldalnet\CommerceSzamlazzhu\Header\DeliveryNoteHeader;

/**
 * Szállítólevél segédosztály
 *
 * @package Weboldalnet\CommerceSzamlazzhu\document
 */
class DeliveryNote extends Invoice {

    /**
     * Szállítólevél kiállítása
     *
     * @throws \Exception
     */
    function __construct() {
        parent::__construct(null);
        // Alapértelmezett fejléc adatok hozzáadása
        $this->setHeader(new DeliveryNoteHeader());
    }
 }