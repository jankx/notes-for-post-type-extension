<?php

namespace Jankx\Extensions\NotesForPostType\Admin;

use Jankx\Extensions\NotesForPostType\Services\NotesService;

/**
 * Notes MetaBoxes
 *
 * Adds a "Ghi chú" (Notes) metabox using the WordPress WYSIWYG editor
 * (TinyMCE) to every post type configured to support notes in the
 * Theme Options panel.
 *
 * @package Jankx\Extensions\NotesForPostType\Admin
 */
class NotesMetaBoxes
{
    const NONCE_NAME = 'jankx_notes_meta_nonce';
    const NONCE_ACTION = 'jankx_notes_meta_action';

    const EDITOR_ID = 'jankx_notes';
    const FIELD_NAME = 'jankx_notes';

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
     * Register the notes metabox for every post type selected in theme options.
     */
    public function addMetaBoxes(): void
    {
        if (!$this->service->isEnabled()) {
            return;
        }

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
     * Load the WYSIWYG editor assets on post edit screens.
     */
    public function enqueueAssets(string $hook): void
    {
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }

        wp_enqueue_editor();
    }

    /**
     * Render the notes metabox with the full WYSIWYG editor.
     *
     * @param \WP_Post $post Current post object.
     */
    public function renderMetaBox(\WP_Post $post): void
    {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        $note = $this->service->getNote($post->ID);

        wp_editor(
            $note,
            self::EDITOR_ID,
            [
                'textarea_name' => self::FIELD_NAME,
                'textarea_rows' => 12,
                'media_buttons' => true,
                'teeny'         => false,
                'tinymce'       => true,
                'quicktags'     => true,
                'dfw'           => true,
                'editor_class'  => 'jankx-notes-editor',
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

        $note = isset($_POST[self::FIELD_NAME])
            ? wp_kses_post(wp_unslash($_POST[self::FIELD_NAME]))
            : '';

        $this->service->saveNote($postId, $note);
    }
}