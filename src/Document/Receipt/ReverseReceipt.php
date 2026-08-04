<?php

namespace Weboldalnet\CommerceSzamlazzhu\Document\Receipt;

use Weboldalnet\CommerceSzamlazzhu\Header\ReverseReceiptHeader;

/**
 * Sztornó nyugta
 *
 * @package Weboldalnet\CommerceSzamlazzhu\document\receipt
 */
class ReverseReceipt extends Receipt {

    /**
     * Sztornó nyugta létrehozása nyugtaszám alapján
     *
     * @param string $receiptNumber
     */
    public function __construct($receiptNumber = '') {
        parent::__construct(null);
        $this->setHeader(new ReverseReceiptHeader($receiptNumber));
    }
}