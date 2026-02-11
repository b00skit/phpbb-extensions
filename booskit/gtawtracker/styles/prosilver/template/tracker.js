(function($) {
	'use strict';

	$(document).ready(function() {
		var panel = $('#gtaw_tracker_panel');
		if (!panel.length) return;

		var url = panel.data('ajax-url');
		var langTotal = panel.data('lang-total');
		var langNoChar = panel.data('lang-no-char');
		var langError = panel.data('lang-error');
		var container = $('#gtaw_tracker_content');

		$.ajax({
			url: url,
			type: 'GET',
			dataType: 'json',
			success: function(response) {
				if (response.characters && response.characters.length > 0) {
					var html = '<table class="table1" style="width: 100%;"><thead><tr><th>Character</th><th>Rank</th><th>ABAS</th></tr></thead><tbody>';

					$.each(response.characters, function(i, char) {
						html += '<tr><td>' + char.name + '</td><td>' + char.rank + '</td><td>' + char.abas + '</td></tr>';
					});

					html += '</tbody><tfoot><tr><td colspan="2" style="text-align: right; font-weight: bold;">' + langTotal + ':</td><td style="font-weight: bold;">' + response.total_abas + '</td></tr></tfoot></table>';

					container.html(html);
				} else {
					container.html('<p>' + langNoChar + '</p>');
				}
			},
			error: function(xhr, status, error) {
				var msg = langError;
				if (xhr.responseJSON && xhr.responseJSON.error) {
					msg = xhr.responseJSON.error;
				}
				container.html('<p class="error">' + msg + '</p>');
			}
		});
	});
})(jQuery);
