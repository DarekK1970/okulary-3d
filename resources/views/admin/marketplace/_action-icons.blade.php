<div class="article-action-icons">
    <a class="article-action-icon" href="{{ $editUrl }}" title="{{ __('marketplace.admin.common.edit') }}" aria-label="{{ __('marketplace.admin.common.edit') }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4l10.5-10.5a2.8 2.8 0 0 0-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg>
    </a>
    <form method="post" action="{{ route('admin.translations.translate', ['type' => $translationType, 'id' => $item->id]) }}">
        @csrf
        <button class="article-action-icon is-translate" type="submit" title="{{ __('marketplace.admin.common.ai_translation') }}" aria-label="{{ __('marketplace.admin.common.ai_translation') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3.5 12h17"/><path d="M12 3c2.2 2.4 3.4 5.4 3.4 9S14.2 18.6 12 21"/><path d="M12 3C9.8 5.4 8.6 8.4 8.6 12S9.8 18.6 12 21"/></svg>
        </button>
    </form>
    @if($canDelete)
        <form method="post" action="{{ $deleteUrl }}" onsubmit="return confirm('{{ __('marketplace.admin.common.delete_confirm') }}')">
            @csrf @method('DELETE')
            <button class="article-action-icon is-danger" type="submit" title="{{ __('marketplace.admin.common.delete') }}" aria-label="{{ __('marketplace.admin.common.delete') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16"/><path d="M9 7V4h6v3"/><path d="M6.5 7 7.5 20h9l1-13"/><path d="M10 11v5"/><path d="M14 11v5"/></svg>
            </button>
        </form>
    @endif
</div>
