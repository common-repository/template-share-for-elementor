(function ($) {
    "use strict";

    function genLicense(length) {
        var result = "";
        var characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        for (var i = 0; i < length; i++) {
            result += characters[Math.floor(Math.random() * characters.length)];
        }
        // result = result.match(/\d{1,4}/g).join("-");
        return CryptoJS.MD5("emsh" + new Date().getTime() + result).toString();
    }

    function initSortable() {
        // sortable
        if ( $( '.emsh-sortable-wrap' ).sortable( "instance" ) ) {

            $( '.emsh-sortable-wrap' ).sortable( "refresh" );
        }
        else {

            $('.emsh-sortable-wrap').sortable({
                axis: "y",
                items: '.emsh-single-sortable-item:not(.counter-0)'
            });
        }
    }

    $(document).ready(function () {
        // on load, change menu url to template group category
        var $el = $('.toplevel_page_emsh ul li a[href="admin.php?page=emsh-template-group-category"]');

        if ( $el.length && $el.attr('href').includes('category') ) {
            $el.attr('href', 'edit-tags.php?taxonomy=emsh-template-group-category&post_type=emsh-template-groups');
        }

        initSortable();

        // select2
        if ( $.fn.selectWoo ) {

            $('.emsh_multiple').selectWoo();
        }
    });

    // On change template type selector value
    $(document).on('change', '.template_type_selector select', function(e){
        var value = this.value;
        var $admin = $(this).closest('.emsh-admin');
        $admin.find('.form-group:not(.template_type_selector)').hide();
        if ( value === 'specific_cats' ) {
            $admin.find('.category_selector').show();
        }
        else if ( value === 'specific_templates' ) {
            $admin.find('.template_selector').show();
        }
    });

    // On click generate license key
    $(document).on('click', 'button.license-generator', function (e) {
        e.preventDefault();
        var newLicense = genLicense(16);
        $(this).parent().find('#license_key').val(newLicense);
    });

    // On click copy license key
    $(document).on('click', '.cpy-btn', function(e){
        e.preventDefault();
        var $input = $(this).parent().find('#license_key');
        var license = $input.val();
        $input[0].focus();
        $input[0].select();
        navigator.clipboard.writeText(license);
    });

    function isObject(val) {
        if (val === null) { return false;}
        return ( (typeof val === 'function') || (typeof val === 'object') );
    }

    function click_on_save_or_publish() {
        if ( $('input[type=submit][name="publish"]').length ) {
    
            $('input[type=submit][name="publish"]').click();
        }
        else {
            $('input[type=submit][name="save"]').click();
        }
    }

    $(document).on('click', '.connect-btn', function(e){
        e.preventDefault();
        var $this = $(this);
        $('.emsh-notice').hide();
        

        if ($this.attr('data-status') === '' || $this.attr('data-status') === 'notconnected' ) {
            // not connected, so try to connect.

            if ( $('#emsh_site_license_key').val() && $('#emsh_site_url').val() ) {

                $this.html($this.attr('data-connecting'));
        
                var data = {
                    action: 'emsh_connect',
                    license_key: $('#emsh_site_license_key').val(),
                    site_url: $('#emsh_site_url').val(),
                };
    
    
                $this.attr('disabled', true);
                $.post(ajaxurl, data, function(response){
                    if (response && ('success' in response) && response.success ) {
                        // connection successful
                        $this.html($this.attr('data-disconnect'));
                        $this.attr('data-status', 'connected');
    
                        $('.emsh-notice').hide();
                        $('.emsh-notice.notice-success').html(response.message).show();
    
                        $('input[name="emsh_connection_status"]').val('connected');
                        if ( 'expiry_date' in response ) {

                            $('input[name="emsh_site_expiry_date"]').val(response.expiry_date);
                        }

                        click_on_save_or_publish();
                    }
                    else {
                        // failed connection
                        $this.html($this.attr('data-connect'));
                        $this.attr('data-status', 'notconnected');
    
                        if ( isObject(response) && ('message' in response) ) {
                            $('.emsh-notice').hide();
                            $('.emsh-notice.notice-error').html(response.message).show();
                        }
    
                        $('input[name="emsh_connection_status"]').val('notconnected');
                    }

                    $this.attr('disabled', false);
                });
            }
        }
        else {
            // already connected. so try to disconnect
            if ( $('#emsh_site_license_key').val() && $('#emsh_site_url').val() ) {

                $this.html($this.attr('data-disconnecting'));
        
                var data = {
                    action: 'emsh_disconnect',
                    license_key: $('#emsh_site_license_key').val(),
                    site_url: $('#emsh_site_url').val(),
                };
    
                $this.attr('disabled', true);
        
                $.post(ajaxurl, data, function(response){
                    if (response && ('success' in response) && response.success ) {
                        // connection successful
                        $this.html($this.attr('data-connect'));
                        $this.attr('data-status', 'notconnected');
    
                        $('.emsh-notice').hide();
                        $('.emsh-notice.notice-success').html(response.message).show();
    
                        $('input[name="emsh_connection_status"]').val('notconnected');
                        $('input[name="emsh_site_expiry_date"]').val('');
                        
                        click_on_save_or_publish();
                        
                    }
                    else {
                        // failed disconnection
                        $this.html($this.attr('data-disconnect'));
                        $this.attr('data-status', 'connected');
    
                        if ( isObject(response) && ('message' in response) ) {
                            $('.emsh-notice').hide();
                            $('.emsh-notice.notice-error').html(response.message).show();
                        }
    
                        $('input[name="emsh_connection_status"]').val('connected');
                    }

                    $this.attr('disabled', false);
                });
            }
        }

        
    });

    $(document).on('click', '.server-disconnect', function(e){
        e.preventDefault();
        if ( confirm(emsh_admin.areyousure) ) {

            if ( $('#license_key').val() ) {
                $('.emsh-notice').hide();
                var $this = $(this);
                var $li = $this.parent();
                var url = $li.find('a').html();
                var data = {
                    action: 'emsh_revoke_site',
                    site_url: url,
                    license_key: $('#license_key').val()
                }
    
                $this.attr('disabled', true);
                $.post(ajaxurl, data, function(response){
                    if (response && ('success' in response) && response.success ) {
                        // revoking successful
                        click_on_save_or_publish();
                    }
                    else {
                        // failed to revoke
                        // show error notice
                        $('.emsh-notice').hide();
                        if ( isObject(response) && ('message' in response) ) {
    
                            $('.emsh-notice.notice-error').html(response.message).show();
                        }
                    }
    
                    $this.attr('disabled', false);
                });
            }
        }    
    });
    
    $(document).on('click', '.server-block.disabled', function(e){
        e.preventDefault();
    });

    $(document).on('click', '.server-block:not(.disabled)', function(e){
        e.preventDefault();
        if ( confirm(emsh_admin.areyousure) ) {

            if ( $('#license_key').val() ) {
                $('.emsh-notice').hide();
                var $this = $(this);
                var $li = $this.parent();
                var url = $li.find('a').html();
                var data = {
                    action: 'emsh_block_site',
                    site_url: url,
                    license_key: $('#license_key').val()
                }
    
                $this.attr('disabled', true);
                $.post(ajaxurl, data, function(response){
                    if (response && ('success' in response) && response.success ) {
                        // revoking successful
                        click_on_save_or_publish();
                    }
                    else {
                        // failed to revoke
                        // show error notice
                        $('.emsh-notice').hide();
                        if ( isObject(response) && ('message' in response) ) {
    
                            $('.emsh-notice.notice-error').html(response.message).show();
                        }
                    }
    
                    $this.attr('disabled', false);
                });
            }
        }
    });

    $(document).on('click', '.server-unblock', function(e){
        e.preventDefault();
        if ( confirm(emsh_admin.areyousure) ) {

            if ( $('#license_key').val() ) {
                $('.emsh-notice').hide();
                var $this = $(this);
                var $li = $this.parent();
                var url = $li.find('a').html();
                var data = {
                    action: 'emsh_unblock_site',
                    site_url: url,
                    license_key: $('#license_key').val()
                }
    
                $this.attr('disabled', true);
                $.post(ajaxurl, data, function(response){
                    if (response && ('success' in response) && response.success ) {
                        // revoking successful
                        click_on_save_or_publish();
                    }
                    else {
                        // failed to revoke
                        // show error notice
                        $('.emsh-notice').hide();
                        if ( isObject(response) && ('message' in response) ) {
    
                            $('.emsh-notice.notice-error').html(response.message).show();
                        }
                    }
    
                    $this.attr('disabled', false);
                });
            }
        }
    });

    $(document).on('click', '.addsortableitem', function(e){
        e.preventDefault();
        var $newElement = $(this).parent().find('.emsh-single-sortable-item:last').clone()
        $newElement.appendTo($('.emsh-sortable-wrap')).removeClass('counter-0').find('.url-data a').hide();
        $newElement.find('.tg-delete-item').show();
        $newElement.find('input').val('');

        initSortable();
    });

    $(document).on('change', '#emsh_template_group_templates', function(e){
        $(this).parent().find('.url-data a').hide();
        $(this).parent().find('.url-data a[data-id='+this.value+']').show();
    });

    $(document).on('click', '.tg-delete-item', function(e){
        e.preventDefault();
        if ( confirm(emsh_admin.areyousure) ) {
            $(this).closest('.emsh-single-sortable-item').remove();
        }
    });

})(jQuery);