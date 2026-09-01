<?php

use App\Services\MysppDokumenService;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('services.myspp.url', 'http://myspp.test/api/espp_dokumen.php');
    config()->set('services.myspp.api_key', 'test-myspp-token');
    Http::preventStrayRequests();
});

it('passes through binary dokumen from MySPP', function () {
    Http::fake([
        'myspp.test/api/espp_dokumen.php*' => Http::response(
            '%PDF-1.4 test',
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="000103121674_pmr.pdf"',
                'Content-Length' => '14',
            ]
        ),
    ]);

    $response = app(MysppDokumenService::class)->streamDokumen('2023000103121674', 'PMR');

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('Content-Type'))->toStartWith('application/pdf')
        ->and($response->getContent())->toBe('%PDF-1.4 test');
});

it('returns json errors from MySPP without wrapping file bytes', function () {
    Http::fake([
        'myspp.test/api/espp_dokumen.php*' => Http::response(
            ['success' => false, 'errmsg' => 'File not found'],
            404
        ),
    ]);

    $response = app(MysppDokumenService::class)->streamDokumen('2023000103121674', 'PMR');

    expect($response->getStatusCode())->toBe(404)
        ->and($response->headers->get('Content-Type'))->toContain('json')
        ->and($response->getData(true)['errmsg'])->toBe('File not found');
});

it('returns 502 when MySPP cannot be reached', function () {
    Http::fake(function () {
        throw new Illuminate\Http\Client\ConnectionException('timed out');
    });

    $response = app(MysppDokumenService::class)->streamDokumen('2023000103121674', 'PMR');

    expect($response->getStatusCode())->toBe(502)
        ->and($response->getData(true)['errmsg'])->toBe('MySPP tidak dapat dihubungi.');
});
