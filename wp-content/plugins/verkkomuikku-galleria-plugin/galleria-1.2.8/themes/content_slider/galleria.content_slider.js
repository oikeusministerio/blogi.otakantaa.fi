/**
 * @preserve Galleria Content Slider Theme 2012-08-21
 * http://www.verkkomuikku.fi
 *
 * Copyright (c) 2012, Verkkomuikku
 * Licensed under the MIT license.
 */

/*global jQuery, Galleria */

//Galleria.requires(1.28, 'This version of Image Slider theme requires Galleria 1.2.8 or later');
(function($) {

Galleria.addTheme({
    name: 'content_slider',
    author: 'Verkkomuikku',
    css: 'galleria.content_slider.css',
    defaults: {
        transition: "slide",
        transitionSpeed: 1000,
        autoplay: 7000,
        thumbCrop: false,
        imageCrop: false,
        carousel: false,
        imagePan: true,
        clicknext: false,
        thumbnails: false,
        showInfo: false,
        showCounter: false,
        // set this to false if you want to show the caption all the time:
        _toggleInfo: false
    },
    init: function(options) {

        // add some elements
        this.addElement('info-link','info-close');
        this.append({
            'info' : ['info-link','info-close']
        });

        // cache some stuff
        var info = this.$('info-link,info-close,info-text'),
            touch = Galleria.TOUCH,
            click = touch ? 'touchstart' : 'click';

        // show loader & counter with opacity
        this.$('loader,counter').show().css('opacity', 0.4);

        // some stuff for non-touch browsers
        /*
        if (! touch ) {
            this.addIdleState( this.get('image-nav-left'), { left:-36 });
            this.addIdleState( this.get('image-nav-right'), { right:-36 });
            this.addIdleState( this.get('counter'), { opacity:0 });
        }
        */

        // toggle info
        if ( options._toggleInfo === true ) {
            info.bind( click, function() {
                info.toggle();
            });
        } else {
            info.show();
            this.$('info-link, info-close').hide();
        }

        // bind some stuff
        this.bind('thumbnail', function(e) {

            if (! touch ) {
                // fade thumbnails
                $(e.thumbTarget).css('opacity', 0.6).parent().hover(function() {
                    $(this).not('.active').children().stop().fadeTo(100, 1);
                }, function() {
                    $(this).not('.active').children().stop().fadeTo(400, 0.6);
                });

                if ( e.index === this.getIndex() ) {
                    $(e.thumbTarget).css('opacity',1);
                }
            } else {
                $(e.thumbTarget).css('opacity', this.getIndex() ? 1 : 0.6);
            }
        });

        this.bind('loadstart', function(e) {
            if (!e.cached) {
                this.$('loader').show().fadeTo(200, 0.4);
            }

            this.$('info').toggle( this.hasInfo() );

            $(e.thumbTarget).css('opacity',1).parent().siblings().children().css('opacity', 0.6);
        });

        this.bind('loadfinish', function(e) {
            this.$('loader').fadeOut(200);
        });
    }
});

}(jQuery));