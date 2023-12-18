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
                '<span class="qs-option-price"><%- data.price %></span>' +
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
            template_word_suggestion:
                '<li onclick="setLocation(\'/catalogsearch/result/?q=<%- data.suggestion_text %>\');" class="<%- data.suggestion_text %> category-suggestion" id="qs-option-<%- data.index %>" role="option">' +
                '<div class="qs-option-description">' +
                '<span class="qs-option-title">' +
                '<a href="/catalogsearch/result/?q=<%- data.suggestion_text %>" title="<%- data.suggestion_text %>"><%- data.suggestion_text %></a>' +
                '</span>' +
                '</div>' +
                '</li>',
            submitBtn: 'button[type="submit"]',
            closeBtn: 'button.close',
            searchLabel: '[data-role=minisearch-label]',
            template_product_suggestion_selector: '#product-suggestion',
            template_category_suggestion_selector: '#category-suggestion',
            template_word_suggestion_selector: '#word-suggestion',
            destinationSelector: '#search_autocomplete'
        },

        _create: function () {
            this.autoComplete = $(this.options.destinationSelector);
            this.searchForm = $(this.options.formSelector);
            this.submitBtn = this.searchForm.find(this.options.submitBtn)[0];
            this.searchLabel = $(this.options.searchLabel);
            this.loading = false;

            _.bindAll(this, '_onPropertyChange', '_onSubmit');

            this.submitBtn.disabled = true;
            this.element.attr('autocomplete', this.options.autocomplete);
            this.element.trigger('blur');

            this.element.on('focus', $.proxy(function () {
                this.searchLabel.addClass('active');
            }, this));

            this.element.on('input propertychange', $.proxy(function () {
                this._onPropertyChange();
            }, this));

            this.searchForm.on('submit', $.proxy(function () {
                this._onSubmit();
            }, this));
        },
        _onSubmit: function (e) {
            var value = this.element.val();

            if (isEmpty(value)) {
                e.preventDefault();
            }
        },

        _onPropertyChange: function () {
            var searchField = this.element,
                templateProductSuggestion = mageTemplate(this.options.template_product_suggestion),
                templateCategorySuggestion = mageTemplate(this.options.template_category_suggestion),
                templateWordSuggestion = mageTemplate(this.options.template_word_suggestion),
                value = this.element.val();

            this.submitBtn.disabled = isEmpty(value);
            searchField.closest('form').addClass('loading');
            this.submitBtn.disabled = true;

            $.get(this.options.url, {q: value}, $.proxy(function (data) {

                this.submitBtn.disabled = false;

                var categorySuggestions = $('<div class="category-suggestions"></div>');
                $.each(data.results.categories, function (index, element) {
                    element.index = index;
                    var html = templateCategorySuggestion({
                        data: element
                    });
                    categorySuggestions.append(html);
                });

                var wordSuggestions = $('<div class="word-suggestions"></div>');
                $.each(data.results.suggestions, function (index, element) {
                    element.index = index;
                    var html = templateWordSuggestion({
                        data: element
                    });
                    wordSuggestions.append(html);
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
                var wordTitle = $('<div class="word-title">Suggestions Keywords</div>');

                var suggestionsRow = $('<div class="row"></div>').append(
                    $('<div class="custom-left-column"></div>').append(productTitle, productSuggestions),
                    $('<div class="custom-right-column"></div>').append(categoryTitle, categorySuggestions),
                    $('<div class="custom-right-column"></div>').append(wordTitle, wordSuggestions)
                );
                this.autoComplete.html(suggestionsRow).show();
                this.element
                    .removeAttr('aria-activedescendant')
                    .closest('form').removeClass('loading');

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

        }
    });

    return $.mage.quickSearch;
});
