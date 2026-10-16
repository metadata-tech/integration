<?php

namespace App\Services;

class MQRInstitutionGroup
{
    /** @var array<string, array<int, string>> */
    private const FAMILIES = [
        'IPGM' => ['INSTITUT PENDIDIKAN GURU MALAYSIA', 'INSTITUT PENDIDIKAN GURU (IPG)', 'INSTITUT PENDIDIKAN GURU (KAMPUS', 'INSTITUT PENDIDIKAN GURU KAMPUS'],
        'UITM' => ['UNIVERSITI TEKNOLOGI MARA'],
        'UTM' => ['UNIVERSITI TEKNOLOGI MALAYSIA'],
        'UNISZA' => ['UNIVERSITI SULTAN ZAINAL ABIDIN'],
        'UNIKL' => ['UNIVERSITI KUALA LUMPUR'],
        'POLI' => ['POLITEKNIK'],
        'KOLEJ KOMUNITI' => ['KOLEJ KOMUNITI'],
        'KOLEJ VOKASIONAL' => ['KOLEJ VOKASIONAL'],
        'IKM' => ['INSTITUT KEMAHIRAN MARA'],
        'KKTM' => ['KOLEJ KEMAHIRAN TINGGI MARA'],
        'KPTM' => ['KOLEJ POLY-TECH MARA'],
        'KOLEJ PROFESIONAL MARA' => ['KOLEJ PROFESIONAL MARA'],
        'ADTEC' => ['PUSAT LATIHAN TEKNOLOGI TINGGI'],
        'ILKKM' => ['INSTITUT LATIHAN KEMENTERIAN KESIHATAN MALAYSIA'],
        'ILP' => ['INSTITUT LATIHAN PERINDUSTRIAN'],
        'ITTAR' => ['INSTITUT TEKNOLOGI TUN ABDUL RAZAK'],
        'TAR UMT' => ['TUNKU ABDUL RAHMAN UNIVERSITY OF MANAGEMENT AND TECHNOLOGY', 'KOLEJ UNIVERSITI TUNKU ABDUL RAHMAN'],
        'UTAR' => ['UNIVERSITI TUNKU ABDUL RAHMAN'],
        'MMU' => ['UNIVERSITI MULTIMEDIA'],
        'UNISEL' => ['UNIVERSITI SELANGOR'],
        'UNITEN' => ['UNIVERSITI TENAGA NASIONAL'],
        'UCSI' => ['UNIVERSITI UCSI'],
        'USM' => ['UNIVERSITI SAINS MALAYSIA'],
        'UUM' => ['UNIVERSITI UTARA MALAYSIA'],
        'UNIRAZAK' => ['UNIVERSITI TUN ABDUL RAZAK'],
        'UMT' => ['UNIVERSITI MALAYSIA TERENGGANU'],
        'UMS' => ['UNIVERSITI MALAYSIA SABAH'],
        'UNIMAS' => ['UNIVERSITI MALAYSIA SARAWAK'],
        'UNIMAP' => ['UNIVERSITI MALAYSIA PERLIS'],
        'UMK' => ['UNIVERSITI MALAYSIA KELANTAN'],
        'UPSI' => ['UNIVERSITI PENDIDIKAN SULTAN IDRIS'],
        'UPM' => ['UNIVERSITI PUTRA MALAYSIA'],
        'UKM' => ['UNIVERSITI KEBANGSAAN MALAYSIA'],
        'UIAM' => ['UNIVERSITI ISLAM ANTARABANGSA MALAYSIA'],
        'USIM' => ['UNIVERSITI SAINS ISLAM MALAYSIA'],
        'UTEM' => ['UNIVERSITI TEKNIKAL MALAYSIA MELAKA'],
        'UTHM' => ['UNIVERSITI TUN HUSSEIN ONN'],
        'ALAM' => ['AKADEMI LAUT MALAYSIA'],
        'KOLEJ OLYMPIA' => ['KOLEJ OLYMPIA'],
        'INSTITUT AKAUNTAN KTC' => ['INSTITUT AKAUNTAN KTC'],
    ];

    public static function resolve(?string $name): ?string
    {
        $name = mb_strtoupper(trim((string) $name));

        if ($name === '') {
            return null;
        }

        foreach (self::FAMILIES as $group => $roots) {
            foreach ($roots as $root) {
                if (preg_match('/^'.preg_quote($root, '/').'(?=$|[\s,(\-])/u', $name) === 1) {
                    return $group;
                }
            }
        }

        // Akronim rujukan induk yang jelas; nama organisasi berbeza tidak
        // dikumpulkan hanya kerana mempunyai awalan yang sama.
        if (preg_match('/^(.+?)\s+\(([A-Z][A-Z0-9]{1,7})\)(?:\s*(?:,?\s*(?:KAMPUS|CAWANGAN)\b.*))?$/u', $name, $match) === 1
            && ! str_contains($match[1], '/')
            && ! in_array($match[2], ['KAMPUS', 'MALAYSIA', 'SARAWAK', 'SABAH', 'SELANGOR'], true)) {
            return trim($match[2]);
        }

        return null;
    }
}
