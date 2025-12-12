<div id="<?= esc($id ?? 'moduleNotification') ?>"
     role="alert"
     aria-live="polite"
     style="
        display: none;
        margin: 0.75rem auto 0 auto;
        padding: 0.75rem 1rem;
        max-width: 1180px;
        border-radius: 6px;
        border: 1px solid #bbf7d0;
        background: #ecfdf5;
        color: #166534;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.15);
        font-size: 0.95rem;
        font-weight: 500;
        position: relative;
        z-index: 1000;
">
    <i id="<?= esc(($id ?? 'moduleNotification') . 'Icon') ?>"
       class="fas fa-check-circle"
       aria-hidden="true"
       style="font-size: 1.1rem; flex-shrink: 0;"></i>
    <span id="<?= esc(($id ?? 'moduleNotification') . 'Text') ?>"
          style="flex: 1;"></span>
    <button type="button"
            onclick="<?= esc($dismissFn ?? 'dismissModuleNotification()') ?>"
            aria-label="Dismiss notification"
            style="margin-left:auto; background:transparent; border:none; cursor:pointer; color:inherit; padding: 0.25rem; flex-shrink: 0;">
        <i class="fas fa-times" style="font-size: 0.9rem;"></i>
    </button>
</div>