<?php

namespace App\Vendors\Scanner\Services\AWS\Textract;

use Aws\Textract\TextractClient;

class DocumentAnalyzer
{
    public function __construct(private string $scan)
    {
    }

    /**
     * @return array
     */
    public function analyze(): array
    {
        $blocks = $this->getBlocks();

        // Get key and value maps
        $keyMap   = $this->getKeyMaps($blocks);
        $valueMap = $this->getValueMaps($blocks);
        $blockMap = $this->getBlockMaps($blocks);

        return $this->getKeyValueRelationships($keyMap, $valueMap, $blockMap);
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
            $value      = $this->getText($valueBlock, $blockMap);

            $keyValues[mb_strtolower($label)][] = [
                'block_id' => $blockId,
                'label' => $label,
                'value' => $value,
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
            $block_id = $block['Id'];
            if ($block['BlockType'] == "KEY_VALUE_SET") {
                if (in_array('KEY', $block['EntityTypes'])) {
                    $keyMap[$block_id] = $block;
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
            $block_id = $block['Id'];
            if ($block['BlockType'] == "KEY_VALUE_SET") {
                if (!in_array('KEY', $block['EntityTypes'])) {
                    $valueMap[$block_id] = $block;
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
            $block_id            = $block['Id'];
            $blockMap[$block_id] = $block;
        }

        return $blockMap;
    }

    /**
     * @return array
     */
    private function getBlocks(): array
    {
        $client = new TextractClient([
            'region'      => 'eu-west-3',
            'version'     => 'latest',
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        $filename     = 'scan_1.jpg';
        $file         = fopen(storage_path('app/public/scans/' . $filename), 'rb');
        $fileContents = fread($file, filesize(storage_path('app/public/scans/' . $filename)));
        fclose($file);

        $options = [
            'Document'     => [
                'Bytes' => $fileContents,
            ],
            'FeatureTypes' => ['FORMS'], // REQUIRED
        ];

        $response = $client->analyzeDocument($options);

        $results = $response->toArray();
        if (!empty($results['Blocks'])) {
            return $results['Blocks'];
        }

        return [];
    }

    /**
     * @return string
     */
    public function getScan(): string
    {
        return $this->scan;
    }

    /**
     * @param string $scan
     */
    public function setScan(string $scan): void
    {
        $this->scan = $scan;
    }
}
