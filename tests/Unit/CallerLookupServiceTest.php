<?php

use App\Models\Dienstleister;
use App\Modules\AdUsers\Models\AdUser;
use App\Services\CallerLookupService;
use Illuminate\Support\Facades\Schema;

/*
 * Selbstständiger Test der Matching-Logik: erstellt nur die drei benötigten
 * Tabellen direkt (ohne den vollständigen Migrations-Lauf, da einige Modul-
 * Migrationen MySQL-spezifisches DDL nutzen und auf SQLite nicht laufen).
 */
uses(Tests\TestCase::class);

beforeEach(function () {
    foreach (['dienstleister', 'dienstleister_ansprechpartner', 'adusers'] as $t) {
        Schema::dropIfExists($t);
    }

    Schema::create('dienstleister', function ($table) {
        $table->id();
        $table->string('firmenname');
        $table->string('telefon')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
    });

    Schema::create('dienstleister_ansprechpartner', function ($table) {
        $table->id();
        $table->foreignId('dienstleister_id');
        $table->string('vorname')->nullable();
        $table->string('nachname')->nullable();
        $table->string('funktion')->nullable();
        $table->string('telefon')->nullable();
        $table->string('handy')->nullable();
        $table->string('email')->nullable();
        $table->integer('sort_order')->default(0);
        $table->timestamps();
    });

    Schema::create('adusers', function ($table) {
        $table->id();
        $table->string('samaccountname');
        $table->string('vorname')->nullable();
        $table->string('nachname')->nullable();
        $table->string('telefon')->nullable();
        $table->string('email')->nullable();
        $table->string('abteilung')->nullable();
        $table->boolean('ad_vorhanden')->default(true);
        $table->boolean('ad_aktiv')->default(true);
        $table->timestamps();
    });
});

function svc(): CallerLookupService
{
    return new CallerLookupService();
}

it('findet einen exakten Dienstleister-Treffer', function () {
    $d = Dienstleister::create(['firmenname' => 'Alpha GmbH', 'telefon' => '08161 100100', 'status' => 'aktiv']);

    $res = svc()->lookup('+498161100100');

    expect($res['e164'])->toBe('+498161100100')
        ->and($res['matches'])->toHaveCount(1)
        ->and($res['matches'][0]['kind'])->toBe('dienstleister')
        ->and($res['matches'][0]['match_type'])->toBe('exact')
        ->and($res['matches'][0]['dienstleister']->id)->toBe($d->id);
});

it('matcht eine eingehende Durchwahl gegen die Stammnummer (Prefix/Bereich)', function () {
    Dienstleister::create(['firmenname' => 'Beta AG', 'telefon' => '08161 5367', 'status' => 'aktiv']);

    // Stammnummer-Schlüssel 4981615367 ist Präfix der eingehenden Durchwahl.
    $res = svc()->lookup('+4981615367123');

    expect($res['matches'])->toHaveCount(1)
        ->and($res['matches'][0]['match_type'])->toBe('range');
});

it('bevorzugt den exakten vor dem Bereich-Treffer', function () {
    Dienstleister::create(['firmenname' => 'Stamm', 'telefon' => '08161 5367', 'status' => 'aktiv']);
    Dienstleister::create(['firmenname' => 'Exakt', 'telefon' => '08161 5367123', 'status' => 'aktiv']);

    $res = svc()->lookup('+4981615367123');

    expect($res['matches'])->toHaveCount(2)
        ->and($res['matches'][0]['match_type'])->toBe('exact')
        ->and($res['matches'][0]['dienstleister']->firmenname)->toBe('Exakt');
});

it('findet einen Treffer über die Ansprechpartner-Nummer', function () {
    $d = Dienstleister::create(['firmenname' => 'Gamma', 'status' => 'aktiv']);
    $d->kontakte()->create(['nachname' => 'Müller', 'telefon' => '08161 770077']);

    $res = svc()->lookup('+498161770077');

    expect($res['matches'])->toHaveCount(1)
        ->and($res['matches'][0]['via'])->toBe('ansprechpartner')
        ->and($res['matches'][0]['contact']->nachname)->toBe('Müller');
});

it('findet einen internen AD-Benutzer', function () {
    AdUser::create([
        'samaccountname' => 'mmuster', 'vorname' => 'Max', 'nachname' => 'Muster',
        'telefon' => '08161 600123', 'ad_vorhanden' => true, 'ad_aktiv' => true,
    ]);

    $res = svc()->lookup('+498161600123');

    expect($res['matches'])->toHaveCount(1)
        ->and($res['matches'][0]['kind'])->toBe('aduser')
        ->and($res['matches'][0]['aduser']->samaccountname)->toBe('mmuster');
});

it('liefert mehrere Treffer zur Auswahl', function () {
    Dienstleister::create(['firmenname' => 'A', 'telefon' => '08161 111222', 'status' => 'aktiv']);
    Dienstleister::create(['firmenname' => 'B', 'telefon' => '+49 8161 111222', 'status' => 'aktiv']);

    $res = svc()->lookup('08161 111222');

    expect($res['matches'])->toHaveCount(2);
});

it('liefert keinen Treffer für eine unbekannte Nummer', function () {
    Dienstleister::create(['firmenname' => 'A', 'telefon' => '08161 111222', 'status' => 'aktiv']);

    $res = svc()->lookup('+49 9131 999999');

    expect($res['matches'])->toBeEmpty()
        ->and($res['e164'])->toBe('+499131999999');
});

it('ignoriert nicht vorhandene AD-Benutzer', function () {
    AdUser::create([
        'samaccountname' => 'alt', 'telefon' => '08161 600123',
        'ad_vorhanden' => false, 'ad_aktiv' => false,
    ]);

    $res = svc()->lookup('+498161600123');

    expect($res['matches'])->toBeEmpty();
});
