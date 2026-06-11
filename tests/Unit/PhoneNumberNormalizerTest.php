<?php

use App\Support\PhoneNumberNormalizer;

it('normalisiert verschiedene Formate auf E.164', function (string $input, ?string $expected) {
    expect(PhoneNumberNormalizer::toE164($input))->toBe($expected);
})->with([
    'bereits E.164'            => ['+498161536789', '+498161536789'],
    'mit Leerzeichen'          => ['+49 8161 536789', '+498161536789'],
    '00-Notation'             => ['0049 8161 536789', '+498161536789'],
    'national mit 0'           => ['08161 536789', '+498161536789'],
    'mit Slash und Bindestrich' => ['08161 / 53 67-89', '+498161536789'],
    'mit Klammern'             => ['(08161) 53 67 89', '+498161536789'],
    'deutsche (0)-Schreibweise' => ['+49 (0)8161 536789', '+498161536789'],
    'ohne jede Vorwahl-Notation' => ['8161536789', '+498161536789'],
]);

it('gibt null für leere oder unbrauchbare Eingaben zurück', function (?string $input) {
    expect(PhoneNumberNormalizer::toE164($input))->toBeNull();
})->with([
    'null'        => [null],
    'leer'        => [''],
    'nur Text'    => ['kein Anschluss'],
    'zu kurz'     => ['123'],
]);

it('respektiert eine abweichende Default-Ländervorwahl', function () {
    expect(PhoneNumberNormalizer::toE164('0660 1234567', '43'))->toBe('+436601234567');
});

it('liefert den Ziffern-Schlüssel ohne +', function () {
    expect(PhoneNumberNormalizer::digitsKey('08161 536789'))->toBe('498161536789')
        ->and(PhoneNumberNormalizer::digitsKey('Quatsch'))->toBeNull();
});
