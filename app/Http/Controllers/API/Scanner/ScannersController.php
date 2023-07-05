<?php

namespace App\Http\Controllers\API\Scanner;

use App\Http\Controllers\API\APIsController;
use App\Vendors\Scanner\Traits\Scanner\ScannerTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScannersController extends APIsController
{
    use ScannerTrait;

    private array $allowedScanExtensions  = ['jpg', 'jpeg', 'png'];
    private array $allowedScanBlobReturns = ['text', 'single-digit', 'number', 'checkbox'];

    /**
     * ScannersController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param \Illuminate\Http\Request $request
     */
    public function validateScanRequest(Request $request)
    {
        if ($request->header('token') !== config('scanner.token_secret')) {
            $this->addError('Wrong Token');
            $this->setResponseStatus(JsonResponse::HTTP_UNAUTHORIZED);

            return;
        }

        if (empty($request->get('svg'))) {
            $this->addError('SVG schema is missing');
        }

        if (empty($request->get('images'))) {
            $this->addError('Images are missing');
        } else {
            foreach ($request->get('images') as $index => $imageData) {
                if (empty($imageData['page'])) {
                    $this->addError('Missing page number in image ' . ($index + 1));
                }

                $extension = $this->getExtensionFromBlob($imageData['image'], true);
                if (!in_array($extension, $this->allowedScanExtensions)) {
                    $this->addError('Extension is not supported for image ' . ($index + 1));
                }

                if (empty($this->getBlobContents($imageData['image'])))  {
                    $this->addError('Cannot convert image ' . ($index + 1));
                }
            }
        }

        if (!empty($request->get('settings'))) {
            $settings = $request->get('settings');
            if (!empty($settings['return_blob'])) {
                foreach ($settings['return_blob'] as $type) {
                    if (!in_array($type, $this->allowedScanBlobReturns)) {
                        $this->addError('Unknown blob return type ' . $type);
                    }
                }
            }
        }
    }
}
