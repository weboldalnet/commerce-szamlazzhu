<?php

namespace Weboldalnet\CommerceSzamlazzhu\Providers;

use Weboldalnet\CommerceCore\Contracts\InvoiceProviderInterface;
use Weboldalnet\CommerceCore\Data\InvoiceRequestData;
use Weboldalnet\CommerceSzamlazzhu\Services\SzamlazzhuService;

class SzamlazzhuInvoiceProvider implements InvoiceProviderInterface
{
    protected $service;

    public function __construct(SzamlazzhuService $service)
    {
        $this->service = $service;
    }

    public function getCode()
    {
        return 'szamlazzhu';
    }

    public function getName()
    {
        return 'Számlázz.hu';
    }

    public function createInvoice(InvoiceRequestData $data)
    {
        return $this->service->createInvoice($data);
    }

    public function voidInvoice($invoiceNumber)
    {
        return $this->service->voidInvoice($invoiceNumber);
    }

    public function downloadPdf($invoiceNumber)
    {
        return $this->service->downloadPdf($invoiceNumber);
    }
}
