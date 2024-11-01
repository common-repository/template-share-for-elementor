<?php
/**
 * Templates Search View
 */

$q_args = array(
    'post_type' => 'emsh-sites',
    'post_status' => 'publish',
    'posts_per_page' => -1,
);
$tempquery = new WP_Query($q_args);

?>
<div class="emsh-site-selector">
    <h1><?php echo esc_html__( 'Choose Site', 'template-share-for-elementor' ); ?></h1>
    <div class="site-selector-wrapper">
        <select name="" id="">
        <?php 
        if ( isset( $tempquery->posts ) && is_array($tempquery->posts) ) {
            foreach ($tempquery->posts as $post_obj) {
                $license = get_post_meta( $post_obj->ID, 'emsh_site_license_key', true );
                $website = get_post_meta( $post_obj->ID, 'emsh_site_url', true );
                ?> 
                <option data-licensekey="<?php echo esc_attr( $license ); ?>" data-siteurl="<?php echo esc_attr( $website ); ?>" value="<?php echo esc_attr( $post_obj->ID ); ?>"><?php echo esc_html( $post_obj->post_title ); ?></option>
                <?php
            }
        }
        ?>
        </select>
        <button class="elementor-button elementor-button-success emsh-browse-btn">
            <?php echo esc_html__( 'Browse', 'template-share-for-elementor' ); ?>
        </button>
    </div>
</div>