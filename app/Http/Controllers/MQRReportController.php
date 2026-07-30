<?php

namespace App\Http\Controllers;

use App\Models\MqrMappingRun;
use App\Services\MQRReportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MQRReportController extends Controller
{
    public function __invoke(
        MqrMappingRun $run,
        string $type,
        MQRReportService $reportService
    ): StreamedResponse {
        return $reportService->download($run, $type);
    }
}
