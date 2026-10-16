<?php

use App\Livewire\StpmForm;
use App\Livewire\IntegrationMap;
use App\Models\APILog;
use App\Models\User;
use App\Services\MYGDXService;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

it('shows the STPM form on its integration page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('integrasi.mygdx-stpm'))
        ->assertOk()
        ->assertSee('Semak MyGDX STPM');
});

it('sends valid details to the STPM service and displays the response', function () {
    $service = Mockery::mock(MYGDXService::class);
    $service->shouldReceive('getSTPM')
        ->once()
        ->with('021009031069', '2021')
        ->andReturn(['keputusan' => 'Lulus']);
    app()->instance(MYGDXService::class, $service);

    Livewire::test(StpmForm::class)
        ->set('no_kp', '021009031069')
        ->set('tahun', '2021')
        ->call('process')
        ->assertHasNoErrors()
        ->assertSee('Lulus');
});

it('rejects an invalid year before calling the STPM service', function () {
    $service = Mockery::mock(MYGDXService::class);
    $service->shouldNotReceive('getSTPM');
    app()->instance(MYGDXService::class, $service);

    Livewire::test(StpmForm::class)
        ->set('no_kp', '021009031069')
        ->set('tahun', '21')
        ->call('process')
        ->assertHasErrors(['tahun']);
});

it('keeps MUET and STPM traffic separate on the integration map', function () {
    config()->set('integrations', array_intersect_key(
        config('integrations'),
        array_flip(['mygdx-muet', 'mygdx-stpm'])
    ));

    APILog::create([
        'url' => 'https://spp.mygdx.gov.my',
        'input' => ['xml_body' => '<iden:serviceCode>MPM-SSKSM-R-MPMFikSTPM</iden:serviceCode>'],
        'status_code' => 200,
    ]);
    APILog::create([
        'url' => 'https://spp.mygdx.gov.my',
        'input' => ['xml_body' => '<iden:serviceCode>MPM-SSKSM-R-MPMFikMUET</iden:serviceCode>'],
        'status_code' => 500,
    ]);

    Livewire::test(IntegrationMap::class);

    $health = Cache::get('integration-map.health');
    expect($health['results']['mygdx-muet']['status'])->toBe('warning')
        ->and($health['results']['mygdx-stpm']['status'])->toBe('up');
});
