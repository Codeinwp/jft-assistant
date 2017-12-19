(function($, jft){

    $(document).ready(function(e){
        initAll();
    });

    $(window).load(function(e){
        initWindow();
    });

    function initWindow() {
        if(jft.screen === 'theme-install' && jft.jft_page){
            // make search box full size.
            $('div.wp-filter .search-form input[type=search]').css('width', '100%');
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