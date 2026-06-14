/**
 * LoopMosaic – Carousel (Swiper) initializer
 * Runs after Swiper is loaded. Safe to call multiple times (idempotent per wrap).
 */
(function ($) {
    'use strict';

    var INIT_ATTR = 'data-lm-swiper-init';

    function initCarousels() {
        $('.loopmosaic-carousel-wrap').each(function () {
            var $wrap = $(this);

            // Skip already-initialised instances
            if ($wrap.attr(INIT_ATTR)) return;
            $wrap.attr(INIT_ATTR, '1');

            var rawSettings = {};
            try {
                rawSettings = JSON.parse($wrap.attr('data-carousel') || '{}');
            } catch (e) { /* bad JSON – use defaults */ }

            var swiperEl = $wrap.find('.loopmosaic-swiper')[0];
            if (!swiperEl) return;

            var prevBtn = $wrap.find('.lm-nav-prev')[0];
            var nextBtn = $wrap.find('.lm-nav-next')[0];

            var config = {
                loop:           rawSettings.loop !== false,
                speed:          parseInt(rawSettings.speed, 10)  || 600,
                grabCursor:     true,
                watchSlidesProgress: true,

                navigation: {
                    prevEl: prevBtn,
                    nextEl: nextBtn,
                },

                pagination: rawSettings.dots ? {
                    el: $wrap.find('.swiper-pagination')[0] || null,
                    clickable: true,
                } : false,

                a11y: {
                    prevSlideMessage: 'Previous slide',
                    nextSlideMessage: 'Next slide',
                },
            };

            if (rawSettings.autoplay) {
                config.autoplay = {
                    delay:                rawSettings.autoplaySpeed || 4000,
                    disableOnInteraction: false,
                    pauseOnMouseEnter:    true,
                };
            }

            new Swiper(swiperEl, config);
        });
    }

    // On DOM ready
    $(function () {
        if (typeof Swiper !== 'undefined') {
            initCarousels();
        }
    });

    // Re-init after Elementor frontend re-renders (editor / AJAX)
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/loopmosaic-grid.default', function () {
            if (typeof Swiper !== 'undefined') {
                initCarousels();
            }
        });
    });

})(jQuery);
