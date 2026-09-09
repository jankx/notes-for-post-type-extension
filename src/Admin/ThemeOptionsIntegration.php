<?php

namespace Jankx\Extensions\NotesForPostType\Admin;

use Jankx\Extensions\NotesForPostType\Services\NotesService;
use Jankx\Dashboard\Factories\FieldFactory;
use Jankx\Dashboard\Elements\Page;
use Jankx\Dashboard\Elements\Section;
use Jankx\Adapter\Options\Framework as OptionFramework;

/**
 * Theme Options Integration
 *
 * Injects the extension settings page into the Jankx theme options
 * (OptionFramework / dashboard-framework) by adding the page directly
 * to the framework's pages array after createSections() has run.
 */
class ThemeOptionsIntegration
{
    const PAGE_ID = 'notes_for_post_type';

    /**
     * @var NotesService
     */
    protected $service;

    protected $injected = false;

    public function __construct(NotesService $service)
    {
        $this->service = $service;
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'injectPage'], 1);
    }

    /**
     * Inject the page into the OptionFramework's pages array.
     */
    public function injectPage(): void
    {
        if ($this->injected) {
            return;
        }
        $this->injected = true;

        $framework = $this->getFramework();
        if (!$framework) {
            return;
        }

        foreach ($framework->pages as $existing) {
            if (($existing->getId() ?? '') === self::PAGE_ID) {
                return;
            }
        }

        $saved = get_option('jankx_options', []);

        $page = new Page(__('Notes for Post Type', 'jankx'), [], 'dashicons-before dashicons-edit-page');
        $page->setId(self::PAGE_ID);
        $page->setDescription(__('Enable internal notes on selected post types', 'jankx'));
        $page->setPriority(46);

        $section = new Section(__('General', 'jankx'), []);
        $section->setId(self::PAGE_ID . '_general');

        $section->addField(FieldFactory::create(
            NotesService::OPTION_ENABLED,
            __('Enable Notes', 'jankx'),
            'switch',
            [
                'on' => __('On', 'jankx'),
                'off' => __('Off', 'jankx'),
                'value' => $saved[NotesService::OPTION_ENABLED] ?? 1,
                'default' => 1,
                'description' => __('Master switch for post type notes', 'jankx'),
            ]
        ));

        $section->addField(FieldFactory::create(
            NotesService::OPTION_POST_TYPES,
            __('Supported Post Types', 'jankx'),
            'checkbox',
            [
                'options' => $this->service->getPublicPostTypes(),
                'value' => $saved[NotesService::OPTION_POST_TYPES] ?? $this->getDefaultPostTypes(),
                'default' => $this->getDefaultPostTypes(),
                'layout' => 'vertical',
                'description' => __('Select post types that support internal notes. Can also be overridden via the jankx/notes-for-post-type/post-types filter.', 'jankx'),
            ]
        ));

        $page->addSection($section);
        $framework->addPage($page);
    }

    protected function getFramework()
    {
        try {
            $adapter = OptionFramework::getActiveFramework();
            if ($adapter && method_exists($adapter, 'getFramework')) {
                return $adapter->getFramework();
            }
        } catch (\Exception $e) {
        }
        return null;
    }

    protected function getDefaultPostTypes(): array
    {
        return ['post', 'page'];
    }
}
