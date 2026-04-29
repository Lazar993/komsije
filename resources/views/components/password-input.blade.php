@props([
    'id' => 'password',
    'name' => 'password',
    'inputClass' => 'komsije-input w-full rounded-2xl px-4 py-3 text-slate-950 transition',
])

<div class="relative" data-password-wrapper>
    <input
        {{ $attributes->merge([
            'id' => $id,
            'name' => $name,
            'type' => 'password',
            'class' => $inputClass . ' pr-12',
            'autocomplete' => 'current-password',
        ]) }}
    >
    <button
        type="button"
        data-password-toggle
        aria-label="{{ __('Show password') }}"
        aria-pressed="false"
        class="absolute inset-y-0 right-0 flex items-center justify-center px-3 text-slate-500 transition hover:text-slate-700 focus:outline-none focus-visible:text-[var(--komsije-primary)]"
    >
        <svg data-icon="eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.644C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .644C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
        <svg data-icon="eye-off" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="hidden h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 11.678a1.012 1.012 0 0 0 0 .644C3.423 16.49 7.36 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .644a10.443 10.443 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
        </svg>
    </button>
</div>

@once
    <script>
        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-password-toggle]');
            if (!button) return;
            const wrapper = button.closest('[data-password-wrapper]');
            if (!wrapper) return;
            const input = wrapper.querySelector('input');
            if (!input) return;
            const showing = input.getAttribute('type') === 'text';
            input.setAttribute('type', showing ? 'password' : 'text');
            button.setAttribute('aria-pressed', showing ? 'false' : 'true');
            button.setAttribute('aria-label', showing ? @json(__('Show password')) : @json(__('Hide password')));
            const eye = button.querySelector('[data-icon="eye"]');
            const eyeOff = button.querySelector('[data-icon="eye-off"]');
            if (eye && eyeOff) {
                eye.classList.toggle('hidden', !showing);
                eyeOff.classList.toggle('hidden', showing);
            }
        });
    </script>
@endonce
