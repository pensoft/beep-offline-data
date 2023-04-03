<?php


namespace App\Http\Controllers\Dashboard;


use App\Http\Controllers\Controller;
use App\Vendors\ScannerTest\ImagickScanner;
use App\Vendors\ScannerTest\Map\Map;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class DashboardController extends Controller
{
    /**
     * DashboardController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param \Illuminate\Http\Response $response
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index(Response $response)
    {

        return view('dashboard.index');
    }

    public function view(Response $response, int $dpi = 300)
    {
        $scanner = new ImagickScanner();
        $scanner->setDebug(true);
        $scanner->setImagePath($this->getImagePath($dpi));
        $scanner->setDebugPath($this->getDebugImagePath($dpi));

        $map = Map::create($this->getMapPath($dpi));

        $result = $scanner->scan($map);

        $json    = file_get_contents($this->getMapPath($dpi));
        $imageUrl = File::exists($this->getDebugImagePath($dpi)) ?
            asset('storage/examples/p_poc_' . $dpi . '_dpi_debug.jpg') : asset('storage/examples/p_poc_' . $dpi . '_dpi.jpg');
        $imageUrl .= '?t=' . Carbon::now()->timestamp;

        return view('dashboard.view', ['results' => $result->toArray(), 'json' => $json, 'imageUrl' => $imageUrl]);
    }

    /**
     * @param int $dpi
     *
     * @return string
     */
    private function getImagePath(int $dpi)
    {
        return storage_path('app/public/examples/p_poc_' . $dpi . '_dpi.jpg');
    }

    /**
     * @param int $dpi
     *
     * @return string
     */
    private function getDebugImagePath(int $dpi)
    {
        return str_replace('_dpi', '_dpi_debug', $this->getImagePath($dpi));
    }

    /**
     * @param int $dpi
     *
     * @return string
     */
    private function getMapPath(int $dpi)
    {
        return storage_path('app/public/examples/map_' . $dpi . '_dpi.json');
    }
}
