{{--
    Megerősítő ablak űrlap-küldéshez, a natív confirm() helyett (az blokkolja a lapot).

    Használat: a küldő gombra tedd rá a js-szh-confirm osztályt, és add meg a
    data-confirm-title / data-confirm-text attribútumokat. A gomb a saját
    <form>-ját küldi be.
--}}
<div class="modal fade" id="szhConfirmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="szhConfirmTitle">Megerősítés</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p id="szhConfirmText" class="mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Mégsem</button>
                <button type="button" class="btn btn-primary" id="szhConfirmOk">Rendben</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var pendingForm = null;

        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('.js-szh-confirm');
            if (!trigger) {
                return;
            }

            event.preventDefault();
            pendingForm = trigger.closest('form');

            if (!pendingForm) {
                console.warn('szh-confirm: a gombhoz nem tartozik űrlap');
                return;
            }

            document.getElementById('szhConfirmTitle').textContent =
                trigger.dataset.confirmTitle || 'Megerősítés';
            document.getElementById('szhConfirmText').textContent =
                trigger.dataset.confirmText || 'Biztosan folytatod?';

            $('#szhConfirmModal').modal('show');
        });

        document.getElementById('szhConfirmOk').addEventListener('click', function () {
            $('#szhConfirmModal').modal('hide');
            if (pendingForm) {
                pendingForm.submit();
            }
        });
    })();
</script>
