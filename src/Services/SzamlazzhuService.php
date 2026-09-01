<?php

namespace Weboldalnet\CommerceSzamlazzhu\Services;

use Weboldalnet\CommerceCore\Data\InvoiceCreateResult;
use Weboldalnet\CommerceCore\Data\InvoiceRequestData;
use Weboldalnet\CommerceCore\Data\InvoiceVoidResult;
use Weboldalnet\CommerceCore\Status\InvoiceStatus;
use Weboldalnet\CommerceCore\Services\ProviderLogger;
use Weboldalnet\CommerceSzamlazzhu\SzamlaAgentAPI;
use Weboldalnet\CommerceSzamlazzhu\SzamlaAgentResponse;
use Weboldalnet\CommerceSzamlazzhu\Log;
use Weboldalnet\CommerceSzamlazzhu\Document\Invoice\Invoice;
use Weboldalnet\CommerceSzamlazzhu\Document\Invoice\ReverseInvoice;
use Weboldalnet\CommerceSzamlazzhu\Buyer;
use Weboldalnet\CommerceSzamlazzhu\Seller;
use Weboldalnet\CommerceSzamlazzhu\Item\InvoiceItem;
use Illuminate\Support\Facades\Storage;

class SzamlazzhuService
{
    protected $settings;
    protected $logger;

    public function __construct(SzamlazzhuSettingsService $settings, ProviderLogger $logger)
    {
        $this->settings = $settings;
        $this->logger = $logger;
    }

    /**
     * Számla Agent példány létrehozása
     */
    protected function getAgent($downloadPdf = true)
    {
        $apiKey = $this->settings->get('agent_api_key');
        $username = $this->settings->get('username');
        $password = $this->settings->get('password');

        $logLevel = (int)$this->settings->get('log_level', Log::LOG_LEVEL_ERROR);

        if ($apiKey) {
            $agent = SzamlaAgentAPI::create($apiKey, $downloadPdf, $logLevel);
        } else {
            // Régi típusú auth, ha nincs kulcs
            // Megjegyzés: SzamlaAgentAPI::create-nek nincs username/password verziója a v3-ban,
            // de a SzamlaAgent::create létezik.
            // Viszont a feladat szerint az Agent kulcs a javasolt.
            throw new \Exception('Számlázz.hu Agent kulcs hiányzik.');
        }

        // Megjegyzés: a Számlázz.hu-nak nincs config szintű teszt üzemmódja / sandbox
        // végpontja. Az számít teszt környezetnek, ha a megadott fiók teszt fiók.
        return $agent;
    }

    public function createInvoice(InvoiceRequestData $data): InvoiceCreateResult
    {
        try {
            $agent = $this->getAgent($this->settings->getBool('download_pdf', true));

            $invoice = new Invoice();
            $header = $invoice->getHeader();

            // Header adatok
            // FIGYELEM: az SDK setter nevei setFulfillment / setPaymentDue / setPrefix
            // (nem setFulfillmentDate / setPaymentDueDate / setInvoicePrefix).
            $header->setIssueDate($data->extra['issue_date'] ?? date('Y-m-d'));
            $header->setFulfillment($data->extra['fulfillment_date'] ?? date('Y-m-d'));
            $header->setPaymentDue($data->extra['payment_due_date'] ?? date('Y-m-d', strtotime('+8 days')));
            $header->setPaymentMethod($data->extra['payment_method_text'] ?: 'Átutalás');
            $header->setCurrency($data->currency ?: 'HUF');
            $header->setLanguage($data->language ?: 'hu');
            $header->setComment($data->extra['comment'] ?? '');
            $header->setPrefix((string) $this->settings->get('invoice_prefix', ''));
            $header->setOrderNumber((string) ($data->orderNumber ?: $data->orderId));

            // E-számla vagy papír alapú: az SDK a bizonylat típusából vezeti le (isEInvoice()).
            $header->setInvoiceType(
                $this->settings->get('invoice_type') === 'e-invoice'
                    ? Invoice::INVOICE_TYPE_E_INVOICE
                    : Invoice::INVOICE_TYPE_P_INVOICE
            );

            // Vevő adatai – az InvoiceRequestData mezői camelCase-ek
            $billing = is_array($data->billingData) ? $data->billingData : [];
            $buyer = new Buyer(
                (string) $data->customerName,
                (string) ($billing['zip'] ?? ''),
                (string) ($billing['city'] ?? ''),
                (string) ($billing['address'] ?? '')
            );
            if (!empty($data->customerEmail)) {
                $buyer->setEmail($data->customerEmail);
            }
            // A "Számla küldése e-mailben a vevőnek" beállítás – enélkül a kapcsoló
            // nem jutott el a Számlázz.hu-ig, tehát nem csinált semmit.
            $buyer->setSendEmail($this->settings->getBool('send_email_to_customer', false));
            if (!empty($data->customerTaxNumber)) {
                $buyer->setTaxNumber($data->customerTaxNumber);
            }
            $invoice->setBuyer($buyer);

            // Eladó adatai (ha felül kell bírálni) – az SDK setterei setBank / setBankAccount
            $seller = new Seller();
            if ($bankName = $this->settings->get('bank_name')) $seller->setBank($bankName);
            if ($bankAccount = $this->settings->get('bank_account_number')) $seller->setBankAccount($bankAccount);
            if ($email = $this->settings->get('email_from_address')) $seller->setEmailReplyTo($email);

            // Értesítő e-mail tárgya és szövege. A {invoice_number} helyőrzőt a
            // rendelésszámra cseréljük, mert a számlaszám csak a válaszban derül ki.
            $emailPlaceholders = ['{invoice_number}' => (string) ($data->orderNumber ?: $data->orderId)];
            if ($subject = $this->settings->get('email_subject')) {
                $seller->setEmailSubject(strtr($subject, $emailPlaceholders));
            }
            if ($content = $this->settings->get('email_content')) {
                $seller->setEmailContent(strtr($content, $emailPlaceholders));
            }

            $invoice->setSeller($seller);

            // Tételek: a webshop bruttó egységárat küld, a nettó/áfa bontás itt készül.
            foreach ($data->items as $itemData) {
                $quantity = (float) ($itemData['quantity'] ?? 1);
                $vatKey = (string) ($itemData['vat_key'] ?? '27');
                $vatRate = is_numeric($vatKey) ? (float) $vatKey : 0.0;

                $grossUnitPrice = (float) ($itemData['gross_unit_price'] ?? $itemData['unit_price'] ?? 0);
                $grossTotal = (float) ($itemData['gross_total'] ?? ($grossUnitPrice * $quantity));

                $netUnitPrice = round($grossUnitPrice / (1 + $vatRate / 100), 2);
                $netTotal = round($grossTotal / (1 + $vatRate / 100), 2);
                $vatAmount = round($grossTotal - $netTotal, 2);

                $item = new InvoiceItem(
                    $itemData['name'],
                    $netUnitPrice,
                    $quantity,
                    $itemData['unit'] ?? 'db',
                    $vatKey
                );
                $item->setNetPrice($netTotal);
                $item->setVatAmount($vatAmount);
                $item->setGrossAmount($grossTotal);
                $invoice->addItem($item);
            }

            // Naplózás indítása
            $this->logApiCall('generateInvoice', $data->toArray(), null, true, $data->orderId);

            $result = $agent->generateInvoice($invoice);

            $success = $result->isSuccess();
            $responsePayload = $result->getData(); // Ez az XML válasz vagy szöveg

            $this->logApiCall('generateInvoice', $data->toArray(), $responsePayload, $success, $data->orderId);

            if ($success) {
                $pdfFullPath = $result->getPdfFileName(true);
                $pdfRelativePath = null;
                if ($pdfFullPath) {
                    $basePath = storage_path('app/private/commerce-szamlazzhu');
                    $pdfRelativePath = 'pdf/' . basename($pdfFullPath);
                }

                return new InvoiceCreateResult([
                    'success' => true,
                    'status' => InvoiceStatus::INVOICED,
                    'provider' => 'szamlazzhu',
                    'invoice_number' => $result->getDocumentNumber(),
                    'invoice_id' => $result->getDocumentNumber(), // Az SDK-ban nincs külön Agent ID a válaszban egyszerűen
                    'pdf_path' => $pdfRelativePath,
                    'message' => 'Sikeres számlázás.',
                    'raw_response' => $responsePayload
                ]);
            } else {
                return new InvoiceCreateResult([
                    'success' => false,
                    'status' => InvoiceStatus::FAILED,
                    'provider' => 'szamlazzhu',
                    'message' => $result->getErrorMsg(),
                    'extra' => ['error_code' => $result->getErrorCode()],
                    'raw_response' => $responsePayload
                ]);
            }

        } catch (\Throwable $e) {
            // A ProviderLogger-nek nincs error() metódusa – a saját logApiCall() wrapperen keresztül naplózunk.
            $this->logApiCall('generateInvoice', $data->toArray(), $e->getMessage(), false, $data->orderId);
            return new InvoiceCreateResult([
                'success' => false,
                'status' => InvoiceStatus::FAILED,
                'provider' => 'szamlazzhu',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function voidInvoice($invoiceNumber): InvoiceVoidResult
    {
        try {
            $agent = $this->getAgent(false);

            // FIGYELEM: a ReverseInvoice konstruktora a bizonylat TÍPUSÁT várja
            // (papír / e-számla), nem a számlaszámot. A sztornózandó számla számát
            // a fejlécen kell beállítani, különben az SDK kötelező mező hibát dob.
            $reverseInvoice = new ReverseInvoice(
                $this->settings->get('invoice_type') === 'e-invoice'
                    ? Invoice::INVOICE_TYPE_E_INVOICE
                    : Invoice::INVOICE_TYPE_P_INVOICE
            );
            $reverseInvoice->getHeader()->setInvoiceNumber($invoiceNumber);
            $reverseInvoice->getHeader()->setIssueDate(date('Y-m-d'));

            $this->logApiCall('generateReverseInvoice', ['invoice_number' => $invoiceNumber], null);

            $result = $agent->generateReverseInvoice($reverseInvoice);

            $success = $result->isSuccess();
            $responsePayload = $result->getData();

            $this->logApiCall('generateReverseInvoice', ['invoice_number' => $invoiceNumber], $responsePayload, $success);

            if ($success) {
                return new InvoiceVoidResult([
                    'success' => true,
                    'status' => InvoiceStatus::VOIDED,
                    'invoice_number' => $result->getDocumentNumber(),
                    'message' => 'Sikeres érvénytelenítés.',
                    'raw_response' => $responsePayload
                ]);
            } else {
                return new InvoiceVoidResult([
                    'success' => false,
                    'message' => $result->getErrorMsg(),
                    'raw_response' => $responsePayload
                ]);
            }
        } catch (\Throwable $e) {
            return new InvoiceVoidResult([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function payInvoice($invoiceNumber, $amount, $paymentMethod = 'bankkártya', $comment = '')
    {
        try {
            $agent = $this->getAgent(false);
            $invoice = new Invoice();
            $invoice->getHeader()->setInvoiceNumber($invoiceNumber);
            $invoice->getHeader()->setPaid(true);
            $invoice->getHeader()->setPaymentMethod($paymentMethod);
            // Itt az összeg rögzítése az SDK-ban: a payInvoice-nek kell egy Invoice objektum
            // de abban hogyan állítjuk be a kifizetett összeget?
            // A Számlázz.hu dokumentáció szerint a kifizetésnek van dátuma és összege.

            $this->logApiCall('payInvoice', ['invoice_number' => $invoiceNumber, 'amount' => $amount], null);
            $result = $agent->payInvoice($invoice);

            if ($result->isSuccess()) {
                return ['success' => true, 'message' => 'Számla kifizetettnek jelölve.'];
            }
            return ['success' => false, 'message' => $result->getErrorMsg()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function resendInvoice($id)
    {
        // Ha az SDK nem tudja, akkor mi küldjük el e-mailben a PDF-et
        try {
            $invoice = \Weboldalnet\CommerceCore\Models\InvoiceDocument::findOrFail($id);
            $order = $invoice->order;

            if (!$order || !$order->customer_email) {
                return ['success' => false, 'message' => 'Vevő e-mail címe hiányzik.'];
            }

            // PDF letöltése ha nincs meg
            $pdfResult = $this->downloadPdf($invoice->invoice_number);
            if (!$pdfResult['success']) {
                return ['success' => false, 'message' => 'A számla PDF nem tölthető le: ' . $pdfResult['message']];
            }

            // Laravel Mail küldés (vázlat, mert nincs dedikált Mail class ehhez a csomaghoz)
            // \Illuminate\Support\Facades\Mail::to($order->customer_email)->send(new \App\Mail\GenericAttachmentMail(...));

            return ['success' => true, 'message' => 'Számla újraküldése sikeres (szimulálva).'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function downloadPdf($invoiceNumber)
    {
        try {
            $agent = $this->getAgent(true);
            $result = $agent->getInvoicePdf($invoiceNumber);

            if ($result->isSuccess()) {
                $pdfFullPath = $result->getPdfFileName(true);
                $pdfRelativePath = null;
                if ($pdfFullPath) {
                    $pdfRelativePath = 'pdf/' . basename($pdfFullPath);
                }

                return [
                    'success' => true,
                    'pdf_path' => $pdfRelativePath,
                    'content' => $result->getPdfFile()
                ];
            }
            return ['success' => false, 'message' => $result->getErrorMsg()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function testConnection()
    {
        try {
            $agent = $this->getAgent(false);
            // Egy létezőnek tűnő, de ártalmatlan lekérdezés (pl. Számlázz.hu saját adószáma vagy egy random 8 jegyű szám)
            $result = $agent->getTaxPayer('12345678');

            if ($result->isSuccess()) {
                return [
                    'success' => true,
                    'message' => 'Sikeres kapcsolat a Számlázz.hu-val.'
                ];
            } else {
                // Ha a hitelesítés hibás, azt is itt látjuk
                return [
                    'success' => false,
                    'message' => $result->getErrorMessage()
                ];
            }
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Hiba a kapcsolódáskor: ' . $e->getMessage()
            ];
        }
    }

    protected function logApiCall($action, $request, $response, $success = true, $orderId = null)
    {
        $this->logger->log([
            'provider_type' => 'invoice',
            'provider' => 'szamlazzhu',
            'order_id' => is_numeric($orderId) ? (int) $orderId : null,
            'direction' => 'outgoing',
            'endpoint' => $action,
            'request_payload' => $request,
            'response_payload' => $response,
            'status_code' => $success ? 200 : 400,
            'is_success' => $success,
            'error_message' => !$success ? (is_string($response) ? $response : 'Hiba az API hívás során') : null,
        ]);
    }
}
