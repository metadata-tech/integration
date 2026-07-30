<?php

use App\Services\MQRService;

function mqrReferences(
    MQRService $service,
    array $institutions,
    array $specializations = []
): array {
    $institutionRecords = collect($institutions)->map(
        fn (array $institution): object => (object) [
            'KOD' => $institution[0],
            'DISKRIPSI' => $institution[1],
        ]
    );

    $specializationRecords = collect($specializations)->map(
        fn (array $specialization): object => (object) [
            'kod' => $specialization[0],
            'DISKRIPSI' => $specialization[1],
            'KATEGORI' => $specialization[2] ?? 1,
        ]
    );

    return $service->prepareReferenceData(
        $institutionRecords,
        $specializationRecords
    );
}

function mqrRecord(
    ?string $institution,
    ?string $qualification,
    ?string $mqaCode = 'MQA/FA0001'
): object {
    return (object) [
        'kod_rujukan_akreditasi_penuh' => $mqaCode,
        'nama_institusi' => $institution,
        'nama_kelayakan_bm' => $qualification,
    ];
}

it('matches an institution when MQR appends a campus qualifier', function () {
    $service = new MQRService;
    $references = mqrReferences(
        $service,
        [
            ['00038', 'UNIVERSITI SULTAN ZAINAL ABIDIN (UNISZA)'],
        ],
        [
            ['P001', 'Sarjana Muda Teknologi Maklumat'],
        ]
    );

    $result = $service->evaluateMapping(
        mqrRecord(
            'Universiti Sultan Zainal Abidin (UniSZA) (Kampus Gong Badak)',
            'SARJANA MUDA TEKNOLOGI MAKLUMAT'
        ),
        $references
    );

    expect($result['success'])->toBeTrue()
        ->and($result['institution']->KOD)->toBe('00038')
        ->and($result['institution_match_method'])
        ->toBe('Maklumat kampus/cawangan di hujung nama diabaikan');
});

it('chooses the longest specific institution before a generic prefix', function () {
    $service = new MQRService;
    $references = mqrReferences(
        $service,
        [
            ['00427', 'CITY UNIVERSITY'],
            ['00428', 'CITY UNIVERSITY OF HONG KONG'],
        ],
        [
            ['P001', 'Sarjana Muda Sains'],
        ]
    );

    $result = $service->evaluateMapping(
        mqrRecord(
            'City University of Hong Kong (Campus Kowloon)',
            'Sarjana Muda Sains'
        ),
        $references
    );

    expect($result['success'])->toBeTrue()
        ->and($result['institution']->KOD)->toBe('00428');
});

it('does not treat an organisation name continuation as a campus suffix', function () {
    $service = new MQRService;
    $references = mqrReferences(
        $service,
        [
            ['00427', 'CITY UNIVERSITY'],
        ],
        [
            ['P001', 'Sarjana Muda Sains'],
        ]
    );

    $result = $service->evaluateMapping(
        mqrRecord('City University of Hong Kong', 'Sarjana Muda Sains'),
        $references
    );

    expect($result['success'])->toBeFalse()
        ->and($result['institution'])->toBeNull()
        ->and($result['reason'])->toContain('Tiada institusi yang sepadan');
});

it('does not confuse Universiti Malaya-Wales with Universiti Malaya', function () {
    $service = new MQRService;
    $references = mqrReferences(
        $service,
        [
            ['00026', 'UNIVERSITI MALAYA (UM)'],
        ],
        [
            ['P001', 'Sarjana Muda Sains'],
        ]
    );

    $result = $service->evaluateMapping(
        mqrRecord('Universiti Malaya-Wales (UM-Wales)', 'Sarjana Muda Sains'),
        $references
    );

    expect($result['success'])->toBeFalse()
        ->and($result['institution'])->toBeNull()
        ->and($result['reason'])->toContain('Tiada institusi yang sepadan');
});

it('matches an appended campus location without an explicit branch marker', function () {
    $service = new MQRService;
    $references = mqrReferences(
        $service,
        [
            ['00021', 'UNIVERSITI TEKNOLOGI MARA (UITM)'],
        ],
        [
            ['P001', 'Sarjana Muda Sains'],
        ]
    );

    $result = $service->evaluateMapping(
        mqrRecord(
            'Universiti Teknologi MARA (UiTM) Shah Alam',
            'Sarjana Muda Sains'
        ),
        $references
    );

    expect($result['success'])->toBeTrue()
        ->and($result['institution']->KOD)->toBe('00021')
        ->and($result['institution_match_method'])
        ->toBe('Nama institusi diikuti maklumat lokasi/cawangan');
});

it('matches a qualified official name from a sufficiently specific base name', function () {
    $service = new MQRService;
    $references = mqrReferences(
        $service,
        [
            ['00031', 'UNIVERSITI MALAYSIA PAHANG (UMP)'],
        ],
        [
            ['P001', 'Sarjana Muda Sains'],
        ]
    );

    $result = $service->evaluateMapping(
        mqrRecord(
            'Universiti Malaysia Pahang Al-Sultan Abdullah',
            'Sarjana Muda Sains'
        ),
        $references
    );

    expect($result['success'])->toBeTrue()
        ->and($result['institution']->KOD)->toBe('00031')
        ->and($result['institution_match_method'])
        ->toBe('Nama asas institusi diikuti maklumat lokasi/cawangan');
});

it('does not count a numeric token as an extra word for a generic base prefix', function () {
    $service = new MQRService;
    $references = mqrReferences(
        $service,
        [
            ['00032', 'KOLEJ 1 MALAYSIA (K1M)'],
        ],
        [
            ['P001', 'Diploma Contoh'],
        ]
    );

    $result = $service->evaluateMapping(
        mqrRecord('Kolej 1 Malaysia Selatan', 'Diploma Contoh'),
        $references
    );

    expect($result['success'])->toBeFalse()
        ->and($result['institution'])->toBeNull()
        ->and($result['reason'])->toContain('Tiada institusi yang sepadan');
});

it('matches an explicit branch when MQR omits the reference acronym', function () {
    $service = new MQRService;
    $references = mqrReferences(
        $service,
        [
            ['00021', 'UNIVERSITI TEKNOLOGI MARA (UITM)'],
        ],
        [
            ['P001', 'Sarjana Muda Sains'],
        ]
    );

    $result = $service->evaluateMapping(
        mqrRecord(
            'Universiti Teknologi MARA Cawangan Selangor Kampus Puncak Alam',
            'Sarjana Muda Sains'
        ),
        $references
    );

    expect($result['success'])->toBeTrue()
        ->and($result['institution']->KOD)->toBe('00021')
        ->and($result['institution_match_method'])
        ->toBe('Maklumat kampus/cawangan di hujung nama diabaikan');
});

it('does not discard a meaningful terminal parenthetical qualifier', function () {
    $service = new MQRService;
    $references = mqrReferences(
        $service,
        [
            ['00001', 'KOLEJ CONTOH (MALAYSIA)'],
        ],
        [
            ['P001', 'Diploma Contoh'],
        ]
    );

    $result = $service->evaluateMapping(
        mqrRecord('Kolej Contoh (Singapura)', 'Diploma Contoh'),
        $references
    );

    expect($result['success'])->toBeFalse()
        ->and($result['institution'])->toBeNull()
        ->and($result['reason'])->toContain('Tiada institusi yang sepadan');
});

it('treats controlled former-name text in a reference as metadata only', function () {
    $service = new MQRService;
    $references = mqrReferences(
        $service,
        [
            [
                '01236',
                'KOLEJ REGENT (DAHULU DIKENALI SEBAGAI KOLEJ CYBER PUTRA)',
            ],
        ],
        [
            ['P001', 'Diploma Contoh'],
        ]
    );

    $currentNameResult = $service->evaluateMapping(
        mqrRecord('Kolej Regent', 'Diploma Contoh'),
        $references
    );
    $formerNameResult = $service->evaluateMapping(
        mqrRecord('Kolej Cyber Putra', 'Diploma Contoh'),
        $references
    );

    expect($currentNameResult['success'])->toBeTrue()
        ->and($currentNameResult['institution']->KOD)->toBe('01236')
        ->and($formerNameResult['success'])->toBeFalse()
        ->and($formerNameResult['institution'])->toBeNull();
});

it('does not remove a semantic institution name in parentheses', function () {
    $service = new MQRService;
    $references = mqrReferences(
        $service,
        [
            ['00427', 'UNIVERSITY OF WALES (CARDIFF UNIVERSITY)'],
        ],
        [
            ['P001', 'Sarjana Muda Sains'],
        ]
    );

    $result = $service->evaluateMapping(
        mqrRecord('University Of Wales', 'Sarjana Muda Sains'),
        $references
    );

    expect($result['success'])->toBeFalse()
        ->and($result['institution'])->toBeNull()
        ->and($result['reason'])->toContain('Tiada institusi yang sepadan');
});

it('requires a complete word boundary for partial institution matching', function () {
    $service = new MQRService;
    $references = mqrReferences(
        $service,
        [
            ['00427', 'CITY UNIVERSITY'],
        ],
        [
            ['P001', 'Sarjana Muda Sains'],
        ]
    );

    $result = $service->evaluateMapping(
        mqrRecord('City Universityville', 'Sarjana Muda Sains'),
        $references
    );

    expect($result['success'])->toBeFalse()
        ->and($result['institution'])->toBeNull()
        ->and($result['reason'])->toContain('Tiada institusi yang sepadan');
});

it('reports an ambiguous institution instead of selecting one arbitrarily', function () {
    $service = new MQRService;
    $references = mqrReferences(
        $service,
        [
            ['01308', 'KOLEJ UNIVERSITI YAYASAN PAHANG'],
            ['03062', 'Kolej Universiti Yayasan Pahang'],
        ],
        [
            ['P001', 'Diploma Pengurusan'],
        ]
    );

    $result = $service->evaluateMapping(
        mqrRecord('Kolej Universiti Yayasan Pahang', 'Diploma Pengurusan'),
        $references
    );

    expect($result['success'])->toBeFalse()
        ->and($result['institution'])->toBeNull()
        ->and($result['reason'])->toContain('Lebih daripada satu institusi rujukan');
});

it('scopes cached institution results to the supplied reference set', function () {
    $service = new MQRService;
    $referencesA = mqrReferences(
        $service,
        [['A001', 'UNIVERSITI CONTOH']],
        [['P001', 'Diploma Contoh']]
    );
    $referencesB = mqrReferences(
        $service,
        [['B001', 'UNIVERSITI CONTOH']],
        [['P001', 'Diploma Contoh']]
    );

    $resultA = $service->evaluateMapping(
        mqrRecord('Universiti Contoh', 'Diploma Contoh'),
        $referencesA
    );
    $resultB = $service->evaluateMapping(
        mqrRecord('Universiti Contoh', 'Diploma Contoh'),
        $referencesB
    );

    expect($resultA['institution']->KOD)->toBe('A001')
        ->and($resultB['institution']->KOD)->toBe('B001');
});

it('reports every applicable reason when a mapping fails', function () {
    $service = new MQRService;
    $references = mqrReferences(
        $service,
        [
            ['00001', 'UNIVERSITI CONTOH'],
        ],
        [
            ['P001', 'Diploma Contoh'],
        ]
    );

    $result = $service->evaluateMapping(
        mqrRecord('Institusi Tidak Wujud', 'Kelayakan Tidak Wujud', ' '),
        $references
    );

    expect($result['success'])->toBeFalse()
        ->and($result['reason'])
        ->toContain('Kod rujukan MQA tidak dibekalkan')
        ->toContain('Tiada institusi yang sepadan')
        ->toContain('Tiada pengkhususan yang sepadan');
});

it('normalizes punctuation as word boundaries', function () {
    $service = new MQRService;
    $references = mqrReferences(
        $service,
        [
            ['00001', 'INSTITUT MARA-JAPAN'],
        ],
        [
            ['P001', 'Diploma Contoh'],
        ]
    );

    $result = $service->evaluateMapping(
        mqrRecord('Institut MARA JAPAN', 'Diploma Contoh'),
        $references
    );

    expect($result['success'])->toBeTrue()
        ->and($result['institution']->KOD)->toBe('00001');
});
