<?php

namespace App\Http\Controllers;

use App\Models\BinaryAgent;
use App\Models\CodeSignElement;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\UnableToRetrieveMetadata;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncomingRequestController extends Controller
{
    protected $request;

    private function setRequest($request): void
    {
        $this->request = $request;
    }

    private function request($key, $default = '')
    {
        return $this->request->input($key, $default);
    }

    public function initializeCodeSigning(Request $request): JsonResponse
    {
        $this->setRequest($request);

        $this->injectVariableIntoScript($content);

        $this->saveScriptFile($content, $path);

        $this->saveClientRequest($signingElement, $path);

        return $this->successResponse('', $signingElement);
    }

    private function injectVariableIntoScript(&$content): void
    {
        $originalContent = $this->getOriginalContent();

        $replacedContent = Str::replace('agentDLChange', $this->getAgentDownloadLink(), $originalContent);
        $replacedContent = Str::replace('apiURLChange', $this->request('apiUrl'), $replacedContent);
        $replacedContent = Str::replace('meshDLChange', $this->request('meshUrl'), $replacedContent);
        $replacedContent = Str::replace('noMeshChange', $this->request('noMesh'), $replacedContent);
        $content = Str::replace('tokenChange', $this->request('authToken'), $replacedContent);
    }

    private function getOriginalContent(): false|string
    {
        $files = File::files(app_path('CodeSign/Scripts'));

        foreach ($files as $file) {
            if (stripos($file->getFilename(), $this->request['platform']) !== false) {
                $matchedFile = $file;
                break;
            }
        }

        return file_get_contents($matchedFile);
    }

    private function saveScriptFile($contents, &$path): void
    {
        $format = $this->request('platform') === 'windows' ? '.ps1' : '.sh';
        $path = 'code-signed-scripts/faveo-agent-' . Str::random(10) . $format;

        Storage::put($path, $contents);
    }


    private function saveClientRequest(&$element, $pathToScript, $type = 'windows_script')
    {
        $element = CodeSignElement::create([
            'client_id' => Auth::id(),
            'type' => $type,
            'payload' => $this->request->toArray(),
            'path' => $pathToScript
        ]);

    }

    public function saveCompiledAgents(Request $request): JsonResponse
    {
        $agent = $request->file('agent');

        if (!($agent instanceof UploadedFile)) {
            return $this->errorResponse('Binary missing in payload');
        }

        $this->setRequest($request);

        $path = 'incoming-agents/' .Str::random(17).DIRECTORY_SEPARATOR.$agent->getClientOriginalName();

        Storage::put($path, $agent->getContent());

        $this->saveClientRequest($signingElement, $path, 'agent');

        return $this->successResponse("Agent Saved successfully at $path",  $signingElement);
    }

    public function downloadAgent($hash): StreamedResponse|JsonResponse|BinaryFileResponse
    {
        $agent = BinaryAgent::whereHash($hash)->firstOr(function () {
            throw new HttpResponseException($this->errorResponse('Invalid Agent Hash'));
        });

        try {
            return Storage::download(base_path($agent->path));
        } catch (\Exception $exception) {
            if ($exception instanceof UnableToRetrieveMetadata) {
                return response()->download(base_path($agent->path));
            }

            return $this->errorResponse('Failed to download agent : '. $exception->getMessage());
        }
    }

    private function getAgentDownloadLink() : string
    {
        $hash = BinaryAgent::where([
            'platform' => $this->request->platform,
            'architecture' => $this->request->architecture
        ])->orderBy('version', 'desc')->value('hash');

        return url("api/agent/download/$hash");
    }
}
