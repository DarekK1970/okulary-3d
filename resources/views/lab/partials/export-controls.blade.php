<div class="lab-control">
    <label for="export-size">{{ __('lab.common.export.size') }}</label>
    <select id="export-size" data-control="exportSize">
        <option value="1200">1200 px</option>
        <option value="2400" selected>2400 px</option>
        <option value="4096">4096 px</option>
        <option value="original">{{ __('lab.common.export.original') }}</option>
    </select>
</div>

<button
    class="lab-primary-button lab-export-button"
    type="button"
    data-action="export"
>
    ↓ {{ __('lab.common.export.button') }}
</button>

<p class="lab-export-note">
    {{ __('lab.common.export.note') }}
</p>
