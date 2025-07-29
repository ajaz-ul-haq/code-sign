<?php

namespace App\Http\Controllers;

use App\Http\Requests\CodeSignRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\Process\Process;

class CodeSignController extends Controller
{

    public function handleCodeSign(CodeSignRequest $request): JsonResponse
    {
        $this->loadCertificates($certificates);

        dd($certificates);
    }

    private function loadCertificates(&$certificates)
    {
        $certificates = config('app.certificates');
    }
}
