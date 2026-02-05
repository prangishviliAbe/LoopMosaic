/**
 * LoopMosaic - JetSmartFilters Integration (Vanilla JS)
 * 
 * @package LoopMosaic
 * @author Abe Prangishvili
 * @version 1.2.2
 */

(function () {
    'use strict';

    // LoopMosaic Filter Handler
    const LoopMosaicFilters = {

        /**
         * Initialize
         */
        init: function () {
            this.grids = {};
            this.bindEvents();
            this.initJetSmartFilters();
            this.storeGridSettings();
            this.initModalHandler();
            this.initInfiniteScroll();
            this.disableNativeLinks();
        },

        /**
         * Disable native links for popups to prevent navigation
         */
        disableNativeLinks: function () {
            const triggers = document.querySelectorAll('.loopmosaic-modal-trigger');
            triggers.forEach(link => {
                const href = link.getAttribute('href');
                if (href && href !== '#' && href.indexOf('javascript') === -1) {
                    link.setAttribute('data-href', href);
                    link.setAttribute('href', 'javascript:void(0);');
                }
            });
        },

        /**
         * Initialize Built-in Modal handler
         */
        initModalHandler: function () {
            const self = this;

            // Create Modal HTML if not exists
            if (!document.getElementById('loopmosaic-modal')) {
                const modalHTML = `
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
                `;
                document.body.insertAdjacentHTML('beforeend', modalHTML);
            }

            const modal = document.getElementById('loopmosaic-modal');
            const container = modal.querySelector('.loopmosaic-modal-container');
            const content = modal.querySelector('.loopmosaic-modal-content');
            const closeBtn = modal.querySelector('.loopmosaic-modal-close');

            // Close events
            closeBtn.addEventListener('click', function () {
                self.closeModal();
            });

            modal.addEventListener('click', function (e) {
                if (e.target.classList.contains('loopmosaic-modal-overlay')) {
                    self.closeModal();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && modal.classList.contains('is-active')) {
                    self.closeModal();
                }
            });

            // Trigger Event Delegation
            document.addEventListener('click', function (e) {
                const target = e.target.closest('.loopmosaic-modal-trigger');
                if (target) {
                    e.preventDefault();
                    e.stopPropagation();

                    const postId = target.getAttribute('data-post-id');
                    const templateId = target.getAttribute('data-modal-template-id');
                    const autoTemplate = target.getAttribute('data-auto-template');

                    if (!postId) return;

                    // Open Modal & Show Loader
                    modal.classList.add('is-active', 'is-loading');
                    container.style.opacity = '0';
                    container.style.transform = 'translateY(20px)';
                    content.innerHTML = ''; // Clear old content
                    document.documentElement.style.overflow = 'hidden';
                    document.body.style.overflow = 'hidden';

                    // Prepare FormData
                    const formData = new FormData();
                    formData.append('action', 'loopmosaic_get_modal_content');
                    formData.append('nonce', loopMosaicConfig.nonce);
                    formData.append('post_id', postId);
                    if (templateId) formData.append('template_id', templateId);
                    if (autoTemplate) formData.append('auto_template', autoTemplate);

                    // Fetch Content
                    fetch(loopMosaicConfig.ajaxUrl, {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(response => {
                            modal.classList.remove('is-loading');

                            if (response.success && response.data.content) {
                                content.innerHTML = response.data.content;

                                // Re-init Elementor Frontend
                                if (window.elementorFrontend) {
                                    // Trigger standard elementor init
                                    window.elementorFrontend.init();

                                    // Manually trigger widget handlers
                                    const elements = content.querySelectorAll('[data-element_type]');
                                    elements.forEach(element => {
                                        let elementType = element.getAttribute('data-element_type');
                                        if ('widget' === elementType) {
                                            elementType = element.getAttribute('data-widget_type');
                                            // Use jQuery bridge for Elementor hooks as they are jQuery based
                                            if (window.jQuery) {
                                                window.elementorFrontend.hooks.doAction('frontend/element_ready/' + elementType, window.jQuery(element));
                                            }
                                        }
                                    });

                                    // Specific fix for Swiper/Gallery
                                    setTimeout(function () {
                                        window.dispatchEvent(new Event('resize'));
                                    }, 200);
                                }

                                // Animate container in
                                setTimeout(function () {
                                    container.style.opacity = '1';
                                    container.style.transform = 'translateY(0)';
                                }, 50);
                            } else {
                                content.innerHTML = '<div class="loopmosaic-modal-body"><p>Error loading content.</p></div>';
                                container.style.opacity = '1';
                                container.style.transform = 'translateY(0)';
                            }
                        })
                        .catch(() => {
                            modal.classList.remove('is-loading');
                            content.innerHTML = '<div class="loopmosaic-modal-body"><p>Connection error.</p></div>';
                            container.style.opacity = '1';
                            container.style.transform = 'translateY(0)';
                        });
                }
            });
        },

        closeModal: function () {
            const modal = document.getElementById('loopmosaic-modal');
            if (modal) {
                modal.classList.remove('is-active');
                setTimeout(function () {
                    modal.classList.remove('is-loading');
                    const content = modal.querySelector('.loopmosaic-modal-content');
                    if (content) content.innerHTML = '';
                }, 300);
                document.documentElement.style.overflow = '';
                document.body.style.overflow = '';
            }
        },

        /**
         * Store grid settings for AJAX
         */
        storeGridSettings: function () {
            const self = this;
            const grids = document.querySelectorAll('.loopmosaic-grid[data-provider="loopmosaic"]');

            grids.forEach(grid => {
                const queryId = grid.getAttribute('data-query-id') || 'default';
                // Dataset access creates a DOMStringMap, need to parse if it's JSON in DOM
                // But typically data-settings is a string attribute
                let settings = {};
                try {
                    const settingsAttr = grid.getAttribute('data-settings');
                    if (settingsAttr) {
                        settings = JSON.parse(settingsAttr);
                    }
                } catch (e) {
                    console.error('LoopMosaic: Error parsing grid settings', e);
                }

                self.grids[queryId] = {
                    element: grid,
                    settings: settings
                };
            });
        },

        /**
         * Bind events
         */
        bindEvents: function () {
            const self = this;

            // Use jQuery for external integrations if available
            if (window.jQuery) {
                const $ = window.jQuery;

                // Listen for JetSmartFilters events
                $(document).on('jet-smart-filters/inited', function (e, filterGroup) {
                    self.onFiltersInit();
                });

                $(document).on('jet-filter-content-rendered', function (e, provider, queryId, response) {
                    self.onContentRendered(provider, queryId, response);
                });

                // JetSmartFilters AJAX events
                $(document).on('jet-smart-filters/before-filter', function (event, $provider, filterGroup, data) {
                    if (data.provider === 'loopmosaic') {
                        self.beforeFilter(data);
                    }
                });

                $(document).on('jet-smart-filters/ajax-content-rendered', function (event, data) {
                    if (data.provider === 'loopmosaic') {
                        self.afterFilter(data);
                    }
                });

                // Pagination event
                $(document).on('jet-smart-filters/pagination-applied', function (event, data) {
                    if (data.provider === 'loopmosaic') {
                        self.handlePagination(data);
                    }
                });

                // Custom AJAX filter event (support both jQuery and Native if needed, keeping jQuery for bridge)
                $(document).on('loopmosaic:filter', function (e, data) {
                    self.handleFilter(null, data);
                });
            }
        },

        /**
         * Initialize JetSmartFilters integration
         */
        initJetSmartFilters: function () {
            // Check if JetSmartFilters global exists
            // Since this variable is likely defined by JSF plugin, we access it globally
            if (typeof window.JetSmartFilters === 'undefined') {
                return;
            }

            const self = this;

            // Register LoopMosaic provider
            if (window.JetSmartFiltersSettings && window.JetSmartFiltersSettings.providers) {
                window.JetSmartFiltersSettings.providers['loopmosaic'] = {
                    name: 'loopmosaic',
                    selector: '.loopmosaic-grid[data-provider="loopmosaic"]',
                    idPrefix: 'loopmosaic_',
                    isAjax: true,
                    ajaxAction: 'loopmosaic_jsf_filter',
                    apply: function (queryId, filters, pagination) {
                        self.applyFiltersAjax(queryId, filters, pagination);
                    },
                    reset: function (queryId) {
                        self.resetFilters(queryId);
                    }
                };
            }

            // Mark grids as filter targets
            const grids = document.querySelectorAll('.loopmosaic-grid[data-provider="loopmosaic"]');
            grids.forEach(grid => {
                const queryId = grid.getAttribute('data-query-id') || 'default';
                grid.setAttribute('data-jet-filter-visible', 'true');
                grid.setAttribute('data-jet-filter', queryId);
            });
        },

        /**
         * On filters initialized
         */
        onFiltersInit: function () {
            // Mark all LoopMosaic grids as initialized
            const grids = document.querySelectorAll('.loopmosaic-grid[data-provider="loopmosaic"]');
            grids.forEach(grid => {
                grid.classList.add('jsf-initialized');
            });
            this.disableNativeLinks();
        },

        /**
         * Before filter is applied
         */
        beforeFilter: function (data) {
            const queryId = data.queryId || 'default';
            const grid = this.getGrid(queryId);

            if (grid) {
                grid.classList.add('jet-filters-loading');
            }
        },

        /**
         * After filter is applied
         */
        afterFilter: function (data) {
            const queryId = data.queryId || 'default';
            const grid = this.getGrid(queryId);

            if (grid) {
                grid.classList.remove('jet-filters-loading');
                this.animateItems(grid);
            }
        },

        /**
         * On content rendered after filter
         */
        onContentRendered: function (provider, queryId, response) {
            if (provider !== 'loopmosaic') {
                return;
            }

            const grid = this.getGrid(queryId);

            if (!grid) {
                return;
            }

            // Remove loading state
            grid.classList.remove('jet-filters-loading');

            // Update content if provided
            // JetSmartFilters often provides the HTML content in response
            // The structure of 'response' depends on the plugin logic
            // Assuming response contains 'content' property as per previous code
            if (response && response.content) {
                grid.innerHTML = response.content;
            }

            // Trigger animation
            this.animateItems(grid);
            this.disableNativeLinks();
        },

        /**
         * Apply filters via AJAX
         */
        applyFiltersAjax: function (queryId, filters, pagination) {
            const self = this;
            const grid = this.getGrid(queryId);

            if (!grid) {
                return;
            }

            // Add loading state
            grid.classList.add('jet-filters-loading');

            // Get widget settings
            const settings = this.grids[queryId] ? this.grids[queryId].settings : {};
            const page = pagination ? pagination.page : 1;

            // Determine config
            const ajaxUrl = (typeof loopMosaicJSF !== 'undefined' && loopMosaicJSF.ajaxUrl) ? loopMosaicJSF.ajaxUrl : loopMosaicConfig.ajaxUrl;
            const nonce = (typeof loopMosaicJSF !== 'undefined' && loopMosaicJSF.nonce) ? loopMosaicJSF.nonce : loopMosaicConfig.nonce;

            const formData = new FormData();
            formData.append('action', 'loopmosaic_jsf_filter');
            formData.append('nonce', nonce);
            formData.append('query_id', queryId);
            formData.append('page', page);
            formData.append('settings', JSON.stringify(settings));
            // filters is typically an object, needs serialization if array/obj or individual appending
            // JSF usually expects 'filters' as key
            // We need to check how JSF sends it. Usually it's handled by PHP correctly if POSTed.
            // But complex objects in FormData can be tricky.
            // Let's iterate and append or stringify depending on expectations.
            // Previous code used `filters: filters` in jQuery ajax data object.
            // jQuery serializes objects recursively.
            // We will do a simple recursive append for FormData helper
            this.appendFormData(formData, 'filters', filters);

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(response => {
                    if (response.success && response.data) {
                        // Update content
                        if (response.data.content) {
                            grid.innerHTML = response.data.content;
                        }

                        // Trigger events (jQuery for JSF compatibility)
                        if (window.jQuery) {
                            window.jQuery(document).trigger('jet-filter-content-rendered', ['loopmosaic', queryId, response.data]);
                        }

                        // Update pagination info
                        if (response.data.max_pages) {
                            grid.setAttribute('data-max-pages', response.data.max_pages);
                            grid.setAttribute('data-found-posts', response.data.found_posts);
                        }
                    }

                    grid.classList.remove('jet-filters-loading');
                    self.animateItems(grid);
                    self.disableNativeLinks();
                })
                .catch(error => {
                    grid.classList.remove('jet-filters-loading');
                    console.error('LoopMosaic: Filter request failed', error);
                });
        },

        /**
         * Helper to append object to FormData
         */
        appendFormData: function (formData, key, data) {
            if (data === null || data === undefined) return;
            if (typeof data === 'object' && !(data instanceof File) && !(data instanceof Blob)) {
                if (Array.isArray(data)) {
                    // PHP expects array as key[]
                    data.forEach((value, index) => {
                        this.appendFormData(formData, `${key}[${index}]`, value);
                    });
                } else {
                    for (const prop in data) {
                        if (data.hasOwnProperty(prop)) {
                            this.appendFormData(formData, `${key}[${prop}]`, data[prop]);
                        }
                    }
                }
            } else {
                formData.append(key, data);
            }
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
            // First try stored grid (but return DOM element)
            if (this.grids[queryId] && this.grids[queryId].element) {
                return this.grids[queryId].element;
            }

            // Otherwise find by data attribute
            return document.querySelector('.loopmosaic-grid[data-query-id="' + queryId + '"]');
        },

        /**
         * Animate items after content load
         */
        animateItems: function (grid) {
            const items = grid.querySelectorAll('.loopmosaic-item');

            items.forEach((item, index) => {
                // Reset and animate
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';

                // Force reflow
                void item.offsetWidth;

                setTimeout(function () {
                    item.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 50);
            });
        },

        /**
         * Refresh grid layout
         */
        refreshLayout: function (queryId) {
            let grids = [];
            if (queryId) {
                const g = this.getGrid(queryId);
                if (g) grids.push(g);
            } else {
                grids = document.querySelectorAll('.loopmosaic-grid');
            }

            // Trigger reflow
            grids.forEach(grid => {
                void grid.offsetHeight;
            });
        },

        /**
         * Initialize Infinite Scroll
         */
        initInfiniteScroll: function () {
            const self = this;
            const grids = document.querySelectorAll('.loopmosaic-grid[data-infinite-scroll="true"]');

            if (!grids.length) return;

            // Throttled Scroll Event
            let ticking = false;
            window.addEventListener('scroll', function () {
                if (!ticking) {
                    window.requestAnimationFrame(function () {
                        self.handleScroll(grids);
                        ticking = false;
                    });
                    ticking = true;
                }
            }, { passive: true });

            // Initial check in case content is short
            self.handleScroll(grids);
        },

        /**
         * Handle Scroll for Infinite Grids
         */
        handleScroll: function (grids) {
            const self = this;
            const windowHeight = window.innerHeight;
            const scrollTop = window.scrollY || document.documentElement.scrollTop;
            const buffer = 300; // Load when within 300px of bottom

            grids.forEach(grid => {
                // Skip if loading or finished
                if (grid.classList.contains('is-loading-more') || grid.classList.contains('is-finished')) return;

                const rect = grid.getBoundingClientRect();
                const gridOffset = rect.top + scrollTop;
                const gridHeight = grid.offsetHeight;
                const gridBottom = gridOffset + gridHeight;
                const scrollBottom = scrollTop + windowHeight;

                if (scrollBottom > gridBottom - buffer) {
                    self.loadMorePosts(grid);
                }
            });
        },

        /**
         * Load More Posts AJAX
         */
        loadMorePosts: function (grid) {
            const self = this;
            const maxPages = parseInt(grid.getAttribute('data-max-pages')) || 1;
            const currentPage = parseInt(grid.getAttribute('data-paged')) || 1;
            const nextPage = currentPage + 1;

            if (nextPage > maxPages) {
                grid.classList.add('is-finished');
                return;
            }

            // Create Loader if not exists
            let loader = grid.nextElementSibling;
            if (!loader || !loader.classList.contains('loopmosaic-infinite-loader')) {
                const loaderDiv = document.createElement('div');
                loaderDiv.className = 'loopmosaic-infinite-loader';
                loaderDiv.innerHTML = '<div class="loopmosaic-spinner"></div>';
                grid.insertAdjacentElement('afterend', loaderDiv);
                loader = loaderDiv;
            }

            grid.classList.add('is-loading-more');
            loader.classList.add('is-active');

            let settings = {};
            try {
                const s = grid.getAttribute('data-settings');
                if (s) settings = JSON.parse(s);
            } catch (e) { }

            // Allow override from JS globals if needed
            const nonce = (typeof loopMosaicJSF !== 'undefined' && loopMosaicJSF.nonce) ? loopMosaicJSF.nonce : loopMosaicConfig.nonce;

            const formData = new FormData();
            formData.append('action', 'loopmosaic_load_more');
            formData.append('nonce', nonce);
            formData.append('settings', JSON.stringify(settings));
            formData.append('paged', nextPage);

            fetch(loopMosaicConfig.ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(response => {
                    if (response.success && response.data.content) {
                        // Append Content - Need to convert string HTML to nodes
                        // Use insertAdjacentHTML for simpler appending
                        grid.insertAdjacentHTML('beforeend', response.data.content);

                        // Update State
                        grid.setAttribute('data-paged', nextPage);
                        if (nextPage >= maxPages) {
                            grid.classList.add('is-finished');
                        }

                        // Animate New Items
                        // We only want to animate the new items
                        // The new items have 'loopmosaic-item-new' class from PHP
                        // But our animateItems function selects all. 
                        // Let's refine animateItems to accept a NodeList or just run on grid
                        // The PHP implementation adds 'loopmosaic-item-new' which has CSS animation. 
                        // So we might NOT need JS animation for infinite scroll if CSS handles it.
                        // But let's trigger it anyway for consistency or just layout updates.

                        // Trigger resize for layout adjustments
                        window.dispatchEvent(new Event('resize'));

                        // Re-init Elementor hooks if new content has widgets
                        if (window.elementorFrontend && window.jQuery) {
                            // We need to find new widgets.
                            // Since we just appended text, we don't have direct references to new nodes easily without querying
                            // But we can query the last X elements or just let Elementor handle it if we trigger general events
                            // For infinite scroll plain posts, usually no widgets inside unless Elementor Loop.
                        }

                    } else {
                        grid.classList.add('is-finished');
                    }

                    grid.classList.remove('is-loading-more');
                    loader.classList.remove('is-active');
                })
                .catch(() => {
                    grid.classList.remove('is-loading-more');
                    loader.classList.remove('is-active');
                });
        }
    };

    // Initialize on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function () {
        LoopMosaicFilters.init();
    });

    // Reinitialize on Elementor frontend init
    window.addEventListener('elementor/frontend/init', function () {
        if (window.elementorFrontend && window.elementorFrontend.hooks) {
            // Elementor hooks work with jQuery objects usually
            if (window.jQuery) {
                window.elementorFrontend.hooks.addAction('frontend/element_ready/loopmosaic-grid.default', function ($scope) {
                    LoopMosaicFilters.storeGridSettings();
                    LoopMosaicFilters.initJetSmartFilters();
                });
            }
        }
    });

    // Re-init after AJAX complete (for compatibility with other plugins using jQuery AJAX)
    if (window.jQuery) {
        window.jQuery(document).ajaxComplete(function (event, xhr, settings) {
            if (settings.data && typeof settings.data === 'string' && settings.data.indexOf('loopmosaic') !== -1) {
                setTimeout(function () {
                    LoopMosaicFilters.storeGridSettings();
                }, 100);
            }
        });
    }

    // Expose for external use
    window.LoopMosaicFilters = LoopMosaicFilters;

})();
