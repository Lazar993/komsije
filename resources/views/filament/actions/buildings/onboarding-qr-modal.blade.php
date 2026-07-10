@php
    /** @var \App\Models\Building $building */
@endphp

<div class="space-y-3">
    <div class="rounded-xl border border-gray-200 bg-white p-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Building') }}</p>
        <p class="mt-1 text-sm font-semibold text-gray-900">{{ $building->name }}</p>
        @if (auth()->user()?->isSuperAdmin())
            <p class="mt-1 text-xs text-gray-500">{{ __('Onboarding token') }}: <span class="font-mono">{{ $token }}</span></p>
        @endif
    </div>

    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 text-center">
        <img src="{{ $qrDataUri }}" alt="QR" class="mx-auto h-36 w-36 rounded-lg border border-gray-200 bg-white p-1.5 sm:h-40 sm:w-40">
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Join link') }}</p>
        <p class="mt-1 break-all text-sm text-gray-800">{{ $joinUrl }}</p>
        <div x-data="{ copied: false }" class="mt-2">
            <x-filament::button
                color="gray"
                icon="heroicon-o-link"
                size="sm"
                type="button"
                data-link="{{ $joinUrl }}"
                data-copy-prompt="{{ __('Copy this link:') }}"
                x-on:click="
                    const text = $el.dataset.link;
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(text).then(() => {
                            copied = true;
                            setTimeout(() => copied = false, 1400);
                        });
                    } else {
                        window.prompt($el.dataset.copyPrompt, text);
                    }
                "
            >
                {{ __('Copy link') }}
            </x-filament::button>
            <span x-show="copied" x-cloak class="ml-2 inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ __('Copied') }}</span>
        </div>
    </div>
</div>
