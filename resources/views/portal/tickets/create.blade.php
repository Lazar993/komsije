@extends('portal.layouts.app')

@section('title', __('Create Ticket'))

@section('content')
    @php
        $isQuickReport = ! auth()->user()->isBuildingAdmin($currentBuilding->getKey());
    @endphp

    <section class="grid gap-6 {{ $isQuickReport ? 'lg:grid-cols-[minmax(0,1fr)_18rem]' : 'lg:grid-cols-[minmax(0,1fr)_20rem]' }}">
        <article class="komsije-surface rounded-[2rem] p-6 sm:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Prijavi kvar') }}</p>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ $isQuickReport ? __('Brza prijava za :building', ['building' => $currentBuilding->name]) : __('Prijavite problem u zgradi :building', ['building' => $currentBuilding->name]) }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ $isQuickReport ? __('Od otvaranja forme do slanja prijave stižete za nekoliko sekundi. Opišite problem, po želji dodajte fotografiju i pošaljite.') : __('Kratko opišite kvar i, ako želite, dodajte fotografije. Sve bitno treba da stane u par koraka.') }}</p>
                </div>

                @if ($isQuickReport)
                    <span class="inline-flex w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-[var(--komsije-primary)]">{{ __('2 dodira') }}</span>
                @endif
            </div>

            <div class="mt-8">
                @include('portal.tickets._form')
            </div>
        </article>

        <aside class="{{ $isQuickReport ? 'hidden lg:block ' : '' }}komsije-surface rounded-[2rem] p-6">
            <h2 class="text-lg font-semibold text-slate-950">{{ $isQuickReport ? __('Najbrži put') : __('Saveti za bržu obradu') }}</h2>
            <div class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                @if ($isQuickReport)
                    <p>{{ __('Opišite šta se desilo.') }}</p>
                    <p>{{ __('Dodajte stan, hitnost ili fotografiju samo ako pomažu da kvar brže stigne do upravnika.') }}</p>
                    <p>{{ __('Pošaljite prijavu odmah, a dodatne detalje možete uneti i kasnije kroz komentar.') }}</p>
                @else
                    <p>{{ __('Navedite gde je problem nastao i kada ste ga primetili.') }}</p>
                    <p>{{ __('Ako postoji hitnost, izaberite odgovarajući prioritet.') }}</p>
                    <p>{{ __('Fotografija često ubrzava dodelu i rešavanje kvara.') }}</p>
                @endif
            </div>
        </aside>
    </section>
@endsection