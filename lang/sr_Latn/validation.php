<?php

declare(strict_types=1);

return [
    'accepted' => ':attribute mora biti prihvaćen.',
    'array' => ':attribute mora biti niz.',
    'boolean' => 'Polje :attribute mora biti tačno ili netačno.',
    'date' => ':attribute nije ispravan datum.',
    'email' => ':attribute mora biti ispravna email adresa.',
    'enum' => 'Izabrani :attribute nije ispravan.',
    'exists' => 'Izabrani :attribute nije ispravan.',
    'file' => ':attribute mora biti fajl.',
    'image' => ':attribute mora biti slika.',
    'integer' => ':attribute mora biti ceo broj.',
    'max' => [
        'array' => ':attribute ne sme imati više od :max stavki.',
        'file' => ':attribute ne sme biti veći od :max kilobajta.',
        'numeric' => ':attribute ne sme biti veći od :max.',
        'string' => ':attribute ne sme imati više od :max karaktera.',
    ],
    'required' => 'Polje :attribute je obavezno.',
    'string' => ':attribute mora biti tekst.',
    'attributes' => [
        'apartment_id' => 'stan',
        'assigned_to' => 'dodeljeni upravnik',
        'attachments' => 'prilozi',
        'attachments.*' => 'prilog',
        'body' => 'komentar',
        'building_id' => 'zgrada',
        'content' => 'sadržaj',
        'description' => 'opis',
        'email' => 'email',
        'locale' => 'jezik',
        'password' => 'lozinka',
        'priority' => 'prioritet',
        'published_at' => 'vreme objave',
        'remember' => 'zapamti me',
        'status' => 'status',
        'status_note' => 'napomena uz status',
        'title' => 'naslov',
    ],
];