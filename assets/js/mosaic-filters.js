/**
 * LoopMosaic - JetSmartFilters Integration
 * 
 * Production version - All filter types supported
 * 
 * @package LoopMosaic
 * @author Abe Prangishvili
 * @version 1.12.0
 */

(function ($) {
    'use strict';

    const LoopMosaicFilters = {
        isInitialized: false,

        init: function () {
            if (this.isInitialized) return;
            this.isInitialized = true;

            console.log('LoopMosaic: Filters script initialized (v1.12.0) via ' + (document.readyState === 'complete' ? 'late' : 'ready'));
            this.grids = {};
            this.filterValues = {};
            this.debounceTimers = {};

            // Global Interceptor - The Nuclear Option for cached HTML
            $(document).on('click', '.loopmosaic-modal-trigger', function (e) {
                if ($(this).attr('href') !== 'javascript:void(0);') {
                    console.log('LoopMosaic: Global interception of legacy link');
                    e.preventDefault();
                    $(this).attr('href', 'javascript:void(0);');
                }
            });

            this.bindEvents();
            this.storeGridSettings();
            this.initModalHandler();
            this.initInfiniteScroll();
            this.disableNativeLinks();
            this.bindAllJSFFilters();
            this.initMasonry();
        },

        disableNativeLinks: function () {
            $('.loopmosaic-modal-trigger').each(function () {
                var $link = $(this);
                // Aggressively rewrite href to prevent navigation
                if ($link.attr('href') && $link.attr('href') !== 'javascript:void(0);') {
                    console.log('LoopMosaic: Rewriting link for', $link);
                    $link.attr('href', 'javascript:void(0);');
                }
            });
        },

        bindAllJSFFilters: function () {
            const self = this;

            this.bindSearchFilters();
            this.bindCheckboxFilters();
            this.bindRadioFilters();
            this.bindSelectFilters();
            this.bindRangeFilters();
            this.bindDateFilters();
            this.bindApplyButtons();

            setTimeout(function () {
                self.bindAllJSFFilters_Late();
            }, 1000);
        },

        bindAllJSFFilters_Late: function () {
            this.bindSearchFilters();
            this.bindCheckboxFilters();
            this.bindRadioFilters();
            this.bindSelectFilters();
            this.bindRangeFilters();
            this.bindDateFilters();
            this.bindApplyButtons();
        },

        bindSearchFilters: function () {
            const self = this;

            $('[class*="jet-smart-filters"] input[type="search"], [class*="jet-smart-filters"] input[type="text"], .jet-search-filter__input').each(function () {
                const $input = $(this);
                if ($input.data('loopmosaic-bound')) return;
                $input.data('loopmosaic-bound', true);

                const queryId = self.getQueryIdFromElement($input);

                $input.on('input keyup', function () {
                    self.scheduleFilter(queryId, 300);
                });

                $input.on('keypress', function (e) {
                    if (e.which === 13) {
                        e.preventDefault();
                        self.triggerFilter(queryId);
                    }
                });
            });
        },

        bindCheckboxFilters: function () {
            const self = this;

            $('.jet-checkboxes-list input[type="checkbox"], .jet-smart-filters-checkboxes input[type="checkbox"]').each(function () {
                const $checkbox = $(this);
                if ($checkbox.data('loopmosaic-bound')) return;
                $checkbox.data('loopmosaic-bound', true);

                const queryId = self.getQueryIdFromElement($checkbox);

                $checkbox.on('change', function () {
                    self.triggerFilter(queryId);
                });
            });
        },

        bindRadioFilters: function () {
            const self = this;

            $('.jet-radio-list input[type="radio"], .jet-smart-filters-radio input[type="radio"]').each(function () {
                const $radio = $(this);
                if ($radio.data('loopmosaic-bound')) return;
                $radio.data('loopmosaic-bound', true);

                const queryId = self.getQueryIdFromElement($radio);

                $radio.on('change', function () {
                    self.triggerFilter(queryId);
                });
            });
        },

        bindSelectFilters: function () {
            const self = this;

            $('.jet-select select, .jet-smart-filters-select select, [class*="jet-filter"] select').each(function () {
                const $select = $(this);
                if ($select.data('loopmosaic-bound')) return;
                $select.data('loopmosaic-bound', true);

                const queryId = self.getQueryIdFromElement($select);

                $select.on('change', function () {
                    self.triggerFilter(queryId);
                });
            });
        },

        bindRangeFilters: function () {
            const self = this;

            $('.jet-range__slider, .jet-smart-filters-range, [class*="jet-range"]').each(function () {
                const $range = $(this);
                if ($range.data('loopmosaic-bound')) return;
                $range.data('loopmosaic-bound', true);

                const queryId = self.getQueryIdFromElement($range);

                const $inputs = $range.find('input');
                $inputs.on('change', function () {
                    self.scheduleFilter(queryId, 500);
                });

                $range.on('slidechange slidestop', function () {
                    self.scheduleFilter(queryId, 300);
                });
            });
        },

        bindDateFilters: function () {
            const self = this;

            $('.jet-date-period, .jet-smart-filters-date-period, [class*="jet-date"]').each(function () {
                const $date = $(this);
                if ($date.data('loopmosaic-bound')) return;
                $date.data('loopmosaic-bound', true);

                const queryId = self.getQueryIdFromElement($date);

                const $inputs = $date.find('input');
                $inputs.on('change', function () {
                    self.triggerFilter(queryId);
                });
            });
        },

        bindApplyButtons: function () {
            const self = this;

            $('.jet-smart-filters-apply, .apply-filters__button, [class*="jet-apply"]').each(function () {
                const $btn = $(this);
                if ($btn.data('loopmosaic-bound')) return;
                $btn.data('loopmosaic-bound', true);

                const queryId = self.getQueryIdFromElement($btn);

                $btn.on('click', function (e) {
                    e.preventDefault();
                    self.triggerFilter(queryId);
                });
            });

            $('.jet-smart-filters-remove, .jet-remove-all-filters, [class*="jet-remove"]').each(function () {
                const $btn = $(this);
                if ($btn.data('loopmosaic-bound')) return;
                $btn.data('loopmosaic-bound', true);

                const queryId = self.getQueryIdFromElement($btn);

                $btn.on('click', function (e) {
                    e.preventDefault();
                    self.filterValues[queryId] = {};
                    self.triggerFilter(queryId);
                });
            });
        },

        getQueryIdFromElement: function ($el) {
            const $widget = $el.closest('[data-query-id]');
            if ($widget.length) {
                let qid = $widget.data('query-id');
                if (typeof qid === 'string' && qid.indexOf(' ') !== -1) {
                    qid = qid.split(' ')[0];
                }
                return qid;
            }

            const $provider = $el.closest('[data-content-provider]');
            if ($provider.length) {
                return 'loop-mosaic';
            }

            return 'loop-mosaic';
        },

        scheduleFilter: function (queryId, delay) {
            const self = this;

            if (this.debounceTimers[queryId]) {
                clearTimeout(this.debounceTimers[queryId]);
            }

            this.debounceTimers[queryId] = setTimeout(function () {
                self.triggerFilter(queryId);
            }, delay);
        },

        triggerFilter: function (queryId) {
            const filters = this.collectFilterValues(queryId);
            this.performFilterAjax(queryId, filters);
        },

        collectFilterValues: function (queryId) {
            const filters = {};

            $('[class*="jet-smart-filters"] input[type="search"], [class*="jet-smart-filters"] input[type="text"], .jet-search-filter__input').each(function () {
                const val = $(this).val().trim();
                if (val) {
                    filters._s = val;
                }
            });

            const checkboxes = {};
            $('.jet-checkboxes-list input[type="checkbox"]:checked, .jet-smart-filters-checkboxes input[type="checkbox"]:checked').each(function () {
                const name = $(this).attr('name') || 'checkbox';
                const val = $(this).val();
                if (!checkboxes[name]) checkboxes[name] = [];
                checkboxes[name].push(val);
            });
            if (Object.keys(checkboxes).length) {
                filters.checkboxes = checkboxes;
            }

            $('.jet-radio-list input[type="radio"]:checked, .jet-smart-filters-radio input[type="radio"]:checked').each(function () {
                const name = $(this).attr('name') || 'radio';
                filters[name] = $(this).val();
            });

            $('.jet-select select, .jet-smart-filters-select select, [class*="jet-filter"] select').each(function () {
                const val = $(this).val();
                const name = $(this).attr('name') || 'select';
                if (val && val !== '') {
                    filters[name] = val;
                }
            });

            $('.jet-range__slider input, .jet-smart-filters-range input').each(function () {
                const $input = $(this);
                const name = $input.attr('name') || 'range';
                const val = $input.val();
                if (val) {
                    filters[name] = val;
                }
            });

            $('.jet-date-period input, .jet-smart-filters-date-period input').each(function () {
                const $input = $(this);
                const name = $input.attr('name') || 'date';
                const val = $input.val();
                if (val) {
                    filters[name] = val;
                }
            });

            return filters;
        },

        performFilterAjax: function (queryId, filters) {
            const self = this;
            const $grid = this.getGrid(queryId);

            if (!$grid.length) {
                return;
            }

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
                    filters: JSON.stringify(filters),
                    search: filters._s || ''
                },
                success: function (response) {
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
                    }

                    $grid.removeClass('jet-filters-loading');
                    self.animateItems($grid);
                    self.disableNativeLinks();
                },
                error: function () {
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

            // Ensure modal container exists
            function ensureModalExists() {
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

                    // Re-bind close events for the new modal
                    const $modal = $('#loopmosaic-modal');
                    $modal.find('.loopmosaic-modal-close').on('click', function () { self.closeModal(); });
                    $modal.on('click', function (e) { if ($(e.target).hasClass('loopmosaic-modal-overlay')) self.closeModal(); });
                }
            }

            ensureModalExists();

            // Bind Escape key once
            $(document).on('keydown', function (e) {
                if (e.key === 'Escape' && $('#loopmosaic-modal').hasClass('is-active')) self.closeModal();
            });

            // Delegated Click Handler
            $(document).on('click', '.loopmosaic-modal-trigger', function (e) {
                console.log('LoopMosaic: Trigger clicked', this);
                e.preventDefault();
                e.stopPropagation();

                ensureModalExists(); // Double check

                const $target = $(this);
                const postId = $target.data('post-id');
                const templateId = $target.data('modal-template-id');
                const autoTemplate = $target.data('auto-template');

                if (!postId) {
                    console.error('LoopMosaic: No Post ID found on trigger', $target);
                    return;
                }

                const $modal = $('#loopmosaic-modal');
                const $container = $modal.find('.loopmosaic-modal-container');
                const $content = $modal.find('.loopmosaic-modal-content');

                $modal.addClass('is-active is-loading');
                $container.css({ opacity: 0, transform: 'translateY(20px)' });
                $content.empty();
                $('html, body').css('overflow', 'hidden');

                // Check config
                if (typeof loopMosaicConfig === 'undefined') {
                    console.error('LoopMosaic: Configuration object missing');
                    $modal.removeClass('is-loading');
                    return;
                }

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

                            // Initialize Elementor elements
                            if (window.elementorFrontend) {
                                try {
                                    // Init elements without full re-init which might cause issues
                                    $content.find('[data-element_type]').each(function () {
                                        var $element = $(this);
                                        var elementType = $element.data('element_type');
                                        if ('widget' === elementType) {
                                            elementType = $element.data('widget_type');
                                            // Run standard Elementor hook
                                            window.elementorFrontend.hooks.doAction('frontend/element_ready/' + elementType, $element);
                                        } else {
                                            // Run section/column hooks
                                            window.elementorFrontend.hooks.doAction('frontend/element_ready/' + elementType, $element);
                                        }
                                    });

                                    // Trigger window resize to fix layout headers
                                    setTimeout(function () { $(window).trigger('resize'); }, 200);
                                } catch (err) {
                                    console.log('LoopMosaic: Elementor init warning', err);
                                }
                            }

                            setTimeout(function () { $container.css({ opacity: 1, transform: 'translateY(0)' }); }, 50);
                        } else {
                            $content.html('<div style="padding:20px;text-align:center;">Error loading content.</div>');
                            $container.css({ opacity: 1, transform: 'translateY(0)' });
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('LoopMosaic: AJAX Error', error);
                        $modal.removeClass('is-loading');
                        $content.html('<div style="padding:20px;text-align:center;">Connection error.</div>');
                        $container.css({ opacity: 1, transform: 'translateY(0)' });
                    }
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
            $('.loopmosaic-grid').each(function () {
                const $grid = $(this);
                let queryId = $grid.data('query-id') || 'default';
                if (typeof queryId === 'string' && queryId.indexOf(' ') !== -1) {
                    queryId = queryId.split(' ')[0];
                }
                self.grids[queryId] = { $grid: $grid, settings: $grid.data('settings') || {} };
            });
        },

        bindEvents: function () {
            const self = this;

            $(document).on('jet-smart-filters/inited', function () {
                self.disableNativeLinks();
            });

            $(document).on('jet-filter-content-rendered', function (e, provider, queryId, response) {
                if (provider === 'loop-mosaic') {
                    const $grid = self.getGrid(queryId);
                    if ($grid.length) {
                        $grid.removeClass('jet-filters-loading');
                        if (response && response.content) $grid.html(response.content);

                        if ($grid.hasClass('loopmosaic-masonry')) {
                            $grid.imagesLoaded(function () {
                                $grid.masonry('reloadItems');
                                $grid.masonry('layout');
                            });
                        }

                        self.animateItems($grid);
                        self.disableNativeLinks();
                    }
                }
            });

            $(document).on('loopmosaic:filter', function (event, data) {
                if (data && data.queryId) {
                    self.triggerFilter(data.queryId);
                }
            });
        },

        getGrid: function (queryId) {
            if (this.grids[queryId] && this.grids[queryId].$grid) return this.grids[queryId].$grid;
            return $('.loopmosaic-grid[data-query-id*="' + queryId + '"]').first();
        },

        animateItems: function ($grid, $newItems) {
            // If $newItems provided, only animate those. Otherwise find items marked as new
            const $items = $newItems ? $newItems : $grid.find('.loopmosaic-item-new');
            if (!$items.length) return;

            // Set initial state (hidden)
            $items.css({
                'opacity': '0',
                'transform': 'translateY(30px) scale(0.98)',
                'transition': 'none'
            });

            // Trigger reflow
            $items[0].offsetHeight;

            // Staggered animation
            $items.each(function (index) {
                const $item = $(this);
                setTimeout(function () {
                    $item.css({
                        'transition': 'opacity 0.5s cubic-bezier(0.4, 0, 0.2, 1), transform 0.5s cubic-bezier(0.4, 0, 0.2, 1)',
                        'opacity': '1',
                        'transform': 'translateY(0) scale(1)'
                    });
                    // Remove the -new class after animation
                    setTimeout(function () {
                        $item.removeClass('loopmosaic-item-new');
                    }, 500);
                }, index * 80); // 80ms stagger for elegant cascade
            });
        },

        initInfiniteScroll: function () {
            const self = this;
            const $grids = $('.loopmosaic-grid[data-infinite-scroll="true"]');
            if (!$grids.length) return;

            $grids.each(function () {
                const $grid = $(this);
                const settings = $grid.data('settings') || {};
                const trigger = settings.infinite_scroll_trigger || 'scroll';

                if (trigger === 'button') {
                    // Button Trigger
                    const widgetId = $grid.closest('.elementor-widget').data('id');
                    const $btn = $('.loopmosaic-load-more-btn[data-widget-id="' + widgetId + '"]');

                    if ($btn.length) {
                        $btn.off('click').on('click', function (e) {
                            e.preventDefault();
                            if ($btn.hasClass('is-loading')) return;
                            self.loadMorePosts($grid, $btn);
                        });
                    }
                } else {
                    // Scroll Trigger
                    let ticking = false;
                    $(window).on('scroll', function () {
                        if (!ticking) {
                            window.requestAnimationFrame(function () {
                                self.handleScroll($grid);
                                ticking = false;
                            });
                            ticking = true;
                        }
                    });
                    self.handleScroll($grid);
                }
            });
        },

        handleScroll: function ($grid) {
            const self = this;
            if ($grid.hasClass('is-loading-more') || $grid.hasClass('is-finished')) return;

            const windowHeight = $(window).height();
            const scrollTop = $(window).scrollTop();
            const buffer = 300;
            const gridBottom = $grid.offset().top + $grid.outerHeight();
            const triggerPoint = scrollTop + windowHeight;

            if (triggerPoint > gridBottom - buffer) {
                self.loadMorePosts($grid);
            }
        },

        loadMorePosts: function ($grid, $btn = null) {
            const self = this;
            const maxPages = parseInt($grid.data('max-pages')) || 1;
            const currentPage = parseInt($grid.data('paged')) || 1;
            const nextPage = currentPage + 1;

            if (nextPage > maxPages) {
                $grid.addClass('is-finished');
                if ($btn) $btn.parent().hide(); // Hide wrapper
                return;
            }

            let $loader;
            if ($btn) {
                $btn.addClass('is-loading');
            } else {
                $loader = $grid.next('.loopmosaic-infinite-loader');
                if (!$loader.length) {
                    $loader = $('<div class="loopmosaic-infinite-loader"><div class="loopmosaic-spinner"></div></div>');
                    $grid.after($loader);
                }
                $loader.addClass('is-active');
            }

            $grid.addClass('is-loading-more');

            const settings = $grid.data('settings') || {};
            // Use nonce from either JSF or Config
            let nonce = loopMosaicConfig.nonce;
            if (typeof loopMosaicJSF !== 'undefined' && loopMosaicJSF.nonce) {
                nonce = loopMosaicJSF.nonce;
            }

            $.ajax({
                url: loopMosaicConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'loopmosaic_load_more',
                    nonce: nonce,
                    settings: JSON.stringify(settings),
                    paged: nextPage,
                    query_id: settings.jsf_query_id // Ensure query ID is passed for context
                },
                success: function (response) {
                    if (response.success && response.data.content) {
                        const $newItems = $(response.data.content).addClass('loopmosaic-item-new');
                        $grid.append($newItems);

                        if ($grid.hasClass('loopmosaic-masonry')) {
                            $grid.imagesLoaded(function () {
                                $grid.masonry('appended', $newItems);
                                // self.animateItems($grid, $newItems); // Masonry handles layout, animation might conflict or need delay
                                setTimeout(() => self.animateItems($grid, $newItems), 100);
                            });
                        } else {
                            self.animateItems($grid, $newItems);
                        }

                        $grid.data('paged', nextPage);
                        if (nextPage >= maxPages) {
                            $grid.addClass('is-finished');
                            if ($btn) $btn.parent().hide();
                        }

                        $(window).trigger('resize');
                    }
                    else {
                        $grid.addClass('is-finished');
                        if ($btn) $btn.parent().hide();
                    }

                    $grid.removeClass('is-loading-more');
                    if ($btn) $btn.removeClass('is-loading');
                    else if ($loader) $loader.removeClass('is-active');
                },
                error: function () {
                    $grid.removeClass('is-loading-more');
                    if ($btn) $btn.removeClass('is-loading');
                    else if ($loader) $loader.removeClass('is-active');
                }
            });
        },

        initMasonry: function ($context) {
            const $grids = $context ? $context.find('.loopmosaic-masonry') : $('.loopmosaic-masonry');
            $grids.each(function () {
                const $grid = $(this);
                if ($grid.data('masonry')) {
                    $grid.masonry('layout');
                } else {
                    $grid.imagesLoaded(function () {
                        $grid.masonry({
                            itemSelector: '.loopmosaic-item',
                            percentPosition: true,
                            columnWidth: '.loopmosaic-item'
                        });
                    });
                }
            });
        },

        initLoadMoreButton: function () {
            const self = this;
            $('.loopmosaic-load-more-button').on('click', function () {
                const $btn = $(this);
                const $grid = $btn.closest('.loopmosaic-grid');
                if (!$grid.length) return;

                self.loadMorePosts($grid);
            });
        }
    };

    $(document).ready(function () { LoopMosaicFilters.init(); });

    $(window).on('elementor/frontend/init', function () {
        LoopMosaicFilters.init(); // Redundant init check handles safety
        if (window.elementorFrontend && elementorFrontend.hooks) {
            elementorFrontend.hooks.addAction('frontend/element_ready/loopmosaic-grid.default', function ($scope) {
                LoopMosaicFilters.storeGridSettings();
                LoopMosaicFilters.bindAllJSFFilters();
                LoopMosaicFilters.initMasonry($scope);
            });
        }
    });

    window.LoopMosaicFilters = LoopMosaicFilters;

})(jQuery);
