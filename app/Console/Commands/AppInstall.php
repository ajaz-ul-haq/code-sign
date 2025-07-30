<?php

namespace App\Console\Commands;

use App\Http\Controllers\InstallationController;
use Illuminate\Console\Command;

class AppInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:install {username? : The username for the initial user (default: faveobot)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->argument('username') ?? 'faveobot';

        $controller = new InstallationController();

        $controller->handleInstall($username, $response);

        ($response['success']) ?
            $this->table(['username',  'password'], [$response['data']]):
            $this->error("Installation failed: " . $response['message']);


        return 0;
    }
}
