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

        dd('END TESTS');
    }
}
