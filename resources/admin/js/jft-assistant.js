/* global ajaxurl */
/* global jft */
(function($, jft){

    $(document).ready(function(){
        initAll();
    });

    $(window).load(function(){
        initWindow();
    });

    function initWindow() {
        if(jft.screen === 'theme-install' && jft.jft_page){
            // make search box full size.
            $('div.wp-filter .search-form input[type=search]').css('width', '100%');
            $('div.wrap').addClass('jft-page');

            // remove the existing menu highlight and add highlight to the JFT page menu item.
            $('ul.wp-submenu li.current').removeClass('current').find('a').removeClass('current');
            $('ul.wp-submenu li').find('a').each( function() {
                if('theme-install.php?browse=jft&pg=jft' === $(this).attr('href')){
                    $(this).addClass('current').parent().addClass('current');
                }
            } );

            $.ajax({
                url     : ajaxurl,
                method  : 'post',
                data    : {
                    nonce   : jft.ajax.nonce,
                    action  : jft.ajax.action,
                    _action : 'in_page'
                }
            });

            $(window).unload(function(){
                $.ajax({
                    url     : ajaxurl,
                    method  : 'post',
                    async   : false,
                    data    : {
                        nonce   : jft.ajax.nonce,
                        action  : jft.ajax.action,
                        _action : 'out_page'
                    }
                });
            });
        }
    }

    function initAll() {
        if(jft.screen === 'theme-install'){
            if(jft.jft_page){
                $('div.wp-filter .filter-count').remove();
                $('div.wp-filter .filter-links').remove();
                $('div.wp-filter button').remove();
                $('div.wp-filter .search-form').css('width', '100%');
                $('h1').html(jft.tab_name);
            }else{
                $('ul.filter-links').append('<li><a href="#" data-sort="jft">' + jft.tab_name + '</a></li>');
            }
        }
    }


})(jQuery, jft);