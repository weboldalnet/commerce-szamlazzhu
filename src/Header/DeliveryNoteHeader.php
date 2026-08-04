<?php

namespace Weboldalnet\CommerceSzamlazzhu\Header;

/**
 * Szállítólevél fejléc
 *
 * @package Weboldalnet\CommerceSzamlazzhu\Header
 */
class DeliveryNoteHeader extends InvoiceHeader {

    /**
     * @throws \Weboldalnet\CommerceSzamlazzhu\SzamlaAgentException
     */
    function __construct() {
        parent::__construct();
        $this->setDeliveryNote(true);
        $this->setPaid(false);
    }
}