<?php

use App\Models\APILog;
use App\Services\KPTSKPGService;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('services.kpt_skpg.url', 'https://kpt.test');
    config()->set('services.kpt_skpg.username', 'test-user');
    config()->set('services.kpt_skpg.password', 'test-password');

    Http::preventStrayRequests();
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
        ['course_code' => 'LEVEL-1', 'level_course_id' => 1],
        ['course_code' => 'LEVEL-2', 'level_course_id' => 2],
        ['course_code' => 'LEVEL-3', 'level_course_id' => '3'],
        ['course_code' => 'LEVEL-7', 'level_course_id' => ' 7 '],
        ['course_code' => 'LEVEL-8', 'level_course_id' => 8],
        ['course_code' => 'NO-LEVEL'],
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
