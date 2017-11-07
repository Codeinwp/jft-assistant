(function($, jft){

    $(document).ready(function(e){
        initAll();
    });

    function initAll() {
        $('#jft-theme').on('keyup', function(e){
            showSpinner($(this));
            $.ajax({
                url     : ajaxurl,
                method  : 'post',
                data    : {
                    'action'    : jft.ajax['action'],
                    'nonce'     : jft.ajax['nonce'],
                    '_action'   : 'search',
                    'name'      : $(this).val()
                },
                success : function(data){
                    if(data.success){
                        $.each(data.data.data.ids, function(index, id){
                            $('<div class="jft-theme" data-jft-id="' + id + '"><img src="' + jft.meta['base_url'] + id + '.png"></div>').appendTo($('#jft-search-results'));
                        });
                    }
                    $('.jft-theme').on('click', function(e){
                        download($(this), $(this).attr('data-jft-id'));
                    });
                    hideSpinner();
                }
            });
        });

        if(jft.screen === 'theme-install'){
            $('ul.filter-links').append('<li><a href="#" data-sort="jft">' + jft.theme_tab + '</a></li>');
        }
    }

    function download(element, id) {
        showSpinner(element);
        $.ajax({
            url     : ajaxurl,
            method  : 'post',
            data    : {
                'action'    : jft.ajax['action'],
                'nonce'     : jft.ajax['nonce'],
                '_action'   : 'download',
                'id'        : id
            },
            success : function(data){
                $('<div class="jft-installation">' + data + '</div>').appendTo(element);
                hideSpinner();
            }
        });
    }

    function showSpinner(element) {
        element.parent().append('<div class="jft-spinner"></div>');
        $('.jft-spinner').show();
    }

    function hideSpinner() {
        $('.jft-spinner').remove().hide();
    }


})(jQuery, jft);