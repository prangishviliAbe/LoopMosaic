/**
 * LoopMosaic - JetSmartFilters Integration V3
 * 
 * @package LoopMosaic
 * @author Abe Prangishvili
 * @version 1.0.1
 */

(function ($) {
    'use strict';

    // LoopMosaic Filter Handler
    const LoopMosaicFilters = {

        /**
         * Initialize
         */
        init: function () {
            console.log('LoopMosaic V3: Filters Init Started');

            this.grids = {};
            this.bindEvents();
            this.initJetSmartFilters();
            this.storeGridSettings();
            this.initModalHandler();
            this.initInfiniteScroll();
            this.disableNativeLinks();

            console.log('LoopMosaic V3: Filters Init Complete');
        },

        /**
         * Disable native links for popups to prevent navigation
         */
        disableNativeLinks: function () {
            // Modal Triggers
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

            // Create Modal HTML if not exists
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
                            <div class="loopmosaic-modal-content">
                                <!-- Dynamic Content Here -->
                            </div>
                        </div>
                    </div>
                `);
            }

            const $modal = $('#loopmosaic-modal');
            const $container = $modal.find('.loopmosaic-modal-container');
            const $content = $modal.find('.loopmosaic-modal-content');

            // Close events
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

            // Trigger Event
            $(document).on('click', '.loopmosaic-modal-trigger', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const $target = $(this);
                const postId = $target.data('post-id');
                const templateId = $target.data('modal-template-id');
                const autoTemplate = $target.data('auto-template');

                if (!postId) return;

                // Open Modal & Show Loader
                $modal.addClass('is-active is-loading');
                $container.css({ opacity: 0, transform: 'translateY(20px)' }); // Reset container state
                $content.empty(); // Clear old content
                $('html, body').css('overflow', 'hidden'); // Prevent scrolling

                // Fetch Content
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

                            // Re-init Elementor Frontend
                            if (window.elementorFrontend) {
                                // Trigger standard elementor init
                                window.elementorFrontend.init();

                                // Manually trigger widget handlers for the new content
                                $content.find('[data-element_type]').each(function () {
                                    var $element = $(this);
                                    var elementType = $element.data('element_type');

                                    if ('widget' === elementType) {
                                        elementType = $element.data('widget_type');
                                        window.elementorFrontend.hooks.doAction('frontend/element_ready/' + elementType, $element);
                                    }
                                });

                                // Specific fix for Swiper/Gallery
                                setTimeout(function () {
                                    $(window).trigger('resize'); // Force recalculation
                                }, 200);
                            }

                            // Animate container in
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

        /**
         * Store grid settings for AJAX
         */
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

        /**
         * Bind events
         */
        bindEvents: function () {
            const self = this;

            // Listen for JetSmartFilters events
            $(document).on('jet-smart-filters/inited', this.onFiltersInit.bind(this));
            $(document).on('jet-filter-content-rendered', this.onContentRendered.bind(this));

            // JetSmartFilters AJAX events
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

            // Custom AJAX filter event
            $(document).on('loopmosaic:filter', this.handleFilter.bind(this));

            // Pagination event
            $(document).on('jet-smart-filters/pagination-applied', function (event, data) {
                if (data.provider === 'loop-mosaic') {
                    self.handlePagination(data);
                }
            });
        },

        /**
         * Initialize JetSmartFilters integration
         */
        initJetSmartFilters: function () {
            const self = this;
            let attempts = 0;
            const maxAttempts = 30; // 3 seconds total

            console.log('LoopMosaic V3: initJetSmartFilters checking settings...', {
                hasSettings: !!window.JetSmartFiltersSettings,
                hasProviders: !!(window.JetSmartFiltersSettings && window.JetSmartFiltersSettings.providers)
            });

            // DEBUG: Find correct Settings Variable (Aggressive)
            const allWindowKeys = Object.keys(window);
            const jetVars = allWindowKeys.filter(key => key.toLowerCase().indexOf('jet') !== -1 || key.toLowerCase().indexOf('smart') !== -1);
            console.error('LoopMosaic V3: DIAGNOSTIC - Found Global Variables:', JSON.stringify(jetVars));

            // Also check Elementor Config just in case
            if (window.elementorFrontend && window.elementorFrontend.config) {
                console.error('LoopMosaic V3: DIAGNOSTIC - Elementor Config Keys:', JSON.stringify(Object.keys(window.elementorFrontend.config)));
            }

            const registerProvider = function () {
                if (window.JetSmartFiltersSettings && window.JetSmartFiltersSettings.providers) {
                    console.log('LoopMosaic V3: JSF Settings Found. Registering Provider.');

                    window.JetSmartFiltersSettings.providers['loop-mosaic'] = {
                        name: 'loop-mosaic',
                        selector: '.loopmosaic-grid[data-provider="loop-mosaic"]',
                        idPrefix: 'loopmosaic_',
                        isAjax: true,
                        ajaxAction: 'loopmosaic_jsf_filter',
                        apply: function (queryId, filters, pagination) {
                            console.log('LoopMosaic V3: Custom Apply Called for ' + queryId);
                            self.applyFiltersAjax(queryId, filters, pagination);
                        },
                        reset: function (queryId) {
                            self.resetFilters(queryId);
                        }
                    };

                    // Mark grids as filter targets
                    $('.loopmosaic-grid[data-provider="loop-mosaic"]').each(function () {
                        const $grid = $(this);
                        const queryId = $grid.data('query-id') || 'default';

                        $grid.attr('data-jet-filter-visible', 'true');
                        $grid.attr('data-jet-filter', queryId);
                    });

                    return true;
                }
                return false;
            };

            // Try immediately
            if (!registerProvider()) {
                console.log('LoopMosaic V3: JSF Settings NOT found. Starting Polling...');

                const interval = setInterval(function () {
                    attempts++;
                    console.log('LoopMosaic V3: Polling for JSF Settings... Attempt ' + attempts);

                    if (registerProvider()) {
                        clearInterval(interval);
                        console.log('LoopMosaic V3: Provider Registered via Polling.');
                    } else if (attempts >= maxAttempts) {
                        clearInterval(interval);
                        console.error('LoopMosaic V3: JSF Settings GAVE UP after 3s. Main Dependency Missing?');
                    }
                }, 100);
            }
        },

        /**
         * On filters initialized
         */
        onFiltersInit: function (event, filterGroup) {
            // Mark all LoopMosaic grids as initialized
            $('.loopmosaic-grid[data-provider="loop-mosaic"]').each(function () {
                $(this).addClass('jsf-initialized');
            });
            this.disableNativeLinks();
        },

        /**
         * Before filter is applied
         */
        beforeFilter: function (data) {
            const queryId = data.queryId || 'default';
            const $grid = this.getGrid(queryId);

            if ($grid.length) {
                $grid.addClass('jet-filters-loading');
            }
        },

        /**
         * After filter is applied
         */
        afterFilter: function (data) {
            const queryId = data.queryId || 'default';
            const $grid = this.getGrid(queryId);

            if ($grid.length) {
                $grid.removeClass('jet-filters-loading');
                this.animateItems($grid);
            }
        },

        /**
         * On content rendered after filter
         */
        onContentRendered: function (event, provider, queryId, response) {
            if (provider !== 'loop-mosaic') {
                return;
            }

            const $grid = this.getGrid(queryId);

            if (!$grid.length) {
                return;
            }

            // Remove loading state
            $grid.removeClass('jet-filters-loading');

            // Update content if provided
            if (response && response.content) {
                $grid.html(response.content);
            }

            // Trigger animation
            this.animateItems($grid);
            this.disableNativeLinks();
        },

        /**
         * Apply filters via AJAX
         */
        applyFiltersAjax: function (queryId, filters, pagination) {
            const self = this;
            const $grid = this.getGrid(queryId);

            if (!$grid.length) {
                return;
            }

            // Add loading state
            $grid.addClass('jet-filters-loading');

            // Get widget settings
            const settings = this.grids[queryId] ? this.grids[queryId].settings : {};
            const page = pagination ? pagination.page : 1;

            // Determine config
            let ajaxUrl = loopMosaicConfig.ajaxUrl;
            let nonce = loopMosaicConfig.nonce;

            if (typeof loopMosaicJSF !== 'undefined') {
                if (loopMosaicJSF.ajaxUrl) ajaxUrl = loopMosaicJSF.ajaxUrl;
                if (loopMosaicJSF.nonce) nonce = loopMosaicJSF.nonce;
            }

            console.log('LoopMosaic V3: Apply Filter', { queryId, filters, page, ajaxUrl });

            // AJAX request
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
                    filters: JSON.stringify(filters) // Ensure filters are stringified if complex
                },
                success: function (response) {
                    console.log('LoopMosaic V3: Filter Success', response);

                    if (response.success && response.data) {
                        // Update content
                        if (response.data.content) {
                            $grid.html(response.data.content);
                        }

                        // Trigger events
                        $(document).trigger('jet-filter-content-rendered', ['loop-mosaic', queryId, response.data]);

                        // Update pagination info
                        if (response.data.max_pages) {
                            $grid.data('max-pages', response.data.max_pages);
                            $grid.data('found-posts', response.data.found_posts);
                        }
                    } else {
                        console.error('LoopMosaic V3: Filter Response Error', response);
                    }

                    $grid.removeClass('jet-filters-loading');
                    self.animateItems($grid);
                    self.disableNativeLinks();
                },
                error: function (xhr, status, error) {
                    $grid.removeClass('jet-filters-loading');
                    console.error('LoopMosaic V3: Filter Request Failed', { status, error, response: xhr.responseText });
                }
            });
        },

        /**
         * Reset filters
         */
        resetFilters: function (queryId) {
            this.applyFiltersAjax(queryId, {}, null);
        },

        /**
         * Handle pagination
         */
        handlePagination: function (data) {
            const queryId = data.queryId || 'default';
            const page = data.page || 1;

            this.applyFiltersAjax(queryId, data.filters || {}, { page: page });
        },

        /**
         * Handle custom filter event
         */
        handleFilter: function (event, data) {
            if (!data || !data.queryId) {
                return;
            }

            this.applyFiltersAjax(data.queryId, data.filters || {}, data.pagination || null);
        },

        /**
         * Get grid element by query ID
         */
        getGrid: function (queryId) {
            // First try stored grid
            if (this.grids[queryId] && this.grids[queryId].$grid) {
                return this.grids[queryId].$grid;
            }

            // Otherwise find by data attribute
            return $('.loopmosaic-grid[data-query-id="' + queryId + '"]');
        },

        /**
         * Animate items after content load
         */
        animateItems: function ($grid) {
            const $items = $grid.find('.loopmosaic-item');

            $items.each(function (index) {
                const $item = $(this);

                // Reset and animate
                $item.css({
                    'opacity': '0',
                    'transform': 'translateY(20px)'
                });

                setTimeout(function () {
                    $item.css({
                        'transition': 'opacity 0.4s ease, transform 0.4s ease',
                        'opacity': '1',
                        'transform': 'translateY(0)'
                    });
                }, index * 50);
            });
        },

        /**
         * Refresh grid layout
         */
        refreshLayout: function (queryId) {
            const $grid = queryId ? this.getGrid(queryId) : $('.loopmosaic-grid');

            // Trigger reflow
            $grid.each(function () {
                this.offsetHeight;
            });
        },

        /**
         * Initialize Infinite Scroll
         */
        initInfiniteScroll: function () {
            const self = this;
            const $grids = $('.loopmosaic-grid[data-infinite-scroll="true"]');

            if (!$grids.length) return;

            // Throttled Scroll Event
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

            // Initial check in case content is short
            self.handleScroll($grids);
        },

        /**
         * Handle Scroll for Infinite Grids
         */
        handleScroll: function ($grids) {
            const self = this;
            const windowHeight = $(window).height();
            const scrollTop = $(window).scrollTop();
            const buffer = 300; // Load when within 300px of bottom

            $grids.each(function () {
                const $grid = $(this);

                // Skip if loading or finished
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

        /**
         * Load More Posts AJAX
         */
        loadMorePosts: function ($grid) {
            const self = this;
            const maxPages = parseInt($grid.data('max-pages')) || 1;
            const currentPage = parseInt($grid.data('paged')) || 1;
            const nextPage = currentPage + 1;

            if (nextPage > maxPages) {
                $grid.addClass('is-finished');
                return;
            }

            // Create Loader if not exists
            let $loader = $grid.next('.loopmosaic-infinite-loader');
            if (!$loader.length) {
                $loader = $('<div class="loopmosaic-infinite-loader"><div class="loopmosaic-spinner"></div></div>');
                $grid.after($loader);
            }

            $grid.addClass('is-loading-more');
            $loader.addClass('is-active');

            const settings = $grid.data('settings') || {};
            const queryId = $grid.data('query-id');
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

                        // Append Content
                        $grid.append($content);

                        // Update State
                        $grid.data('paged', nextPage);
                        if (nextPage >= maxPages) {
                            $grid.addClass('is-finished');
                        }

                        // Animate New Items
                        self.animateItems($grid);

                        // Bind Events for new items (like Modals)
                        // Note: initModalHandler uses delegated events mostly, but let's double check specific bindings if needed
                        // The modal trigger uses $(document).on, so it should work automatically.

                        // Trigger resize for layout adjustments
                        $(window).trigger('resize');
                    } else {
                        // Assumption: No more posts or error treating as end
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

    // Initialize on document ready
    $(document).ready(function () {
        LoopMosaicFilters.init();
    });

    // Reinitialize on Elementor frontend init
    $(window).on('elementor/frontend/init', function () {
        if (window.elementorFrontend && elementorFrontend.hooks) {
            elementorFrontend.hooks.addAction('frontend/element_ready/loopmosaic-grid.default', function ($scope) {
                console.log('LoopMosaic V3: Elementor Widget Ready Hook');
                LoopMosaicFilters.storeGridSettings();
                LoopMosaicFilters.initJetSmartFilters();
            });
        }
    });

    // Re-init after AJAX complete
    $(document).ajaxComplete(function (event, xhr, settings) {
        if (settings.data && settings.data.indexOf('loop-mosaic') !== -1) {
            setTimeout(function () {
                LoopMosaicFilters.storeGridSettings();
            }, 100);
        }
    });

    // Expose for external use
    window.LoopMosaicFilters = LoopMosaicFilters;

})(jQuery);
