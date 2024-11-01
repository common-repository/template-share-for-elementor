<?php
/**
 * Template Library Header
 */
?>
<div id="emshelementor-template-modal-header-title">
    <span class="emshelementor-template-modal-header-title__logo"><img src="<?php echo EMSHF_ASSETS_URL . 'images/template-share.png'; ?>" /></span>
    <?php echo esc_html__( 'Template Share Library', 'template-share-for-elementor' ); ?>
</div>
<div id="emshelementor-template-modal-header-tabs"></div>
<div id="emshelementor-template-modal-header-actions">
    <div class="emsh-source" style="display: none;">
        <span class="emsh-source-url"></span>
        <span class="emsh-source-change"><?php echo esc_html__( 'Change', 'template-share-for-elementor' ); ?></span>
    </div>
    <div id="emshelementor-template-modal-header-close-modal" class="elementor-template-library-header-item"
        title="<?php echo esc_html__( 'Close', 'template-share-for-elementor' ); ?>">
        <i class="eicon-close" title="Close"></i>
    </div>
</div>