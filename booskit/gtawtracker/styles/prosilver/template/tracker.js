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
					var html = '<table class="gtaw-tracker-table"><thead><tr><th>Character</th><th>Rank</th><th>ABAS</th></tr></thead><tbody>';

					$.each(response.characters, function(i, char) {
						var pillClass = 'abas-pill' + (char.is_low ? ' low' : '');
						html += '<tr><td>' + char.name + '</td><td>' + char.rank + '</td><td><span class="' + pillClass + '">' + char.abas + '</span></td></tr>';
					});

					html += '</tbody></table>';

					// Footer
					var totalClass = 'total-value' + (response.total_abas_low ? ' low' : '');
					html += '<div class="gtaw-tracker-footer">';
					html += '<span>Total Characters: <strong>' + response.total_characters + '</strong></span>';
					html += '<span>' + langTotal + ': <span class="' + totalClass + '">' + response.total_abas + '</span></span>';
					html += '</div>';

					container.html(html);
				} else {
					container.html('<div class="gtaw-tracker-error"><span class="error-icon">⚠</span><span class="error-msg">' + langNoChar + '</span></div>');
				}
			},
			error: function(xhr, status, error) {
				var msg = langError;
				if (xhr.responseJSON && xhr.responseJSON.error) {
					msg = xhr.responseJSON.error;
				}
				container.html('<div class="gtaw-tracker-error"><span class="error-icon">⚠</span><span class="error-msg">' + msg + '</span></div>');
			}
		});
	});
})(jQuery);
