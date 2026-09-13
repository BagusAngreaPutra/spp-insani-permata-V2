<style>
    .app-confirm-layer[hidden] { display: none !important; }
    .app-confirm-layer {
        position: fixed;
        z-index: 10050;
        inset: 0;
        display: grid;
        place-items: center;
        padding: 20px;
        background: rgba(16, 24, 40, .46);
        backdrop-filter: blur(3px);
    }
    .app-confirm-card {
        width: min(100%, 410px);
        overflow: hidden;
        background: #fff;
        border: 1px solid #e4e7ec;
        border-radius: 14px;
        box-shadow: 0 24px 64px rgba(16, 24, 40, .24);
        animation: app-confirm-enter .16s ease-out;
    }
    .app-confirm-body { display: flex; gap: 13px; padding: 20px 20px 17px; }
    .app-confirm-icon {
        flex: 0 0 38px;
        display: grid;
        place-items: center;
        width: 38px;
        height: 38px;
        color: #b54708;
        background: #fffaeb;
        border: 1px solid #fedf89;
        border-radius: 10px;
        font-size: 15px;
    }
    .app-confirm-card.is-danger .app-confirm-icon { color: #b42318; background: #fef3f2; border-color: #fecdca; }
    .app-confirm-card.is-success .app-confirm-icon { color: #087451; background: #ecfdf3; border-color: #abefc6; }
    .app-confirm-copy { min-width: 0; }
    .app-confirm-copy h2 { margin: 1px 0 6px; color: #101828; font-size: 15px; font-weight: 750; }
    .app-confirm-copy p { margin: 0; color: #667085; font-size: 11px; line-height: 1.6; }
    .app-confirm-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        padding: 12px 20px;
        background: #f9fafb;
        border-top: 1px solid #eaecf0;
    }
    .app-confirm-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 36px;
        padding: 0 14px;
        color: #344054;
        background: #fff;
        border: 1px solid #d0d5dd;
        border-radius: 7px;
        font: inherit;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
    }
    .app-confirm-button.is-confirm { color: #fff; background: #1d6b4c; border-color: #1d6b4c; }
    .app-confirm-card.is-danger .app-confirm-button.is-confirm { background: #d92d20; border-color: #d92d20; }
    @keyframes app-confirm-enter { from { opacity: 0; transform: translateY(7px) scale(.985); } }
</style>

<div class="app-confirm-layer" id="appConfirmLayer" hidden aria-hidden="true">
    <section class="app-confirm-card" id="appConfirmCard" role="dialog" aria-modal="true" aria-labelledby="appConfirmTitle" aria-describedby="appConfirmMessage">
        <div class="app-confirm-body">
            <span class="app-confirm-icon"><i class="fas fa-question" id="appConfirmIcon" aria-hidden="true"></i></span>
            <div class="app-confirm-copy">
                <h2 id="appConfirmTitle">Konfirmasi tindakan</h2>
                <p id="appConfirmMessage"></p>
            </div>
        </div>
        <div class="app-confirm-actions">
            <button class="app-confirm-button" id="appConfirmCancel" type="button">Batal</button>
            <button class="app-confirm-button is-confirm" id="appConfirmAccept" type="button">Lanjutkan</button>
        </div>
    </section>
</div>

<script>
(() => {
    const layer = document.getElementById('appConfirmLayer');
    const card = document.getElementById('appConfirmCard');
    const title = document.getElementById('appConfirmTitle');
    const message = document.getElementById('appConfirmMessage');
    const icon = document.getElementById('appConfirmIcon');
    const cancel = document.getElementById('appConfirmCancel');
    const accept = document.getElementById('appConfirmAccept');
    let finish = null;

    const close = (result) => {
        if (!finish) return;
        const resolver = finish;
        finish = null;
        layer.hidden = true;
        layer.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        resolver(result);
    };

    window.appConfirm = (options = {}) => new Promise((resolve) => {
        if (finish) close(false);
        const tone = options.tone || 'warning';
        card.className = `app-confirm-card is-${tone}`;
        title.textContent = options.title || 'Konfirmasi tindakan';
        message.textContent = options.message || 'Apakah Anda yakin ingin melanjutkan?';
        accept.textContent = options.confirmText || 'Lanjutkan';
        icon.className = tone === 'danger' ? 'fas fa-trash-can' : (tone === 'success' ? 'fas fa-circle-check' : 'fas fa-triangle-exclamation');
        finish = resolve;
        layer.hidden = false;
        layer.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        window.setTimeout(() => cancel.focus(), 0);
    });

    cancel.addEventListener('click', () => close(false));
    accept.addEventListener('click', () => close(true));
    layer.addEventListener('click', (event) => { if (event.target === layer) close(false); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !layer.hidden) close(false); });

    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirmMessage) return;
        if (form.dataset.confirmBypass === 'true') {
            delete form.dataset.confirmBypass;
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        const confirmed = await window.appConfirm({
            title: form.dataset.confirmTitle,
            message: form.dataset.confirmMessage,
            confirmText: form.dataset.confirmLabel,
            tone: form.dataset.confirmTone,
        });
        if (!confirmed) return;
        form.dataset.confirmBypass = 'true';
        form.requestSubmit(event.submitter || undefined);
    }, true);

    document.addEventListener('click', async (event) => {
        const trigger = event.target.closest('[data-confirm-message]');
        if (!trigger || trigger instanceof HTMLFormElement) return;
        if (trigger.dataset.confirmBypass === 'true') {
            delete trigger.dataset.confirmBypass;
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        const confirmed = await window.appConfirm({
            title: trigger.dataset.confirmTitle,
            message: trigger.dataset.confirmMessage,
            confirmText: trigger.dataset.confirmLabel,
            tone: trigger.dataset.confirmTone,
        });
        if (!confirmed) return;
        trigger.dataset.confirmBypass = 'true';
        trigger.click();
    }, true);
})();
</script>
