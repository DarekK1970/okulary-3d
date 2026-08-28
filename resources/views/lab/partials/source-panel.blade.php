<section class="lab-source-panel">
    <div class="lab-source-heading">
        <div>
            <span class="lab-kicker">{{ __('lab.common.sources_kicker') }}</span>
            <h2>{{ __('lab.common.sources_title') }}</h2>
        </div>

        <p>{{ __('lab.common.sources_help') }}</p>
    </div>

    <div class="lab-drop-grid">
        <label class="lab-dropzone" data-dropzone="left">
            <input
                type="file"
                accept="image/jpeg,image/png,image/webp"
                data-file-input="left"
            >
            <span class="lab-drop-side">L</span>
            <strong>{{ __('lab.common.left_image') }}</strong>
            <span>{{ __('lab.common.choose_or_drop') }}</span>
            <small data-file-name="left">{{ __('lab.common.no_file') }}</small>
        </label>

        <label class="lab-dropzone" data-dropzone="right">
            <input
                type="file"
                accept="image/jpeg,image/png,image/webp"
                data-file-input="right"
            >
            <span class="lab-drop-side">R</span>
            <strong>{{ __('lab.common.right_image') }}</strong>
            <span>{{ __('lab.common.choose_or_drop') }}</span>
            <small data-file-name="right">{{ __('lab.common.no_file') }}</small>
        </label>
    </div>
</section>
