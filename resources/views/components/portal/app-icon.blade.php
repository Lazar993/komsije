@props([
    'name',
    'class' => 'h-5 w-5',
])

@switch($name)
    @case('home')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round']) }}>
            <path d="M3 10.75 12 3l9 7.75" />
            <path d="M5.25 9.5V20a1 1 0 0 0 1 1h3.75v-6h4v6h3.75a1 1 0 0 0 1-1V9.5" />
        </svg>
        @break

    @case('tickets')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round']) }}>
            <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v2a2 2 0 0 0 0 4v2A2.5 2.5 0 0 1 17.5 18h-11A2.5 2.5 0 0 1 4 15.5v-2a2 2 0 0 0 0-4v-2Z" />
            <path d="M9 9.5h6" />
            <path d="M9 14.5h4" />
        </svg>
        @break

    @case('announcements')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round']) }}>
            <path d="M5 10.5V8.75A2.75 2.75 0 0 1 7.75 6h8.5A2.75 2.75 0 0 1 19 8.75v6.5A2.75 2.75 0 0 1 16.25 18h-7.5L5 21v-3.75" />
            <path d="M8.5 10h7" />
            <path d="M8.5 13.5h5" />
        </svg>
        @break

    @case('profile')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round']) }}>
            <path d="M19.25 20a7.25 7.25 0 0 0-14.5 0" />
            <circle cx="12" cy="8" r="3.25" />
        </svg>
        @break

    @case('bell')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round']) }}>
            <path d="M9.25 19a2.75 2.75 0 0 0 5.5 0" />
            <path d="M5.5 17.5h13l-1.15-1.92a4.5 4.5 0 0 1-.65-2.3V10a4.7 4.7 0 1 0-9.4 0v3.28a4.5 4.5 0 0 1-.65 2.3L5.5 17.5Z" />
        </svg>
        @break

    @case('admin')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round']) }}>
            <path d="M12 3l6.25 2.5v5.75c0 4.2-2.54 8.08-6.25 9.75-3.71-1.67-6.25-5.55-6.25-9.75V5.5L12 3Z" />
            <path d="M9.5 12.25 11.25 14 14.75 10.5" />
        </svg>
        @break

    @case('plus')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round']) }}>
            <path d="M12 5v14" />
            <path d="M5 12h14" />
        </svg>
        @break
@endswitch