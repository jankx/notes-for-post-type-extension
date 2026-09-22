<?php

namespace Jankx\Extensions\NotesForPostType\Admin;

use Jankx\Extensions\NotesForPostType\Services\NotesService;
use Jankx\Adapter\Options\Helper;

/**
 * Notes MetaBoxes
 *
 * Adds a "Notes" metabox to post types that support it,
 * allowing administrators to save internal notes per post.
 */
class NotesMetaBoxes
{
    const NONCE_NAME = 'jankx_notes_meta_nonce';
    const NONCE_ACTION = 'jankx_notes_meta_action';

    /**
     * @var NotesService
     */
    protected $service;

    public function __construct(NotesService $service)
    {
        $this->service = $service;
    }

    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('save_post', [$this, 'saveMetaBoxes']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Register the notes metabox for each supported post type.
     */
    public function addMetaBoxes(): void
    {
        foreach ($this->service->getAllowedPostTypes() as $postType) {
            add_meta_box(
                'jankx_notes',
                __('Ghi chú', 'jankx'),
                [$this, 'renderMetaBox'],
                $postType,
                'normal',
                'high'
            );
        }
    }

    /**
     * Enqueue editor assets so wp_editor works in Gutenberg context.
     */
    public function enqueueAssets(string $hook): void
    {
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }

        wp_enqueue_editor();
    }

    /**
     * Render the notes metabox using wp_editor.
     *
     * @param \WP_Post $post Current post object.
     */
    public function renderMetaBox(\WP_Post $post): void
    {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        $note = $this->service->getNote($post->ID);

        wp_editor(
            $note,
            'jankx_notes',
            [
                'textarea_name' => 'jankx_notes',
                'textarea_rows' => 10,
                'media_buttons' => true,
                'teeny'         => false,
                'tinymce'       => true,
                'quicktags'     => true,
            ]
        );
    }

    /**
     * Save the note when the post is saved.
     *
     * @param int $postId Post ID.
     */
    public function saveMetaBoxes(int $postId): void
    {
        if (!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce($_POST[self::NONCE_NAME], self::NONCE_ACTION)) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        $note = isset($_POST['jankx_notes']) ? wp_kses_post(wp_unslash($_POST['jankx_notes'])) : '';
        $this->service->saveNote($postId, $note);
    }
}
