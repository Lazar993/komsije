@extends('portal.layouts.app')

@section('title', __('Edit Announcement'))

@section('content')
    <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <article class="komsije-surface min-w-0 rounded-[2rem] p-6 sm:p-8">
            <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Izmena objave') }}</p>
            <h1 class="mt-3 break-words text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">{{ $announcement->title }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ __('Ažurirajte sadržaj, vreme objave ili status objavljivanja za ovu poruku.') }}</p>

            <div class="mt-8">
                @include('portal.announcements._form', ['announcement' => $announcement])
            </div>
        </article>

        <aside class="komsije-surface min-w-0 rounded-[2rem] p-6">
            <h2 class="text-lg font-semibold text-slate-950">{{ __('Saveti za izmenu') }}</h2>
            <div class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                <p>{{ __('Ažurirajte naslov kada želite da stanari odmah prepoznaju novu informaciju.') }}</p>
                <p>{{ __('Ako objava ne treba odmah da bude vidljiva, podesite vreme objave.') }}</p>
                <p>{{ __('Uklonite zastarele priloge i dodajte nove samo kada su potrebni.') }}</p>
            </div>
        </aside>
    </section>
@endsection