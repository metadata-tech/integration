<?php

namespace App\Services;

class MQRInstitutionType
{
    /**
     * Ikut jenis rujukan induk dalam kumpulan yang sama. Rujukan lain
     * menggunakan jenis 2, seperti kebanyakan institusi sedia ada.
     *
     * @param  array<string, string>  $groupTypes
     */
    public static function resolve(string $name, ?string $group, array $groupTypes): string
    {
        if ($group !== null && in_array($groupTypes[$group] ?? null, ['1', '2'], true)) {
            return $groupTypes[$group];
        }

        $name = preg_replace('/\s+/u', ' ', mb_strtoupper(trim($name), 'UTF-8'));

        // Nama rasmi yang tidak sama dengan rujukan induk sedia ada.
        if (preg_match('/^(?:UNIVERSITI MALAYA|UNIVERSITI PERTAHANAN NASIONAL MALAYSIA|INSTITUT KRAF NEGARA)(?:$|\s*\(|\s+(?:KAMPUS|CAWANGAN)\b)/u', $name) === 1
            || preg_match('/^UNIVERSITI MALAYSIA PAHANG(?:$|\s+AL-SULTAN\b|\s*\(|\s+(?:KAMPUS|CAWANGAN)\b)/u', $name) === 1
            || preg_match('/^INSTITUT KEMAHIRAN TINGGI BELIA NEGARA(?:$|\s|\()/u', $name) === 1
            || preg_match('/^KOLEJ VOKASIONAL(?:$|\s|\()/u', $name) === 1
            || str_contains($name, '@KOLEJ KOMUNITI ')) {
            return '1';
        }

        return '2';
    }

    /** @return array<string, string> */
    public static function groupTypes(iterable $institutions): array
    {
        $types = [];
        foreach ($institutions as $institution) {
            $group = trim((string) ($institution->KUMPULAN_INSTITUSI ?? ''));
            $type = trim((string) ($institution->JENIS_INSTITUSI ?? ''));
            if ($group === '' || ! in_array($type, ['1', '2'], true)) {
                continue;
            }
            if (isset($types[$group]) && $types[$group] !== $type) {
                $types[$group] = '';
            } elseif (! array_key_exists($group, $types)) {
                $types[$group] = $type;
            }
        }

        return $types;
    }
}
