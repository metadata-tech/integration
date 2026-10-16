<?php

use App\Services\MQRInstitutionGroup;
use App\Services\MQRInstitutionType;
use App\Services\MQRService;

function mqrReferences(array $institutions, array $specializations): array
{
    $service = new MQRService;
    $references = $service->prepareReferenceData(
        collect($institutions)->map(fn ($row) => (object) ['KOD' => $row[0], 'DISKRIPSI' => $row[1]]),
        collect($specializations)->map(fn ($row) => (object) ['kod' => $row[0], 'DISKRIPSI' => $row[1], 'KATEGORI' => $row[2] ?? 2])
    );
    $references['qualification_level_codes'] = collect([1, 2, 3, 4, 6, 7])->mapWithKeys(fn ($code) => [$code => $code]);

    return $references;
}

function mqrRecord(?string $institution, ?string $specialization, ?string $mqa = 'MQA/FA1', ?string $level = null, ?string $nec = null): object
{
    return (object) [
        'nama_institusi' => $institution,
        'nama_kelayakan_bm' => $specialization,
        'kod_rujukan_akreditasi_penuh' => $mqa,
        'peringkat_kelayakan' => $level,
        'kod_nec' => $nec,
    ];
}

it('matches only case and edge-space differences', function () {
    $service = new MQRService;
    $refs = mqrReferences([['00012', 'Institut Pendidikan Guru Malaysia']], [['12', 'Diploma Pendidikan']]);

    expect($service->evaluateMapping(mqrRecord(' institut pendidikan guru malaysia ', ' DIPLOMA PENDIDIKAN '), $refs)['success'])->toBeTrue()
        ->and($service->evaluateMapping(mqrRecord('Institut Pendidikan Guru (IPG) (Kampus Bahasa Melayu)', 'Diploma Pendidikan'), $refs)['success'])->toBeFalse()
        ->and($service->evaluateMapping(mqrRecord('Institut Pendidikan Guru Malaysia', 'Diploma  Pendidikan'), $refs)['success'])->toBeFalse();
});

it('keeps punctuation and branch names distinct', function () {
    $service = new MQRService;
    $refs = mqrReferences([['00041', 'Universiti Teknologi MARA (UiTM)']], [['12', 'Diploma Sains']]);

    expect($service->evaluateMapping(mqrRecord('Universiti Teknologi MARA Cawangan Perlis Kampus Arau', 'Diploma Sains'), $refs)['success'])->toBeFalse()
        ->and($service->evaluateMapping(mqrRecord('Universiti Teknologi MARA (UiTM)', 'Diploma-Sains'), $refs)['success'])->toBeFalse();
});

it('chooses the lowest numeric reference code for an exact duplicate name', function () {
    $service = new MQRService;
    $refs = mqrReferences(
        [['03062', 'Kolej Universiti Yayasan Pahang'], ['01308', 'KOLEJ UNIVERSITI YAYASAN PAHANG']],
        [['24000', 'Diploma Pengurusan', 2], ['15900', 'DIPLOMA PENGURUSAN', 1]]
    );
    $result = $service->evaluateMapping(mqrRecord('Kolej Universiti Yayasan Pahang', 'Diploma Pengurusan'), $refs);

    expect($result['success'])->toBeTrue()
        ->and($result['institution']->KOD)->toBe('01308')
        ->and($result['specialization']->kod)->toBe('15900')
        ->and($result['specialization']->KATEGORI)->toBe(1);
});

it('groups known families without merging similar organisations', function () {
    expect(MQRInstitutionGroup::resolve('Institut Pendidikan Guru (IPG) (Kampus Bahasa Melayu)'))->toBe('IPGM')
        ->and(MQRInstitutionGroup::resolve('Universiti Teknologi MARA Cawangan Perlis Kampus Arau'))->toBe('UITM')
        ->and(MQRInstitutionGroup::resolve('Politeknik Ungku Omar'))->toBe('POLI')
        ->and(MQRInstitutionGroup::resolve('Kolej Poly-Tech MARA Kuantan'))->toBe('KPTM')
        ->and(MQRInstitutionGroup::resolve('Tunku Abdul Rahman University of Management and Technology Cawangan Johor'))->toBe('TAR UMT')
        ->and(MQRInstitutionGroup::resolve('Universiti Tunku Abdul Rahman (UTAR) Kampus Sungai Long'))->toBe('UTAR')
        ->and(MQRInstitutionGroup::resolve('Institusi Tidak Diketahui'))->toBeNull();
});

it('inherits institution type from a known family and classifies other new references', function () {
    $types = MQRInstitutionType::groupTypes([
        (object) ['KUMPULAN_INSTITUSI' => 'IPGM', 'JENIS_INSTITUSI' => '1'],
        (object) ['KUMPULAN_INSTITUSI' => 'UNIKL', 'JENIS_INSTITUSI' => '2'],
    ]);

    expect(MQRInstitutionType::resolve('Institut Pendidikan Guru Kampus Bahasa Melayu', 'IPGM', $types))->toBe('1')
        ->and(MQRInstitutionType::resolve('Universiti Kuala Lumpur Kampus Kota', 'UNIKL', $types))->toBe('2')
        ->and(MQRInstitutionType::resolve('Universiti Malaya', null, $types))->toBe('1')
        ->and(MQRInstitutionType::resolve('Universiti Malaya-Wales', null, $types))->toBe('2')
        ->and(MQRInstitutionType::resolve('Kolej  Vokasional Kajang', null, $types))->toBe('1')
        ->and(MQRInstitutionType::resolve('Kolej Baharu', null, $types))->toBe('2');
});

it('maps qualification level nine by education NEC', function () {
    $service = new MQRService;
    $refs = mqrReferences([['1', 'Universiti Contoh']], [['2', 'Diploma Contoh']]);

    expect($service->evaluateMapping(mqrRecord('Universiti Contoh', 'Diploma Contoh', 'A1', '9', '0112'), $refs)['qualification_level_id'])->toBe(1)
        ->and($service->evaluateMapping(mqrRecord('Universiti Contoh', 'Diploma Contoh', 'A2', '9', '0210'), $refs)['qualification_level_id'])->toBe(2);
});
