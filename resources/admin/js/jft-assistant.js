(function($, jft){

    $(document).ready(function(e){
        initAll();
    });

    function initAll() {
        if(jft.screen === 'theme-install'){
            $('ul.filter-links').append('<li><a href="#" data-sort="jft">' + jft.theme_tab + '</a></li>');
        }
    }


})(jQuery, jft);