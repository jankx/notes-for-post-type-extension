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

        if (is_admin()) {
            $metaBoxes = new NotesMetaBoxes($this->service);
            $metaBoxes->register();
        }
    }
}
