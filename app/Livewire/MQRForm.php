<?php

namespace App\Livewire;

use App\Models\MqrData;
use App\Models\MqrMappingRun;
use App\Services\MQRReportService;
use App\Services\MQRService;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class MQRForm extends Component
{
    public $result = null;

    #[Locked]
    public ?string $runId = null;

    public $isMapping = false;

    public bool $hasActiveRun = false;

    public ?string $runStatus = null;

    public $totalMqr = 0;

    public $processedCount = 0;

    public $berjayaCount = 0;

    public $gagalCount = 0;

    public $logBerjaya = [];

    public $logGagal = [];

    public array $completedReports = [];

    public function mount(MQRReportService $reportService): void
    {
        $this->refreshCompletedReports($reportService);

        $running = $reportService->currentRunning();

        if ($running) {
            $this->runId = $running->id;
            $this->hydrateRunState($reportService->state($running));
        }
    }

    public function render()
    {
        return view('livewire.m-q-r-form');
    }

    public function process(
        MQRService $service,
        MQRReportService $reportService
    ): void {
        $this->result = $reportService->syncWhenIdle(
            fn (): array => $service->syncData()
        );
        $this->totalMqr = MqrData::query()->count();
    }

    public function startMapping(MQRReportService $reportService): void
    {
        if (
            ! $reportService->currentRunning()
            && ! MqrData::query()->exists()
        ) {
            $this->isMapping = false;
            $this->result = [
                'status' => false,
                'message' => 'Tiada data MQR di pangkalan data. Sila Sync (API) dahulu.',
            ];

            return;
        }

        try {
            $run = $reportService->startOrResume(auth()->id());
        } catch (Throwable $e) {
            $this->isMapping = false;
            $this->result = [
                'status' => false,
                'message' => 'Proses padanan tidak dapat dimulakan: '.$e->getMessage(),
            ];

            return;
        }

        $this->runId = $run->id;
        $this->hydrateRunState($reportService->state($run));
        $this->logBerjaya = [];
        $this->logGagal = [];
        $this->isMapping = true;
        $this->result = null;

        $this->dispatch('process-next-batch');
    }

    #[On('process-next-batch')]
    public function handleNextBatch(
        MQRService $service,
        MQRReportService $reportService
    ): void {
        if (! $this->isMapping || $this->runId === null) {
            return;
        }

        $response = $service->mapNextBatch(
            $this->runId,
            250,
            $reportService
        );

        if (! $response['status']) {
            $this->isMapping = false;
            $this->result = [
                'status' => false,
                'message' => 'Proses padanan dijeda selepas ralat. Klik Automasi Padanan untuk menyambung semula: '
                    .$response['message'],
            ];

            $running = $reportService->currentRunning();

            if ($running?->id === $this->runId) {
                $this->hydrateRunState($reportService->state($running));
            }

            return;
        }

        $stats = $response['data'];
        $this->hydrateRunState($stats['run']);
        $this->logBerjaya = array_slice(
            array_merge($stats['latest_berjaya'], $this->logBerjaya),
            0,
            10
        );
        $this->logGagal = array_slice(
            array_merge($stats['latest_gagal'], $this->logGagal),
            0,
            10
        );

        if ($stats['run']['status'] === MqrMappingRun::STATUS_COMPLETED) {
            $this->isMapping = false;
            $this->result = [
                'status' => true,
                'message' => 'Proses padanan selesai. Laporan Excel kini tersedia untuk dimuat turun.',
            ];
            $this->refreshCompletedReports($reportService);

            return;
        }

        $this->dispatch('process-next-batch');
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function hydrateRunState(array $state): void
    {
        $this->totalMqr = (int) $state['total'];
        $this->processedCount = (int) $state['processed'];
        $this->berjayaCount = (int) $state['berjaya'];
        $this->gagalCount = (int) $state['gagal'];
        $this->runStatus = (string) $state['status'];
        $this->hasActiveRun = in_array($state['status'], [
            MqrMappingRun::STATUS_RUNNING,
            MqrMappingRun::STATUS_FINALIZING,
        ], true);
    }

    private function refreshCompletedReports(
        MQRReportService $reportService
    ): void {
        $this->completedReports = $reportService->completedReports();
    }
}
