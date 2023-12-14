define([
    'jquery',
    'underscore',
    'mage/template',
    'jquery/ui',
    'mage/translate'
], function ($, _, mageTemplate) {
    'use strict';

    function isEmpty(value) {
        return (value.length === 0) || (value === null) || /^\s+$/.test(value);
    }

    $.widget('mage.quickSearch', {
        options: {
            timeout: 1500,
            autocomplete: 'off',
            minSearchLength: 1,
            responseFieldElements: 'ul li',
            selectClass: 'selected',
            template_product_suggestion:
                '<li onclick="setLocation(\'<%- data.url %>\');" class="<%- data.row_class %> product-suggestion" id="qs-option-<%- data.index %>" role="option">' +
                '<div class="qs-option-image">' +
                '<a href="<%- data.url %>" title="<%- data.name %>">' +
                '<img src="<%- data.image %>" title="<%- data.name %>" />' +
                '</a>' +
                '</div>' +
                '<div class="qs-option-description">' +
                '<span class="qs-option-title">' +
                '<a href="<%- data.url %>" title="<%- data.name %>"><%- data.name %></a>' +
                '</span>' +
                '</div>' +
                '</li>',
            template_category_suggestion:
                '<li onclick="setLocation(\'<%- data.category_url %>\');" class="<%- data.category_name %> category-suggestion" id="qs-option-<%- data.index %>" role="option">' +
                '<div class="qs-option-description">' +
                '<span class="qs-option-title">' +
                '<a href="<%- data.category_url %>" title="<%- data.category_name %>"><%- data.category_name %></a>' +
                '</span>' +
                '</div>' +
                '</li>',
            resultsTemplate:
                '<li class="full-search">' +
                '<a href="<%- data.category_url %>" title="' + $.mage.__('View full list') + '">' +
                '<span>' + $.mage.__('View All Results') + ': <%- data.size %></span>' +
                '</a>' +
                '<button id="btn-quicksearch-close" class="action close" data-bind="attr: { title: $t(\'Close\') }" data-action="close" type="button" title="' + $.mage.__('Close') + '">' +
                '</button>' +
                '</li>',
            submitBtn: 'button[type="submit"]',
            closeBtn: 'button.close',
            searchLabel: '[data-role=minisearch-label]',
            template_product_suggestion_selector: '#product-suggestion',
            template_category_suggestion_selector: '#category-suggestion',
            destinationSelector: '#search_autocomplete'
        },

        _create: function () {
            this.responseList = {
                indexList: null,
                selected: null
            };
            this.autoComplete = $(this.options.destinationSelector);
            this.searchForm = $(this.options.formSelector);
            this.submitBtn = this.searchForm.find(this.options.submitBtn)[0];
            this.searchLabel = $(this.options.searchLabel);
            this.loading = false;
            this.timer = 0;

            _.bindAll(this, '_onKeyDown', '_onPropertyChange', '_onSubmit');

            this.submitBtn.disabled = true;

            this.element.attr('autocomplete', this.options.autocomplete);

            this.element.trigger('blur');

            this.element.on('focus', $.proxy(function () {
                this.searchLabel.addClass('active');
            }, this));
            this.element.on('keydown', this._onKeyDown);
            this.element.on('input propertychange', $.proxy(function () {
                if (this.timer) {
                    clearTimeout(this.timer);
                }
                this.timer = setTimeout(this._onPropertyChange, this.options.timeout);
            }, this));

            this.searchForm.on('submit', $.proxy(function () {
                this._onSubmit();
                this._updateAriaHasPopup(false);
            }, this));
        },

        _getFirstVisibleElement: function () {
            return this.responseList.indexList ? this.responseList.indexList.first() : false;
        },

        _getLastElement: function () {
            return this.responseList.indexList ? this.responseList.indexList.last() : false;
        },

        _updateAriaHasPopup: function (show) {
            if (show) {
                this.element.attr('aria-haspopup', 'true');
            } else {
                this.element.attr('aria-haspopup', 'false');
            }
        },

        _resetResponseList: function (all) {
            this.responseList.selected = null;

            if (all === true) {
                this.responseList.indexList = null;
            }
        },

        _onSubmit: function (e) {
            var value = this.element.val();

            if (isEmpty(value)) {
                e.preventDefault();
            }

            if (this.responseList.selected) {
                this.element.val(this.responseList.selected.find('.qs-option-name').text());
            }
        },

        _onKeyDown: function (e) {
            var keyCode = e.keyCode || e.which;

            switch (keyCode) {
                case $.ui.keyCode.HOME:
                    this._getFirstVisibleElement().addClass(this.options.selectClass);
                    this.responseList.selected = this._getFirstVisibleElement();
                    break;
                case $.ui.keyCode.END:
                    this._getLastElement().addClass(this.options.selectClass);
                    this.responseList.selected = this._getLastElement();
                    break;
                case $.ui.keyCode.ESCAPE:
                    this._resetResponseList(true);
                    this.autoComplete.hide();
                    break;
                case $.ui.keyCode.ENTER:
                    this.searchForm.trigger('submit');
                    break;
                case $.ui.keyCode.DOWN:
                    if (this.responseList.indexList) {
                        if (!this.responseList.selected) {
                            this._getFirstVisibleElement().addClass(this.options.selectClass);
                            this.responseList.selected = this._getFirstVisibleElement();
                        } else if (!this._getLastElement().hasClass(this.options.selectClass)) {
                            var nextElement = this.responseList.selected.next();
                            this.responseList.selected.removeClass(this.options.selectClass);
                            nextElement.addClass(this.options.selectClass);
                            this.responseList.selected = nextElement;
                        } else {
                            this.responseList.selected.removeClass(this.options.selectClass);
                            this._getFirstVisibleElement().addClass(this.options.selectClass);
                            this.responseList.selected = this._getFirstVisibleElement();
                        }
                        this.element.val(this.responseList.selected.find('.qs-option-name').text());
                        this.element.attr('aria-activedescendant', this.responseList.selected.attr('id'));
                    }
                    break;
                case $.ui.keyCode.UP:
                    if (this.responseList.indexList !== null) {
                        if (!this._getFirstVisibleElement().hasClass(this.options.selectClass)) {
                            var prevElement = this.responseList.selected.prev();
                            this.responseList.selected.removeClass(this.options.selectClass);
                            prevElement.addClass(this.options.selectClass);
                            this.responseList.selected = prevElement;
                        } else {
                            this.responseList.selected.removeClass(this.options.selectClass);
                            this._getLastElement().addClass(this.options.selectClass);
                            this.responseList.selected = this._getLastElement();
                        }
                        this.element.val(this.responseList.selected.find('.qs-option-name').text());
                        this.element.attr('aria-activedescendant', this.responseList.selected.attr('id'));
                    }
                    break;
                default:
                    return true;
            }
        },

        _onPropertyChange: function () {
            var searchField = this.element,
                templateProductSuggestion = mageTemplate(this.options.template_product_suggestion),
                templateCategorySuggestion = mageTemplate(this.options.template_category_suggestion),
                resultsTemplate = mageTemplate(this.options.resultsTemplate),
                info = $('<div class="info"></div>'),
                value = this.element.val();
            this.submitBtn.disabled = isEmpty(value);

            if (value.length >= parseInt(this.options.minSearchLength, 10)) {
                searchField.closest('form').addClass('loading');
                this.submitBtn.disabled = true;
                $.get(this.options.url, {q: value}, $.proxy(function (data) {
                    this.submitBtn.disabled = false;
                    // Add full result link
                    var html = resultsTemplate({
                        data: data.info
                    });
                    info.append(html);

                    var categorySuggestions = $('<div class="category-suggestions"></div>');
                    $.each(data.results.categories, function (index, element) {
                        element.index = index;
                        var html = templateCategorySuggestion({
                            data: element
                        });
                        categorySuggestions.append(html);
                    });

                    var productSuggestions = $('<div class="product-suggestions"></div>');
                    $.each(data.results.products, function (index, element) {
                        element.index = index;
                        var html = templateProductSuggestion({
                            data: element
                        });
                        productSuggestions.append(html);
                    });

                    var categoryTitle = $('<div class="category-title">Category Suggestions</div>');

                    var productTitle = $('<div class="product-title">Product Suggestions</div>');

                    var infoTitle = $('<div class="info-title">Info</div>');

                    var suggestionsRow = $('<div class="row"></div>').append(
                        $('<div class="custom-left-column"></div>').append(productTitle, productSuggestions),
                        $('<div class="custom-right-column"></div>').append(infoTitle, info),
                        $('<div class="custom-right-column"></div>').append(categoryTitle, categorySuggestions)
                    );
                    this.responseList.indexList = this.autoComplete.html(suggestionsRow)
                        .show()
                        .find(this.options.responseFieldElements + ':visible');

                    this._resetResponseList(false);
                    this.element
                        .removeAttr('aria-activedescendant')
                        .closest('form').removeClass('loading');

                    if (this.responseList.indexList.length) {
                        this._updateAriaHasPopup(true);
                    } else {
                        this._updateAriaHasPopup(false);
                    }

                    this.responseList.indexList
                        .on('mouseenter mouseleave', function (e) {
                            this.responseList.indexList.removeClass(this.options.selectClass);
                            $(e.target).addClass(this.options.selectClass);
                            this.responseList.selected = $(e.target);
                            this.element.attr('aria-activedescendant', $(e.target).attr('id'));
                        }.bind(this))
                        .on('mouseout', function (e) {
                            if (!this._getLastElement() && this._getLastElement().hasClass(this.options.selectClass)) {
                                $(e.target).removeClass(this.options.selectClass);
                                this._resetResponseList(false);
                            }
                        }.bind(this));

                    // Close action
                    var closeBtn = this.autoComplete.find(this.options.closeBtn);
                    closeBtn.on('click', $.proxy(function () {
                        this.autoComplete.hide();
                    }, this));
                    $(document).on('click', $.proxy(function (event) {
                        if (this.searchForm.has($(event.target)).length <= 0) {
                            this.autoComplete.hide();
                        }
                    }, this));

                }, this));

            } else {
                this._resetResponseList(true);
                this.autoComplete.hide();
                this._updateAriaHasPopup(false);
                this.element.removeAttr('aria-activedescendant');
            }
        }
    });

    return $.mage.quickSearch;
});
