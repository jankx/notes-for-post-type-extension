<?php
namespace Jankx\Extensions\NotesForPostType\Blocks;

use Jankx\Extensions\NotesForPostType\Block;
use Jankx\Extensions\NotesForPostType\Services\NotesService;

class PostNotesBlock extends Block
{
    protected $blockId = 'jankx/post-notes';

    public function render($attributes, $content = '', $block = null)
    {
        $isEditor = (defined('REST_REQUEST') && REST_REQUEST && !empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/block-renderer/') !== false) || (is_admin() && !wp_doing_ajax());

        $postId = $this->resolvePostId($block);
        $service = new NotesService();

        $title = isset($attributes['title']) ? sanitize_text_field($attributes['title']) : '';
        $prefix = isset($attributes['prefix']) ? sanitize_text_field($attributes['prefix']) : '';
        $showWhenEmpty = isset($attributes['showWhenEmpty']) ? (bool) $attributes['showWhenEmpty'] : false;
        $emptyText = isset($attributes['emptyText']) ? sanitize_text_field($attributes['emptyText']) : '';
        $tagName = isset($attributes['tagName']) ? sanitize_key($attributes['tagName']) : 'div';

        $allowedTags = ['div', 'section', 'article', 'aside', 'p', 'span'];
        if (!in_array($tagName, $allowedTags, true)) {
            $tagName = 'div';
        }

        $note = '';
        if ($postId) {
            $note = $service->getNote($postId);
        }

        if (empty($note)) {
            if ($isEditor) {
                $note = __('Ghi chú mẫu hiển thị trên giao diện (nội dung ghi chú bài viết).', 'jankx');
            } elseif (!$showWhenEmpty) {
                return '';
            } else {
                $note = $emptyText;
            }
        }

        if (empty($note)) {
            return '';
        }

        $wrapperAttrs = get_block_wrapper_attributes([
            'class' => 'wp-block-jankx-post-notes jankx-post-notes' . ($isEditor ? ' is-editor-preview' : ''),
        ]);

        ob_start();
        ?>
        <<?php echo esc_attr($tagName); ?> <?php echo $wrapperAttrs; ?>>
            <?php if (!empty($title)): ?>
                <h5 class="jankx-post-notes__title"><?php echo esc_html($title); ?></h5>
            <?php endif; ?>
            <div class="jankx-post-notes__content">
                <?php if (!empty($prefix)): ?>
                    <span class="jankx-post-notes__prefix"><?php echo esc_html($prefix); ?></span>
                <?php endif; ?>
                <div class="jankx-post-notes__text"><?php echo wp_kses_post(nl2br(esc_html($note))); ?></div>
            </div>
        </<?php echo esc_attr($tagName); ?>>
        <?php
        return ob_get_clean();
    }

    protected function resolvePostId($block): int
    {
        if ($block instanceof \WP_Block && !empty($block->context['postId'])) {
            return (int) $block->context['postId'];
        }

        $postId = get_the_ID();
        if ($postId) {
            return (int) $postId;
        }

        global $post;
        if ($post && isset($post->ID)) {
            return (int) $post->ID;
        }

        return 0;
    }
}
