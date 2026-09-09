<?php

namespace Jankx\Extensions\NotesForPostType\Services;

/**
 * Notes Service
 *
 * Manages option reading, post type resolution, and note meta CRUD
 * for the Notes for Post Type extension.
 */
class NotesService
{
    const META_KEY = '_jankx_notes';
    const OPTION_ENABLED = 'notes_for_post_type_enabled';
    const OPTION_POST_TYPES = 'notes_for_post_type_post_types';

    /**
     * Cached allowed post types
     *
     * @var array|null
     */
    protected $allowedPostTypes = null;

    /**
     * Get raw option value from jankx_options
     *
     * @param string $key Option key
     * @param mixed $default Default value
     * @return mixed
     */
    public function getOption(string $key, $default = null)
    {
        $themeMod = get_theme_mod($key);
        if ($themeMod !== false && !is_null($themeMod)) {
            return $themeMod;
        }

        $options = get_option('jankx_options', []);
        if (is_array($options) && array_key_exists($key, $options)) {
            return $options[$key];
        }
        return $default;
    }

    /**
     * Check if the extension is globally enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        $enabled = $this->getOption(self::OPTION_ENABLED, 1);
        return (bool) $enabled;
    }

    /**
     * Get all public post types keyed by name
     *
     * @return array
     */
    public function getPublicPostTypes(): array
    {
        $postTypes = get_post_types(['public' => true], 'objects');
        $list = [];
        foreach ($postTypes as $postType) {
            $label = $postType->labels->singular_name ?? $postType->label ?? $postType->name;
            $list[$postType->name] = sprintf('%s (%s)', $label, $postType->name);
        }
        return $list;
    }

    /**
     * Get post types that support notes
     *
     * Priority: `jankx/notes-for-post-type/post-types` filter
     * > Theme Options checkbox > empty (none)
     *
     * @return array List of post type names
     */
    public function getAllowedPostTypes(): array
    {
        if ($this->allowedPostTypes !== null) {
            return $this->allowedPostTypes;
        }

        if (!$this->isEnabled()) {
            $this->allowedPostTypes = [];
            return $this->allowedPostTypes;
        }

        $saved = $this->getOption(self::OPTION_POST_TYPES, null);
        if ($saved === null) {
            $saved = array_keys($this->getPublicPostTypes());
        }
        if (is_string($saved)) {
            $saved = $saved !== '' ? [$saved] : [];
        }
        if (!is_array($saved)) {
            $saved = [];
        }

        $public = array_keys($this->getPublicPostTypes());
        $allowed = array_values(array_intersect($saved, $public));

        $this->allowedPostTypes = apply_filters(
            'jankx/notes-for-post-type/post-types',
            $allowed
        );

        return $this->allowedPostTypes;
    }

    /**
     * Check if a post type supports notes
     *
     * @param string $postType Post type name
     * @return bool
     */
    public function isPostTypeSupported(string $postType): bool
    {
        return in_array($postType, $this->getAllowedPostTypes(), true);
    }

    /**
     * Get note for a post
     *
     * @param int $postId Post ID
     * @return string Note content (empty string if none)
     */
    public function getNote(int $postId): string
    {
        $note = get_post_meta($postId, self::META_KEY, true);
        return is_string($note) ? $note : '';
    }

    /**
     * Save note for a post
     *
     * @param int $postId Post ID
     * @param string $note Note content
     * @return bool
     */
    public function saveNote(int $postId, string $note): bool
    {
        if ($note === '') {
            return (bool) delete_post_meta($postId, self::META_KEY);
        }
        return (bool) update_post_meta($postId, self::META_KEY, $note);
    }

    /**
     * Register post meta for allowed post types
     *
     * @return void
     */
    public function registerMeta(): void
    {
        foreach ($this->getAllowedPostTypes() as $postType) {
            register_post_meta($postType, self::META_KEY, [
                'type' => 'string',
                'single' => true,
                'sanitize_callback' => 'sanitize_textarea_field',
                'show_in_rest' => true,
            ]);
        }
    }
}
