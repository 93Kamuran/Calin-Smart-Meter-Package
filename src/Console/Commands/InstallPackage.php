<?php

namespace Inensus\CalinSmartMeter\Console\Commands;

use Illuminate\Console\Command;
use Inensus\CalinSmartMeter\Helpers\ApiHelpers;
use Inensus\CalinSmartMeter\Services\CalinSmartCredentialService;
use Inensus\CalinSmartMeter\Services\MenuItemService;

class InstallPackage extends Command
{
    protected $signature = 'calin-smart-meter:install';
    protected $description = 'Install CalinSmartMeter Package';

    private $menuItemService;
    private $apiHelpers;
    private $credentialService;

    public function __construct(
        MenuItemService $menuItemService,
        CalinSmartCredentialService $credentialService,
        ApiHelpers $apiHelpers
    ) {
        parent::__construct();
        $this->menuItemService = $menuItemService;
        $this->apiHelpers = $apiHelpers;
        $this->credentialService = $credentialService;
    }

    public function handle(): void
    {
        $this->info('Installing CalinSmartMeter Integration Package\n');

        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\CalinSmartMeter\Providers\ServiceProvider",
            '--tag' => "migrations"
        ]);

        $this->info('Creating database tables\n');
        $this->call('migrate');

        $this->info('Copying vue files\n');

        $this->call('vendor:publish', [
            '--provider' => "Inensus\CalinSmartMeter\Providers\ServiceProvider",
            '--tag' => "vue-components"
        ]);
        $this->apiHelpers->registerCalinMeterManufacturer();
        $this->credentialService->createCredentials();
        $this->call('plugin:add', [
            'name' => "CalinSmartMeter",
            'composer_name' => "inensus/calin-smart-meter",
            'description' => "CalinSmartMeter integration package for MicroPowerManager",
        ]);
        $this->call('routes:generate');

        $menuItems = $this->menuItemService->createMenuItems();
        $this->call('menu-items:generate', [
            'menuItem' => $menuItems['menuItem'],
            'subMenuItems' => $menuItems['subMenuItems'],
        ]);

        $this->call('sidebar:generate');

        $this->info('Package installed successfully..');
    }
}
