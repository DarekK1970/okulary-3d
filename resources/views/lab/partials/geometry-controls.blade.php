<section class="lab-panel">
    <div class="lab-panel-title">
        <span>02</span>
        <div>
            <h2>{{ __('lab.common.geometry.title') }}</h2>
            <p>{{ __('lab.common.geometry.help') }}</p>
        </div>
    </div>

    <div class="lab-range-control">
        <div>
            <label for="shift-x">{{ __('lab.common.geometry.shift_x') }}</label>
            <output data-output="shiftX">0 px</output>
        </div>
        <input
            id="shift-x"
            type="range"
            min="-150"
            max="150"
            step="1"
            value="0"
            data-control="shiftX"
        >
    </div>

    <div class="lab-range-control">
        <div>
            <label for="shift-y">{{ __('lab.common.geometry.shift_y') }}</label>
            <output data-output="shiftY">0 px</output>
        </div>
        <input
            id="shift-y"
            type="range"
            min="-100"
            max="100"
            step="1"
            value="0"
            data-control="shiftY"
        >
    </div>

    <div class="lab-range-control">
        <div>
            <label for="scale-right">{{ __('lab.common.geometry.scale') }}</label>
            <output data-output="scale">100%</output>
        </div>
        <input
            id="scale-right"
            type="range"
            min="92"
            max="108"
            step="0.1"
            value="100"
            data-control="scale"
        >
    </div>

    <div class="lab-range-control">
        <div>
            <label for="rotation-right">{{ __('lab.common.geometry.rotation') }}</label>
            <output data-output="rotation">0°</output>
        </div>
        <input
            id="rotation-right"
            type="range"
            min="-3"
            max="3"
            step="0.05"
            value="0"
            data-control="rotation"
        >
    </div>

    <div class="lab-geometry-tip">
        {{ __('lab.common.geometry.tip') }}
    </div>
</section>
