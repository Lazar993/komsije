@php
    $ranges = $this->getRangeOptions();
    $buildings = $this->getBuildingOptions();
    $showBuildingFilter = $this->hasMultipleBuildings();
    $pollingInterval = $this->getPollingInterval();
@endphp

<x-filament-widgets::widget
    :attributes="
        (new \Illuminate\View\ComponentAttributeBag)
            ->merge([
                'wire:poll.' . $pollingInterval => $pollingInterval ? true : null,
            ], escape: false)
            ->class([
                'fi-wi-stats-overview',
            ])
    "
>
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <div style="display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem;">
            <div>
                <h2 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ __('Management overview') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $this->getRangeDescription() }}
                </p>
            </div>

            <div style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem;" wire:loading.class="opacity-70">
                @if ($showBuildingFilter)
                    <div style="display: flex; flex-direction: column; gap: 0.375rem;">
                        <label for="stats-building" class="text-xs font-medium text-gray-500 dark:text-gray-400">
                            {{ __('Building') }}
                        </label>
                        <div style="width: 13rem;">
                            <x-filament::input.wrapper>
                                <x-filament::input.select id="stats-building" wire:model.live="building">
                                    @foreach ($buildings as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                        </div>
                    </div>
                @endif

                <div style="display: flex; flex-direction: column; gap: 0.375rem;">
                    <label for="stats-range" class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ __('Filter by period') }}
                    </label>
                    <div style="width: 11rem;">
                        <x-filament::input.wrapper>
                            <x-filament::input.select id="stats-range" wire:model.live="range">
                                @foreach ($ranges as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                </div>
            </div>
        </div>

        {{ $this->content }}
    </div>
</x-filament-widgets::widget>
