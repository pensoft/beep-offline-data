<?php

namespace App\Http\Controllers\API\Scanner\Scan;

use App\Http\Controllers\API\Scanner\ScannersController;
use App\Vendors\Scanner\ImagickScanner;
use App\Vendors\Scanner\Traits\Scanner\ScannerTrait;
use Error;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScansController extends ScannersController
{
    use ScannerTrait;

    private array $languages = ['eng'];

    /**
     * ScannersController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function scan(Request $request): JsonResponse
    {
        $this->validateScanRequest($request);

        if (count($this->getErrors())) {
            return response()->json(['errors' => $this->getErrors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $imagickScanner = new ImagickScanner($request);
            $imagickScanner->scan();

            return response()
                ->json(
                    [
                        'app_version' => $imagickScanner->getAppVersion(),
                        'scans'       => $imagickScanner->getScanResults(),
                    ]
                );
        } catch (Exception $e) {
            $this->addError($e->getMessage());
        } catch (Error $e) {
            $this->addError($e->getMessage());
        }

        return response()->json(['errors' => $this->getErrors()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }
}
