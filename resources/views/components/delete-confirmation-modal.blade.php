@props([
    'modalId',
    'title',
    'entity',
    'entityName',
    'description' => null,
    'formId'
])

<div id="{{ $modalId }}" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 text-center mb-2">{{ $title }}</h3>

        <div class="bg-gray-50 rounded-md p-3 mb-4">
            <p class="text-sm text-gray-700 text-center">
                <span class="font-medium">{{ $entity }}:</span> {{ $entityName }}
            </p>
        </div>

        @if($description)
            <p class="text-sm text-gray-500 text-center mb-4">
                {{ $description }}
            </p>
        @endif

        <p class="text-sm text-gray-700 text-center mb-4">
            続行するには、下のフィールドに <strong>delete</strong> と入力してください。
        </p>

        <input type="text" id="{{ $modalId }}ConfirmInput" placeholder="delete と入力"
               class="w-full px-3 py-2 border border-gray-300 rounded-md text-center mb-4 focus:ring-red-500 focus:border-red-500">

        <div class="flex space-x-3">
            <button type="button" onclick="hideDeleteConfirmation('{{ $modalId }}')"
                    class="flex-1 px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                キャンセル
            </button>
            <button type="button" id="{{ $modalId }}ConfirmBtn" onclick="executeDelete('{{ $formId }}')" disabled
                    class="flex-1 px-4 py-2 bg-red-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-red-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                削除
            </button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
function showDeleteConfirmation(modalId, confirmMessage = null) {
    if (confirmMessage && !confirm(confirmMessage)) {
        return;
    }

    const modal = document.getElementById(modalId);
    const input = document.getElementById(modalId + 'ConfirmInput');

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    input.focus();
}

function hideDeleteConfirmation(modalId) {
    const modal = document.getElementById(modalId);
    const input = document.getElementById(modalId + 'ConfirmInput');
    const button = document.getElementById(modalId + 'ConfirmBtn');

    modal.classList.add('hidden');
    modal.classList.remove('flex');
    input.value = '';
    button.disabled = true;
}

function executeDelete(formId) {
    document.getElementById(formId).submit();
}

// 入力フィールドの監視を設定
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[id$="ConfirmInput"]').forEach(function(input) {
        const modalId = input.id.replace('ConfirmInput', '');
        const button = document.getElementById(modalId + 'ConfirmBtn');

        input.addEventListener('input', function() {
            button.disabled = this.value !== 'delete';
        });

        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && this.value === 'delete') {
                button.click();
            }
        });
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
});
</script>
@endpush
@endonce