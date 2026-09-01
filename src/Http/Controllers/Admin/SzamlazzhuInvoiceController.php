<?php

namespace Weboldalnet\CommerceSzamlazzhu\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Weboldalnet\CommerceCore\Models\InvoiceDocument;
use Weboldalnet\CommerceCore\Services\InvoiceService;
use Weboldalnet\CommerceSzamlazzhu\Services\SzamlazzhuService;
use Weboldalnet\WebshopAiDefault\Models\WebshopOrder;
use Illuminate\Support\Facades\Storage;

class SzamlazzhuInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = InvoiceDocument::with('order')->orderBy('created_at', 'desc');

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('invoice_number', 'ILIKE', '%' . $request->search . '%')
                  ->orWhereHas('order', function($oq) use ($request) {
                      $oq->where('order_number', 'ILIKE', '%' . $request->search . '%')
                         ->orWhere('customer_name', 'ILIKE', '%' . $request->search . '%');
                  });
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $invoices = $query->paginate(20);

        return view('commerce-szamlazzhu::admin.invoices.index', compact('invoices'));
    }

    public function download($id)
    {
        $invoice = InvoiceDocument::findOrFail($id);

        if (empty($invoice->pdf_path)) {
            return redirect()->back()->with('error', 'Nincs mentett PDF útvonal ehhez a számlához.');
        }

        if (!Storage::disk('private')->exists($invoice->pdf_path)) {
            // Megpróbáljuk letölteni a Számlázz.hu-tól, ha nincs meg helyben
            $service = app(SzamlazzhuService::class);
            $result = $service->downloadPdf($invoice->invoice_number);

            if ($result['success'] && !empty($result['content'])) {
                // Elmentjük helyben is, ha sikerült letölteni
                Storage::disk('private')->put($invoice->pdf_path, $result['content']);
            } else {
                return redirect()->back()->with('error', 'A számla PDF nem található a szerveren és a Számlázz.hu-tól sem sikerült letölteni.');
            }
        }

        return Storage::disk('private')->download($invoice->pdf_path, $invoice->invoice_number . '.pdf');
    }

    public function void($id, SzamlazzhuService $service)
    {
        $invoice = InvoiceDocument::findOrFail($id);

        if ($invoice->isVoided()) {
            return redirect()->back()->with('warning', 'Ez a számla már érvénytelenítve van.');
        }

        $result = $service->voidInvoice($invoice->invoice_number);

        if ($result->success) {
            $invoice->markAsVoided();

            // A rendelés számla-státuszát is át kell vezetni, különben a rendelés
            // "számlázva" maradna egy már érvénytelenített bizonylat mellett.
            if ($invoice->order && method_exists($invoice->order, 'markInvoiceVoided')) {
                $invoice->order->markInvoiceVoided();
            }

            return redirect()->back()->with('success', 'A számla sikeresen érvénytelenítve.');
        }

        return redirect()->back()->with('error', 'Hiba az érvénytelenítéskor: ' . $result->message);
    }

    public function resend($id, SzamlazzhuService $service)
    {
        $result = $service->resendInvoice($id);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    public function pay($id, SzamlazzhuService $service)
    {
        $invoice = InvoiceDocument::findOrFail($id);

        $result = $service->payInvoice($invoice->invoice_number, $invoice->gross_total);

        if ($result['success']) {
            if ($invoice->order) {
                $invoice->order->markPaid();
            }
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }
}
