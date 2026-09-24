<?php

namespace Jankx\Extensions\NotesForPostType;

use Jankx\Extensions\AbstractExtension;
use Jankx\Extensions\NotesForPostType\Services\NotesService;
use Jankx\Extensions\NotesForPostType\Admin\ThemeOptionsIntegration;
use Jankx\Extensions\NotesForPostType\Admin\NotesMetaBoxes;

/**
 * Notes for Post Type Extension
 *
 * Adds an internal notes metabox (WYSIWYG editor) to the post types
 * selected in the Theme Options panel, rendered via the post-notes block.
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
        // Defer register_post_meta until after custom post types have been
        // registered. Custom post types register on `init` at priority 10–15,
        // so we run at priority 20. Running earlier (during after_setup_theme
        // when this extension boots) would compute the allowed-post-types list
        // before those post types exist, caching only built-in types — which
        // means add_meta_boxes would never see them on tour/place/etc.
        if (did_action('init')) {
            $this->service->registerMeta();
        } else {
            add_action('init', [$this->service, 'registerMeta'], 20);
        }

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
