/**
 * LoopMosaic - JetSmartFilters Integration V8
 * 
 * NEW APPROACH: Direct search input listener
 * Since JSF's provider system isn't triggering, we listen to the search input directly.
 * 
 * @package LoopMosaic
 * @author Abe Prangishvili
 * @version 1.0.7
 */

(function ($) {
    'use strict';

    const LoopMosaicFilters = {

        init: function () {
            console.log('LoopMosaic V8: Filters Init Started');

            this.grids = {};
            this.searchDebounceTimers = {};

            this.bindEvents();
            this.storeGridSettings();
            this.initModalHandler();
            this.initInfiniteScroll();
            this.disableNativeLinks();

            // V8: Direct JSF Search Input Binding
            this.bindJSFSearchInputs();

            console.log('LoopMosaic V8: Filters Init Complete');
        },

        /**
         * V8: Bind directly to JetSmartFilters search inputs
         */
        bindJSFSearchInputs: function () {
            const self = this;

            // Find all JSF search filter inputs that are configured for loop-mosaic
            // The filter widget has data-query-id attribute
            const $searchFilters = $('.jet-smart-filters-search, .jsf-search-filter, [data-content-provider="loop-mosaic"]');

            console.log('LoopMosaic V8: Looking for JSF Search Inputs...', $searchFilters.length);

            // Also look for any input inside jet-smart-filters widgets
            const $jsfInputs = $('[class*="jet-smart-filters"] input[type="search"], [class*="jet-smart-filters"] input[type="text"]');
            console.log('LoopMosaic V8: Found JSF Inputs:', $jsfInputs.length);

            // Bind to all potential search inputs
            $jsfInputs.each(function () {
                const $input = $(this);
                const $widget = $input.closest('[data-query-id]');
                const queryId = $widget.data('query-id') || 'loop-mosaic';

                console.log('LoopMosaic V8: Binding search input for queryId:', queryId);

                // Debounced search handler
                $input.on('input keyup', function (e) {
                    const searchValue = $input.val().trim();

                    // Clear existing timer
                    if (self.searchDebounceTimers[queryId]) {
                        clearTimeout(self.searchDebounceTimers[queryId]);
                    }

                    // Set new debounce timer (300ms delay)
                    self.searchDebounceTimers[queryId] = setTimeout(function () {
                        console.log('LoopMosaic V8: Search triggered:', searchValue, 'for:', queryId);
                        self.performSearch(queryId, searchValue);
                    }, 300);
                });

                // Handle Enter key
                $input.on('keypress', function (e) {
                    if (e.which === 13) {
                        e.preventDefault();
                        const searchValue = $input.val().trim();
                        console.log('LoopMosaic V8: Search Enter pressed:', searchValue);

                        if (self.searchDebounceTimers[queryId]) {
                            clearTimeout(self.searchDebounceTimers[queryId]);
                        }
                        self.performSearch(queryId, searchValue);
                    }
                });
            });

            // Also try to find search by examining the page structure
            // Look for elements that might be search filters targeting our grid
            setTimeout(function () {
                self.findAndBindSearchByProvider();
            }, 500);
        },

        /**
         * Find search inputs by examining provider configuration
         */
        findAndBindSearchByProvider: function () {
            const self = this;

            // Look for any element with content-provider attribute
            $('[data-content-provider="loop-mosaic"], [data-provider-id="loop-mosaic"]').each(function () {
                const $filterWidget = $(this);
                const $searchInput = $filterWidget.find('input');
                const queryId = $filterWidget.data('query-id') || 'loop-mosaic';

                if ($searchInput.length && !$searchInput.data('loopmosaic-bound')) {
                    console.log('LoopMosaic V8: Late-binding search input for:', queryId);
                    $searchInput.data('loopmosaic-bound', true);

                    $searchInput.on('input', function () {
                        const searchValue = $searchInput.val().trim();
                        if (self.searchDebounceTimers[queryId]) {
                            clearTimeout(self.searchDebounceTimers[queryId]);
                        }
                        self.searchDebounceTimers[queryId] = setTimeout(function () {
                            self.performSearch(queryId, searchValue);
                        }, 300);
                    });
                }
            });
        },

        /**
         * Perform search AJAX
         */
        performSearch: function (queryId, searchValue) {
            const self = this;
            const $grid = this.getGrid(queryId);

            if (!$grid.length) {
                console.error('LoopMosaic V8: Grid not found for queryId:', queryId);
                return;
            }

            console.log('LoopMosaic V8: Performing search AJAX', { queryId, searchValue });

            // Show loading state
            $grid.addClass('jet-filters-loading');

            const settings = this.grids[queryId] ? this.grids[queryId].settings : ($grid.data('settings') || {});

            let ajaxUrl = loopMosaicConfig.ajaxUrl;
            let nonce = loopMosaicConfig.nonce;
            if (typeof loopMosaicJSF !== 'undefined') {
                if (loopMosaicJSF.ajaxUrl) ajaxUrl = loopMosaicJSF.ajaxUrl;
                if (loopMosaicJSF.nonce) nonce = loopMosaicJSF.nonce;
            }

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'loopmosaic_jsf_filter',
                    nonce: nonce,
                    query_id: queryId,
                    page: 1,
                    settings: JSON.stringify(settings),
                    search: searchValue,
                    filters: JSON.stringify({ _s: searchValue })
                },
                success: function (response) {
                    console.log('LoopMosaic V8: Search AJAX Success', response);

                    if (response.success && response.data) {
                        if (response.data.content) {
                            $grid.html(response.data.content);
                        } else if (response.data.found_posts === 0) {
                            $grid.html('<div class="loopmosaic-no-results">No results found.</div>');
                        }

                        if (response.data.max_pages !== undefined) {
                            $grid.data('max-pages', response.data.max_pages);
                            $grid.data('found-posts', response.data.found_posts);
                        }
                    } else {
                        console.error('LoopMosaic V8: Search Response Error', response);
                    }

                    $grid.removeClass('jet-filters-loading');
                    self.animateItems($grid);
                    self.disableNativeLinks();
                },
                error: function (xhr, status, error) {
                    console.error('LoopMosaic V8: Search AJAX Failed', { status, error, response: xhr.responseText });
                    $grid.removeClass('jet-filters-loading');
                }
            });
        },

        disableNativeLinks: function () {
            $('.loopmosaic-modal-trigger').each(function () {
                var $link = $(this);
                if ($link.attr('href') && $link.attr('href') !== '#' && $link.attr('href').indexOf('javascript') === -1) {
                    $link.attr('data-href', $link.attr('href'));
                    $link.attr('href', 'javascript:void(0);');
                }
            });
        },

        initModalHandler: function () {
            const self = this;

            if ($('#loopmosaic-modal').length === 0) {
                $('body').append(`
                    <div id="loopmosaic-modal" class="loopmosaic-modal-overlay">
                        <div class="loopmosaic-modal-loader"><div class="loopmosaic-modal-spinner"></div></div>
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

            $modal.find('.loopmosaic-modal-close').on('click', function () { self.closeModal(); });
            $modal.on('click', function (e) { if ($(e.target).hasClass('loopmosaic-modal-overlay')) self.closeModal(); });
            $(document).on('keydown', function (e) { if (e.key === 'Escape' && $modal.hasClass('is-active')) self.closeModal(); });

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
                    data: { action: 'loopmosaic_get_modal_content', nonce: loopMosaicConfig.nonce, post_id: postId, template_id: templateId, auto_template: autoTemplate },
                    success: function (response) {
                        $modal.removeClass('is-loading');
                        if (response.success && response.data.content) {
                            $content.html(response.data.content);
                            if (window.elementorFrontend) {
                                window.elementorFrontend.init();
                                $content.find('[data-element_type]').each(function () {
                                    var $element = $(this), elementType = $element.data('element_type');
                                    if ('widget' === elementType) { elementType = $element.data('widget_type'); window.elementorFrontend.hooks.doAction('frontend/element_ready/' + elementType, $element); }
                                });
                                setTimeout(function () { $(window).trigger('resize'); }, 200);
                            }
                            setTimeout(function () { $container.css({ opacity: 1, transform: 'translateY(0)' }); }, 50);
                        } else {
                            $content.html('<p>Error loading content.</p>');
                            $container.css({ opacity: 1, transform: 'translateY(0)' });
                        }
                    },
                    error: function () { $modal.removeClass('is-loading'); $content.html('<p>Connection error.</p>'); $container.css({ opacity: 1, transform: 'translateY(0)' }); }
                });
            });
        },

        closeModal: function () {
            const $modal = $('#loopmosaic-modal');
            $modal.removeClass('is-active');
            setTimeout(function () { $modal.removeClass('is-loading'); $modal.find('.loopmosaic-modal-content').empty(); }, 300);
            $('html, body').css('overflow', '');
        },

        storeGridSettings: function () {
            const self = this;
            $('.loopmosaic-grid[data-provider="loop-mosaic"]').each(function () {
                const $grid = $(this);
                const queryId = $grid.data('query-id') || 'default';
                self.grids[queryId] = { $grid: $grid, settings: $grid.data('settings') || {} };
                console.log('LoopMosaic V8: Stored grid settings for:', queryId);
            });
        },

        bindEvents: function () {
            const self = this;

            // Standard JSF events (in case they start working)
            $(document).on('jet-smart-filters/inited', function (e, data) {
                console.log('LoopMosaic V8: EVENT jet-smart-filters/inited');
                self.disableNativeLinks();
            });

            $(document).on('jet-filter-content-rendered', function (e, provider, queryId, response) {
                console.log('LoopMosaic V8: EVENT jet-filter-content-rendered', provider, queryId);
                if (provider === 'loop-mosaic') {
                    const $grid = self.getGrid(queryId);
                    if ($grid.length) {
                        $grid.removeClass('jet-filters-loading');
                        if (response && response.content) $grid.html(response.content);
                        self.animateItems($grid);
                        self.disableNativeLinks();
                    }
                }
            });

            $(document).on('loopmosaic:filter', this.handleFilter.bind(this));
        },

        handleFilter: function (event, data) {
            if (!data || !data.queryId) return;
            this.performSearch(data.queryId, data.search || '');
        },

        getGrid: function (queryId) {
            if (this.grids[queryId] && this.grids[queryId].$grid) return this.grids[queryId].$grid;
            // Also try matching partial queryId (in case of duplication issues)
            return $('.loopmosaic-grid[data-query-id*="' + queryId + '"]').first();
        },

        animateItems: function ($grid) {
            const $items = $grid.find('.loopmosaic-item');
            $items.each(function (index) {
                const $item = $(this);
                $item.css({ 'opacity': '0', 'transform': 'translateY(20px)' });
                setTimeout(function () { $item.css({ 'transition': 'opacity 0.4s ease, transform 0.4s ease', 'opacity': '1', 'transform': 'translateY(0)' }); }, index * 50);
            });
        },

        initInfiniteScroll: function () {
            const self = this;
            const $grids = $('.loopmosaic-grid[data-infinite-scroll="true"]');
            if (!$grids.length) return;
            let ticking = false;
            $(window).on('scroll', function () { if (!ticking) { window.requestAnimationFrame(function () { self.handleScroll($grids); ticking = false; }); ticking = true; } });
            self.handleScroll($grids);
        },

        handleScroll: function ($grids) {
            const self = this;
            const windowHeight = $(window).height(), scrollTop = $(window).scrollTop(), buffer = 300;
            $grids.each(function () {
                const $grid = $(this);
                if ($grid.hasClass('is-loading-more') || $grid.hasClass('is-finished')) return;
                const gridBottom = $grid.offset().top + $grid.outerHeight();
                if (scrollTop + windowHeight > gridBottom - buffer) self.loadMorePosts($grid);
            });
        },

        loadMorePosts: function ($grid) {
            const self = this;
            const maxPages = parseInt($grid.data('max-pages')) || 1, currentPage = parseInt($grid.data('paged')) || 1, nextPage = currentPage + 1;
            if (nextPage > maxPages) { $grid.addClass('is-finished'); return; }
            let $loader = $grid.next('.loopmosaic-infinite-loader');
            if (!$loader.length) { $loader = $('<div class="loopmosaic-infinite-loader"><div class="loopmosaic-spinner"></div></div>'); $grid.after($loader); }
            $grid.addClass('is-loading-more'); $loader.addClass('is-active');
            const settings = $grid.data('settings') || {}, nonce = (typeof loopMosaicJSF !== 'undefined' && loopMosaicJSF.nonce) ? loopMosaicJSF.nonce : loopMosaicConfig.nonce;
            $.ajax({
                url: loopMosaicConfig.ajaxUrl, type: 'POST',
                data: { action: 'loopmosaic_load_more', nonce: nonce, settings: JSON.stringify(settings), paged: nextPage },
                success: function (response) {
                    if (response.success && response.data.content) { $grid.append($(response.data.content)); $grid.data('paged', nextPage); if (nextPage >= maxPages) $grid.addClass('is-finished'); self.animateItems($grid); $(window).trigger('resize'); }
                    else $grid.addClass('is-finished');
                    $grid.removeClass('is-loading-more'); $loader.removeClass('is-active');
                },
                error: function () { $grid.removeClass('is-loading-more'); $loader.removeClass('is-active'); }
            });
        }
    };

    $(document).ready(function () { LoopMosaicFilters.init(); });

    $(window).on('elementor/frontend/init', function () {
        if (window.elementorFrontend && elementorFrontend.hooks) {
            elementorFrontend.hooks.addAction('frontend/element_ready/loopmosaic-grid.default', function ($scope) {
                console.log('LoopMosaic V8: Elementor Widget Ready');
                LoopMosaicFilters.storeGridSettings();
                LoopMosaicFilters.bindJSFSearchInputs();
            });
        }
    });

    window.LoopMosaicFilters = LoopMosaicFilters;

})(jQuery);
