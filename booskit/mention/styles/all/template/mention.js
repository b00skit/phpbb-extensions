/**
 * booskit/mention JavaScript
 * Handles @username autocomplete suggestions and BBCode insertion
 */

(function () {
	'use strict';

	var configEl = document.getElementById('booskit-mention-config');
	var findUrl = configEl ? configEl.getAttribute('data-find-url') : '';
	if (!findUrl) {
		return;
	}

	var activeTextarea = null;
	var dropdown = null;
	var items = [];
	var selectedIndex = 0;
	var triggerPos = -1;
	var fetchTimer = null;
	var currentQuery = null;

	// Create and append dropdown element
	function createDropdown() {
		if (dropdown) return dropdown;
		dropdown = document.createElement('ul');
		dropdown.className = 'mention-dropdown';
		dropdown.style.display = 'none';
		document.body.appendChild(dropdown);

		// Prevent mousedown on dropdown from blurring textarea
		dropdown.addEventListener('mousedown', function (e) {
			e.preventDefault();
		});

		return dropdown;
	}

	// Calculate caret coordinates using a mirror div
	function getCaretCoordinates(textarea, position) {
		var mirror = document.getElementById('mention-caret-mirror');
		if (!mirror) {
			mirror = document.createElement('div');
			mirror.id = 'mention-caret-mirror';
			document.body.appendChild(mirror);
		}

		var style = window.getComputedStyle(textarea);
		var properties = [
			'boxSizing', 'width', 'height', 'overflowX', 'overflowY',
			'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth',
			'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
			'fontStyle', 'fontVariant', 'fontWeight', 'fontStretch', 'fontSize',
			'fontSizeAdjust', 'lineHeight', 'fontFamily', 'textAlign',
			'textTransform', 'textIndent', 'textDecoration', 'letterSpacing',
			'wordSpacing', 'tabSize', 'MozTabSize'
		];

		mirror.style.position = 'absolute';
		mirror.style.visibility = 'hidden';
		mirror.style.top = '-9999px';
		mirror.style.left = '-9999px';
		mirror.style.whiteSpace = 'pre-wrap';
		mirror.style.wordWrap = 'break-word';

		properties.forEach(function (prop) {
			mirror.style[prop] = style[prop];
		});

		var text = textarea.value.substring(0, position);
		mirror.textContent = text;

		var marker = document.createElement('span');
		marker.textContent = '@';
		mirror.appendChild(marker);

		var textareaRect = textarea.getBoundingClientRect();
		var markerRect = marker.getBoundingClientRect();
		var mirrorRect = mirror.getBoundingClientRect();

		var top = textareaRect.top + window.pageYOffset + (markerRect.top - mirrorRect.top) - textarea.scrollTop;
		var left = textareaRect.left + window.pageXOffset + (markerRect.left - mirrorRect.left) - textarea.scrollLeft;

		var lineHeight = parseInt(style.lineHeight, 10) || 18;

		return {
			top: top + lineHeight + 4,
			left: left
		};
	}

	function showDropdown(coords) {
		if (!dropdown || items.length === 0) {
			hideDropdown();
			return;
		}

		dropdown.innerHTML = '';
		items.forEach(function (user, index) {
			var li = document.createElement('li');
			var isGroup = !!user.is_group;
			li.className = 'mention-item' + (isGroup ? ' mention-item-group' : '') + (index === selectedIndex ? ' active' : '');

			var avatarWrap = document.createElement('span');
			avatarWrap.className = 'mention-avatar-wrap';

			if (isGroup) {
				var grpPlaceholder = document.createElement('span');
				grpPlaceholder.className = 'mention-avatar-placeholder mention-group-avatar';
				grpPlaceholder.textContent = '👥';
				avatarWrap.appendChild(grpPlaceholder);
			} else if (user.avatar) {
				avatarWrap.innerHTML = user.avatar;
			} else {
				var placeholder = document.createElement('span');
				placeholder.className = 'mention-avatar-placeholder';
				placeholder.textContent = user.username ? user.username.charAt(0) : '?';
				avatarWrap.appendChild(placeholder);
			}

			var nameSpan = document.createElement('span');
			nameSpan.className = 'mention-username';
			nameSpan.textContent = user.username;
			if (user.colour) {
				nameSpan.style.color = user.colour;
			}

			li.appendChild(avatarWrap);
			li.appendChild(nameSpan);

			if (isGroup) {
				var badge = document.createElement('span');
				badge.className = 'mention-badge-group';
				badge.textContent = 'Group';
				li.appendChild(badge);
			}

			li.addEventListener('click', function () {
				selectUser(user);
			});

			dropdown.appendChild(li);
		});

		dropdown.style.display = 'block';

		// Adjust positioning to avoid going off screen
		var ddRect = dropdown.getBoundingClientRect();
		var left = coords.left;
		var top = coords.top;

		if (left + ddRect.width > window.innerWidth - 10) {
			left = window.innerWidth - ddRect.width - 10;
		}
		if (left < 10) {
			left = 10;
		}

		if (top + ddRect.height > window.innerHeight + window.pageYOffset - 10) {
			top = top - ddRect.height - 24;
		}

		dropdown.style.left = left + 'px';
		dropdown.style.top = top + 'px';
	}

	function hideDropdown() {
		if (dropdown) {
			dropdown.style.display = 'none';
			dropdown.innerHTML = '';
		}
		items = [];
		selectedIndex = 0;
		triggerPos = -1;
	}

	function selectUser(user) {
		if (!activeTextarea || triggerPos < 0) {
			hideDropdown();
			return;
		}

		var text = activeTextarea.value;
		var caretPos = activeTextarea.selectionStart;

		// BBCode to insert: [mentiongroup=ID]Name[/mentiongroup] or [mention=ID]Username[/mention]
		var bbcode = user.is_group
			? '[mentiongroup=' + user.id + ']' + user.username + '[/mentiongroup] '
			: '[mention=' + user.id + ']' + user.username + '[/mention] ';

		var before = text.substring(0, triggerPos);
		var after = text.substring(caretPos);

		activeTextarea.value = before + bbcode + after;


		var newCaretPos = triggerPos + bbcode.length;
		activeTextarea.selectionStart = newCaretPos;
		activeTextarea.selectionEnd = newCaretPos;

		// Update phpBB storeCaret if present
		if (typeof window.storeCaret === 'function') {
			window.storeCaret(activeTextarea);
		}

		// Trigger input event for drafts and other plugins
		var event = document.createEvent('HTMLEvents');
		event.initEvent('input', true, true);
		activeTextarea.dispatchEvent(event);

		activeTextarea.focus();
		hideDropdown();
	}

	function queryUsers(query, coords) {
		if (currentQuery === query && items.length > 0) {
			showDropdown(coords);
			return;
		}

		var separator = findUrl.indexOf('?') === -1 ? '?' : '&';
		var url = findUrl + separator + 'q=' + encodeURIComponent(query);

		var xhr = new XMLHttpRequest();
		xhr.open('GET', url, true);
		xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		xhr.onreadystatechange = function () {
			if (xhr.readyState === 4) {
				if (xhr.status === 200) {
					try {
						items = JSON.parse(xhr.responseText);
						currentQuery = query;
						selectedIndex = 0;
						if (items.length > 0) {
							showDropdown(coords);
						} else {
							hideDropdown();
						}
					} catch (e) {
						hideDropdown();
					}
				} else {
					hideDropdown();
				}
			}
		};
		xhr.send();
	}

	function handleInput(e) {
		var textarea = e.target;
		activeTextarea = textarea;
		createDropdown();

		var caret = textarea.selectionStart;
		var text = textarea.value.substring(0, caret);

		// Match @word before cursor (preceded by whitespace or start of line)
		var match = /(?:^|\s)@([^\s@]*)$/.exec(text);

		if (match) {
			var query = match[1];
			triggerPos = caret - query.length - 1;

			clearTimeout(fetchTimer);
			fetchTimer = setTimeout(function () {
				var coords = getCaretCoordinates(textarea, triggerPos);
				queryUsers(query, coords);
			}, 150);
		} else {
			hideDropdown();
		}
	}

	function handleKeydown(e) {
		if (!dropdown || dropdown.style.display === 'none' || items.length === 0) {
			return;
		}

		if (e.key === 'ArrowDown' || e.keyCode === 40) {
			e.preventDefault();
			selectedIndex = (selectedIndex + 1) % items.length;
			updateActiveItem();
		} else if (e.key === 'ArrowUp' || e.keyCode === 38) {
			e.preventDefault();
			selectedIndex = (selectedIndex - 1 + items.length) % items.length;
			updateActiveItem();
		} else if (e.key === 'Enter' || e.keyCode === 13 || e.key === 'Tab' || e.keyCode === 9) {
			e.preventDefault();
			if (items[selectedIndex]) {
				selectUser(items[selectedIndex]);
			}
		} else if (e.key === 'Escape' || e.keyCode === 27) {
			e.preventDefault();
			hideDropdown();
		}
	}

	function updateActiveItem() {
		if (!dropdown) return;
		var listItems = dropdown.querySelectorAll('.mention-item');
		listItems.forEach(function (el, idx) {
			if (idx === selectedIndex) {
				el.classList.add('active');
				el.scrollIntoView({ block: 'nearest' });
			} else {
				el.classList.remove('active');
			}
		});
	}

	function attachToTextarea(textarea) {
		if (textarea.dataset.mentionAttached) return;
		textarea.dataset.mentionAttached = 'true';

		textarea.addEventListener('input', handleInput);
		textarea.addEventListener('keydown', handleKeydown);
		textarea.addEventListener('click', function () {
			if (dropdown && dropdown.style.display !== 'none') {
				hideDropdown();
			}
		});
	}

	// Initialize on page load
	function init() {
		createDropdown();

		// Target standard phpBB posting & quickreply textareas
		var textareas = document.querySelectorAll('textarea[name="message"], #message');
		textareas.forEach(attachToTextarea);

		// Monitor DOM for dynamic elements (e.g. quick reply expansion)
		if (window.MutationObserver) {
			var observer = new MutationObserver(function (mutations) {
				mutations.forEach(function (mutation) {
					if (mutation.addedNodes.length) {
						var added = document.querySelectorAll('textarea[name="message"], #message');
						added.forEach(attachToTextarea);
					}
				});
			});
			observer.observe(document.body, { childList: true, subtree: true });
		}

		// Close dropdown on click outside
		document.addEventListener('click', function (e) {
			if (dropdown && dropdown.style.display !== 'none') {
				if (!dropdown.contains(e.target) && e.target !== activeTextarea) {
					hideDropdown();
				}
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
