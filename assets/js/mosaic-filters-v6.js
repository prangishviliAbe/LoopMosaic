/**
 * LoopMosaic - JetSmartFilters Integration V6
 * 
 * @package LoopMosaic
 * @author Abe Prangishvili
 * @version 1.0.5
 */

(function ($) {
    'use strict';

    // LoopMosaic Filter Handler
    const LoopMosaicFilters = {

        /**
         * Initialize
         */
        init: function () {
            console.log('LoopMosaic V6: Filters Init Started');

            this.grids = {};
            this.bindEvents();
            this.initJetSmartFilters();
            this.storeGridSettings();
            this.initModalHandler();
            this.initInfiniteScroll();
            this.disableNativeLinks();

            console.log('LoopMosaic V6: Filters Init Complete');
        },

        /**
         * Disable native links for popups to prevent navigation
         */
        disableNativeLinks: function () {
            $('.loopmosaic-modal-trigger').each(function () {
                var $link = $(this);
                if ($link.attr('href') && $link.attr('href') !== '#' && $link.attr('href').indexOf('javascript') === -1) {
                    $link.attr('data-href', $link.attr('href'));
                    $link.attr('href', 'javascript:void(0);');
                }
            });
        },

        /**
         * Initialize Built-in Modal handler
         */
        initModalHandler: function () {
            const self = this;

            if ($('#loopmosaic-modal').length === 0) {
                $('body').append(`
                    <div id="loopmosaic-modal" class="loopmosaic-modal-overlay">
                        <div class="loopmosaic-modal-loader">
                            <div class="loopmosaic-modal-spinner"></div>
                        </div>
                        <div class="loopmosaic-modal-container">
                            <div class="loopmosaic-modal-close">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                            <div class="loopmosaic-modal-content"></div>
                        </div>
                    </div>
                `);
            }

            const $modal = $('#loopmosaic-modal');
            const $container = $modal.find('.loopmosaic-modal-container');
            const $content = $modal.find('.loopmosaic-modal-content');

            $modal.find('.loopmosaic-modal-close').on('click', function () {
                self.closeModal();
            });

            $modal.on('click', function (e) {
                if ($(e.target).hasClass('loopmosaic-modal-overlay')) {
                    self.closeModal();
                }
            });

            $(document).on('keydown', function (e) {
                if (e.key === 'Escape' && $modal.hasClass('is-active')) {
                    self.closeModal();
                }
            });

            $(document).on('click', '.loopmosaic-modal-trigger', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const $target = $(this);
                const postId = $target.data('post-id');
                const templateId = $target.data('modal-template-id');
                const autoTemplate = $target.data('auto-template');

                if (!postId) return;

                $modal.addClass('is-active is-loading');
                $container.css({ opacity: 0, transform: 'translateY(20px)' });
                $content.empty();
                $('html, body').css('overflow', 'hidden');

                $.ajax({
                    url: loopMosaicConfig.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'loopmosaic_get_modal_content',
                        nonce: loopMosaicConfig.nonce,
                        post_id: postId,
                        template_id: templateId,
                        auto_template: autoTemplate
                    },
                    success: function (response) {
                        $modal.removeClass('is-loading');

                        if (response.success && response.data.content) {
                            $content.html(response.data.content);

                            if (window.elementorFrontend) {
                                window.elementorFrontend.init();
                                $content.find('[data-element_type]').each(function () {
                                    var $element = $(this);
                                    var elementType = $element.data('element_type');
                                    if ('widget' === elementType) {
                                        elementType = $element.data('widget_type');
                                        window.elementorFrontend.hooks.doAction('frontend/element_ready/' + elementType, $element);
                                    }
                                });
                                setTimeout(function () { $(window).trigger('resize'); }, 200);
                            }

                            setTimeout(function () {
                                $container.css({ opacity: 1, transform: 'translateY(0)' });
                            }, 50);
                        } else {
                            $content.html('<div class="loopmosaic-modal-body"><p>Error loading content.</p></div>');
                            $container.css({ opacity: 1, transform: 'translateY(0)' });
                        }
                    },
                    error: function () {
                        $modal.removeClass('is-loading');
                        $content.html('<div class="loopmosaic-modal-body"><p>Connection error.</p></div>');
                        $container.css({ opacity: 1, transform: 'translateY(0)' });
                    }
                });
            });
        },

        closeModal: function () {
            const $modal = $('#loopmosaic-modal');
            $modal.removeClass('is-active');
            setTimeout(function () {
                $modal.removeClass('is-loading');
                $modal.find('.loopmosaic-modal-content').empty();
            }, 300);
            $('html, body').css('overflow', '');
        },

        storeGridSettings: function () {
            const self = this;
            $('.loopmosaic-grid[data-provider="loop-mosaic"]').each(function () {
                const $grid = $(this);
                const queryId = $grid.data('query-id') || 'default';
                self.grids[queryId] = {
                    $grid: $grid,
                    settings: $grid.data('settings') || {}
                };
            });
        },

        bindEvents: function () {
            const self = this;
            $(document).on('jet-smart-filters/inited', this.onFiltersInit.bind(this));
            $(document).on('jet-filter-content-rendered', this.onContentRendered.bind(this));

            $(document).on('jet-smart-filters/before-filter', function (event, $provider, filterGroup, data) {
                if (data.provider === 'loop-mosaic') {
                    self.beforeFilter(data);
                }
            });

            $(document).on('jet-smart-filters/ajax-content-rendered', function (event, data) {
                if (data.provider === 'loop-mosaic') {
                    self.afterFilter(data);
                }
            });

            $(document).on('loopmosaic:filter', this.handleFilter.bind(this));

            $(document).on('jet-smart-filters/pagination-applied', function (event, data) {
                if (data.provider === 'loop-mosaic') {
                    self.handlePagination(data);
                }
            });
        },

        /**
         * Initialize JetSmartFilters integration - V6 DEEP INSPECTION
         */
        initJetSmartFilters: function () {
            const self = this;

            // V6: Log exact structure of BOTH variables
            console.error('LoopMosaic V6: DIAGNOSTIC - JetSmartFilterSettings (Singular):',
                window.JetSmartFilterSettings ? JSON.stringify(Object.keys(window.JetSmartFilterSettings)) : 'UNDEFINED');
            console.error('LoopMosaic V6: DIAGNOSTIC - JetSmartFiltersSettings (Plural):',
                window.JetSmartFiltersSettings ? JSON.stringify(Object.keys(window.JetSmartFiltersSettings)) : 'UNDEFINED');

            // V6: If singular exists but has no providers, log its full content (limited)
            if (window.JetSmartFilterSettings && !window.JetSmartFilterSettings.providers) {
                try {
                    // Attempt to stringify - this might be too large, so we truncate
                    const stringified = JSON.stringify(window.JetSmartFilterSettings);
                    console.error('LoopMosaic V6: JetSmartFilterSettings FULL (Truncated):', stringified.substring(0, 500));
                } catch (e) {
                    console.error('LoopMosaic V6: JetSmartFilterSettings could not be stringified. Logging keys:', Object.keys(window.JetSmartFilterSettings));
                }
            }

            // V6: Also check the JetSmartFilters object itself for providers
            if (window.JetSmartFilters) {
                console.error('LoopMosaic V6: DIAGNOSTIC - JetSmartFilters Object Keys:', JSON.stringify(Object.keys(window.JetSmartFilters)));
                if (window.JetSmartFilters.filtersUI) {
                    console.error('LoopMosaic V6: DIAGNOSTIC - JetSmartFilters.filtersUI Keys:', JSON.stringify(Object.keys(window.JetSmartFilters.filtersUI)));
                }
            }

            // The fix: Try to get providers from multiple possible locations
            const getSettings = function () {
                // Priority 1: Singular (most specific)
                if (window.JetSmartFilterSettings && window.JetSmartFilterSettings.providers) {
                    return window.JetSmartFilterSettings;
                }
                // Priority 2: Plural
                if (window.JetSmartFiltersSettings && window.JetSmartFiltersSettings.providers) {
                    return window.JetSmartFiltersSettings;
                }
                // Priority 3: Initialize providers on settings object if it exists but providers doesn't
                if (window.JetSmartFilterSettings && !window.JetSmartFilterSettings.providers) {
                    console.log('LoopMosaic V6: Creating providers object on JetSmartFilterSettings');
                    window.JetSmartFilterSettings.providers = {};
                    return window.JetSmartFilterSettings;
                }
                if (window.JetSmartFiltersSettings && !window.JetSmartFiltersSettings.providers) {
                    console.log('LoopMosaic V6: Creating providers object on JetSmartFiltersSettings');
                    window.JetSmartFiltersSettings.providers = {};
                    return window.JetSmartFiltersSettings;
                }
                return null;
            };

            let attempts = 0;
            const maxAttempts = 30;

            const registerProvider = function () {
                const settings = getSettings();

                if (settings) {
                    console.log('LoopMosaic V6: JSF Settings Found. Registering Provider.', { object: settings });

                    settings.providers['loop-mosaic'] = {
                        name: 'loop-mosaic',
                        selector: '.loopmosaic-grid[data-provider="loop-mosaic"]',
                        idPrefix: 'loopmosaic_',
                        isAjax: true,
                        ajaxAction: 'loopmosaic_jsf_filter',
                        apply: function (queryId, filters, pagination) {
                            console.log('LoopMosaic V6: Custom Apply Called for ' + queryId);
                            self.applyFiltersAjax(queryId, filters, pagination);
                        },
                        reset: function (queryId) {
                            self.resetFilters(queryId);
                        }
                    };

                    $('.loopmosaic-grid[data-provider="loop-mosaic"]').each(function () {
                        const $grid = $(this);
                        const queryId = $grid.data('query-id') || 'default';
                        $grid.attr('data-jet-filter-visible', 'true');
                        $grid.attr('data-jet-filter', queryId);
                        $grid.addClass('jet-filter-provider');
                    });

                    return true;
                }
                return false;
            };

            if (!registerProvider()) {
                console.log('LoopMosaic V6: JSF Settings NOT found. Starting Polling...');
                const interval = setInterval(function () {
                    attempts++;
                    if (registerProvider()) {
                        clearInterval(interval);
                        console.log('LoopMosaic V6: Provider Registered via Polling.');
                    } else if (attempts >= maxAttempts) {
                        clearInterval(interval);
                        console.error('LoopMosaic V6: JSF Settings GAVE UP after 3s.');
                    }
                }, 100);
            }
        },

        onFiltersInit: function (event, filterGroup) {
            $('.loopmosaic-grid[data-provider="loop-mosaic"]').each(function () {
                $(this).addClass('jsf-initialized');
            });
            this.disableNativeLinks();
        },

        beforeFilter: function (data) {
            const queryId = data.queryId || 'default';
            const $grid = this.getGrid(queryId);
            if ($grid.length) {
                $grid.addClass('jet-filters-loading');
            }
        },

        afterFilter: function (data) {
            const queryId = data.queryId || 'default';
            const $grid = this.getGrid(queryId);
            if ($grid.length) {
                $grid.removeClass('jet-filters-loading');
                this.animateItems($grid);
            }
        },

        onContentRendered: function (event, provider, queryId, response) {
            if (provider !== 'loop-mosaic') return;
            const $grid = this.getGrid(queryId);
            if (!$grid.length) return;
            $grid.removeClass('jet-filters-loading');
            if (response && response.content) {
                $grid.html(response.content);
            }
            this.animateItems($grid);
            this.disableNativeLinks();
        },

        applyFiltersAjax: function (queryId, filters, pagination) {
            const self = this;
            const $grid = this.getGrid(queryId);
            if (!$grid.length) return;

            $grid.addClass('jet-filters-loading');
            const settings = this.grids[queryId] ? this.grids[queryId].settings : {};
            const page = pagination ? pagination.page : 1;

            let ajaxUrl = loopMosaicConfig.ajaxUrl;
            let nonce = loopMosaicConfig.nonce;
            if (typeof loopMosaicJSF !== 'undefined') {
                if (loopMosaicJSF.ajaxUrl) ajaxUrl = loopMosaicJSF.ajaxUrl;
                if (loopMosaicJSF.nonce) nonce = loopMosaicJSF.nonce;
            }

            console.log('LoopMosaic V6: Apply Filter', { queryId, filters, page, ajaxUrl });

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'loopmosaic_jsf_filter',
                    nonce: nonce,
                    query_id: queryId,
                    page: page,
                    settings: JSON.stringify(settings),
                    filters: JSON.stringify(filters)
                },
                success: function (response) {
                    console.log('LoopMosaic V6: Filter Success', response);
                    if (response.success && response.data) {
                        if (response.data.content) {
                            $grid.html(response.data.content);
                        }
                        $(document).trigger('jet-filter-content-rendered', ['loop-mosaic', queryId, response.data]);
                        if (response.data.max_pages) {
                            $grid.data('max-pages', response.data.max_pages);
                            $grid.data('found-posts', response.data.found_posts);
                        }
                    } else {
                        console.error('LoopMosaic V6: Filter Response Error', response);
                    }
                    $grid.removeClass('jet-filters-loading');
                    self.animateItems($grid);
                    self.disableNativeLinks();
                },
                error: function (xhr, status, error) {
                    $grid.removeClass('jet-filters-loading');
                    console.error('LoopMosaic V6: Filter Request Failed', { status, error, response: xhr.responseText });
                }
            });
        },

        resetFilters: function (queryId) {
            this.applyFiltersAjax(queryId, {}, null);
        },

        handlePagination: function (data) {
            const queryId = data.queryId || 'default';
            const page = data.page || 1;
            this.applyFiltersAjax(queryId, data.filters || {}, { page: page });
        },

        handleFilter: function (event, data) {
            if (!data || !data.queryId) return;
            this.applyFiltersAjax(data.queryId, data.filters || {}, data.pagination || null);
        },

        getGrid: function (queryId) {
            if (this.grids[queryId] && this.grids[queryId].$grid) {
                return this.grids[queryId].$grid;
            }
            return $('.loopmosaic-grid[data-query-id="' + queryId + '"]');
        },

        animateItems: function ($grid) {
            const $items = $grid.find('.loopmosaic-item');
            $items.each(function (index) {
                const $item = $(this);
                $item.css({ 'opacity': '0', 'transform': 'translateY(20px)' });
                setTimeout(function () {
                    $item.css({
                        'transition': 'opacity 0.4s ease, transform 0.4s ease',
                        'opacity': '1',
                        'transform': 'translateY(0)'
                    });
                }, index * 50);
            });
        },

        refreshLayout: function (queryId) {
            const $grid = queryId ? this.getGrid(queryId) : $('.loopmosaic-grid');
            $grid.each(function () { this.offsetHeight; });
        },

        initInfiniteScroll: function () {
            const self = this;
            const $grids = $('.loopmosaic-grid[data-infinite-scroll="true"]');
            if (!$grids.length) return;
            let ticking = false;
            $(window).on('scroll', function () {
                if (!ticking) {
                    window.requestAnimationFrame(function () {
                        self.handleScroll($grids);
                        ticking = false;
                    });
                    ticking = true;
                }
            });
            self.handleScroll($grids);
        },

        handleScroll: function ($grids) {
            const self = this;
            const windowHeight = $(window).height();
            const scrollTop = $(window).scrollTop();
            const buffer = 300;
            $grids.each(function () {
                const $grid = $(this);
                if ($grid.hasClass('is-loading-more') || $grid.hasClass('is-finished')) return;
                const gridOffset = $grid.offset().top;
                const gridHeight = $grid.outerHeight();
                const gridBottom = gridOffset + gridHeight;
                const scrollBottom = scrollTop + windowHeight;
                if (scrollBottom > gridBottom - buffer) {
                    self.loadMorePosts($grid);
                }
            });
        },

        loadMorePosts: function ($grid) {
            const self = this;
            const maxPages = parseInt($grid.data('max-pages')) || 1;
            const currentPage = parseInt($grid.data('paged')) || 1;
            const nextPage = currentPage + 1;
            if (nextPage > maxPages) {
                $grid.addClass('is-finished');
                return;
            }
            let $loader = $grid.next('.loopmosaic-infinite-loader');
            if (!$loader.length) {
                $loader = $('<div class="loopmosaic-infinite-loader"><div class="loopmosaic-spinner"></div></div>');
                $grid.after($loader);
            }
            $grid.addClass('is-loading-more');
            $loader.addClass('is-active');
            const settings = $grid.data('settings') || {};
            const nonce = (typeof loopMosaicJSF !== 'undefined' && loopMosaicJSF.nonce) ? loopMosaicJSF.nonce : loopMosaicConfig.nonce;
            $.ajax({
                url: loopMosaicConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'loopmosaic_load_more',
                    nonce: nonce,
                    settings: JSON.stringify(settings),
                    paged: nextPage
                },
                success: function (response) {
                    if (response.success && response.data.content) {
                        const $content = $(response.data.content);
                        $grid.append($content);
                        $grid.data('paged', nextPage);
                        if (nextPage >= maxPages) $grid.addClass('is-finished');
                        self.animateItems($grid);
                        $(window).trigger('resize');
                    } else {
                        $grid.addClass('is-finished');
                    }
                    $grid.removeClass('is-loading-more');
                    $loader.removeClass('is-active');
                },
                error: function () {
                    $grid.removeClass('is-loading-more');
                    $loader.removeClass('is-active');
                }
            });
        }
    };

    $(document).ready(function () {
        LoopMosaicFilters.init();
    });

    $(window).on('elementor/frontend/init', function () {
        if (window.elementorFrontend && elementorFrontend.hooks) {
            elementorFrontend.hooks.addAction('frontend/element_ready/loopmosaic-grid.default', function ($scope) {
                console.log('LoopMosaic V6: Elementor Widget Ready Hook');
                LoopMosaicFilters.storeGridSettings();
                LoopMosaicFilters.initJetSmartFilters();
            });
        }
    });

    $(document).ajaxComplete(function (event, xhr, settings) {
        if (settings.data && settings.data.indexOf('loop-mosaic') !== -1) {
            setTimeout(function () { LoopMosaicFilters.storeGridSettings(); }, 100);
        }
    });

    window.LoopMosaicFilters = LoopMosaicFilters;

})(jQuery);
