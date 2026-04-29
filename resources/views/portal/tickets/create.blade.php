@extends('portal.layouts.app')

@section('title', __('Create Ticket'))

@section('content')
    <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <article class="komsije-surface rounded-[2rem] p-6 sm:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Prijavi kvar') }}</p>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ __('Prijavite problem u zgradi :building', ['building' => $currentBuilding->name]) }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ __('Kratko opišite kvar i, ako želite, dodajte fotografije. Sve bitno treba da stane u par koraka.') }}</p>
                </div>
            </div>

            <div class="mt-8">
                @include('portal.tickets._form')
            </div>
        </article>

        <aside class="komsije-surface rounded-[2rem] p-6">
            <h2 class="text-lg font-semibold text-slate-950">{{ __('Saveti za bržu obradu') }}</h2>
            <div class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                <p>{{ __('Navedite gde je problem nastao i kada ste ga primetili.') }}</p>
                <p>{{ __('Ako postoji hitnost, izaberite odgovarajući prioritet.') }}</p>
                <p>{{ __('Fotografija često ubrzava dodelu i rešavanje kvara.') }}</p>
            </div>
        </aside>
    </section>
@endsection
