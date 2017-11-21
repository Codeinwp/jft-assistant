(function($, jft){

    $(document).ready(function(e){
        initAll();
    });

    function initAll() {
        if(jft.screen === 'theme-install'){
            if(jft.jft_page){
                $('div.wp-filter').remove();
                $('h1').html(jft.tab_name);
            }else{
                $('ul.filter-links').append('<li><a href="#" data-sort="jft">' + jft.tab_name + '</a></li>');
            }
        }
    }


})(jQuery, jft);