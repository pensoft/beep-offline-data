<?php

namespace App\Vendors\Scanner\Services\AWS\Textract;

use App\Vendors\Scanner\Traits\Scanner\ScannerTrait;
use Aws\Textract\TextractClient;
use Exception;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class DocumentAnalyzer
{
    use ScannerTrait;

    private string $folder;
    private string $filename;

    private string $error = '';

    /** @var \Psr\Log\LoggerInterface */
    protected LoggerInterface $log;

    public function __construct(string $folder, string $filename)
    {
        $this->setFolder($folder);
        $this->setFilename($filename);

        $this->log = Log::build(
            [
                'driver' => 'single',
                'path'   => $folder . '/' . 'scan.log',
                'level'  => config('scanner.log_mode'),
            ]
        );
    }

    /**
     * @return array
     */
    public function analyze(): array
    {
        $blocks = $this->analyzeDocument();

        // Get key and value maps
        $keyMap   = $this->getKeyMaps($blocks);
        $valueMap = $this->getValueMaps($blocks);
        $blockMap = $this->getBlockMaps($blocks);

        $analyzeResults = $this->getKeyValueRelationships($keyMap, $valueMap, $blockMap);

        $this->getLog()->debug('AWS Analyze results: ' . json_encode($analyzeResults));

        return $analyzeResults;
    }

    /**
     * @param $keyMap
     * @param $valueMap
     * @param $blockMap
     *
     * @return array
     */
    public function getKeyValueRelationships($keyMap, $valueMap, $blockMap): array
    {
        $keyValues = [];
        foreach ($keyMap as $blockId => $keyBlock) {
            $valueBlock = $this->findValueBlock($keyBlock, $valueMap);
            $label      = $this->getText($keyBlock, $blockMap);
            $key        = $this->getLabelKey($label);
            $value      = $this->getText($valueBlock, $blockMap);

            $keyValues[$key][] = [
                'block_id' => $blockId,
                'label'    => trim($label),
                'value'    => trim($value),
            ];
        }

        return $keyValues;
    }

    /**
     * @param $keyBlock
     * @param $valueMap
     *
     * @return array|null
     */
    public function findValueBlock($keyBlock, $valueMap): ?array
    {
        $valueBlock = null;
        foreach ($keyBlock['Relationships'] as $relationship) {
            if ($relationship['Type'] == 'VALUE') {
                foreach ($relationship['Ids'] as $valueId) {
                    $valueBlock = $valueMap[$valueId];
                }
            }
        }

        return $valueBlock;
    }

    /**
     * @param $result
     * @param $blocksMap
     *
     * @return string
     */
    public function getText($result, $blocksMap): string
    {
        $text = '';
        if (isset($result['Relationships'])) {
            foreach ($result['Relationships'] as $relationship) {
                if ($relationship['Type'] == 'CHILD') {
                    foreach ($relationship['Ids'] as $childId) {
                        $word = $blocksMap[$childId];
                        if ($word['BlockType'] == 'WORD') {
                            $text .= $word['Text'] . ' ';
                        }
                        if ($word['BlockType'] == 'SELECTION_ELEMENT') {
                            if ($word['SelectionStatus'] == 'SELECTED') {
                                $text .= 'X ';
                            }
                        }
                    }
                }
            }
        }

        return $text;
    }

    /**
     * @param array $blocks
     *
     * @return array
     */
    private function getKeyMaps(array $blocks): array
    {
        $keyMap = [];
        foreach ($blocks as $block) {
            if ($block['BlockType'] == "KEY_VALUE_SET") {
                if (in_array('KEY', $block['EntityTypes'])) {
                    $keyMap[$block['Id']] = $block;
                }
            }
        }

        return $keyMap;
    }

    /**
     * @param array $blocks
     *
     * @return array
     */
    private function getValueMaps(array $blocks): array
    {
        $valueMap = [];
        foreach ($blocks as $block) {
            if ($block['BlockType'] == "KEY_VALUE_SET") {
                if (!in_array('KEY', $block['EntityTypes'])) {
                    $valueMap[$block['Id']] = $block;
                }
            }
        }

        return $valueMap;
    }

    /**
     * @param array $blocks
     *
     * @return array
     */
    private function getBlockMaps(array $blocks): array
    {
        $blockMap = [];
        foreach ($blocks as $block) {
            $blockMap[$block['Id']] = $block;
        }

        return $blockMap;
    }

    /**
     * @return array
     */
    private function analyzeDocument(): array
    {
        $client = new TextractClient(
            [
                'region'      => 'eu-west-3',
                'version'     => 'latest',
                'credentials' => [
                    'key'    => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ]
        );

        $file         = fopen($this->getFolder() . '/' . $this->getFilename(), 'rb');
        $fileContents = fread($file, filesize($this->getFolder() . '/' . $this->getFilename()));
        fclose($file);

        $options = [
            'Document'     => [
                'Bytes' => $fileContents,
            ],
            'FeatureTypes' => ['FORMS'], // REQUIRED
        ];

        $results = [];
        try {
            $response = $client->analyzeDocument($options);

            $response = $response->toArray();
            $results  = $response['Blocks'] ?? [];
        } catch (Exception $exception) {
            $this->setError($exception->getMessage());
            $this->getLog()->error('AWS Analyze Document error: ' . $exception->getMessage());
        }

        return $results;
    }

    /**
     * @return string
     */
    public function getFolder(): string
    {
        return $this->folder;
    }

    /**
     * @param string $folder
     */
    public function setFolder(string $folder): void
    {
        $this->folder = $folder;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * @param string $error
     */
    public function setError(string $error): void
    {
        $this->error = $error;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLog(): LoggerInterface
    {
        return $this->log;
    }
}
