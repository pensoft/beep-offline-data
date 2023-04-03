<?php

namespace App\Http\Controllers\Tests;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use DOMDocument;

class TestsIvoController extends Controller
{
    /**
     * TestsIvoController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param \Illuminate\Http\Response $response
     */
    public function index(Response $response)
    {
        $data = [1, 2, 3, 4, 1, 5, 2];
        $data = array_unique($data);
        dd($data);

        $contents = file_get_contents(storage_path('app/public/scanner/configs/template_page_1.svg'));

        $document = new DOMDocument();
        $document->loadXML($contents);
        //        $svg = $document->getElementsByTagName('svg');

        $data   = [];
        $labels = $document->getElementsByTagName('rect');
        foreach ($labels as $label) {
            $data[] = [
                'x'      => $label->getAttribute('x'),
                'y'      => $label->getAttribute('y'),
                'width'  => $label->getAttribute('width'),
                'height' => $label->getAttribute('height'),
                'id'     => $label->getAttribute('data-question_id'),
                'type'   => $label->getAttribute('data-type'),
                'name'   => $label->getAttribute('data-label'),
                'value'  => $label->getAttribute('data-value'),
            ];
        }
        dump($data);
        dd('END TESTS');
    }
}
