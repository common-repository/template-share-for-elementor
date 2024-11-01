<?php
/**
 * Template Item
 */
?>

<div class="elementor-template-library-template-body">
	<div class="elementor-template-library-template-screenshot">
		<div class="elementor-template-library-template-title">
            <span class="">{{ title }}</span>
        </div>
        <div class="emshelementor-template--thumb">
            <div class="emshelementor-template--label">
                <# if ( template_type === 'page' ) { #>
                <span class="emshelementor-template--tag emshelementor-template--pro"><?php echo esc_html__( 'Page', 'template-share-for-elementor' ); ?></span><span class="emshelementor-template--sep"></span>
                <# } #>
                <# if ( template_type === 'section' ) { #>
                <span class="emshelementor-template--tag emshelementor-template--pro"><?php echo esc_html__( 'Section', 'template-share-for-elementor' ); ?></span><span class="emshelementor-template--sep"></span>
                <# } #>
            </div>
            <img src="{{ thumbnail }}" alt="{{ title }}">
            <# if ( preview_url ) { #>
                <a href="{{preview_url}}" target="_blank">
                    <div class="emsh-preview-layer"><?php echo esc_html__( 'Live Preview', 'template-share-for-elementor' ); ?></div>
                </a>
            <# } #>
            <# if ( type === 'emsh_template_groups' && template_count_text ) { #>
                <div data-name="{{title}}" class="emsh-preview-layer template-group-layer" data-id="{{template_id}}">{{template_count_text}}</div>
            <# } #>
        </div>
	</div>
</div>
<# if ( type !== 'emsh_template_groups' ) { #>
    <div class="elementor-template-library-template-controls">
        <button data-required-plugins="{{required_plugins}}" class="elementor-template-library-template-action emshelementor-template-insert elementor-button elementor-button-success">
            <i class="eicon-file-download"></i>
            <span class="elementor-button-title"><?php echo esc_html__( 'Insert', 'template-share-for-elementor' ); ?></span>
        </button>
    </div>
<# } #>
