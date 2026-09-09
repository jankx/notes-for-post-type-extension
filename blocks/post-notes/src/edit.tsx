import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl, SelectControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
    const blockProps = useBlockProps({
        className: 'wp-block-jankx-post-notes is-editor-preview',
    });

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Cấu hình Ghi chú Bài viết', 'jankx' ) }>
                    <TextControl
                        label={ __( 'Tiêu đề khối', 'jankx' ) }
                        value={ attributes.title || '' }
                        onChange={ ( value ) => setAttributes( { title: value } ) }
                        placeholder={ __( 'Ghi chú', 'jankx' ) }
                    />
                    <TextControl
                        label={ __( 'Tiền tố (Prefix)', 'jankx' ) }
                        value={ attributes.prefix || '' }
                        onChange={ ( value ) => setAttributes( { prefix: value } ) }
                        placeholder={ __( 'Lưu ý: ', 'jankx' ) }
                    />
                    <ToggleControl
                        label={ __( 'Hiển thị khi không có ghi chú', 'jankx' ) }
                        checked={ attributes.showWhenEmpty ?? false }
                        onChange={ ( value ) => setAttributes( { showWhenEmpty: value } ) }
                    />
                    { attributes.showWhenEmpty && (
                        <TextControl
                            label={ __( 'Nội dung khi trống', 'jankx' ) }
                            value={ attributes.emptyText || '' }
                            onChange={ ( value ) => setAttributes( { emptyText: value } ) }
                            placeholder={ __( 'Không có ghi chú', 'jankx' ) }
                        />
                    ) }
                    <SelectControl
                        label={ __( 'Thẻ HTML bao ngoài', 'jankx' ) }
                        value={ attributes.tagName || 'div' }
                        options={ [
                            { label: 'div', value: 'div' },
                            { label: 'section', value: 'section' },
                            { label: 'article', value: 'article' },
                            { label: 'aside', value: 'aside' },
                            { label: 'p', value: 'p' },
                        ] }
                        onChange={ ( value ) => setAttributes( { tagName: value } ) }
                    />
                </PanelBody>
            </InspectorControls>
            <div { ...blockProps }>
                <ServerSideRender
                    block="jankx/post-notes"
                    attributes={ attributes }
                />
            </div>
        </>
    );
}
