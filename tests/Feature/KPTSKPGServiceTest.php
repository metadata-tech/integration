<?php

use App\Models\APILog;
use App\Models\User;
use App\Livewire\KPTSKPGForm;
use App\Services\KPTSKPGService;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function (): void {
    config()->set('services.kpt_skpg.url', 'https://kpt.test');
    config()->set('services.kpt_skpg.username', 'test-user');
    config()->set('services.kpt_skpg.password', 'test-password');

    Http::preventStrayRequests();
});

it('requests upstream SKPG data for the former testing IC', function () {
    $upstreamResponse = [
        'status' => '200',
        'message' => ['TOTAL ROW : 1', 'SUCCESS'],
        'data' => [
            ['ic_no' => '000116030974', 'level_course_id' => 2, 'status_pelajar' => 'Aktif', 'course_code' => 'UPSTREAM'],
        ],
    ];

    fakeKptSkpgResponse($upstreamResponse);

    $result = app(KPTSKPGService::class)->callAPI('000116030974');

    expect($result)->toBe($upstreamResponse);

    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => $request->url() === 'https://kpt.test/MOHE/Student'
        && $request['ic_no'] === '000116030974');
});

it('filters the API response but shows the unmodified KPT response on the integration page', function () {
    $validRecord = [
        'course_code' => 'VALID',
        'level_course_id' => 2,
        'status_pelajar' => 'Aktif',
        'CGPA' => 3.5,
    ];
    $upstreamResponse = [
        'status' => '200',
        'message' => ['TOTAL ROW : 5', 'SUCCESS'],
        'data' => [
            $validRecord,
            $validRecord,
            ['course_code' => 'WRONG-LEVEL', 'level_course_id' => 8, 'status_pelajar' => 'Aktif', 'CGPA' => 3.5],
            ['course_code' => 'WRONG-STATUS', 'level_course_id' => 2, 'status_pelajar' => 'Berhenti - Sebab Peribadi', 'CGPA' => 3.5],
            ['course_code' => 'WRONG-CGPA', 'level_course_id' => 2, 'status_pelajar' => 'Aktif', 'CGPA' => -1],
        ],
    ];

    fakeKptSkpgResponse($upstreamResponse);

    $this->actingAs(User::factory()->create())
        ->getJson('/api/kpt/skpg?no_kp=760122075273')
        ->assertOk()
        ->assertExactJson([
            'status' => '200',
            'message' => ['TOTAL ROW : 1', 'SUCCESS'],
            'data' => [$validRecord],
        ]);

    Livewire::test(KPTSKPGForm::class)
        ->set('no_kp', '760122075273')
        ->call('process')
        ->assertSet('result', $upstreamResponse)
        ->assertSee('WRONG-LEVEL');

    Http::assertSentCount(4);

    $studentLogs = APILog::query()
        ->where('url', 'https://kpt.test/MOHE/Student')
        ->orderBy('id')
        ->get();

    expect($studentLogs)->toHaveCount(2)
        ->and($studentLogs->pluck('output')->all())->toBe([
            $upstreamResponse,
            $upstreamResponse,
        ]);
});

it('removes duplicate KPT records and updates the total row message', function () {
    $qualification = [
        'id_univ' => '18',
        'univ_short' => 'UKM',
        'ic_no' => '760122075273',
        'end_date' => '2018-08-27',
        'course_code' => 'SSNKOM(TEKPRISIAN)-TTV',
        'course_long' => "SARJANA SAINS KOMPUTER (TEKNOLOGI PERISIAN)\n",
        'level_course_id' => 4,
        'status_pelajar' => 'Tamat Pengajian',
        'CGPA' => 3.7,
        'campus_code' => '001',
        'MQA_code' => 'MQA/SWA13128',
        'TarikhMula' => '15/01/2019',
        'TarikhTamat' => '12/06/2026',
    ];
    $otherQualification = [
        ...$qualification,
        'end_date' => '2001-05-12',
        'course_code' => 'SMTEKNO(YPM)-TTG',
        'level_course_id' => 3,
        'CGPA' => 3,
        'MQA_code' => 'MQA/SWA13061',
    ];
    $upstreamResponse = [
        'status' => '200',
        'message' => ['TOTAL ROW : 3', 'SUCCESS'],
        'data' => [$qualification, $qualification, $otherQualification],
    ];

    fakeKptSkpgResponse($upstreamResponse);

    $result = app(KPTSKPGService::class)->callAPI('760122075273');

    expect($result['status'])->toBe('200')
        ->and($result['message'])->toBe(['TOTAL ROW : 2', 'SUCCESS'])
        ->and($result['data'])->toBe([$qualification, $otherQualification]);

    Http::assertSentCount(2);

    $studentLog = APILog::query()
        ->where('url', 'https://kpt.test/MOHE/Student')
        ->firstOrFail();

    expect($studentLog->output['data'])->toHaveCount(3);
});

it('keeps different qualifications even when their dates are the same', function () {
    $first = [
        'ic_no' => '760122075273',
        'id_univ' => '18',
        'level_course_id' => 4,
        'status_pelajar' => 'Aktif',
        'course_code' => 'COURSE-A',
        'end_date' => '2018-08-27',
        'MQA_code' => 'MQA/A',
        'TarikhMula' => '15/01/2019',
        'TarikhTamat' => '12/06/2026',
    ];
    $second = [
        ...$first,
        'course_code' => 'COURSE-B',
        'MQA_code' => 'MQA/B',
    ];

    fakeKptSkpgResponse([
        'status' => '200',
        'message' => ['TOTAL ROW : 2', 'SUCCESS'],
        'data' => [$first, $second],
    ]);

    $result = app(KPTSKPGService::class)->callAPI('760122075273');

    expect($result['data'])->toBe([$first, $second])
        ->and($result['message'])->toContain('TOTAL ROW : 2');
});

it('treats key order and line endings as non-meaningful differences', function () {
    $first = [
        'ic_no' => '760122075273',
        'level_course_id' => 4,
        'status_pelajar' => 'Aktif',
        'course_code' => 'COURSE-A',
        'course_long' => "PROGRAM KOMPUTER\r\n",
        'MQA_code' => 'MQA/A',
    ];
    $duplicate = array_reverse($first, true);
    $duplicate['course_long'] = "PROGRAM KOMPUTER\n";

    fakeKptSkpgResponse([
        'status' => '200',
        'message' => ['TOTAL ROW : 2', 'SUCCESS'],
        'data' => [$first, $duplicate],
    ]);

    $result = app(KPTSKPGService::class)->callAPI('760122075273');

    expect($result['data'])->toBe([$first])
        ->and($result['message'])->toContain('TOTAL ROW : 1');
});

it('only returns course levels two through seven', function () {
    $records = [
        ['course_code' => 'LEVEL-1', 'level_course_id' => 1, 'status_pelajar' => 'Aktif'],
        ['course_code' => 'LEVEL-2', 'level_course_id' => 2, 'status_pelajar' => 'Aktif'],
        ['course_code' => 'LEVEL-3', 'level_course_id' => '3', 'status_pelajar' => 'Aktif'],
        ['course_code' => 'LEVEL-7', 'level_course_id' => ' 7 ', 'status_pelajar' => 'Aktif'],
        ['course_code' => 'LEVEL-8', 'level_course_id' => 8, 'status_pelajar' => 'Aktif'],
        ['course_code' => 'NO-LEVEL', 'status_pelajar' => 'Aktif'],
    ];

    fakeKptSkpgResponse([
        'status' => '200',
        'message' => ['TOTAL ROW : 6', 'SUCCESS'],
        'data' => $records,
    ]);

    $result = app(KPTSKPGService::class)->callAPI('760122075273');

    expect(array_column($result['data'], 'course_code'))->toBe([
        'LEVEL-2',
        'LEVEL-3',
        'LEVEL-7',
    ])->and($result['message'])->toContain('TOTAL ROW : 3');
});

it('only returns the selected enrolment statuses or completed studies', function () {
    $records = [
        ['course_code' => 'ACTIVE', 'level_course_id' => 2, 'status_pelajar' => 'Aktif'],
        ['course_code' => 'COMPLETED', 'level_course_id' => 3, 'status_pelajar' => 'Tamat Pengajian'],
        ['course_code' => 'ACTIVE-UPPER', 'level_course_id' => 4, 'status_pelajar' => ' AKTIF '],
        ['course_code' => 'COMPLETED-LOWER', 'level_course_id' => 5, 'status_pelajar' => 'tamat pengajian'],
        ['course_code' => 'SPECIAL', 'level_course_id' => 2, 'status_pelajar' => 'Aktif (Kes Khas)'],
        ['course_code' => 'OUTBOUND', 'level_course_id' => 2, 'status_pelajar' => 'Aktif (Outbound Exchange)'],
        ['course_code' => 'DEFERRED', 'level_course_id' => 2, 'status_pelajar' => 'Tangguh Pengajian'],
        ['course_code' => 'NATIONAL-SERVICE', 'level_course_id' => 2, 'status_pelajar' => 'Gap Year Khidmat Negara'],
        ['course_code' => 'VOLUNTEERING', 'level_course_id' => 2, 'status_pelajar' => 'Gap Year Kesukarelawan'],
        ['course_code' => 'GAP-YEAR', 'level_course_id' => 2, 'status_pelajar' => 'Gap Year Umum'],
        ['course_code' => 'SUSPENDED', 'level_course_id' => 2, 'status_pelajar' => 'Gantung'],
        ['course_code' => 'INBOUND', 'level_course_id' => 2, 'status_pelajar' => 'Aktif (Inbound Exchange)'],
        ['course_code' => 'OTHER-COMPLETION', 'level_course_id' => 3, 'status_pelajar' => 'Tamat Pengajian Dengan Sebab Lain'],
        ['course_code' => 'WITHDRAWN', 'level_course_id' => 4, 'status_pelajar' => 'Berhenti - Sebab Peribadi'],
        ['course_code' => 'NULL-STATUS', 'level_course_id' => 5, 'status_pelajar' => null],
        ['course_code' => 'MISSING-STATUS', 'level_course_id' => 6],
    ];

    fakeKptSkpgResponse([
        'status' => '200',
        'message' => ['TOTAL ROW : 16', 'SUCCESS'],
        'data' => $records,
    ]);

    $result = app(KPTSKPGService::class)->callAPI('760122075273');

    expect(array_column($result['data'], 'course_code'))->toBe([
        'ACTIVE',
        'COMPLETED',
        'ACTIVE-UPPER',
        'COMPLETED-LOWER',
        'SPECIAL',
        'OUTBOUND',
        'DEFERRED',
        'NATIONAL-SERVICE',
        'VOLUNTEERING',
        'GAP-YEAR',
        'SUSPENDED',
    ])->and($result['message'])->toBe(['TOTAL ROW : 11', 'SUCCESS']);
});

it('excludes only the specified CGPA codes', function () {
    $records = [
        ['course_code' => 'MINUS-ONE', 'level_course_id' => 2, 'status_pelajar' => 'Aktif', 'CGPA' => -1],
        ['course_code' => 'MINUS-ONE-DECIMAL', 'level_course_id' => 2, 'status_pelajar' => 'Aktif', 'CGPA' => '-1.0'],
        ['course_code' => 'MINUS-TWO', 'level_course_id' => 2, 'status_pelajar' => 'Aktif', 'CGPA' => -2],
        ['course_code' => 'MINUS-TWO-SPACED', 'level_course_id' => 2, 'status_pelajar' => 'Aktif', 'CGPA' => ' -2 '],
        ['course_code' => 'MINUS-TWO-HUNDRED', 'level_course_id' => 2, 'status_pelajar' => 'Aktif', 'CGPA' => '-200'],
        ['course_code' => 'MINUS-ONE-HUNDRED', 'level_course_id' => 2, 'status_pelajar' => 'Aktif', 'CGPA' => -100],
        ['course_code' => 'MINUS-THREE-HUNDRED', 'level_course_id' => 2, 'status_pelajar' => 'Aktif', 'CGPA' => -300],
        ['course_code' => 'ZERO', 'level_course_id' => 2, 'status_pelajar' => 'Aktif', 'CGPA' => 0],
        ['course_code' => 'VALID', 'level_course_id' => 2, 'status_pelajar' => 'Aktif', 'CGPA' => 3.45],
        ['course_code' => 'NULL-CGPA', 'level_course_id' => 2, 'status_pelajar' => 'Aktif', 'CGPA' => null],
        ['course_code' => 'MISSING-CGPA', 'level_course_id' => 2, 'status_pelajar' => 'Aktif'],
    ];

    fakeKptSkpgResponse([
        'status' => '200',
        'message' => ['TOTAL ROW : 11', 'SUCCESS'],
        'data' => $records,
    ]);

    $result = app(KPTSKPGService::class)->callAPI('760122075273');

    expect(array_column($result['data'], 'course_code'))->toBe([
        'MINUS-ONE-HUNDRED',
        'MINUS-THREE-HUNDRED',
        'ZERO',
        'VALID',
        'NULL-CGPA',
        'MISSING-CGPA',
    ])->and($result['message'])->toBe(['TOTAL ROW : 6', 'SUCCESS']);
});

it('leaves non-list response data unchanged', function () {
    $upstreamResponse = [
        'status' => '404',
        'message' => 'NOT_FOUND',
        'data' => ['error' => 'Student not found'],
    ];

    fakeKptSkpgResponse($upstreamResponse);

    $result = app(KPTSKPGService::class)->callAPI('760122075273');

    expect($result)->toBe($upstreamResponse);
});

function fakeKptSkpgResponse(array $studentResponse): void
{
    Http::fake([
        'https://kpt.test/Login' => Http::response([
            'data' => ['auth_Token' => 'test-token'],
        ]),
        'https://kpt.test/MOHE/Student' => Http::response($studentResponse),
    ]);
}
