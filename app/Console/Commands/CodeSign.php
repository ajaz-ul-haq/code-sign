<?php

namespace App\Console\Commands;

use App\Http\Controllers\CodeSignController;
use App\Models\CodeSignElement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CodeSign extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'code:sign';


    /**
     * The Controller
     * @var CodeSignController
     */

    private CodeSignController $handler;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Code Sign the executables and scripts';

    public function __construct()
    {
        $codeSigningConfig = config('app.code-sign');

        $this->handler = new CodeSignController($codeSigningConfig);

        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pendingElements = CodeSignElement::whereAny(['status', 'sent'], 0)->get();

        foreach ($pendingElements as $codeSigning ) {
            $this->handler->setExecutionElement($codeSigning)
                ->when($codeSigning->isForScript(), function () {
                    $this->handler->codeSignWindowsScript()->sendScriptToFaveo();
                })->when($codeSigning->isForAgent(), function () {
                    $this->handler->codeSignBinaryAgent()->markAsDeployed();
                });
        }
    }
}
