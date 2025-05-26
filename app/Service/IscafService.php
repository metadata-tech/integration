<?php

namespace App\Service;
use App\Models\APILog;

use App\Models\iscaf\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IscafService {


    public function process($ic_no, $activity_id, $branch_id, $user_id)
    {
        $app = App::query()
            ->where('ic_no', $ic_no)
            ->where('branch_id', $branch_id)
            ->with('file.historyDocumentNumber')
            ->orderBy('id', 'desc')
            ->first();

        ray($app)->purple();

        //dump($app->file->historyDocumentNumber);

        $userDefineValuesAry = [
            "name" => "HASSAN TEST",
            "department" => "BAHAGIAN PERKHIDMATAN",
            "unit" => "UNIT PERKHIDMATAN 4 (W.P PUTRAJAYA) & DATA DAN PENTADBIRAN",
            "position" => "PENOLONG PEGAWAI TEKNOLOGI MAKLUMAT"
        ];

        if(count($app->file->historyDocumentNumber) > 0) {
            Log::info("sudah ada historyDocumentNumber");


            $last_document_number = $app->file->historyDocumentNumber->last()->value;
            Log::info("nombor sekarang adalah ".$last_document_number);

            $new_document_number = $last_document_number + 1;

            Log::info("nombor baru adalah ".$new_document_number);

            $app->file->historyDocumentNumber()->create([
                'value' => $new_document_number,
                'activity' => $activity_id,
                'user_id' => $user_id,
                'user_defined_values' => json_encode($userDefineValuesAry)
            ]);

            $app->file->update([
                'document_number' => $new_document_number
            ]);


        }
        else if (count($app->file->historyDocumentNumber) == 0) {
            Log::info("tiada historyDocumentNumber");


            $new_document_number = 1;
            Log::info("nombor baru adalah ".$new_document_number);

            $app->file->historyDocumentNumber()->create([
                'value' => $new_document_number,
                'activity' => $activity_id,
                'user_id' => $user_id,
                'user_defined_values' => json_encode($userDefineValuesAry)
            ]);

            $app->file->update([
                'document_number' => $new_document_number
            ]);

            Log::info("berjaya tambah");
        }
        ray($app)->red();


        #dd($app->file->historyDocumentNumber->toArray());
        // return back
        $app = App::query()
            ->where('ic_no', $ic_no)
            ->where('branch_id', $branch_id)
            ->with('file.historyDocumentNumber')
            ->orderBy('id', 'desc')
            ->first();

        return $app;
    }


}
