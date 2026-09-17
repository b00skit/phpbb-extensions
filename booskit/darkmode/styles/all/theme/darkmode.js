/**
 * Booskit Dark Mode - Smart Color-Preserving Darkening Engine
 *
 * 1. Preserves existing dark colors (e.g. dark green, dark navy) without alteration.
 * 2. Neutral-aware: Detects true neutrals (e.g. #fafcfe, white, gray) to prevent tint artifacts.
 * 3. Chromatic preservation: Real colors retain their exact hue when darkened.
 * 4. WCAG 2.1 Contrast Guarantee: Text is dynamically brightened with !important if contrast < 4.5:1.
 *
 * @package booskit/darkmode
 * @license MIT
 */
(function(window, document) {
	'use strict';

	var STORAGE_KEY = 'booskit_darkmode';
	var COOKIE_NAME = 'booskit_darkmode';
	var observer = null;

	// =========================================================================
	// Color Space Utilities (RGB, HSL, Luminance, Contrast)
	// =========================================================================

	function parseColor(colorStr) {
		if (!colorStr || colorStr === 'transparent' || colorStr === 'inherit') {
			return null;
		}
		var match = colorStr.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/);
		if (match) {
			var a = match[4] !== undefined ? parseFloat(match[4]) : 1;
			if (a === 0) return null;
			return {
				r: parseInt(match[1], 10),
				g: parseInt(match[2], 10),
				b: parseInt(match[3], 10),
				a: a
			};
		}
		return null;
	}

	function rgbToHsl(r, g, b) {
		var rf = r / 255, gf = g / 255, bf = b / 255;
		var max = Math.max(rf, gf, bf), min = Math.min(rf, gf, bf);
		var h = 0, s = 0, l = (max + min) / 2;
		var chroma = max - min;

		// Perceptual neutral check: if RGB delta is under ~18/255 (0.07),
		// it is achromatic (white, off-white, gray, black).
		if (chroma < 0.07) {
			return { h: 0, s: 0, l: l, isNeutral: true };
		}

		s = l > 0.5 ? chroma / (2 - max - min) : chroma / (max + min);
		switch (max) {
			case rf: h = (gf - bf) / chroma + (gf < bf ? 6 : 0); break;
			case gf: h = (bf - rf) / chroma + 2; break;
			case bf: h = (rf - gf) / chroma + 4; break;
		}
		h /= 6;
		return { h: Math.round(h * 360), s: s, l: l, isNeutral: false };
	}

	function hslToRgb(h, s, l) {
		h = ((h % 360) + 360) % 360 / 360;

		if (s === 0) {
			var val = Math.round(l * 255);
			return { r: val, g: val, b: val };
		}

		function hue2rgb(p, q, t) {
			if (t < 0) t += 1;
			if (t > 1) t -= 1;
			if (t < 1/6) return p + (q - p) * 6 * t;
			if (t < 1/2) return q;
			if (t < 2/3) return p + (q - p) * (2/3 - t) * 6;
			return p;
		}

		var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
		var p = 2 * l - q;
		var r = hue2rgb(p, q, h + 1/3);
		var g = hue2rgb(p, q, h);
		var b = hue2rgb(p, q, h - 1/3);

		return {
			r: Math.round(r * 255),
			g: Math.round(g * 255),
			b: Math.round(b * 255)
		};
	}

	// WCAG Relative Luminance
	function getRelativeLuminance(r, g, b) {
		var rs = r / 255, gs = g / 255, bs = b / 255;
		var R = (rs <= 0.03928) ? rs / 12.92 : Math.pow((rs + 0.055) / 1.055, 2.4);
		var G = (gs <= 0.03928) ? gs / 12.92 : Math.pow((gs + 0.055) / 1.055, 2.4);
		var B = (bs <= 0.03928) ? bs / 12.92 : Math.pow((bs + 0.055) / 1.055, 2.4);
		return 0.2126 * R + 0.7152 * G + 0.0722 * B;
	}

	// WCAG Contrast Ratio
	function getContrastRatio(lum1, lum2) {
		var l1 = Math.max(lum1, lum2);
		var l2 = Math.min(lum1, lum2);
		return (l1 + 0.05) / (l2 + 0.05);
	}

	function isIgnoredElement(el) {
		if (!el || el.nodeType !== 1) return true;
		var tag = el.tagName.toLowerCase();
		if (tag === 'img' || tag === 'video' || tag === 'picture' || tag === 'canvas' ||
		    tag === 'iframe' || tag === 'svg' || tag === 'embed' || tag === 'object' ||
		    tag === 'script' || tag === 'style' || tag === 'link') {
			return true;
		}
		if (el.classList && (
			el.classList.contains('avatar') ||
			el.classList.contains('header-avatar') ||
			el.classList.contains('no-avatar') ||
			el.classList.contains('darkmode-toggle-btn')
		)) {
			return true;
		}
		return false;
	}

	function getEffectiveBgColor(el) {
		var current = el;
		while (current && current !== document) {
			var style = window.getComputedStyle(current);
			var bg = parseColor(style.backgroundColor);
			if (bg && bg.a > 0.2) {
				return bg;
			}
			current = current.parentElement;
		}
		return { r: 30, g: 33, b: 37, a: 1 };
	}

	// =========================================================================
	// Smart Color Adaptation
	// =========================================================================

	function processElement(el) {
		if (isIgnoredElement(el)) {
			return;
		}

		var style = window.getComputedStyle(el);

		// 1. Background adaptation
		var bgRgb = parseColor(style.backgroundColor);
		var effectiveBgRgb = bgRgb;

		if (bgRgb && bgRgb.a > 0.05) {
			var bgHsl = rgbToHsl(bgRgb.r, bgRgb.g, bgRgb.b);

			// If already dark (L <= 0.32, such as dark green, dark navy, dark charcoal):
			// LEAVE IT COMPLETELY UNTOUCHED!
			if (bgHsl.l > 0.32) {
				if (!el.dataset.dmOrigBg) {
					el.dataset.dmOrigBg = el.style.backgroundColor || '__EMPTY__';
				}

				var newRgb;
				if (bgHsl.isNeutral) {
					// Truly neutral light colors (white, off-white #fafcfe, light gray)
					// map to dark neutral charcoal, NOT navy blue!
					newRgb = { r: 30, g: 33, b: 37 };
				} else {
					// Actual chromatic color: preserve exact hue, lower lightness
					var targetL = Math.min(0.18, Math.max(0.12, bgHsl.l * 0.18));
					newRgb = hslToRgb(bgHsl.h, Math.min(bgHsl.s, 0.45), targetL);
				}

				el.style.setProperty('background-color', 'rgb(' + newRgb.r + ', ' + newRgb.g + ', ' + newRgb.b + ')', 'important');
				effectiveBgRgb = newRgb;
			}
		}

		if (!effectiveBgRgb || effectiveBgRgb.a < 0.2) {
			effectiveBgRgb = getEffectiveBgColor(el);
		}

		// 2. Text adaptation for WCAG readability
		var textRgb = parseColor(style.color);
		if (textRgb && effectiveBgRgb) {
			var bgLum = getRelativeLuminance(effectiveBgRgb.r, effectiveBgRgb.g, effectiveBgRgb.b);
			var textLum = getRelativeLuminance(textRgb.r, textRgb.g, textRgb.b);
			var contrast = getContrastRatio(bgLum, textLum);

			// If contrast is below 4.5:1 (unreadable on dark background), boost text brightness
			if (contrast < 4.5) {
				if (!el.dataset.dmOrigColor) {
					el.dataset.dmOrigColor = el.style.color || '__EMPTY__';
				}

				var textHsl = rgbToHsl(textRgb.r, textRgb.g, textRgb.b);
				var readableRgb;

				if (textHsl.isNeutral) {
					// Neutral text (black, dark gray) becomes crisp off-white
					readableRgb = { r: 232, g: 235, b: 240 };
				} else {
					// Colored text (e.g. blue link, red admin, green mod, gold title):
					// Keep exact hue, boost lightness to high-contrast readable range
					readableRgb = hslToRgb(textHsl.h, Math.min(textHsl.s, 0.75), 0.78);
				}

				el.style.setProperty('color', 'rgb(' + readableRgb.r + ', ' + readableRgb.g + ', ' + readableRgb.b + ')', 'important');
			}
		}

		// 3. Border adaptation
		var borderRgb = parseColor(style.borderColor);
		if (borderRgb && borderRgb.a > 0.05) {
			var borderHsl = rgbToHsl(borderRgb.r, borderRgb.g, borderRgb.b);
			if (borderHsl.l > 0.35) {
				if (!el.dataset.dmOrigBorder) {
					el.dataset.dmOrigBorder = el.style.borderColor || '__EMPTY__';
				}
				if (borderHsl.isNeutral) {
					el.style.setProperty('border-color', 'rgba(255, 255, 255, 0.12)', 'important');
				} else {
					var newBorderRgb = hslToRgb(borderHsl.h, Math.min(borderHsl.s, 0.35), 0.24);
					el.style.setProperty('border-color', 'rgba(' + newBorderRgb.r + ', ' + newBorderRgb.g + ', ' + newBorderRgb.b + ', 0.6)', 'important');
				}
			}
		}
	}

	function runSmartAdaptation(rootNode) {
		var elements = (rootNode || document.body).querySelectorAll('*');
		for (var i = 0; i < elements.length; i++) {
			processElement(elements[i]);
		}
	}

	function revertSmartAdaptation() {
		var modifiedElements = document.querySelectorAll('[data-dm-orig-bg], [data-dm-orig-color], [data-dm-orig-border]');
		for (var i = 0; i < modifiedElements.length; i++) {
			var el = modifiedElements[i];
			if (el.dataset.dmOrigBg !== undefined) {
				if (el.dataset.dmOrigBg === '__EMPTY__') {
					el.style.removeProperty('background-color');
				} else {
					el.style.backgroundColor = el.dataset.dmOrigBg;
				}
				delete el.dataset.dmOrigBg;
			}
			if (el.dataset.dmOrigColor !== undefined) {
				if (el.dataset.dmOrigColor === '__EMPTY__') {
					el.style.removeProperty('color');
				} else {
					el.style.color = el.dataset.dmOrigColor;
				}
				delete el.dataset.dmOrigColor;
			}
			if (el.dataset.dmOrigBorder !== undefined) {
				if (el.dataset.dmOrigBorder === '__EMPTY__') {
					el.style.removeProperty('border-color');
				} else {
					el.style.borderColor = el.dataset.dmOrigBorder;
				}
				delete el.dataset.dmOrigBorder;
			}
		}
	}

	function startObserver() {
		if (observer || !window.MutationObserver) return;
		observer = new MutationObserver(function(mutations) {
			if (!isDarkModeEnabled()) return;
			mutations.forEach(function(mutation) {
				for (var i = 0; i < mutation.addedNodes.length; i++) {
					var node = mutation.addedNodes[i];
					if (node.nodeType === 1) {
						processElement(node);
						runSmartAdaptation(node);
					}
				}
			});
		});
		observer.observe(document.body, { childList: true, subtree: true });
	}

	function stopObserver() {
		if (observer) {
			observer.disconnect();
			observer = null;
		}
	}

	function getCookie(name) {
		var match = document.cookie.match(new RegExp('(?:^|;\\s*)' + name + '=([^;]*)'));
		return match ? decodeURIComponent(match[1]) : null;
	}

	function setCookie(name, val, days) {
		var expires = '';
		if (days) {
			var date = new Date();
			date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
			expires = '; expires=' + date.toUTCString();
		}
		document.cookie = name + '=' + encodeURIComponent(val) + expires + '; path=/; SameSite=Lax';

		// Also set cookie with any phpBB cookie prefix if detectable
		var allCookies = document.cookie.split(';');
		for (var i = 0; i < allCookies.length; i++) {
			var c = allCookies[i].trim();
			var match = c.match(/^([a-zA-Z0-9_-]+)_(?:sid|u|k)=/);
			if (match && match[1]) {
				var prefix = match[1];
				document.cookie = prefix + '_' + name + '=' + encodeURIComponent(val) + expires + '; path=/; SameSite=Lax';
			}
		}
	}

	function isDarkModeEnabled() {
		try {
			var localVal = localStorage.getItem(STORAGE_KEY);
			if (localVal !== null) {
				return localVal === 'true';
			}
		} catch (e) {}

		var cookieVal = getCookie(COOKIE_NAME);
		if (cookieVal !== null) {
			return cookieVal === '1';
		}

		return false;
	}

	function applyTheme(isDark) {
		var root = document.documentElement;
		if (isDark) {
			root.setAttribute('data-theme', 'dark');
			root.classList.add('dark-mode');
			if (document.body) {
				runSmartAdaptation(document.body);
				startObserver();
			}
		} else {
			root.removeAttribute('data-theme');
			root.classList.remove('dark-mode');
			stopObserver();
			revertSmartAdaptation();
		}

		updateButtonsUI(isDark);
	}

	function updateButtonsUI(isDark) {
		var buttons = document.querySelectorAll('.darkmode-toggle-btn');
		buttons.forEach(function(btn) {
			var enableText = btn.getAttribute('data-enable') || 'Enable dark mode';
			var disableText = btn.getAttribute('data-disable') || 'Disable dark mode';
			var textSpan = btn.querySelector('.darkmode-text');
			var icon = btn.querySelector('.darkmode-icon');

			if (textSpan) {
				textSpan.textContent = isDark ? disableText : enableText;
			}

			if (icon) {
				if (isDark) {
					icon.className = 'icon fa-sun-o fa-fw darkmode-icon';
				} else {
					icon.className = 'icon fa-moon-o fa-fw darkmode-icon';
				}
			}

			btn.setAttribute('title', isDark ? disableText : enableText);
		});
	}

	function setDarkMode(enabled) {
		try {
			localStorage.setItem(STORAGE_KEY, enabled ? 'true' : 'false');
		} catch (e) {}

		setCookie(COOKIE_NAME, enabled ? '1' : '0', 365);
		applyTheme(enabled);
	}

	function toggleDarkMode(e) {
		if (e) {
			e.preventDefault();
			e.stopPropagation();
		}
		var current = isDarkModeEnabled();
		setDarkMode(!current);
	}

	function ensureElementsInserted() {
		var loggedInDropdown = document.querySelector('#username_logged_in .dropdown-contents');
		var existingUserBtn = document.getElementById('darkmode-toggle-user');

		if (loggedInDropdown && !existingUserBtn) {
			var li = document.createElement('li');
			li.id = 'darkmode-toggle-user-container';

			var a = document.createElement('a');
			a.href = 'javascript:void(0);';
			a.id = 'darkmode-toggle-user';
			a.className = 'darkmode-toggle-btn';
			a.setAttribute('role', 'menuitem');
			a.setAttribute('data-enable', 'Enable dark mode');
			a.setAttribute('data-disable', 'Disable dark mode');

			var i = document.createElement('i');
			i.className = 'icon fa-moon-o fa-fw darkmode-icon';
			i.setAttribute('aria-hidden', 'true');

			var span = document.createElement('span');
			span.className = 'darkmode-text';
			span.textContent = 'Enable dark mode';

			a.appendChild(i);
			a.appendChild(span);
			li.appendChild(a);

			var separator = loggedInDropdown.querySelector('li.separator');
			var logoutLink = loggedInDropdown.querySelector('a[href*="mode=logout"]');
			var logoutLi = logoutLink ? logoutLink.closest('li') : null;

			if (separator) {
				loggedInDropdown.insertBefore(li, separator);
			} else if (logoutLi) {
				loggedInDropdown.insertBefore(li, logoutLi);
			} else {
				loggedInDropdown.appendChild(li);
			}
		}

		var isGuest = !document.getElementById('username_logged_in');
		var existingGuestBtn = document.getElementById('darkmode-toggle-guest');

		if (isGuest && !existingGuestBtn) {
			var headerMenu = document.querySelector('.inventea-user-menu') ||
			                 document.querySelector('ul#nav-main') ||
			                 document.querySelector('.navbar .linklist') ||
			                 document.querySelector('#nav-breadcrumbs');

			if (headerMenu) {
				var guestLi = document.createElement('li');
				guestLi.className = 'rightside small-icon darkmode-guest-container';
				guestLi.setAttribute('data-skip-responsive', 'true');

				var guestA = document.createElement('a');
				guestA.href = 'javascript:void(0);';
				guestA.id = 'darkmode-toggle-guest';
				guestA.className = 'darkmode-toggle-btn';
				guestA.setAttribute('role', 'menuitem');
				guestA.setAttribute('data-enable', 'Enable dark mode');
				guestA.setAttribute('data-disable', 'Disable dark mode');

				var guestI = document.createElement('i');
				guestI.className = 'icon fa-moon-o fa-fw darkmode-icon';
				guestI.setAttribute('aria-hidden', 'true');

				var guestSpan = document.createElement('span');
				guestSpan.className = 'darkmode-text';
				guestSpan.textContent = 'Enable dark mode';

				guestA.appendChild(guestI);
				guestA.appendChild(guestSpan);
				guestLi.appendChild(guestA);

				var loginLink = headerMenu.querySelector('a[href*="mode=login"]');
				var loginLi = loginLink ? loginLink.closest('li') : null;

				if (loginLi && loginLi.parentNode === headerMenu) {
					headerMenu.insertBefore(guestLi, loginLi);
				} else {
					headerMenu.appendChild(guestLi);
				}
			}
		}
	}

	function init() {
		ensureElementsInserted();

		var isDark = isDarkModeEnabled();
		applyTheme(isDark);

		document.addEventListener('click', function(e) {
			var toggleBtn = e.target.closest('.darkmode-toggle-btn');
			if (toggleBtn) {
				toggleDarkMode(e);
			}
		});

		setTimeout(function() {
			document.documentElement.classList.add('darkmode-ready');
		}, 100);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

})(window, document);
