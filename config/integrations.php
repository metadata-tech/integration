<?php

/*
|--------------------------------------------------------------------------
| Integration catalog
|--------------------------------------------------------------------------
|
| Drives the sidebar navigation, the per-integration pages under
| /integrasi/{slug}, the dashboard overview and the integration map.
| The "component" key is the Livewire component mounted on the page.
| "health_url" is pinged by the dashboard map to show connectivity,
| and "code" is the short badge shown on the agency node.
|
*/

return [
    'iscaf' => [
        'label' => 'iSCAF',
        'title' => 'Integrasi iSCAF',
        'description' => 'Semakan status, pendaftaran dan kemas kini fail anggota melalui API iSCAF.',
        'component' => 'iscaf-form',
        'code' => 'ISC',
        'health_url' => env('ISCAF_URL', 'http://10.29.59.123/api/v1'),
    ],
    'jpn' => [
        'label' => 'JPN MyIdentity',
        'title' => 'Integrasi JPN MyIdentity',
        'description' => 'Semakan maklumat individu berdasarkan nombor kad pengenalan melalui JPN.',
        'component' => 'j-p-n-form',
        'code' => 'JPN',
        'health_url' => env('jpn_url'),
    ],
    'kpm' => [
        'label' => 'KPM ePenempatan / eOperasi',
        'title' => 'Integrasi KPM ePenempatan / eOperasi',
        'description' => 'Semakan maklumat penempatan dan operasi guru melalui KPM.',
        'component' => 'k-p-m-form',
        'code' => 'KPM',
        'health_url' => env('kpm_url'),
    ],
    'kpt-esis' => [
        'label' => 'KPT eSIS',
        'title' => 'Integrasi KPT eSIS',
        'description' => 'Semakan maklumat pelajar institusi pengajian tinggi melalui KPT eSIS.',
        'component' => 'k-p-t-form',
        'code' => 'KPT',
        'health_url' => env('kpt_esis_url'),
    ],
    'kpt-skpg' => [
        'label' => 'KPT SKPG',
        'title' => 'Integrasi KPT SKPG',
        'description' => 'Semakan maklumat melalui sistem KPT SKPG.',
        'component' => 'k-p-t-s-k-p-g-form',
        'code' => 'KPT',
        'health_url' => env('kpt_skpg_url'),
    ],
    'lpm-svk-spm' => [
        'label' => 'LPM SVK SPM',
        'title' => 'Integrasi LPM SVK SPM',
        'description' => 'Semakan keputusan SPM melalui Lembaga Peperiksaan (SVK).',
        'component' => 'l-p-m-form',
        'code' => 'LPM',
        'health_url' => env('lpm_svk_url'),
    ],
    'lpm-spm-ulangan' => [
        'label' => 'LPM SPM Ulangan',
        'title' => 'Integrasi LPM SVK SPM Ulangan',
        'description' => 'Semakan keputusan SPM Ulangan melalui Lembaga Peperiksaan (SVK).',
        'component' => 'l-p-m-s-v-k-s-p-m-ulangan-form',
        'code' => 'LPM',
        'health_url' => env('lpm_svk_url'),
    ],
    'lpm-apkv' => [
        'label' => 'LPM APKV / SVM',
        'title' => 'Integrasi LPM APKV / SVM',
        'description' => 'Semakan keputusan SVM / APKV melalui Lembaga Peperiksaan.',
        'component' => 'l-p-m-a-p-k-v-form',
        'code' => 'LPM',
        'health_url' => env('lpm_apkv_url'),
    ],
    'mygdx-muet' => [
        'label' => 'MyGDX MUET',
        'title' => 'Integrasi MyGDX MUET',
        'description' => 'Semakan keputusan MUET melalui perkhidmatan MyGDX.',
        'component' => 'muet-form',
        'code' => 'GDX',
        'health_url' => 'https://spp.mygdx.gov.my',
    ],
    'mqr' => [
        'label' => 'MQR',
        'title' => 'MQR (Malaysian Qualifications Register)',
        'description' => 'Penarikan data rujukan MQR dan automasi padanan kelayakan secara batch.',
        'component' => 'm-q-r-form',
        'code' => 'MQR',
        'health_url' => env('MQR_URL'),
    ],
];
