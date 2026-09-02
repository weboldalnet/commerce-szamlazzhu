@extends('admin.layouts.layout')
@section('title', 'Számlázz.hu beállítások')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="header-box my-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-0">Számlázz.hu beállítások</h1>
                        <p class="text-muted small mb-0">Számla Agent API integráció konfigurálása</p>
                    </div>
                    <div>
                        <button type="button" id="test-connection-btn" class="btn btn-warning font-weight-bold">
                            <i class="fa fa-plug mr-1"></i> Kapcsolat tesztelése
                        </button>
                        <div id="test-connection-result" class="mt-2 mb-0 d-none"></div>
                    </div>
                </div>
            </div>

            @include('admin.webshop.partials.alerts')

            <form action="{{ route('admin.webshop.szamlazzhu.settings.update') }}" method="POST" id="szamlazzhu-settings-form">
                @csrf
                
                <div class="row">
                    <!-- Fő beállítások -->
                    <div class="col-lg-6 mb-4">
                        <div class="header-box product-info mb-1">Modul állapota</div>
                        <div class="content-box bordered mb-3">
                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="enabled" name="enabled" 
                                       @if(filter_var($szSettings['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) checked @endif>
                                <label class="custom-control-label fw-600" for="enabled">Számlázz.hu modul engedélyezve</label>
                            </div>
                            <div class="alert alert-info py-2 small mb-0">
                                <i class="fa fa-info-circle mr-1"></i> A modul bekapcsolása előtt töltsd ki a kötelező hitelesítési adatokat!
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <div class="header-box product-info mb-1">Működési mód</div>
                        <div class="content-box bordered mb-3">
                            <div class="form-group mb-3">
                                <label class="fw-600">Számlázási mód</label>
                                <select name="billing_mode" id="billing_mode" class="form-control">
                                    <option value="manual" @if(($szSettings['billing_mode'] ?? '') === 'manual') selected @endif>Manuális (csak admin gombra)</option>
                                    <option value="automatic" @if(($szSettings['billing_mode'] ?? '') === 'automatic') selected @endif>Automatikus (sikeres online fizetés után)</option>
                                </select>
                                <small class="text-muted">Az automatikus mód csak sikeres online fizetés esetén indítja el a számlázást.</small>
                            </div>
                            {{-- A Számlázz.hu-nak nincs config szintű teszt üzemmódja: az számít
                                 teszt környezetnek, hogy a megadott fiók teszt fiók-e. Ezért itt
                                 nincs kapcsoló – korábban volt egy, de nem csinált semmit. --}}
                            <div class="alert alert-info mb-0 py-2 px-3 small">
                                <i class="fa fa-info-circle mr-1"></i>
                                A Számlázz.hu-nál nincs külön teszt üzemmód: az számít teszt környezetnek,
                                ha a fent megadott fiók teszt fiók. Éles fiókkal minden kiállított számla
                                valódi bizonylat.
                            </div>
                        </div>
                    </div>

                    <!-- Hitelesítés -->
                    <div class="col-12 mb-4">
                        <div class="header-box product-info mb-1">Hitelesítés (Agent kulcs vagy Felhasználó)</div>
                        <div class="content-box bordered mb-3">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="fw-600">Számla Agent kulcs (ajánlott)</label>
                                    <input type="text" name="agent_api_key" id="agent_api_key" class="form-control" value="{{ $szSettings['agent_api_key'] ?? '' }}" placeholder="Agent kulcs megadása...">
                                    <small class="text-muted">Ha meg van adva Agent kulcs, a rendszer ezt használja a hitelesítéshez.</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-600">Felhasználónév (email)</label>
                                    <input type="text" name="username" class="form-control" value="{{ $szSettings['username'] ?? '' }}" placeholder="pelda@email.hu">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-600">Jelszó</label>
                                    <input type="password" name="password" class="form-control" value="{{ $szSettings['password'] ?? '' }}" placeholder="********">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Számla alapértelmezések -->
                    <div class="col-lg-6 mb-4">
                        <div class="header-box product-info mb-1">Számla beállítások</div>
                        <div class="content-box bordered mb-3">
                            <div class="form-group mb-3">
                                <label class="fw-600">Számla típusa</label>
                                <select name="invoice_type" class="form-control">
                                    <option value="e-invoice" @if(($szSettings['invoice_type'] ?? '') === 'e-invoice') selected @endif>E-számla (hitelesített PDF)</option>
                                    <option value="paper" @if(($szSettings['invoice_type'] ?? '') === 'paper') selected @endif>Papír alapú számla</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="fw-600">Számla nyelve</label>
                                    <select name="language" class="form-control">
                                        <option value="hu" @if(($szSettings['language'] ?? '') === 'hu') selected @endif>Magyar</option>
                                        <option value="en" @if(($szSettings['language'] ?? '') === 'en') selected @endif>Angol</option>
                                        <option value="de" @if(($szSettings['language'] ?? '') === 'de') selected @endif>Német</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-600">Pénznem</label>
                                    <select name="currency" class="form-control">
                                        <option value="HUF" @if(($szSettings['currency'] ?? '') === 'HUF') selected @endif>HUF</option>
                                        <option value="EUR" @if(($szSettings['currency'] ?? '') === 'EUR') selected @endif>EUR</option>
                                        <option value="USD" @if(($szSettings['currency'] ?? '') === 'USD') selected @endif>USD</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-600">Alapértelmezett ÁFA kulcs (%)</label>
                                    <input type="text" name="vat_key" class="form-control" value="{{ $szSettings['vat_key'] ?? '27' }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-600">Fizetési határidő (nap)</label>
                                    <input type="number" name="payment_deadline_days" class="form-control" value="{{ $szSettings['payment_deadline_days'] ?? '8' }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-600">Számlaszám előtag (opcionális)</label>
                                    <input type="text" name="invoice_prefix" class="form-control" value="{{ $szSettings['invoice_prefix'] ?? '' }}" placeholder="pl. WEB">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-600">Mennyiségi egység</label>
                                    <input type="text" name="unit_of_measure" class="form-control" value="{{ $szSettings['unit_of_measure'] ?? 'db' }}">
                                </div>
                            </div>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input" id="auto_pay_online" name="auto_pay_online" 
                                       @if(filter_var($szSettings['auto_pay_online'] ?? true, FILTER_VALIDATE_BOOLEAN)) checked @endif>
                                <label class="custom-control-label" for="auto_pay_online">Online fizetésnél azonnal kifizetettre állítás</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input" id="send_email_to_customer" name="send_email_to_customer" 
                                       @if(filter_var($szSettings['send_email_to_customer'] ?? false, FILTER_VALIDATE_BOOLEAN)) checked @endif>
                                <label class="custom-control-label" for="send_email_to_customer">Számla küldése e-mailben a vevőnek</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input" id="download_pdf" name="download_pdf" 
                                       @if(filter_var($szSettings['download_pdf'] ?? true, FILTER_VALIDATE_BOOLEAN)) checked @endif>
                                <label class="custom-control-label" for="download_pdf">Számla PDF letöltése és tárolása</label>
                            </div>
                        </div>
                    </div>

                    <!-- Eladó adatai -->
                    <div class="col-lg-6 mb-4">
                        <div class="header-box product-info mb-1">Eladó adatai (felülbírálás)</div>
                        <div class="content-box bordered mb-3">
                            <div class="form-group mb-3">
                                <label class="fw-600">Bank neve</label>
                                <input type="text" name="bank_name" class="form-control" value="{{ $szSettings['bank_name'] ?? '' }}">
                            </div>
                            <div class="form-group mb-3">
                                <label class="fw-600">Bankszámlaszám</label>
                                <input type="text" name="bank_account_number" class="form-control" value="{{ $szSettings['bank_account_number'] ?? '' }}">
                            </div>
                            <div class="form-group mb-3">
                                <label class="fw-600">Válasz e-mail cím (Reply-to)</label>
                                <input type="email" name="email_from_address" class="form-control" value="{{ $szSettings['email_from_address'] ?? '' }}">
                            </div>
                            <div class="custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="use_signature" name="use_signature" 
                                       @if(filter_var($szSettings['use_signature'] ?? false, FILTER_VALIDATE_BOOLEAN)) checked @endif>
                                <label class="custom-control-label" for="use_signature">Aláírás és pecsét használata a PDF-en</label>
                            </div>
                            <p class="small text-muted mb-0">Hagyd üresen, ha a Számlázz.hu fiókodban beállított adatokat szeretnéd használni.</p>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3 mb-5">
                    <button type="submit" class="btn btn-primary fs-18 font-weight-bold px-5">
                        <i class="fa fa-save mr-1"></i> Beállítások mentése
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const enabledSwitch = document.getElementById('enabled');
        const agentKeyInput = document.getElementById('agent_api_key');
        const billingModeSelect = document.getElementById('billing_mode');
        const testBtn = document.getElementById('test-connection-btn');

        function checkRequirements() {
            const hasAuth = agentKeyInput.value.trim() !== '';
            const hasBillingMode = billingModeSelect.value !== '';
            
            if (!hasAuth || !hasBillingMode) {
                enabledSwitch.disabled = true;
                enabledSwitch.parentElement.title = "A hitelesítési adatok és a számlázási mód megadása kötelező a bekapcsoláshoz!";
                $(enabledSwitch.parentElement).tooltip();
            } else {
                enabledSwitch.disabled = false;
                $(enabledSwitch.parentElement).tooltip('dispose');
            }
        }

        agentKeyInput.addEventListener('input', checkRequirements);
        billingModeSelect.addEventListener('change', checkRequirements);
        checkRequirements();

        const testResult = document.getElementById('test-connection-result');

        // Beágyazott visszajelzés natív alert() helyett: az alert blokkolja a lapot.
        function showTestResult(isSuccess, message) {
            testResult.className = 'mt-2 mb-0 alert ' + (isSuccess ? 'alert-success' : 'alert-danger');
            testResult.textContent = message;
        }

        testBtn.addEventListener('click', function() {
            testBtn.disabled = true;
            testBtn.innerHTML = '<i class="fa fa-spinner fa-spin mr-1"></i> Tesztelés...';
            testResult.className = 'mt-2 mb-0 d-none';
            testResult.textContent = '';

            fetch('{{ route("admin.webshop.szamlazzhu.test-connection") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                showTestResult(!!data.success, (data.success ? 'Sikeres kapcsolat: ' : 'Hiba: ') + (data.message || ''));
            })
            .catch(error => {
                showTestResult(false, 'Váratlan hiba történt a tesztelés során.');
            })
            .finally(() => {
                testBtn.disabled = false;
                testBtn.innerHTML = '<i class="fa fa-plug mr-1"></i> Kapcsolat tesztelése';
            });
        });
    });
</script>
@endsection
