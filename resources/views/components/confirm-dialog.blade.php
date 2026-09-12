{{--
    Centred confirmation dialog, replacing the browser's native confirm().

    Any form can opt in without writing markup or JS:

        <form method="POST" action="..."
              data-confirm
              data-confirm-title="Complete this transaction?"
              data-confirm-message="The customer has collected their cylinders."
              data-confirm-amount="This will also record payment of KSh 3,500.00 as received."
              data-confirm-variant="warning"
              data-confirm-action="Complete & mark paid">

    Styles are scoped, self-contained CSS rather than utility classes: this
    dialog must render correctly even when the Tailwind build has not picked up
    a new class combination, because it guards actions that record money.
--}}

<div id="confirmDialog" class="cdlg" hidden>
    <div class="cdlg__backdrop" data-cdlg-cancel></div>

    <div class="cdlg__panel" role="dialog" aria-modal="true" aria-labelledby="cdlgTitle" aria-describedby="cdlgBody">
        <div class="cdlg__icon" id="cdlgIcon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>

        <h2 class="cdlg__title" id="cdlgTitle">Are you sure?</h2>
        <p class="cdlg__body" id="cdlgBody"></p>

        <div class="cdlg__callout" id="cdlgCallout" hidden>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span id="cdlgCalloutText"></span>
        </div>

        <div class="cdlg__actions">
            <button type="button" class="cdlg__btn cdlg__btn--ghost" data-cdlg-cancel>Cancel</button>
            <button type="button" class="cdlg__btn cdlg__btn--primary" id="cdlgConfirm">Confirm</button>
        </div>
    </div>
</div>

<style>
    .cdlg {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .cdlg[hidden] { display: none !important; }

    .cdlg__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, .55);
        backdrop-filter: blur(3px);
        -webkit-backdrop-filter: blur(3px);
        animation: cdlg-fade .15s ease-out;
    }

    .cdlg__panel {
        position: relative;
        width: 100%;
        max-width: 27rem;
        background: #fff;
        border-radius: 1rem;
        padding: 1.75rem 1.5rem 1.25rem;
        text-align: center;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .35);
        animation: cdlg-pop .18s cubic-bezier(.2, .9, .3, 1.2);
    }

    .cdlg__icon {
        width: 3.5rem;
        height: 3.5rem;
        margin: 0 auto 1rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fef3c7;
        color: #d97706;
    }

    .cdlg__icon svg { width: 1.75rem; height: 1.75rem; }

    /* Variants recolour the badge and the primary button together. */
    .cdlg--danger  .cdlg__icon { background: #fee2e2; color: #dc2626; }
    .cdlg--success .cdlg__icon { background: #dcfce7; color: #16a34a; }

    .cdlg__title {
        margin: 0 0 .5rem;
        font-size: 1.125rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.4;
    }

    .cdlg__body {
        margin: 0;
        font-size: .875rem;
        color: #475569;
        line-height: 1.6;
    }

    .cdlg__body:empty { display: none; }

    .cdlg__callout {
        display: flex;
        gap: .625rem;
        align-items: flex-start;
        text-align: left;
        margin-top: 1rem;
        padding: .75rem .875rem;
        border-radius: .625rem;
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
        font-size: .8125rem;
        font-weight: 600;
        line-height: 1.5;
    }

    .cdlg__callout[hidden] { display: none; }
    .cdlg__callout svg { width: 1rem; height: 1rem; flex: none; margin-top: .125rem; }

    .cdlg__actions {
        display: flex;
        gap: .625rem;
        margin-top: 1.5rem;
    }

    .cdlg__btn {
        flex: 1;
        padding: .625rem 1rem;
        border-radius: .625rem;
        border: 1px solid transparent;
        font-size: .875rem;
        font-weight: 600;
        cursor: pointer;
        transition: background-color .15s, border-color .15s, transform .05s;
    }

    .cdlg__btn:active { transform: translateY(1px); }

    .cdlg__btn:focus-visible {
        outline: 2px solid #f97316;
        outline-offset: 2px;
    }

    .cdlg__btn--ghost {
        background: #fff;
        border-color: #cbd5e1;
        color: #334155;
    }

    .cdlg__btn--ghost:hover { background: #f8fafc; }

    .cdlg__btn--primary { background: #d97706; color: #fff; }
    .cdlg__btn--primary:hover { background: #b45309; }

    .cdlg--danger  .cdlg__btn--primary { background: #dc2626; }
    .cdlg--danger  .cdlg__btn--primary:hover { background: #b91c1c; }
    .cdlg--success .cdlg__btn--primary { background: #16a34a; }
    .cdlg--success .cdlg__btn--primary:hover { background: #15803d; }

    @keyframes cdlg-fade { from { opacity: 0; } }
    @keyframes cdlg-pop {
        from { opacity: 0; transform: translateY(8px) scale(.97); }
    }

    @media (prefers-reduced-motion: reduce) {
        .cdlg__backdrop, .cdlg__panel { animation: none; }
    }

    @media (max-width: 420px) {
        .cdlg__actions { flex-direction: column-reverse; }
    }
</style>

<script>
(function () {
    const dialog = document.getElementById('confirmDialog');
    if (!dialog) return;

    const titleEl = document.getElementById('cdlgTitle');
    const bodyEl = document.getElementById('cdlgBody');
    const calloutEl = document.getElementById('cdlgCallout');
    const calloutTextEl = document.getElementById('cdlgCalloutText');
    const confirmBtn = document.getElementById('cdlgConfirm');

    let pendingForm = null;
    let pendingSubmitter = null;
    let lastFocused = null;

    function open(form, submitter) {
        pendingForm = form;
        pendingSubmitter = submitter;
        lastFocused = document.activeElement;

        const data = form.dataset;

        titleEl.textContent = data.confirmTitle || 'Are you sure?';
        bodyEl.textContent = data.confirmMessage || '';

        if (data.confirmAmount) {
            calloutTextEl.textContent = data.confirmAmount;
            calloutEl.hidden = false;
        } else {
            calloutEl.hidden = true;
        }

        confirmBtn.textContent = data.confirmAction || 'Confirm';

        dialog.classList.remove('cdlg--danger', 'cdlg--success');
        if (data.confirmVariant === 'danger') dialog.classList.add('cdlg--danger');
        if (data.confirmVariant === 'success') dialog.classList.add('cdlg--success');

        dialog.hidden = false;
        document.body.style.overflow = 'hidden';
        confirmBtn.focus();
    }

    function close() {
        dialog.hidden = true;
        document.body.style.overflow = '';
        pendingForm = null;
        pendingSubmitter = null;
        if (lastFocused && lastFocused.focus) lastFocused.focus();
    }

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.matches('[data-confirm]') || form.dataset.confirmed === 'yes') return;

        e.preventDefault();
        open(form, e.submitter || null);
    });

    confirmBtn.addEventListener('click', function () {
        if (!pendingForm) return;

        const form = pendingForm;
        const submitter = pendingSubmitter;

        // form.submit() skips the submit button's own name/value, which the
        // bulk "mark as paid" bar relies on. Carry it across explicitly.
        if (submitter && submitter.name) {
            const carried = document.createElement('input');
            carried.type = 'hidden';
            carried.name = submitter.name;
            carried.value = submitter.value;
            form.appendChild(carried);
        }

        form.dataset.confirmed = 'yes';
        close();

        // requestSubmit keeps native validation; fall back for older browsers.
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit(submitter && !submitter.name ? submitter : undefined);
        } else {
            form.submit();
        }
    });

    dialog.addEventListener('click', function (e) {
        if (e.target.closest('[data-cdlg-cancel]')) close();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !dialog.hidden) close();
    });
})();
</script>
