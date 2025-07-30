<?php

namespace App\Http\Controllers;

use App\Models\BinaryAgent;
use App\Models\CodeSignElement;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class CodeSignController extends Controller
{

    private CodeSignElement $executionElement;

    private array $codeSignConfig;

    private string $oldPath;

    public function __construct($codeSignConfig)
    {
        $this->codeSignConfig = $codeSignConfig;
    }

    public function setExecutionElement($e): static
    {
        $this->executionElement = $e;

        return $this;
    }

    public function when($condition, $callback): static
    {
        if ($condition) {
            $callback();
        }

        return $this;
    }

    public function codeSignWindowsScript(): static
    {
        if ($this->isCodeSignedAlready()) {
            return $this;
        }

        $scriptPath = storage_path('app/private/'.$this->executionElement->getRawOriginal('path'));

        $powershellCmd = <<<EOD
             \$cert = Get-ChildItem -Path Cert:\\LocalMachine\\My | Where-Object { \$_.Thumbprint -eq "{$this->codeSignConfig['thumbprint']}" };
             \$signature = Set-AuthenticodeSignature -FilePath "$scriptPath" -Certificate \$cert -TimestampServer "http://timestamp.digicert.com"
             \$signature | Format-List * | Out-String
        EOD;

        $process = new Process([
            'powershell',
            '-NoProfile',
            '-ExecutionPolicy', 'Bypass',
            '-Command', $powershellCmd
        ]);

        try {
            $process->run();
        } catch (ProcessFailedException $exception) {
            $this->executionElement->markAsFailed($exception->getMessage());
        }

        !$process->isSuccessful() ?
            $this->executionElement->markAsFailed($process->getErrorOutput()) :
            $this->executionElement->isSuccessFull($process->getOutput());

        return $this;
    }

    public function sendScriptToFaveo()
    {
        if ($this->executionElement->isAcknowledged()) {
            return;
        }

        $response = Http::withBody(json_encode([
            'status' => true, 'platform' =>  $this->executionElement->payloadValue('platform'),
            'token' =>  $this->executionElement->payloadValue('authToken'),
            'contents' => file_get_contents(storage_path( 'app/private/'.$this->executionElement->path))
        ]))->post($this->executionElement->getWebhookUrl());

        if ($response->successful()) {
            $this->executionElement->finalize();
        }
    }

    private function isCodeSignedAlready() : bool
    {
        return $this->executionElement->status;
    }

    public function codeSignBinaryAgent(): static
    {
        $this->runInnoSetupToCompileAgent();

        $this->codeSignViaSignTool();

        return $this;
    }

    private function codeSignViaSignTool()
    {
        $process = new Process([
            $this->codeSignConfig['signToolPath'],
            'sign',
            '/f', app_path('CodeSign/Certificates/codeSign.pfx'),
            '/p', $this->codeSignConfig['pfx-password'],
            '/tr', 'http://timestamp.digicert.com',
            '/td', 'sha256',
            '/fd', 'sha256',
            $this->executionElement->path
        ]);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            $this->executionElement->markAsFailed($process->getErrorOutput());
        }

        $this->executionElement->isSuccessFull($process->getOutput());
        $this->executionElement->finalize();
    }

    public function markAsDeployed()
    {
        $requestData = $this->executionElement->payload;

        BinaryAgent::create([
            'hash' => Str::random(37),
            'platform' => $requestData['platform'],
            'architecture' => $requestData['architecture'],
            'version' => $requestData['version'],
            'path' => $this->executionElement->path,
            'deployed' => 1
        ]);

        $this->executionElement->finalize();
    }

    private function createTemporaryIssFile(&$path)
    {
        $this->oldPath = $this->executionElement->path;

        $issFileContents = file_get_contents(app_path('CodeSign/Helpers/faveo-agent.iss'));

        $this->replaceVariable($issFileContents);

        $path = "temporary-iss-files/faveo-agent-".Str::random().'.iss';

        Storage::put($path, $issFileContents);

    }

    private function replaceVariable(&$issFileContents)
    {
        Storage::makeDirectory($pathToUse = 'code-signed-agents/'.Str::random());

        $variables = [
            'logoFilePath' => app_path('CodeSign/Helpers/logo.bmp'),
            'iconFilePath' => app_path('CodeSign/Helpers/logo.ico'),
            'pfxPath' => app_path('CodeSign/Certificates/codeSign.pfx'),
            'pfxPassword' => config('app.code-sign.pfx-password'),
            'binaryFilePath' => storage_path( 'app/private/'.$this->executionElement->path),
            'outPutDir' =>  storage_path($outPutDir = 'app/private/'.$pathToUse),
            'outputFileName' => $outPutFile = 'faveo-agent-windows_'.
                $this->executionElement->payloadValue('architecture').'_'.
                $this->executionElement->payloadValue('version')
        ];

        foreach ($variables as $key => $value) {
            $issFileContents = Str::replace($key, $value, $issFileContents);
        }

        $this->executionElement->path = 'storage/'.$outPutDir.DIRECTORY_SEPARATOR.$outPutFile.'.exe';
    }

    private function deleteTemporaryIssFile($temporaryIssFile)
    {
        Storage::delete($temporaryIssFile);
    }

    private function runInnoSetupToCompileAgent()
    {
        if ($this->executionElement->status) {
           return;
        }

        if ($this->executionElement->payloadValue('platform') != 'windows') {
            $oldPath = $this->executionElement->path;
            $fileName = 'faveo-agent-'.
                $this->executionElement->payloadValue('platform').'_'.
                $this->executionElement->payloadValue('architecture').'_'.
                $this->executionElement->payloadValue('version');

            Storage::move($oldPath, $pathToUse = 'code-signed-agents/'.Str::random().'/'.$fileName);
            Storage::deleteDirectory(dirname($oldPath));
            $this->executionElement->update(['path' => 'storage/app/private/'.$pathToUse, 'status' => 1]);
            $this->executionElement = $this->executionElement->refresh();
            return;
        }

        $this->createTemporaryIssFile($temporaryIssFile);

        $absolutePath = storage_path('app/private/'. $temporaryIssFile);

        $process = new Process([$this->codeSignConfig['innoPath'], $absolutePath]);

        try {
            $process->mustRun();

            Storage::deleteDirectory(dirname($this->oldPath));
        } catch (ProcessFailedException $e) {
            $this->executionElement->markAsFailed($process->getErrorOutput());
        }

        $this->deleteTemporaryIssFile($temporaryIssFile);

        $this->executionElement->isSuccessFull($process->getOutput());
    }
}
