(function($) {
    'use strict';

    $(document).ready(function() {
        var ranksData = window.SELECT2_RANKS_DATA || {};
        var ranksBaseUrl = window.SELECT2_RANKS_BASE_URL || '';

        // Template renderer for User Rank select in ACP
        function formatUserRankOption(state) {
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

        // Template renderer for Manage Rank Image select (rank_image) in ACP
        function formatManageRankImageOption(state) {
            if (!state.id || state.id === '0' || state.id === '' || state.id === 'none') {
                return state.text;
            }

            var fileName = state.id || state.text;
            var imgUrl = ranksBaseUrl + fileName;

            var $container = $('<span class="select2-rank-option"></span>');
            var $img = $('<img>', {
                src: imgUrl,
                class: 'select2-rank-thumb',
                alt: fileName,
                error: function() { $(this).hide(); }
            });
            var $titleSpan = $('<span class="select2-rank-title"></span>').text(fileName);

            $container.append($img).append($titleSpan);
            return $container;
        }

        function initACPSelect2() {
            // 1. Target "Add user to group" on User Page in ACP
            var $groupSelects = $('select[name="g"], select[name="group_id"], select[name="add_group"], select[name="g_id"]');
            $groupSelects.not('.select2-hidden-accessible').each(function() {
                $(this).select2({
                    width: '100%',
                    placeholder: 'Select a group...'
                });
            });

            // 2. Target "User Rank" select on User Page in ACP
            var $userRankSelects = $('select[name="user_rank"], select[name="rank"]');
            $userRankSelects.not('select[name="rank_image"]').not('.select2-hidden-accessible').each(function() {
                $(this).select2({
                    width: '100%',
                    templateResult: formatUserRankOption,
                    templateSelection: formatUserRankOption,
                    escapeMarkup: function(m) { return m; }
                });
            });

            // 3. Target "Manage Rank" image dropdown (rank_image) in ACP
            var $manageRankImageSelects = $('select[name="rank_image"], select#rank_image');
            $manageRankImageSelects.not('.select2-hidden-accessible').each(function() {
                var $select = $(this);
                $select.select2({
                    width: '100%',
                    templateResult: formatManageRankImageOption,
                    templateSelection: formatManageRankImageOption,
                    escapeMarkup: function(m) { return m; }
                });

                // Live image preview block below manage rank dropdown
                var $previewWrap = $select.siblings('.acp-rank-image-preview-wrapper');
                if ($previewWrap.length === 0) {
                    $previewWrap = $('<div class="acp-rank-image-preview-wrapper"><strong style="font-size:0.85em; color:#555;">Image Preview:</strong> <img class="acp-rank-image-preview-img" src="" alt="No Image Selected" style="display:none;" /><span class="acp-rank-image-preview-name" style="font-size:0.85em; color:#777;"></span></div>');
                    $select.after($previewWrap);
                }

                function updatePreview() {
                    var val = $select.val();
                    var $img = $previewWrap.find('.acp-rank-image-preview-img');
                    var $name = $previewWrap.find('.acp-rank-image-preview-name');
                    if (val && val !== '0' && val !== '' && val !== 'none') {
                        $img.attr('src', ranksBaseUrl + val).show();
                        $name.text(val);
                    } else {
                        $img.hide();
                        $name.text('No image selected');
                    }
                }

                updatePreview();
                $select.on('change.acp-rank-preview', updatePreview);
            });
        }

        initACPSelect2();

        $(document).on('ajaxComplete modal:open phpbb:dynamic_content', function() {
            initACPSelect2();
        });
    });
})(jQuery);
