@extends('layouts.presentation')

@php
    $pageTitle = __('Za profesionalne upravnike');
    $pageDescription = __('Komšije je platforma za profesionalne upravnike zgrada i upravljačke firme: kvarovi, obaveštenja, ankete i komunikacija sa stanarima na jednom mestu, uz 30 dana besplatnog perioda.');
    $canonical = route('pages.professionals');
    $ogImage = asset('icons/logo-icon-v3.svg');
@endphp

@section('title', $pageTitle.' - Komšije')
@section('meta_description', $pageDescription)

@section('head')
    <link rel="canonical" href="{{ $canonical }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Komšije">
    <meta property="og:title" content="{{ $pageTitle }} - Komšije">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">

    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $pageTitle }} - Komšije">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => 'Komšije',
            'applicationCategory' => 'BusinessApplication',
            'description' => $pageDescription,
            'url' => $canonical,
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'RSD',
                'description' => __('30 dana besplatnog probnog perioda, bez obaveze.'),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endsection

@section('content')
    <div class="relative isolate overflow-hidden">
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[32rem] bg-[radial-gradient(ellipse_at_top,rgba(37,99,235,0.14),transparent_62%)]"></div>

        {{-- ===================== HERO ===================== --}}
        <section class="mx-auto w-full max-w-7xl px-5 pt-10 pb-10 sm:px-8 sm:pt-16 sm:pb-14 lg:pb-24">
            <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_0.95fr]">
                <div class="min-w-0">
                    <p class="inline-flex rounded-full bg-blue-50 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.22em] text-[var(--komsije-primary)]">
                        {{ __('Za profesionalne upravnike') }}
                    </p>
                    <h1 class="mt-6 text-balance break-words text-3xl font-semibold leading-[1.12] tracking-tight text-[var(--komsije-dark)] sm:text-4xl md:text-5xl lg:text-[3.5rem]">
                        {{ __('Digitalizujte komunikaciju sa stanarima') }}
                    </h1>
                    <p class="mt-6 max-w-xl text-pretty text-lg leading-relaxed text-slate-600">
                        {{ __('Komšije je platforma koja upravnicima zgrada i upravljačkim firmama omogućava da na jednom mestu vode kvarove, obaveštenja, ankete i svakodnevnu komunikaciju sa stanarima - jasno, transparentno i bez haosa.') }}
                    </p>

                    <div class="mt-9 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <a href="{{ $contactUrl }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[var(--komsije-primary)] px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-700">
                            {{ __('Prijavite svoju zgradu za probni period') }}
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.22 4.22a.75.75 0 0 1 1.06 0l5.25 5.25a.75.75 0 0 1 0 1.06l-5.25 5.25a.75.75 0 1 1-1.06-1.06L11.94 10 7.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </a>
                        <span class="text-sm text-slate-500">
                            {{ __('30 dana besplatno · bez ugovorne obaveze') }}
                        </span>
                    </div>
                </div>

                <div class="relative min-w-0">
                    <div class="komsije-surface rounded-[2rem] p-6 sm:p-8">
                        <div class="flex items-center gap-3">
                            <img src="{{ asset('icons/logo-icon-v3.svg') }}" alt="" class="h-11 w-11 rounded-2xl" width="44" height="44" loading="lazy">
                            <div>
                                <p class="text-base font-semibold text-[var(--komsije-dark)]">Komšije</p>
                                <p class="text-sm text-slate-500">{{ __('Sve u vezi zgrade, na jednom mestu.') }}</p>
                            </div>
                        </div>
                        <div class="mt-6 space-y-3">
                            @foreach ([
                                ['tickets', __('Nova prijava kvara - lift'), __('U obradi')],
                                ['announcements', __('Obaveštenje: čišćenje fasade'), __('Objavljeno')],
                                ['bell', __('Push podsetnik za anketu'), __('Poslato')],
                            ] as [$icon, $label, $status])
                                <div class="flex items-center gap-3 rounded-[1.25rem] border border-[var(--komsije-border)] bg-white/80 px-4 py-3">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-50 text-[var(--komsije-primary)]">
                                        <x-portal.app-icon :name="$icon" class="h-4.5 w-4.5" />
                                    </span>
                                    <span class="min-w-0 flex-1 text-sm font-medium text-slate-700">{{ $label }}</span>
                                    <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">{{ $status }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== PROBLEMS ===================== --}}
        <section class="mx-auto w-full max-w-7xl px-5 py-10 sm:px-8 sm:py-14 lg:py-20">
            <div class="max-w-2xl">
                <h2 class="text-3xl font-semibold tracking-tight text-[var(--komsije-dark)] sm:text-4xl">
                    {{ __('Problemi koje Komšije rešava') }}
                </h2>
                <p class="mt-4 text-lg leading-relaxed text-slate-600">
                    {{ __('Svakodnevno upravljanje zgradom najčešće se odvija kroz pozive i poruke koje se lako izgube. Komšije te tokove zamenjuje jednim preglednim mestom.') }}
                </p>
            </div>

            <div class="mt-8 sm:mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    [__('Previše telefonskih poziva'), __('Upravnik dnevno prima desetine poziva za iste probleme, bez ikakve evidencije.')],
                    [__('Haos u Viber grupama'), __('Važne informacije se gube između stotina nepovezanih poruka.')],
                    [__('Izgubljene poruke'), __('Prijava kvara prosleđena u razgovoru lako ostane nezabeležena i nerešena.')],
                    [__('Nema istorije prijava'), __('Ne zna se ko je šta prijavio, kada i da li je problem rešen.')],
                    [__('Otežana komunikacija'), __('Stanari i upravnik nemaju zajednički kanal, pa se informacije prenose usmeno.')],
                    [__('Nedostatak transparentnosti'), __('Stanari ne vide status svojih prijava niti odluke koje se tiču zgrade.')],
                ] as [$title, $desc])
                    <div class="rounded-[1.5rem] border border-[var(--komsije-border)] bg-white/70 p-6 transition hover:border-blue-200 hover:shadow-[0_18px_50px_-30px_rgba(15,23,42,0.35)]">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-500">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M15 9l-6 6M9 9l6 6" />
                                <circle cx="12" cy="12" r="9" />
                            </svg>
                        </span>
                        <h3 class="mt-5 text-lg font-semibold text-[var(--komsije-dark)]">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ===================== FEATURES ===================== --}}
        <section class="mx-auto w-full max-w-7xl px-5 py-10 sm:px-8 sm:py-14 lg:py-20">
            <div class="max-w-2xl">
                <h2 class="text-3xl font-semibold tracking-tight text-[var(--komsije-dark)] sm:text-4xl">
                    {{ __('Ključne funkcionalnosti') }}
                </h2>
                <p class="mt-4 text-lg leading-relaxed text-slate-600">
                    {{ __('Sve što je upravniku potrebno za vođenje zgrade, objedinjeno u jednoj aplikaciji dostupnoj i na računaru i na telefonu.') }}
                </p>
            </div>

            <div class="mt-8 sm:mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $features = [
                        [
                            'title' => __('Obaveštenja'),
                            'desc' => __('Objavite važne informacije svim stanarima odjednom, uz evidenciju ko ih je pročitao.'),
                            'svg' => '<path d="M5 10.5V8.75A2.75 2.75 0 0 1 7.75 6h8.5A2.75 2.75 0 0 1 19 8.75v6.5A2.75 2.75 0 0 1 16.25 18h-7.5L5 21v-3.75" /><path d="M8.5 10h7" /><path d="M8.5 13.5h5" />',
                        ],
                        [
                            'title' => __('Upravljanje prijavama'),
                            'desc' => __('Pratite kvarove kroz jasne statuse, komentare, zadužene osobe i kompletnu istoriju.'),
                            'svg' => '<path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v2a2 2 0 0 0 0 4v2A2.5 2.5 0 0 1 17.5 18h-11A2.5 2.5 0 0 1 4 15.5v-2a2 2 0 0 0 0-4v-2Z" /><path d="M9 9.5h6" /><path d="M9 14.5h4" />',
                        ],
                        [
                            'title' => __('Ankete'),
                            'desc' => __('Donosite odluke zajedno sa stanarima uz brze i transparentne ankete.'),
                            'svg' => '<path d="M7 20V10" /><path d="M12 20V4" /><path d="M17 20v-6" /><path d="M4 20h16" />',
                        ],
                        [
                            'title' => __('Push notifikacije'),
                            'desc' => __('Stanari odmah dobijaju obaveštenja o kvarovima, objavama i anketama na telefon.'),
                            'svg' => '<path d="M9.25 19a2.75 2.75 0 0 0 5.5 0" /><path d="M5.5 17.5h13l-1.15-1.92a4.5 4.5 0 0 1-.65-2.3V10a4.7 4.7 0 1 0-9.4 0v3.28a4.5 4.5 0 0 1-.65 2.3L5.5 17.5Z" />',
                        ],
                        [
                            'title' => __('Komunikacija sa stanarima'),
                            'desc' => __('Jedan zajednički kanal za sve stanare umesto razbacanih poruka i poziva.'),
                            'svg' => '<path d="M5 6.5A2.5 2.5 0 0 1 7.5 4h9A2.5 2.5 0 0 1 19 6.5v6A2.5 2.5 0 0 1 16.5 15H9l-4 4v-4H7.5" /><path d="M8.5 8.5h7" /><path d="M8.5 11.5h4" />',
                        ],
                        [
                            'title' => __('PWA mobilno iskustvo'),
                            'desc' => __('Komšije se instalira kao aplikacija na telefon, bez potrebe za prodavnicom aplikacija.'),
                            'svg' => '<rect x="7" y="3" width="10" height="18" rx="2.5" /><path d="M11 18h2" />',
                        ],
                    ];
                @endphp

                @foreach ($features as $feature)
                    <div class="komsije-surface rounded-[1.5rem] p-6 transition hover:-translate-y-0.5 hover:shadow-[0_28px_70px_-34px_rgba(15,23,42,0.4)]">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-[var(--komsije-primary)]">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $feature['svg'] !!}</svg>
                        </span>
                        <h3 class="mt-5 text-lg font-semibold text-[var(--komsije-dark)]">{{ $feature['title'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ===================== HOW IT WORKS ===================== --}}
        <section class="mx-auto w-full max-w-7xl px-5 py-10 sm:px-8 sm:py-14 lg:py-20">
            <div class="max-w-2xl">
                <h2 class="text-3xl font-semibold tracking-tight text-[var(--komsije-dark)] sm:text-4xl">
                    {{ __('Kako funkcioniše') }}
                </h2>
                <p class="mt-4 text-lg leading-relaxed text-slate-600">
                    {{ __('Uvođenje Komšija je jednostavno i ne zahteva nikakvu tehničku pripremu sa vaše strane.') }}
                </p>
            </div>

            <ol class="mt-8 sm:mt-12 grid gap-6 md:grid-cols-5">
                @php
                    $steps = [
                        [__('Kontaktirajte nas'), __('Javite nam se i dogovorimo osnovne detalje o zgradi.')],
                        [__('Kreiramo vašu zgradu'), __('Postavljamo zgradu, stanove i vaš upravnički nalog.')],
                        [__('Pozovite stanare'), __('Stanari dobijaju pozivnicu i pristupaju svojoj zgradi.')],
                        [__('Koristite Komšije 30 dana'), __('Isprobajte sve funkcionalnosti u realnom radu.')],
                        [__('Odaberite da li nastavljate'), __('Bez pritiska odlučujete da li ostajete na platformi.')],
                    ];
                @endphp

                @foreach ($steps as $index => [$title, $desc])
                    <li class="relative rounded-[1.5rem] border border-[var(--komsije-border)] bg-white/70 p-6">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-[var(--komsije-dark)] text-base font-semibold text-white">
                            {{ $index + 1 }}
                        </span>
                        @unless ($loop->last)
                            <span class="pointer-events-none absolute right-4 top-9 hidden text-slate-300 md:block" aria-hidden="true">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.22 4.22a.75.75 0 0 1 1.06 0l5.25 5.25a.75.75 0 0 1 0 1.06l-5.25 5.25a.75.75 0 1 1-1.06-1.06L11.94 10 7.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        @endunless
                        <h3 class="mt-5 text-base font-semibold text-[var(--komsije-dark)]">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $desc }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- ===================== 30-DAY TRIAL ===================== --}}
        <section class="mx-auto w-full max-w-7xl px-5 py-10 sm:px-8 sm:py-14 lg:py-20">
            <div class="overflow-hidden rounded-[2rem] bg-[var(--komsije-dark)] px-6 py-10 text-white sm:px-10 sm:py-12 lg:px-14">
                <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
                    <div>
                        <p class="inline-flex rounded-full bg-white/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.22em] text-blue-100">
                            {{ __('Probni period') }}
                        </p>
                        <h2 class="mt-5 text-3xl font-semibold tracking-tight sm:text-4xl">
                            {{ __('30 dana besplatno') }}
                        </h2>
                        <p class="mt-4 max-w-md text-base leading-relaxed text-slate-300">
                            {{ __('Isprobajte Komšije u punom kapacitetu, bez unapred definisane obaveze i bez rizika po vaše podatke.') }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ([
                            [__('Sve funkcionalnosti dostupne'), __('Tokom probnog perioda imate pristup svim mogućnostima platforme.')],
                            [__('Bez obaveze'), __('Ne potpisujete ugovor i niste u obavezi da nastavite.')],
                            [__('Podsetnik pre isteka'), __('Blagovremeno vas obaveštavamo pre kraja probnog perioda.')],
                            [__('Podaci se čuvaju'), __('I nakon isteka probnog perioda vaši podaci ostaju sačuvani.')],
                        ] as [$title, $desc])
                            <div class="rounded-[1.25rem] border border-white/10 bg-white/5 p-5">
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-400/20 text-emerald-300">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 0 1 1.4-1.4l3.3 3.29 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                    <div>
                                        <h3 class="text-sm font-semibold text-white">{{ $title }}</h3>
                                        <p class="mt-1 text-sm leading-6 text-slate-300">{{ $desc }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== SECURITY ===================== --}}
        <section class="mx-auto w-full max-w-7xl px-5 py-10 sm:px-8 sm:py-14 lg:py-20">
            <div class="max-w-2xl">
                <h2 class="text-3xl font-semibold tracking-tight text-[var(--komsije-dark)] sm:text-4xl">
                    {{ __('Bezbednost') }}
                </h2>
                <p class="mt-4 text-lg leading-relaxed text-slate-600">
                    {{ __('Podaci vaše zgrade i stanara zaštićeni su na više nivoa.') }}
                </p>
            </div>

            <div class="mt-8 sm:mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @php
                    $security = [
                        [
                            'title' => __('HTTPS'),
                            'desc' => __('Sva komunikacija je šifrovana i zaštićena tokom prenosa.'),
                            'svg' => '<rect x="5" y="10" width="14" height="10" rx="2.5" /><path d="M8 10V8a4 4 0 0 1 8 0v2" /><path d="M12 14v2" />',
                        ],
                        [
                            'title' => __('Izolacija zgrada'),
                            'desc' => __('Podaci svake zgrade su odvojeni i vidljivi samo njenim članovima.'),
                            'svg' => '<path d="M4 20V9l8-5 8 5v11" /><path d="M9 20v-6h6v6" /><path d="M4 20h16" />',
                        ],
                        [
                            'title' => __('Bezbedna autentifikacija'),
                            'desc' => __('Pristup je moguć samo uz proveren nalog i lične pristupne podatke.'),
                            'svg' => '<rect x="4" y="10" width="16" height="10" rx="2.5" /><path d="M8 10V8a4 4 0 0 1 8 0v2" /><circle cx="12" cy="15" r="1.5" />',
                        ],
                        [
                            'title' => __('Redovne rezervne kopije'),
                            'desc' => __('Podaci se redovno arhiviraju kako ništa ne bi bilo izgubljeno.'),
                            'svg' => '<ellipse cx="12" cy="6" rx="7" ry="3" /><path d="M5 6v6c0 1.66 3.13 3 7 3s7-1.34 7-3V6" /><path d="M5 12v6c0 1.66 3.13 3 7 3s7-1.34 7-3v-6" />',
                        ],
                    ];
                @endphp

                @foreach ($security as $item)
                    <div class="rounded-[1.5rem] border border-[var(--komsije-border)] bg-white/70 p-6">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['svg'] !!}</svg>
                        </span>
                        <h3 class="mt-5 text-base font-semibold text-[var(--komsije-dark)]">{{ $item['title'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $item['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ===================== FAQ ===================== --}}
        <section class="mx-auto w-full max-w-3xl px-5 py-10 sm:px-8 sm:py-14 lg:py-20">
            <div class="text-center">
                <h2 class="text-3xl font-semibold tracking-tight text-[var(--komsije-dark)] sm:text-4xl">
                    {{ __('Česta pitanja') }}
                </h2>
                <p class="mt-4 text-lg leading-relaxed text-slate-600">
                    {{ __('Odgovori na pitanja koja upravnici najčešće postavljaju pre uvođenja platforme.') }}
                </p>
            </div>

            <div class="mt-8 sm:mt-10 divide-y divide-[var(--komsije-border)] overflow-hidden rounded-[1.5rem] border border-[var(--komsije-border)] bg-white/80">
                @foreach ([
                    [__('Kako se stanari pridružuju?'), __('Stanari dobijaju pozivnicu putem koje kreiraju nalog i odmah pristupaju svojoj zgradi. Nije potrebna nikakva tehnička priprema.')],
                    [__('Da li radi na iPhone uređajima?'), __('Da. Komšije radi na iPhone uređajima kroz veb pregledač i može se dodati na početni ekran kao aplikacija.')],
                    [__('Da li radi na Android uređajima?'), __('Da. Na Android uređajima Komšije se instalira kao PWA aplikacija i podržava push notifikacije.')],
                    [__('Da li stanari plaćaju?'), __('Ne. Stanari ne plaćaju korišćenje platforme; ona je namenjena upravniku zgrade.')],
                    [__('Šta se dešava nakon probnog perioda?'), __('Nakon 30 dana birate da li nastavljate. Vaši podaci se čuvaju čak i ako probni period istekne.')],
                    [__('Možemo li kasnije ponovo aktivirati nalog?'), __('Da. Nalog i podaci se mogu ponovo aktivirati u bilo kom trenutku, bez gubitka istorije.')],
                ] as [$question, $answer])
                    <details class="group px-6 py-5">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left">
                            <span class="text-base font-semibold text-[var(--komsije-dark)]">{{ $question }}</span>
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition group-open:rotate-45 group-open:bg-blue-50 group-open:text-[var(--komsije-primary)]">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                    <path d="M12 5v14M5 12h14" />
                                </svg>
                            </span>
                        </summary>
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </section>

        {{-- ===================== FINAL CTA ===================== --}}
        <section class="mx-auto w-full max-w-7xl px-5 pb-16 pt-2 sm:px-8 sm:pb-24">
            <div class="komsije-surface relative overflow-hidden rounded-[2rem] bg-[linear-gradient(180deg,rgba(255,255,255,0.96),rgba(239,246,255,0.96))] px-6 py-12 text-center sm:px-10 sm:py-16">
                <div class="pointer-events-none absolute inset-x-0 top-0 h-40 bg-[radial-gradient(ellipse_at_top,rgba(37,99,235,0.16),transparent_70%)]"></div>
                <h2 class="mx-auto max-w-2xl text-balance text-3xl font-semibold tracking-tight text-[var(--komsije-dark)] sm:text-4xl">
                    {{ __('Spremni da modernizujete svoju zgradu?') }}
                </h2>
                <p class="mx-auto mt-4 max-w-xl text-lg leading-relaxed text-slate-600">
                    {{ __('Započnite 30 dana besplatno i uverite se koliko je upravljanje zgradom jednostavnije uz Komšije.') }}
                </p>
                <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ $contactUrl }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[var(--komsije-primary)] px-7 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-700">
                        {{ __('Zatražite 30 dana besplatno') }}
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.22 4.22a.75.75 0 0 1 1.06 0l5.25 5.25a.75.75 0 0 1 0 1.06l-5.25 5.25a.75.75 0 1 1-1.06-1.06L11.94 10 7.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </a>
                    <span class="text-sm text-slate-500">{{ __('Odgovaramo u najkraćem roku.') }}</span>
                </div>
            </div>
        </section>
    </div>
@endsection
