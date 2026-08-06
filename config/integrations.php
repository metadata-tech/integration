<?php

/*
|--------------------------------------------------------------------------
| Integration catalog
|--------------------------------------------------------------------------
|
| Drives the sidebar navigation, the per-integration pages under
| /integrasi/{slug}, and the dashboard overview cards. The "component"
| key is the Livewire component mounted on the integration page.
|
*/

return [
    'iscaf' => [
        'label' => 'iSCAF',
        'title' => 'Integrasi iSCAF',
        'description' => 'Semakan status, pendaftaran dan kemas kini fail anggota melalui API iSCAF.',
        'component' => 'iscaf-form',
    ],
    'jpn' => [
        'label' => 'JPN MyIdentity',
        'title' => 'Integrasi JPN MyIdentity',
        'description' => 'Semakan maklumat individu berdasarkan nombor kad pengenalan melalui JPN.',
        'component' => 'j-p-n-form',
    ],
    'kpm' => [
        'label' => 'KPM ePenempatan / eOperasi',
        'title' => 'Integrasi KPM ePenempatan / eOperasi',
        'description' => 'Semakan maklumat penempatan dan operasi guru melalui KPM.',
        'component' => 'k-p-m-form',
    ],
    'kpt-esis' => [
        'label' => 'KPT eSIS',
        'title' => 'Integrasi KPT eSIS',
        'description' => 'Semakan maklumat pelajar institusi pengajian tinggi melalui KPT eSIS.',
        'component' => 'k-p-t-form',
    ],
    'kpt-skpg' => [
        'label' => 'KPT SKPG',
        'title' => 'Integrasi KPT SKPG',
        'description' => 'Semakan maklumat melalui sistem KPT SKPG.',
        'component' => 'k-p-t-s-k-p-g-form',
    ],
    'lpm-svk-spm' => [
        'label' => 'LPM SVK SPM',
        'title' => 'Integrasi LPM SVK SPM',
        'description' => 'Semakan keputusan SPM melalui Lembaga Peperiksaan (SVK).',
        'component' => 'l-p-m-form',
    ],
    'lpm-spm-ulangan' => [
        'label' => 'LPM SPM Ulangan',
        'title' => 'Integrasi LPM SVK SPM Ulangan',
        'description' => 'Semakan keputusan SPM Ulangan melalui Lembaga Peperiksaan (SVK).',
        'component' => 'l-p-m-s-v-k-s-p-m-ulangan-form',
    ],
    'lpm-apkv' => [
        'label' => 'LPM APKV / SVM',
        'title' => 'Integrasi LPM APKV / SVM',
        'description' => 'Semakan keputusan SVM / APKV melalui Lembaga Peperiksaan.',
        'component' => 'l-p-m-a-p-k-v-form',
    ],
    'mygdx-muet' => [
        'label' => 'MyGDX MUET',
        'title' => 'Integrasi MyGDX MUET',
        'description' => 'Semakan keputusan MUET melalui perkhidmatan MyGDX.',
        'component' => 'muet-form',
    ],
    'mqr' => [
        'label' => 'MQR',
        'title' => 'MQR (Malaysian Qualifications Register)',
        'description' => 'Penarikan data rujukan MQR dan automasi padanan kelayakan secara batch.',
        'component' => 'm-q-r-form',
    ],
];
