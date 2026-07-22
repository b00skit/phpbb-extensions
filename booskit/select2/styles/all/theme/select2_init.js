(function($) {
    'use strict';

    $(document).ready(function() {
        var ranksData = window.SELECT2_RANKS_DATA || {};
        var ranksBaseUrl = window.SELECT2_RANKS_BASE_URL || '';

        // Helper to format rank options with rank image and image name
        function formatRankOption(state) {
            if (!state.id) {
                return state.text;
            }

            var val = state.id;
            var rankInfo = ranksData[val];

            var imgUrl = $(state.element).data('rank-image') || (rankInfo ? rankInfo.image_url : '');
            var imgName = $(state.element).data('rank-image-name') || (rankInfo ? rankInfo.image_name : '');
            var title = state.text;

            if (imgUrl && imgUrl !== '') {
                var $container = $('<span class="select2-rank-option"></span>');
                var $img = $('<img>', {
                    src: imgUrl,
                    class: 'select2-rank-thumb',
                    alt: title
                });
                var $titleSpan = $('<span class="select2-rank-title"></span>').text(title);

                $container.append($img).append($titleSpan);
                if (imgName) {
                    var $imgNameSpan = $('<small class="select2-rank-imgname"></small>').text('(' + imgName + ')');
                    $container.append($imgNameSpan);
                }
                return $container;
            }

            if (imgName) {
                var $container = $('<span class="select2-rank-option"></span>');
                var $titleSpan = $('<span class="select2-rank-title"></span>').text(title);
                var $imgNameSpan = $('<small class="select2-rank-imgname"></small>').text('(' + imgName + ')');
                $container.append($titleSpan).append($imgNameSpan);
                return $container;
            }

            return state.text;
        }

        function initFrontSelect2() {
            $('select').not('.no-select2, .select2-hidden-accessible').each(function() {
                var $select = $(this);

                var name = ($select.attr('name') || '').toLowerCase();
                var id = ($select.attr('id') || '').toLowerCase();
                var isRankSelect = (name.indexOf('rank') !== -1 || id.indexOf('rank') !== -1);

                var options = {
                    width: '100%',
                    allowClear: false
                };

                if (isRankSelect && (Object.keys(ranksData).length > 0 || $select.find('option[data-rank-image]').length > 0)) {
                    options.templateResult = formatRankOption;
                    options.templateSelection = formatRankOption;
                    options.escapeMarkup = function(m) { return m; };
                }

                $select.select2(options);
            });
        }

        initFrontSelect2();

        // Handle dynamic elements
        $(document).on('ajaxComplete modal:open phpbb:dynamic_content', function() {
            initFrontSelect2();
        });
    });
})(jQuery);
