<?php

namespace App\Http\Controllers\Scanner\Generator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RequestGeneratorsController extends Controller
{
    /**
     * RequestGeneratorsController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        return view('scanners.generator.index');
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create(Request $request)
    {
        $images = [];
        if (!empty($request->file('scan'))) {
            foreach ($request->file('scan') as $file) {
                if (empty($file)) {
                    continue;
                }

                $extension = $file->getClientOriginalExtension();
                $images[]  = [
                    "page"  => count($images) + 1,
                    "image" => 'data:image/' . mb_strtoupper($extension) . ';base64,' .
                               base64_encode($file->getContent()),
                ];
            }
        }

        $body = [
            "svg"              => !empty($request->file('svg')) ? $request->file('svg')->getContent() : '',
            "images"           => $images,
            "settings"         => [
                "return_blob" => !empty($request->get('return_blob', [])) ? $request->get('return_blob') : [],
            ],
            "data-user-locale" => !empty($request->get('language', [])) ? $request->get('language') : [],
            "ocr_engine"       => $request->get('ocr_engine', 'tesseract'),
        ];

        $headers = [
            "token" => config('scanner.token_secret'),
        ];

        $request->session()->put(['body' => $body, 'headers' => $headers]);

        return redirect()->route('Scanner::Generator::view');
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function view(Request $request)
    {
        $body    = $request->session()->pull('body');
        $headers = $request->session()->pull('headers');

        $request->session()->forget(['body', 'headers']);
        $request->session()->flush();

        return view('scanners.generator.view', compact('body', 'headers'));
    }
}
