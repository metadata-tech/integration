<?php

namespace App\Services;
use App\Models\APILog;
use Illuminate\Support\Facades\Http;

class KPTSKPGService {

    public function GetToken()
    {
        $request_data = [
            "username" => config('services.kpt_skpg.username'),
            "password" => config('services.kpt_skpg.password'),
        ];

        //$headers = ['x-api-key' => config('services.lpm.api_key')];
        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';
        //$headers = [];

        $url = config('services.kpt_skpg.url')."/Login";

        $log = APILog::create([
            'method' => 'POST',
            'input' => $request_data,
            'headers' => $headers,
            'url' => $url,
            'user_id' => auth()->id()
        ]);

        $response = Http::
        withOptions(['verify' => false,])
            ->withHeaders($headers)
            ->post($url, $request_data);


        #ray($response->json());

        $log->update([
            'output' => $response->json(),
            'status_code' => $response->getStatusCode()
        ]);

        return $response;
    }
    public function callAPI($no_kp)
    {
        if ($temporaryResponse = $this->temporaryTestingResponse($no_kp)) {
            return $temporaryResponse;
        }

        $tokenResponse = $this->GetToken()->json();
        $token = $tokenResponse['data']['auth_Token'] ?? null;

        if (empty($token)) {
            return null;
        }

        $request_data = [
            "ic_no" => $no_kp,
        ];

        $headers = ['Authorization' => 'Bearer '.$token];

        $url = config('services.kpt_skpg.url').'/MOHE/Student';

        $log = APILog::create([
            'method' => 'POST',
            'input' => $request_data,
            'headers' => $headers,
            'url' => $url,
            'user_id' => auth()->id()
        ]);

        $response = Http::
        withOptions(['verify' => false,])
            ->withHeaders($headers)
            ->post($url, $request_data);

        $responseData = $response->json();

        $log->update([
            'output' => $responseData,
            'status_code' => $response->getStatusCode()
        ]);

        return is_array($responseData)
            ? $this->filterResponseData($responseData)
            : $responseData;
    }

    private function filterResponseData(array $responseData): array
    {
        if (
            ! isset($responseData['data'])
            || ! is_array($responseData['data'])
            || ! array_is_list($responseData['data'])
        ) {
            return $responseData;
        }

        $seen = [];
        $uniqueData = [];

        foreach ($responseData['data'] as $record) {
            if (! $this->hasAllowedCourseLevel($record)) {
                continue;
            }

            $fingerprint = hash(
                'sha256',
                serialize($this->canonicalizeForComparison($record))
            );

            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;
            $uniqueData[] = $record;
        }

        $responseData['data'] = $uniqueData;

        if (array_key_exists('message', $responseData)) {
            $responseData['message'] = $this->updateTotalRowMessage(
                $responseData['message'],
                count($uniqueData)
            );
        }

        return $responseData;
    }

    private function temporaryTestingResponse($no_kp): ?array
    {
        if ((string) $no_kp !== '000116030974') {
            return null;
        }

        // Temporary SKPG fixture for integration testing only.
        return [
            'status' => '200',
            'message' => [
                'USER_AUTHENTICATED',
                'CHECK CONNECTION',
                'CONNECTION OPEN',
                'CHECKING DATA IN TABLE',
                'TOTAL ROW : 4',
                'SUCCESS',
            ],
            'data' => [
                [
                    'id_univ' => '11',
                    'univ_short' => 'UiTM',
                    'univ_long' => 'Universiti Teknologi MARA',
                    'std_name' => 'TEST DATA SKPG',
                    'ic_no' => '000116030974',
                    'end_date' => '2018-06-01',
                    'course_code' => 'SR113',
                    'course_long' => 'DIPLOMA PENGAJIAN SUKAN',
                    'course_long_bi' => 'DIPLOMA IN SPORTS STUDIES',
                    'level_course_id' => 2,
                    'level_course_desc' => 'Diploma',
                    'NEC_D6' => '1014',
                    'graduation_name_bm' => 'Diploma Pengajian Sukan',
                    'graduation_name_bi' => 'Diploma in Sports Studies',
                    'majoring_bm' => '-2',
                    'majoring_bi' => '-2',
                    'NEC_D6C' => '1014',
                    'status_pelajar' => 'Tamat Pengajian',
                    'CGPA' => 3.05,
                    'OKU_no' => null,
                    'OKU_desc' => 'Tidak Cacat',
                    'sponsor_desc' => 'Lain-lain Tajaan',
                    'convocation_date' => null,
                    'campus_name' => 'UiTM Kampus Jengka',
                    'campus_code' => '029',
                    'MQA_code' => 'MQA/SWA11987',
                    'TarikhMula' => '08/05/2018',
                    'TarikhTamat' => '12/06/2026',
                    'NilaiTaraf' => 'Diploma',
                ],
                [
                    'id_univ' => '11',
                    'univ_short' => 'UiTM',
                    'univ_long' => 'Universiti Teknologi MARA',
                    'std_name' => 'TEST DATA SKPG',
                    'ic_no' => '000116030974',
                    'end_date' => '2020-06-01',
                    'course_code' => 'SR243',
                    'course_long' => 'SARJANA MUDA SAINS SUKAN (KEPUJIAN)',
                    'course_long_bi' => 'BACHELOR OF SPORTS SCIENCE (HONS.)',
                    'level_course_id' => 3,
                    'level_course_desc' => 'Sarjana Muda/Ijazah Pertama',
                    'NEC_D6' => '1014',
                    'graduation_name_bm' => 'SARJANA MUDA SAINS SUKAN (KEPUJIAN)',
                    'graduation_name_bi' => 'BACHELOR OF SPORTS SCIENCE (HONS.)',
                    'majoring_bm' => '-2',
                    'majoring_bi' => '-2',
                    'NEC_D6C' => '1014',
                    'status_pelajar' => 'Tamat Pengajian',
                    'CGPA' => 2.99,
                    'OKU_no' => null,
                    'OKU_desc' => 'Tidak Cacat',
                    'sponsor_desc' => 'PTPTN',
                    'convocation_date' => '2024-06-30',
                    'campus_name' => 'UiTM Kampus Jengka',
                    'campus_code' => '029',
                    'MQA_code' => 'MQA/SWA12085',
                    'TarikhMula' => '08/05/2018',
                    'TarikhTamat' => '12/06/2026',
                    'NilaiTaraf' => 'Ijazah Sarjana Muda Kepujian',
                ],
                [
                    'id_univ' => '9',
                    'univ_short' => 'UPM',
                    'univ_long' => 'Universiti Putra Malaysia',
                    'std_name' => 'TEST DATA SKPG',
                    'ic_no' => '000116030974',
                    'end_date' => null,
                    'course_code' => 'G14343GS209FPSK3',
                    'course_long' => 'MASTER KESIHATAN PERSEKITARAN DAN PEKERJAAN',
                    'course_long_bi' => 'MASTER OF ENVIRONMENTAL AND OCCUPATIONAL HEALTH',
                    'level_course_id' => 4,
                    'level_course_desc' => 'Sarjana',
                    'NEC_D6' => '1022',
                    'graduation_name_bm' => null,
                    'graduation_name_bi' => null,
                    'majoring_bm' => null,
                    'majoring_bi' => null,
                    'NEC_D6C' => null,
                    'status_pelajar' => 'Berhenti - Sebab Peribadi',
                    'CGPA' => null,
                    'OKU_no' => null,
                    'OKU_desc' => 'Tidak Cacat',
                    'sponsor_desc' => 'Persendirian',
                    'convocation_date' => null,
                    'campus_name' => 'UNIVERSITI PUTRA MALAYSIA (KAMPUS SERDANG)',
                    'campus_code' => 'UPMKS',
                    'MQA_code' => 'MQA/SWA15247',
                    'TarikhMula' => '10/02/2022',
                    'TarikhTamat' => '12/06/2026',
                    'NilaiTaraf' => 'Ijazah Sarjana',
                ],
                [
                    'id_univ' => '18',
                    'univ_short' => 'UKM',
                    'univ_long' => 'Universiti Kebangsaan Malaysia',
                    'std_name' => 'TEST DATA SKPG',
                    'ic_no' => '000116030974',
                    'end_date' => null,
                    'course_code' => 'DRFAL(FSSK)-SKF',
                    'course_long' => 'DOKTOR FALSAFAH',
                    'course_long_bi' => 'DOCTOR OF PHILOSOPHY',
                    'level_course_id' => 5,
                    'level_course_desc' => 'Ph.D.',
                    'NEC_D6' => '0310',
                    'graduation_name_bm' => null,
                    'graduation_name_bi' => null,
                    'majoring_bm' => null,
                    'majoring_bi' => null,
                    'NEC_D6C' => null,
                    'status_pelajar' => 'Aktif',
                    'CGPA' => 0,
                    'OKU_no' => null,
                    'OKU_desc' => 'Tidak Cacat',
                    'sponsor_desc' => 'PTPTN',
                    'convocation_date' => null,
                    'campus_name' => 'UKM-Bangi',
                    'campus_code' => '001',
                    'MQA_code' => 'MQA/SWA11604',
                    'TarikhMula' => '13/05/2015',
                    'TarikhTamat' => '12/06/2026',
                    'NilaiTaraf' => '',
                ],
            ],
        ];
    }

    private function hasAllowedCourseLevel(mixed $record): bool
    {
        if (
            ! is_array($record)
            || ! array_key_exists('level_course_id', $record)
        ) {
            return false;
        }

        return in_array(
            trim((string) $record['level_course_id']),
            ['2', '3', '4', '5', '6', '7'],
            true
        );
    }

    private function canonicalizeForComparison(mixed $value): mixed
    {
        if (is_string($value)) {
            return str_replace(["\r\n", "\r"], "\n", trim($value));
        }

        if (! is_array($value)) {
            return $value;
        }

        $canonical = [];

        foreach ($value as $key => $item) {
            $canonical[$key] = $this->canonicalizeForComparison($item);
        }

        if (! array_is_list($canonical)) {
            ksort($canonical, SORT_STRING);
        }

        return $canonical;
    }

    private function updateTotalRowMessage(mixed $messages, int $total): mixed
    {
        if (! is_array($messages)) {
            return $messages;
        }

        return array_map(
            static function (mixed $message) use ($total): mixed {
                if (
                    is_string($message)
                    && preg_match(
                        '/^\s*TOTAL\s+ROW\s*:\s*\d+\s*$/i',
                        $message
                    ) === 1
                ) {
                    return "TOTAL ROW : {$total}";
                }

                return $message;
            },
            $messages
        );
    }

    public function process($ic_no)
    {

    }


}
