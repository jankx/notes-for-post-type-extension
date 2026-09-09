<?php

namespace Jankx\Extensions\NotesForPostType;

use Jankx\Extensions\AbstractExtension;
use Jankx\Extensions\NotesForPostType\Services\NotesService;
use Jankx\Extensions\NotesForPostType\Admin\ThemeOptionsIntegration;
use Jankx\Extensions\NotesForPostType\Admin\NotesMetaBoxes;

/**
 * Notes for Post Type Extension
 *
 * Adds an internal notes metabox to selected post types,
 * with settings managed through the Jankx Theme Options panel.
 *
 * @package Jankx\Extensions\NotesForPostType
 */
class NotesForPostTypeExtension extends AbstractExtension
{
    protected static $instance;

    /**
     * @var NotesService
     */
    protected $service;

    public function __construct()
    {
        $this->register_autoloader();
        parent::__construct();
    }

    protected function register_autoloader()
    {
        spl_autoload_register(function ($class) {
            $prefix = 'Jankx\\Extensions\\NotesForPostType\\';
            $base_dir = __DIR__ . '/src/';

            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relative_class = substr($class, $len);
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

            if (file_exists($file)) {
                require $file;
            }
        });
    }

    public function init(): void
    {
        self::$instance = $this;

        $this->service = new NotesService();
    }

    public static function get_instance(): ?self
    {
        return self::$instance;
    }

    public function register_hooks(): void
    {
        $this->service->registerMeta();

        $optionsIntegration = new ThemeOptionsIntegration($this->service);
        $optionsIntegration->register();

        if (did_action('init')) {
            $this->registerBlocks();
        } else {
            add_action('init', [$this, 'registerBlocks']);
        }

        if (is_admin()) {
            $metaBoxes = new NotesMetaBoxes($this->service);
            $metaBoxes->register();
        }
    }

    /**
     * Register Gutenberg blocks for this extension
     */
    public function registerBlocks(): void
    {
        $blocksDir = __DIR__ . '/blocks';
        if (!is_dir($blocksDir)) {
            return;
        }

        foreach (glob($blocksDir . '/*', GLOB_ONLYDIR) as $blockDir) {
            if (!file_exists($blockDir . '/block.json')) {
                continue;
            }

            $blockJson = json_decode(file_get_contents($blockDir . '/block.json'), true);
            $blockName = $blockJson['name'] ?? '';

            if ($blockName === 'jankx/post-notes' && !\WP_Block_Type_Registry::get_instance()->is_registered($blockName)) {
                $block = new \Jankx\Extensions\NotesForPostType\Blocks\PostNotesBlock($blockDir);
                $block->setBlockPath($blockDir);
                $block->boot();
                $block->register();
            } elseif ($blockName && !\WP_Block_Type_Registry::get_instance()->is_registered($blockName)) {
                register_block_type_from_metadata($blockDir);
            }
        }
    }
}
