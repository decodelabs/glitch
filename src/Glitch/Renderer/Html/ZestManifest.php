<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Renderer\Html;

use DecodeLabs\Zest\Manifest;

class ZestManifest
{
    public const AssetsDir = __DIR__ . '/../assets';

    protected ?Manifest $manifest = null;

    public function __construct()
    {
        $path = self::AssetsDir . '/.vite/manifest.json.php';

        if (file_exists($path)) {
            $this->manifest = Manifest::load(self::AssetsDir . '/.vite/manifest.json');
        }
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function getCssData(): array
    {
        if ($this->manifest) {
            return $this->manifest->getCssData();
        } else {
            return [
                self::AssetsDir . '/style.css' => [
                    'id' => 'style-glitch'
                ]
            ];
        }
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function getJsData(): array
    {
        $output = [];

        if ($this->manifest) {
            foreach ($this->manifest->getHeadJsData() as $file => $attrs) {
                $output[$file] = $attrs;
            }

            foreach ($this->manifest->getBodyJsData() as $file => $attrs) {
                $output[$file] = $attrs;
            }
        } else {
            $output[self::AssetsDir . '/main.js'] = [
                'id' => 'script-glitch',
                'type' => 'module'
            ];
        }

        return $output;
    }
}
