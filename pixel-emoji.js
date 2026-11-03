// Turns emoji in the beeps into two-tone pixel art, as if drawn on the pager's LCD.
// Each emoji is drawn small by the device's own emoji font, reduced to 16x16,
// turned into an outline with dark details, and shown enlarged without smoothing.
(function () {
	var N = 16;
	var EMOJI = /\p{Regional_Indicator}{2}|(?:\p{Emoji_Presentation}|\p{Extended_Pictographic}️)\p{Emoji_Modifier}?(?:‍(?:\p{Emoji_Presentation}|\p{Extended_Pictographic}️?)\p{Emoji_Modifier}?)*/gu;
	var HAS_EMOJI = new RegExp(EMOJI.source, 'u');
	var BAYER = [0, 8, 2, 10, 12, 4, 14, 6, 3, 11, 1, 9, 15, 7, 13, 5];   // dot pattern for mid tones
	var cache = {};

	// dark pixel colour = the page's --ink, so it always matches the theme
	function ink() {
		var hex = getComputedStyle(document.documentElement).getPropertyValue('--ink').trim().replace('#', '');
		if (hex.length !== 6) hex = '1c2410';
		return [0, 2, 4].map(function (i) { return parseInt(hex.substr(i, 2), 16); });
	}

	function render(ch) {
		if (cache[ch]) return cache[ch];
		var BIG = 128, big = document.createElement('canvas');
		big.width = big.height = BIG;
		var g = big.getContext('2d');
		g.font = Math.round(BIG * 0.78) + 'px "Apple Color Emoji","Segoe UI Emoji","Noto Color Emoji","Twemoji Mozilla",sans-serif';
		g.textAlign = 'center';
		g.textBaseline = 'middle';
		g.fillText(ch, BIG / 2, BIG / 2);

		var small = document.createElement('canvas');
		small.width = small.height = N;
		var s = small.getContext('2d', { willReadFrequently: true });
		s.imageSmoothingEnabled = true;
		s.imageSmoothingQuality = 'high';
		s.drawImage(big, 0, 0, N, N);

		var img = s.getImageData(0, 0, N, N), d = img.data, c = ink();
		var alpha = [], lum = [], i;
		for (i = 0; i < N * N; i++) {
			alpha[i] = d[i * 4 + 3] / 255;
			lum[i] = (0.299 * d[i * 4] + 0.587 * d[i * 4 + 1] + 0.114 * d[i * 4 + 2]) / 255;
		}
		function solid(x, y) { return x >= 0 && y >= 0 && x < N && y < N && alpha[y * N + x] >= 0.5; }
		// LCD icon style: a one pixel outline, dark details solid, mid tones dotted, light fills left empty
		for (i = 0; i < N * N; i++) {
			var x = i % N, y = (i - x) / N, on = false;
			if (alpha[i] >= 0.5) {
				var edge = !solid(x - 1, y) || !solid(x + 1, y) || !solid(x, y - 1) || !solid(x, y + 1);
				var darkness = (0.74 - lum[i]) / 0.34;                      // 0 for light colours up to 1 for dark ones
				on = edge || darkness >= 1 || (darkness > 0 && darkness > (BAYER[(y % 4) * 4 + (x % 4)] + 0.5) / 16);
			}
			d[i * 4] = c[0]; d[i * 4 + 1] = c[1]; d[i * 4 + 2] = c[2]; d[i * 4 + 3] = on ? 255 : 0;
		}
		s.putImageData(img, 0, 0);
		return (cache[ch] = small);
	}

	function make(ch) {
		var span = document.createElement('span');
		span.className = 'emoji';
		span.setAttribute('role', 'img');
		span.setAttribute('aria-label', ch);
		span.title = ch;
		var cv = document.createElement('canvas');
		cv.width = cv.height = N;
		cv.getContext('2d').drawImage(render(ch), 0, 0);
		span.appendChild(cv);
		var orig = document.createElement('span');          // the device's own emoji, hidden until the button is pressed
		orig.className = 'orig';
		orig.setAttribute('aria-hidden', 'true');
		orig.textContent = ch;
		span.appendChild(orig);
		return span;
	}

	function convert(root) {
		var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT), nodes = [], n;
		while ((n = walker.nextNode())) if (HAS_EMOJI.test(n.nodeValue)) nodes.push(n);
		nodes.forEach(function (node) {
			var text = node.nodeValue, frag = document.createDocumentFragment(), last = 0, m;
			EMOJI.lastIndex = 0;
			while ((m = EMOJI.exec(text))) {
				frag.appendChild(document.createTextNode(text.slice(last, m.index)));
				frag.appendChild(make(m[0]));
				last = m.index + m[0].length;
			}
			frag.appendChild(document.createTextNode(text.slice(last)));
			node.parentNode.replaceChild(frag, node);
		});
		if (nodes.length) addToggle(root);
	}

	// A small button after a beep that has emoji: flips between the pixel art and the original emoji
	function addToggle(root) {
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'emoji-toggle';
		function label(original) {
			btn.textContent = original ? '8-bit' : 'orig';
			btn.title = btn.ariaLabel = original ? 'Show pixel emoji' : 'Show original emoji';
			btn.setAttribute('aria-pressed', original ? 'true' : 'false');
		}
		label(false);
		btn.addEventListener('click', function () { label(root.classList.toggle('show-original')); });
		root.appendChild(document.createTextNode(' '));
		root.appendChild(btn);
	}

	function run() {
		try { document.querySelectorAll('.board-table td:nth-child(2), .notice strong').forEach(convert); } catch (e) { /* leave the normal emoji */ }
	}
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run); else run();
})();
