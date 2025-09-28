/**
 * Modal utilities for the application
 */

/**
 * Show delete confirmation modal
 * @param {string} modalId - The ID of the modal to show
 * @param {string|null} confirmMessage - Optional confirmation message to show before opening modal
 */
function showDeleteConfirmation(modalId, confirmMessage = null) {
    if (confirmMessage && !confirm(confirmMessage)) {
        return;
    }

    const modal = document.getElementById(modalId);
    const input = document.getElementById(modalId + 'ConfirmInput');

    if (modal && input) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        input.focus();
    }
}

/**
 * Hide delete confirmation modal
 * @param {string} modalId - The ID of the modal to hide
 */
function hideDeleteConfirmation(modalId) {
    const modal = document.getElementById(modalId);
    const input = document.getElementById(modalId + 'ConfirmInput');
    const button = document.getElementById(modalId + 'ConfirmBtn');

    if (modal && input && button) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        input.value = '';
        button.disabled = true;
    }
}

/**
 * Execute delete form submission
 * @param {string} formId - The ID of the form to submit
 */
function executeDelete(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.submit();
    }
}

/**
 * Initialize modal event listeners
 */
function initializeModals() {
    // 入力フィールドの監視を設定
    document.querySelectorAll('[id$="ConfirmInput"]').forEach(function(input) {
        const modalId = input.id.replace('ConfirmInput', '');
        const button = document.getElementById(modalId + 'ConfirmBtn');

        if (button) {
            input.addEventListener('input', function() {
                button.disabled = this.value !== 'delete';
            });

            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && this.value === 'delete') {
                    button.click();
                }
            });
        }
    });

    // Escapeキーでモーダルを閉じる
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('[id$="Modal"]').forEach(function(modal) {
                if (!modal.classList.contains('hidden')) {
                    const modalId = modal.id;
                    hideDeleteConfirmation(modalId);
                }
            });
        }
    });

    // モーダル外クリックで閉じる
    document.addEventListener('click', function(event) {
        document.querySelectorAll('[id$="Modal"]').forEach(function(modal) {
            if (event.target === modal && !modal.classList.contains('hidden')) {
                const modalId = modal.id;
                hideDeleteConfirmation(modalId);
            }
        });
    });
}

// DOM loaded時に初期化
document.addEventListener('DOMContentLoaded', initializeModals);