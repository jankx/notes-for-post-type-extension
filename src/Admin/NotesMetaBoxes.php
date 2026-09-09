<?php

namespace Jankx\Extensions\NotesForPostType\Admin;

use Jankx\Extensions\NotesForPostType\Services\NotesService;

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
     * Render the notes metabox.
     *
     * @param \WP_Post $post Current post object.
     */
    public function renderMetaBox(\WP_Post $post): void
    {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        $note = $this->service->getNote($post->ID);
        ?>
        <p>
            <label for="jankx_notes"><?php esc_html_e('Ghi chú nội bộ (chỉ hiển thị trong admin):', 'jankx'); ?></label>
        </p>
        <textarea
            id="jankx_notes"
            name="jankx_notes"
            rows="6"
            class="large-text"
            placeholder="<?php esc_attr_e('Nhập ghi chú cho bài viết này...', 'jankx'); ?>"
        ><?php echo esc_textarea($note); ?></textarea>
        <?php
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

        $note = isset($_POST['jankx_notes']) ? sanitize_textarea_field(wp_unslash($_POST['jankx_notes'])) : '';
        $this->service->saveNote($postId, $note);
    }
}
