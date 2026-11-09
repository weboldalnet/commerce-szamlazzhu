@extends('admin.layouts.layout')
@section('title', 'Számlák')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            {{-- Egy gyerekelem: a flex burkolat balra tolná a címet, a .header-box
                 saját text-align: center-je viszont középre rendezi – ahogy a
                 szállítmány-listán is. --}}
            <div class="header-box my-2">
                <div>
                    <h1 class="mb-0">Számlák</h1>
                    <p class="text-muted small mb-0">Rendelésekhez tartozó Számlázz.hu bizonylatok</p>
                </div>
            </div>

            @include('admin.webshop.partials.alerts')

            <div class="content-box bordered mb-4">
                <form action="{{ route('admin.webshop.invoices.index') }}" method="GET" class="row align-items-end">
                    <div class="col-md-4 mb-2">
                        <label class="small fw-600">Keresés (számlaszám, rendelésszám, vevő)</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Keresés...">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="small fw-600">Státusz</label>
                        <select name="status" class="form-control">
                            <option value="">Összes státusz</option>
                            @foreach(\Weboldalnet\CommerceCore\Status\InvoiceStatus::LABELS as $code => $label)
                                <option value="{{ $code }}" @if(request('status') === $code) selected @endif>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-primary btn-block font-weight-bold">
                            <i class="fa fa-search mr-1"></i> Szűrés
                        </button>
                    </div>
                    <div class="col-md-2 mb-2">
                        <a href="{{ route('admin.webshop.invoices.index') }}" class="btn btn-secondary btn-block small">Alaphelyzet</a>
                    </div>
                </form>
            </div>

            {{-- Nincs overflow-hidden: az levágná a sorok Műveletek legördülőjét. --}}
            <div class="content-box bordered p-0 mb-3">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Rendelésszám</th>
                            <th>Számlaszám</th>
                            <th>Vevő</th>
                            <th class="text-right">Összeg</th>
                            <th class="text-center">Státusz</th>
                            <th>Kiállítva</th>
                            <th class="text-center">PDF</th>
                            <th class="text-right">Műveletek</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td class="align-middle">
                                    @if($invoice->order)
                                        <a href="/webshop/orders/{{ $invoice->order->id }}/edit" class="font-weight-bold">
                                            {{ $invoice->order->order_number }}
                                        </a>
                                    @else
                                        <span class="text-muted">{{ $invoice->order_id }}</span>
                                    @endif
                                </td>
                                <td class="align-middle font-weight-bold">{{ $invoice->invoice_number ?? '—' }}</td>
                                <td class="align-middle">
                                    {{ $invoice->order->customer_name ?? '—' }}<br>
                                    <small class="text-muted">{{ $invoice->order->customer_email ?? '' }}</small>
                                </td>
                                <td class="align-middle text-right font-weight-bold">
                                    {{ number_format($invoice->gross_total, 0, '.', ' ') }} {{ $invoice->currency }}
                                </td>
                                <td class="align-middle text-center">
                                    @php
                                        $badgeClass = 'secondary';
                                        if($invoice->status === 'invoiced') $badgeClass = 'success';
                                        elseif($invoice->status === 'failed') $badgeClass = 'danger';
                                        elseif($invoice->status === 'voided') $badgeClass = 'warning';
                                    @endphp
                                    <span class="badge badge-{{ $badgeClass }} px-2 py-1">
                                        {{ $invoice->status_label }}
                                    </span>
                                </td>
                                <td class="align-middle small">
                                    {{ $invoice->issued_at ? $invoice->issued_at->format('Y.m.d H:i') : '—' }}
                                </td>
                                <td class="align-middle text-center">
                                    @if($invoice->pdf_path)
                                        <a href="{{ route('admin.webshop.invoices.download', $invoice->id) }}" class="btn btn-outline-danger btn-sm" title="PDF letöltése">
                                            <i class="fa fa-file-pdf"></i>
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="align-middle text-right">
                                    <div class="dropdown">
                                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
                                            Műveletek
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            @if($invoice->isInvoiced())
                                                <form action="{{ route('admin.webshop.invoices.void', $invoice->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="button" class="dropdown-item text-danger js-szh-confirm"
                                                            data-confirm-title="Számla érvénytelenítése"
                                                            data-confirm-text="Biztosan érvényteleníted a(z) {{ $invoice->invoice_number }} számú számlát? A Számlázz.hu sztornó számlát állít ki, a művelet nem vonható vissza.">
                                                        <i class="fa fa-ban mr-2"></i> Érvénytelenítés
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.webshop.invoices.resend', $invoice->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="button" class="dropdown-item js-szh-confirm"
                                                            data-confirm-title="Számla újraküldése"
                                                            data-confirm-text="Újraküldöd e-mailben a(z) {{ $invoice->invoice_number }} számú számlát a vevőnek?">
                                                        <i class="fa fa-envelope mr-2"></i> Újraküldés e-mailben
                                                    </button>
                                                </form>
                                                @if($invoice->order && !$invoice->order->isPaid())
                                                    <form action="{{ route('admin.webshop.invoices.pay', $invoice->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="fa fa-check-circle mr-2"></i> Kifizetettre állítás
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    Nem található a feltételeknek megfelelő számla.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</div>

@include('commerce-szamlazzhu::admin.partials.action-confirm')
@endsection
