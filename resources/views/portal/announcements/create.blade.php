@extends('portal.layouts.app')

@section('title', __('Create Announcement'))

@section('content')
    <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <article class="komsije-surface rounded-[2rem] p-6 sm:p-8">
            <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Nova objava') }}</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ __('Napišite obaveštenje za :building', ['building' => $currentBuilding->name]) }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ __('Objava treba da bude jasna i kratka, sa vremenom objave ako nije za odmah.') }}</p>

            <div class="mt-8">
                @include('portal.announcements._form')
            </div>
        </article>

        <aside class="komsije-surface rounded-[2rem] p-6">
            <h2 class="text-lg font-semibold text-slate-950">{{ __('Dobra objava') }}</h2>
            <div class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                <p>{{ __('Naslov neka bude kratak i prepoznatljiv već na listi obaveštenja.') }}</p>
                <p>{{ __('U prvom pasusu navedite šta stanari treba da znaju ili urade.') }}</p>
                <p>{{ __('Ako nije hitno, možete sačuvati nacrt i objaviti kasnije.') }}</p>
            </div>
        </aside>
    </section>
@endsection