(function () {
	'use strict';

	if (!window.enewsBuilderV2 || !document.getElementById('enews-builder-v2-app')) {
		return;
	}

	var config = window.enewsBuilderV2;
	var modules = config.modules || {};
	var presets = config.presets || {};
	var paletteEl = document.getElementById('enews-builder-v2-palette');
	var moduleSearchEl = document.getElementById('enews-builder-v2-module-search');
	var canvasEl = document.getElementById('enews-builder-v2-canvas');
	var settingsEl = document.getElementById('enews-builder-v2-settings-panel');
	var selectionMetaEl = document.getElementById('enews-builder-v2-selection-meta');
	var previewEl = document.getElementById('enews-builder-v2-preview');
	var stateInputEl = document.getElementById('builder_state_json');
	var formEl = document.getElementById('enews-builder-v2-form');
	var subjectInputEl = document.getElementById('enews-builder-v2-subject');
	var presetsEl = document.getElementById('enews-builder-v2-presets');
	var savePresetButtonEl = document.getElementById('enews-builder-v2-save-preset');
	var deletePresetButtonEl = document.getElementById('enews-builder-v2-delete-preset');
	var sendTestButtonEl = document.getElementById('enews-builder-v2-send-test');
	var sendTestEmailEl = document.getElementById('enews-builder-v2-preview-email');
	var sendTestStatusEl = document.getElementById('enews-builder-v2-send-test-status');
	var undoButtonEl = document.getElementById('enews-builder-v2-undo');
	var redoButtonEl = document.getElementById('enews-builder-v2-redo');
	var viewDesktopButtonEl = document.getElementById('enews-builder-v2-view-desktop');
	var viewMobileButtonEl = document.getElementById('enews-builder-v2-view-mobile');
	var previewTimer = null;
	var previewRequestId = 0;
	var activeDrag = null;
	var selected = null;
	var idCounter = 0;
	var previewFrame = null;
	var previewStatusEl = null;
	var selectedPresetId = '';
	var history = [];
	var historyIndex = -1;
	var isRestoringHistory = false;
	var previewMode = 'desktop';

	var hiddenSettingKeys = {
		lock_full_width: true,
		grid_span: true,
		grid_col: true,
		grid_row: true,
		grid_rows: true,
		canvas_min_height: true
	};

	var fieldLabelMap = {
		text: 'Text',
		title: 'Titel',
		label: 'Button-Text',
		url: 'Link URL',
		button_label: 'Button-Text',
		button_url: 'Button-Link',
		background: 'Hintergrund',
		text_color: 'Textfarbe',
		button_background: 'Button-Hintergrund',
		button_color: 'Button-Farbe',
		align: 'Ausrichtung',
		image_url: 'Bild-URL',
		image_alt: 'Bild-Alt-Text',
		left_html: 'Linke Spalte (HTML)',
		right_html: 'Rechte Spalte (HTML)',
		height: 'Abstand in Pixel',
		color: 'Farbe',
		thickness: 'Linienstärke',
		query_mode: 'Datenquelle',
		query_scope: 'Zeitraum',
		query_limit: 'Anzahl',
		ids: 'IDs',
		layout: 'Layout',
		show_image: 'Bild anzeigen',
		show_price: 'Preis anzeigen',
		show_old_price: 'Statt-Preis anzeigen',
		show_button: 'Button anzeigen',
		show_badge: 'Badge anzeigen',
		badge_text: 'Badge-Text',
		button_text: 'Button-Text',
		show_excerpt: 'Auszug anzeigen',
		excerpt_words: 'Auszug-Wörter',
		company: 'Unternehmen',
		address: 'Adresse',
		legal_text: 'Rechtstext',
		manage_url: 'Profil-Link',
		view_url: 'Browser-Link',
		unsubscribe_url: 'Abmelden-Link',
		eyebrow: 'Vortitel',
		font_size: 'Schriftgröße',
		level: 'Überschriften-Level'
	};

	var fieldSelectMap = {
		align: [
			{ value: 'left', label: 'Links' },
			{ value: 'center', label: 'Zentriert' },
			{ value: 'right', label: 'Rechts' }
		],
		level: [
			{ value: 'h1', label: 'H1' },
			{ value: 'h2', label: 'H2' },
			{ value: 'h3', label: 'H3' }
		],
		query_mode: [
			{ value: 'manual', label: 'Manuell' },
			{ value: 'latest', label: 'Neueste Inhalte' },
			{ value: 'trigger', label: 'Trigger-Kontext' }
		],
		query_scope: [
			{ value: 'all', label: 'Alle' },
			{ value: 'week', label: 'Diese Woche' },
			{ value: 'month', label: 'Dieser Monat' }
		],
		layout: [
			{ value: 'single', label: 'Einzeln' },
			{ value: 'list', label: 'Liste' },
			{ value: 'grid', label: 'Grid' },
			{ value: 'links', label: 'Linkliste' },
			{ value: 'slider', label: 'Slider' }
		]
	};

	var state = normalizeState(config.state || {});
	replaceHistory(state);

	function uid(prefix) {
		idCounter += 1;
		return prefix + '_' + Date.now().toString(36) + '_' + idCounter.toString(36);
	}

	function clone(value) {
		return JSON.parse(JSON.stringify(value));
	}

	function pushHistorySnapshot() {
		if (isRestoringHistory) {
			return;
		}
		var snapshot = JSON.stringify(state);
		if (historyIndex >= 0 && history[historyIndex] === snapshot) {
			return;
		}
		history = history.slice(0, historyIndex + 1);
		history.push(snapshot);
		if (history.length > 50) {
			history.shift();
		}
		historyIndex = history.length - 1;
		updateHistoryButtons();
	}

	function replaceHistory(nextState) {
		history = [JSON.stringify(normalizeState(clone(nextState)))];
		historyIndex = 0;
		updateHistoryButtons();
	}

	function restoreHistory(direction) {
		var nextIndex = historyIndex + direction;
		if (nextIndex < 0 || nextIndex >= history.length) {
			return;
		}
		isRestoringHistory = true;
		historyIndex = nextIndex;
		state = normalizeState(JSON.parse(history[historyIndex]));
		isRestoringHistory = false;
		renderAll();
		saveStateToInput();
		schedulePreview();
		updateHistoryButtons();
	}

	function updateHistoryButtons() {
		if (undoButtonEl) undoButtonEl.disabled = historyIndex <= 0;
		if (redoButtonEl) redoButtonEl.disabled = historyIndex >= history.length - 1;
	}

	function escapeHtml(value) {
		return String(value == null ? '' : value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function t(key, fallback) {
		return (config.l10n && config.l10n[key]) ? config.l10n[key] : fallback;
	}

	function normalizeState(raw) {
		var defaults = {
			global: {
				subject: '',
				email_title: '',
				full_width: '0',
				content_width: 600,
				background_color: '#eef2f7',
				content_background: '#ffffff',
				text_color: '#374151',
				font_family: 'Arial, sans-serif',
				font_css_url: '',
				section_gap: 20,
				branding_html: '',
				contact_info: '',
				view_browser_html: ''
			},
			sections: [],
			modules: []
		};

		var next = Object.assign({}, defaults, raw || {});
		next.global = Object.assign({}, defaults.global, (raw && raw.global) || {});
		next.global.full_width = String(next.global.full_width) === '1' ? '1' : '0';
		next.global.content_width = Math.max(420, Math.min(760, parseInt(next.global.content_width, 10) || 600));
		next.global.section_gap = Math.max(0, Math.min(60, parseInt(next.global.section_gap, 10) || 20));

		if (Array.isArray(raw && raw.sections) && raw.sections.length) {
			next.sections = sanitizeSections(raw.sections);
		} else {
			next.sections = modulesToSections(Array.isArray(raw && raw.modules) ? raw.modules : []);
		}

		return next;
	}

	function sanitizeSections(sections) {
		var nextSections = [];
		sections.forEach(function (section) {
			var rows = Array.isArray(section && section.rows) ? section.rows : [];
			var nextRows = [];
			rows.forEach(function (row) {
				var columns = Array.isArray(row && row.columns) ? row.columns : [];
				var nextCols = [];
				columns.forEach(function (column) {
					var blocks = Array.isArray(column && column.blocks) ? column.blocks : [];
					var nextBlocks = [];
					blocks.forEach(function (block) {
						var type = block && modules[block.type] ? block.type : null;
						if (!type) {
							return;
						}
						nextBlocks.push({
							id: block.id || uid('blk'),
							type: type,
							settings: Object.assign({}, clone(modules[type].defaults || {}), block.settings || {})
						});
					});

					nextCols.push({
						id: (column && column.id) || uid('col'),
						width: sanitizeWidth(column && column.width, columns.length),
						blocks: nextBlocks
					});
				});

				if (!nextCols.length) {
					nextCols.push(createColumn(100));
				}

				nextRows.push({
					id: (row && row.id) || uid('row'),
					gap: Math.max(0, Math.min(40, parseInt(row && row.gap, 10) || 10)),
					columns: normalizeColumnWidths(nextCols)
				});
			});

			if (!nextRows.length) {
				nextRows.push(createRow(1));
			}

			nextSections.push({
				id: (section && section.id) || uid('sec'),
				rows: nextRows
			});
		});

		if (!nextSections.length) {
			nextSections.push(createSection());
		}

		return nextSections;
	}

	function modulesToSections(rawModules) {
		var section = createSection();
		var col = section.rows[0].columns[0];
		(rawModules || []).forEach(function (module) {
			if (!module || !modules[module.type]) {
				return;
			}
			col.blocks.push({
				id: module.id || uid('blk'),
				type: module.type,
				settings: Object.assign({}, clone(modules[module.type].defaults || {}), module.settings || {})
			});
		});
		return [section];
	}

	function sanitizeWidth(value, columnCount) {
		var width = parseFloat(value);
		if (!isFinite(width) || width <= 0) {
			return Math.round((100 / Math.max(1, columnCount)) * 100) / 100;
		}
		return Math.max(10, Math.min(100, width));
	}

	function normalizeColumnWidths(columns) {
		var total = 0;
		columns.forEach(function (column) {
			total += sanitizeWidth(column.width, columns.length);
		});
		if (total <= 0) {
			return columns;
		}
		return columns.map(function (column) {
			column.width = Math.round((sanitizeWidth(column.width, columns.length) / total) * 10000) / 100;
			return column;
		});
	}

	function createColumn(width) {
		return {
			id: uid('col'),
			width: width || 100,
			blocks: []
		};
	}

	function createRow(columnCount) {
		var count = Math.max(1, Math.min(3, parseInt(columnCount, 10) || 1));
		var width = 100 / count;
		var columns = [];
		for (var i = 0; i < count; i += 1) {
			columns.push(createColumn(width));
		}
		return {
			id: uid('row'),
			gap: 10,
			columns: normalizeColumnWidths(columns)
		};
	}

	function createSection() {
		return {
			id: uid('sec'),
			rows: [createRow(1)]
		};
	}

	function getFirstColumnPath() {
		if (!state.sections.length) {
			state.sections.push(createSection());
		}
		return { section: 0, row: 0, column: 0 };
	}

	function addBlock(type, columnPath, atIndex) {
		if (!modules[type]) {
			return;
		}
		var path = columnPath || (selected ? { section: selected.section, row: selected.row, column: selected.column } : getFirstColumnPath());
		var column = getColumn(path);
		if (!column) {
			return;
		}
		var block = {
			id: uid('blk'),
			type: type,
			settings: clone(modules[type].defaults || {})
		};
		var index = typeof atIndex === 'number' ? Math.max(0, Math.min(column.blocks.length, atIndex)) : column.blocks.length;
		column.blocks.splice(index, 0, block);
		selected = { section: path.section, row: path.row, column: path.column, blockId: block.id };
		afterStateChange();
	}

	function getColumn(path) {
		if (!path) {
			return null;
		}
		var section = state.sections[path.section];
		if (!section) {
			return null;
		}
		var row = section.rows[path.row];
		if (!row) {
			return null;
		}
		return row.columns[path.column] || null;
	}

	function getBlockPathById(blockId) {
		for (var s = 0; s < state.sections.length; s += 1) {
			var section = state.sections[s];
			for (var r = 0; r < section.rows.length; r += 1) {
				var row = section.rows[r];
				for (var c = 0; c < row.columns.length; c += 1) {
					var column = row.columns[c];
					for (var b = 0; b < column.blocks.length; b += 1) {
						if (column.blocks[b].id === blockId) {
							return { section: s, row: r, column: c, block: b };
						}
					}
				}
			}
		}
		return null;
	}

	function moveBlock(sourcePath, targetPath) {
		if (!sourcePath || !targetPath) {
			return;
		}
		var sourceColumn = getColumn(sourcePath);
		var targetColumn = getColumn(targetPath);
		if (!sourceColumn || !targetColumn) {
			return;
		}
		if (!sourceColumn.blocks[sourcePath.block]) {
			return;
		}

		var block = sourceColumn.blocks[sourcePath.block];
		sourceColumn.blocks.splice(sourcePath.block, 1);

		var insertAt = Math.max(0, Math.min(targetColumn.blocks.length, parseInt(targetPath.index, 10) || 0));
		if (sourcePath.section === targetPath.section && sourcePath.row === targetPath.row && sourcePath.column === targetPath.column && sourcePath.block < insertAt) {
			insertAt -= 1;
		}

		targetColumn.blocks.splice(insertAt, 0, block);
		selected = { section: targetPath.section, row: targetPath.row, column: targetPath.column, blockId: block.id };
		afterStateChange();
	}

	function removeBlock(blockId) {
		var path = getBlockPathById(blockId);
		if (!path) {
			return;
		}
		var col = getColumn(path);
		if (!col) {
			return;
		}
		col.blocks.splice(path.block, 1);
		selected = null;
		afterStateChange();
	}

	function duplicateBlock(blockId) {
		var path = getBlockPathById(blockId);
		if (!path) {
			return;
		}
		var col = getColumn(path);
		if (!col) {
			return;
		}
		var cloned = clone(col.blocks[path.block]);
		cloned.id = uid('blk');
		col.blocks.splice(path.block + 1, 0, cloned);
		selected = { section: path.section, row: path.row, column: path.column, blockId: cloned.id };
		afterStateChange();
	}

	function moveBlockByStep(blockId, step) {
		var path = getBlockPathById(blockId);
		if (!path) {
			return;
		}
		var col = getColumn(path);
		if (!col) {
			return;
		}
		var targetIndex = path.block + step;
		if (targetIndex < 0 || targetIndex >= col.blocks.length) {
			return;
		}
		var item = col.blocks[path.block];
		col.blocks.splice(path.block, 1);
		col.blocks.splice(targetIndex, 0, item);
		selected = { section: path.section, row: path.row, column: path.column, blockId: item.id };
		afterStateChange();
	}

	function addSection() {
		state.sections.push(createSection());
		afterStateChange();
	}

	function deleteSection(sectionIndex) {
		if (state.sections.length <= 1) {
			state.sections[0] = createSection();
		} else {
			state.sections.splice(sectionIndex, 1);
		}
		selected = null;
		afterStateChange();
	}

	function addRow(sectionIndex, columns) {
		var section = state.sections[sectionIndex];
		if (!section) {
			return;
		}
		section.rows.push(createRow(columns || 1));
		afterStateChange();
	}

	function deleteRow(sectionIndex, rowIndex) {
		var section = state.sections[sectionIndex];
		if (!section) {
			return;
		}
		if (section.rows.length <= 1) {
			section.rows[0] = createRow(1);
		} else {
			section.rows.splice(rowIndex, 1);
		}
		selected = null;
		afterStateChange();
	}

	function setColumnPreset(sectionIndex, rowIndex, preset) {
		var row = state.sections[sectionIndex] && state.sections[sectionIndex].rows[rowIndex];
		if (!row) {
			return;
		}
		var layout = [];
		if (preset === '1') {
			layout = [100];
		} else if (preset === '2') {
			layout = [50, 50];
		} else if (preset === '3') {
			layout = [33.33, 33.34, 33.33];
		} else if (preset === '37') {
			layout = [30, 70];
		} else if (preset === '73') {
			layout = [70, 30];
		}
		if (!layout.length) {
			return;
		}

		var oldBlocks = [];
		row.columns.forEach(function (column) {
			oldBlocks = oldBlocks.concat(column.blocks || []);
		});

		row.columns = layout.map(function (width) {
			return createColumn(width);
		});
		if (row.columns[0]) {
			row.columns[0].blocks = oldBlocks;
		}
		selected = null;
		afterStateChange();
	}

	function updateGlobalSetting(key, value) {
		state.global[key] = value;
		afterStateChange();
	}

	function updateSelectedBlockSetting(key, value) {
		var block = getSelectedBlock();
		if (!block) {
			return;
		}
		block.settings[key] = value;
		afterStateChange();
	}

	function getSelectedBlock() {
		if (!selected || !selected.blockId) {
			return null;
		}
		var path = getBlockPathById(selected.blockId);
		if (!path) {
			return null;
		}
		var col = getColumn(path);
		return col && col.blocks[path.block] ? col.blocks[path.block] : null;
	}

	function afterStateChange() {
		syncSubjectIntoState();
		saveStateToInput();
		pushHistorySnapshot();
		renderAll();
		schedulePreview();
	}

	function saveStateToInput() {
		if (stateInputEl) {
			stateInputEl.value = JSON.stringify(state);
		}
	}

	function syncSubjectIntoState() {
		if (subjectInputEl) {
			state.global.subject = subjectInputEl.value || '';
		}
	}

	function createDropZone(path, index) {
		var dz = document.createElement('div');
		dz.className = 'enews-builder-v2-drop-zone';
		dz.dataset.section = String(path.section);
		dz.dataset.row = String(path.row);
		dz.dataset.column = String(path.column);
		dz.dataset.index = String(index);

		dz.addEventListener('dragover', function (event) {
			event.preventDefault();
			dz.classList.add('is-over');
		});
		dz.addEventListener('dragleave', function () {
			dz.classList.remove('is-over');
		});
		dz.addEventListener('drop', function (event) {
			event.preventDefault();
			dz.classList.remove('is-over');
			if (activeDrag && activeDrag.kind === 'palette') {
				addBlock(activeDrag.type, path, index);
				activeDrag = null;
				return;
			}
			if (activeDrag && activeDrag.kind === 'block') {
				moveBlock(activeDrag.sourcePath, {
					section: path.section,
					row: path.row,
					column: path.column,
					index: index
				});
				activeDrag = null;
			}
		});
		return dz;
	}

	function getModuleMeta(type) {
		var categoryMap = {
			preheader: 'Struktur',
			header: 'Struktur',
			hero: 'Struktur',
			separator: 'Struktur',
			spacer: 'Struktur',
			footer: 'Struktur',
			canspam: 'Struktur',
			heading: 'Inhalt',
			text: 'Inhalt',
			html: 'Inhalt',
			posts: 'Inhalt',
			columns_2: 'Layout',
			image: 'Medien',
			giphy: 'Medien',
			social: 'Medien',
			button: 'Aktion',
			cta: 'Aktion',
			products: 'Aktion'
		};

		var descriptionMap = {
			preheader: 'Kurztext vor dem eigentlichen Inhalt mit Browser-Link.',
			header: 'Logo, Titel und visueller Einstieg für die Mail.',
			hero: 'Großer Aufmacher mit Bild, Copy und CTA.',
			separator: 'Trenner für klare visuelle Abschnitte.',
			spacer: 'Erzeugt bewusst Luft zwischen Blöcken.',
			footer: 'Footer mit Firma, Adresse und Service-Links.',
			canspam: 'Rechtlicher Footer mit Manage- und Unsubscribe-Links.',
			heading: 'Größere Überschrift für Inhaltsabschnitte.',
			text: 'Freier Textblock für Copy und Einleitungen.',
			html: 'Freier HTML- oder Shortcode-Bereich.',
			posts: 'Automatisierte oder manuelle Blogpost-Auswahl.',
			columns_2: 'Zweispaltiger Inhalt in einer Zeile.',
			image: 'Einzelbild mit Link und Alt-Text.',
			giphy: 'Animiertes GIF oder Giphy-Einsatz.',
			social: 'Social Links mit Titel und Kanal-URLs.',
			button: 'Einzelner Button für klare Aktionen.',
			cta: 'Kompletter Call-to-Action-Bereich mit Copy.',
			products: 'Produktblock mit Layout und Sichtbarkeitsoptionen.'
		};

		return {
			category: categoryMap[type] || 'Weitere',
			description: descriptionMap[type] || 'Modul für den Newsletter-Canvas.'
		};
	}

	function getFilteredModuleTypes() {
		var term = moduleSearchEl ? String(moduleSearchEl.value || '').trim().toLowerCase() : '';
		return Object.keys(modules).filter(function (type) {
			var meta = getModuleMeta(type);
			var label = String(modules[type].label || type).toLowerCase();
			var haystack = [type, label, meta.category, meta.description].join(' ').toLowerCase();
			return !term || haystack.indexOf(term) !== -1;
		}).sort(function (left, right) {
			return String(modules[left].label || left).localeCompare(String(modules[right].label || right), 'de');
		});
	}

	function groupModuleTypes(types) {
		var categoryOrder = ['Struktur', 'Inhalt', 'Medien', 'Aktion', 'Layout', 'Weitere'];
		var groups = {};
		types.forEach(function (type) {
			var category = getModuleMeta(type).category;
			if (!groups[category]) {
				groups[category] = [];
			}
			groups[category].push(type);
		});
		return categoryOrder.filter(function (category) {
			return Array.isArray(groups[category]) && groups[category].length;
		}).map(function (category) {
			return { category: category, items: groups[category] };
		});
	}

	function applyPresetState(presetId) {
		if (!presetId || !presets[presetId] || !presets[presetId].state) return;
		if (!window.confirm(t('applyPresetConfirm', 'Aktuelles Layout ersetzen?'))) return;
		selectedPresetId = presetId;
		state = normalizeState(clone(presets[presetId].state));
		selected = null;
		replaceHistory(state);
		afterStateChange();
		renderPresets();
	}

	function renderPalette() {
		if (!paletteEl) {
			return;
		}
		paletteEl.innerHTML = '';
		var types = getFilteredModuleTypes();
		if (!types.length) {
			var empty = document.createElement('div');
			empty.className = 'enews-builder-v2-palette-empty';
			empty.textContent = 'Keine Module für diesen Filter gefunden.';
			paletteEl.appendChild(empty);
			return;
		}

		groupModuleTypes(types).forEach(function (group) {
			var groupEl = document.createElement('div');
			groupEl.className = 'enews-builder-v2-palette-group';
			var titleEl = document.createElement('h4');
			titleEl.className = 'enews-builder-v2-palette-group-title';
			titleEl.textContent = group.category;
			groupEl.appendChild(titleEl);

			var gridEl = document.createElement('div');
			gridEl.className = 'enews-builder-v2-palette-grid';

			group.items.forEach(function (type) {
				var meta = getModuleMeta(type);
				var item = document.createElement('button');
				item.type = 'button';
				item.className = 'enews-builder-v2-palette-item';
				item.dataset.moduleType = type;
				item.draggable = true;
				item.innerHTML = '' +
					'<span class="enews-builder-v2-palette-icon">' + escapeHtml(modules[type].icon || type.substring(0, 2).toUpperCase()) + '</span>' +
					'<span>' +
						'<span class="enews-builder-v2-palette-label">' + escapeHtml(modules[type].label || type) + '</span>' +
						'<span class="enews-builder-v2-palette-copy">' + escapeHtml(meta.description) + '</span>' +
					'</span>';

				item.addEventListener('click', function () {
					addBlock(type);
				});

				item.addEventListener('dragstart', function (event) {
					activeDrag = { kind: 'palette', type: type };
					event.dataTransfer.setData('text/enews-module-type', type);
					event.dataTransfer.effectAllowed = 'copy';
				});

				item.addEventListener('dragend', function () {
					if (activeDrag && activeDrag.kind === 'palette') {
						activeDrag = null;
					}
				});

				gridEl.appendChild(item);
			});

			groupEl.appendChild(gridEl);
			paletteEl.appendChild(groupEl);
		});
	}

	function renderCanvas() {
		canvasEl.innerHTML = '';
		if (!state.sections.length) {
			state.sections.push(createSection());
		}

		state.sections.forEach(function (section, sectionIndex) {
			var sectionCard = document.createElement('div');
			sectionCard.className = 'enews-builder-v2-section';
			sectionCard.innerHTML = '' +
				'<div class="enews-builder-v2-section-head">' +
				'<div class="enews-builder-v2-section-title"><strong>Section ' + (sectionIndex + 1) + '</strong><span class="enews-builder-v2-settings-box-copy">Container für ein oder mehrere Rows</span></div>' +
				'<div class="enews-builder-v2-inline-actions">' +
				'<button type="button" data-act="add-row" class="button button-small">+ Row</button>' +
				'<button type="button" data-act="delete-section" class="button button-small">Section löschen</button>' +
				'</div>' +
				'</div>';

			sectionCard.querySelector('[data-act="add-row"]').addEventListener('click', function () {
				addRow(sectionIndex, 1);
			});
			sectionCard.querySelector('[data-act="delete-section"]').addEventListener('click', function () {
				deleteSection(sectionIndex);
			});

			section.rows.forEach(function (row, rowIndex) {
				var rowEl = document.createElement('div');
				rowEl.className = 'enews-builder-v2-row';
				var preset = getRowPresetValue(row.columns);
				rowEl.innerHTML = '' +
					'<div class="enews-builder-v2-row-head">' +
					'<div class="enews-builder-v2-row-title"><span>Row ' + (rowIndex + 1) + '</span><span class="enews-builder-v2-settings-box-copy">Wähle das Spaltenlayout für diesen Abschnitt.</span></div>' +
					'<div class="enews-builder-v2-inline-actions">' +
					'<label>Layout</label>' +
					'<select data-act="row-layout">' +
					'<option value="1"' + (preset === '1' ? ' selected' : '') + '>1 Spalte</option>' +
					'<option value="2"' + (preset === '2' ? ' selected' : '') + '>2 Spalten</option>' +
					'<option value="3"' + (preset === '3' ? ' selected' : '') + '>3 Spalten</option>' +
					'<option value="37"' + (preset === '37' ? ' selected' : '') + '>30/70</option>' +
					'<option value="73"' + (preset === '73' ? ' selected' : '') + '>70/30</option>' +
					'</select>' +
					'<button type="button" data-act="delete-row" class="button button-small">Row löschen</button>' +
					'</div>' +
					'</div>';

				rowEl.querySelector('[data-act="row-layout"]').addEventListener('change', function (event) {
					setColumnPreset(sectionIndex, rowIndex, event.target.value);
				});
				rowEl.querySelector('[data-act="delete-row"]').addEventListener('click', function () {
					deleteRow(sectionIndex, rowIndex);
				});

				var colsWrap = document.createElement('div');
				colsWrap.className = 'enews-builder-v2-columns';

				row.columns.forEach(function (column, colIndex) {
					var columnPath = { section: sectionIndex, row: rowIndex, column: colIndex };
					var colEl = document.createElement('div');
					colEl.className = 'enews-builder-v2-column';
					colEl.style.flex = '0 0 ' + column.width + '%';
					colEl.innerHTML = '<div class="enews-builder-v2-column-head">Spalte ' + (colIndex + 1) + ' (' + column.width.toFixed(0) + '%)</div>';
					colEl.appendChild(createDropZone(columnPath, 0));

					column.blocks.forEach(function (block, blockIndex) {
						var blockEl = document.createElement('div');
						blockEl.className = 'enews-builder-v2-block' + (selected && selected.blockId === block.id ? ' is-selected' : '');
						blockEl.draggable = true;
						blockEl.dataset.blockId = block.id;
						blockEl.innerHTML = '' +
							'<div class="enews-builder-v2-block-head">' +
							'<span class="enews-builder-v2-module-chip">' + escapeHtml(modules[block.type] ? modules[block.type].label : block.type) + '</span>' +
							'<div class="enews-builder-v2-inline-actions">' +
							'<button type="button" data-act="up" class="button button-small">' + escapeHtml(t('moveUp', 'Hoch')) + '</button>' +
							'<button type="button" data-act="down" class="button button-small">' + escapeHtml(t('moveDown', 'Runter')) + '</button>' +
							'<button type="button" data-act="duplicate" class="button button-small">' + escapeHtml(t('duplicate', 'Duplizieren')) + '</button>' +
							'<button type="button" data-act="delete" class="button button-small">' + escapeHtml(t('remove', 'Löschen')) + '</button>' +
							'</div>' +
							'</div>' +
							'<div class="enews-builder-v2-block-body">' + escapeHtml(buildSummary(block)) + '</div>';

						blockEl.addEventListener('click', function (event) {
							if (event.target && event.target.closest && event.target.closest('button')) {
								return;
							}
							selected = { section: sectionIndex, row: rowIndex, column: colIndex, blockId: block.id };
							renderCanvas();
							renderSettings();
						});

						blockEl.addEventListener('dragstart', function (event) {
							activeDrag = { kind: 'block', sourcePath: { section: sectionIndex, row: rowIndex, column: colIndex, block: blockIndex } };
							event.dataTransfer.setData('text/enews-block-id', block.id);
							event.dataTransfer.effectAllowed = 'move';
						});
						blockEl.addEventListener('dragend', function () {
							activeDrag = null;
						});

						blockEl.querySelector('[data-act="up"]').addEventListener('click', function () {
							moveBlockByStep(block.id, -1);
						});
						blockEl.querySelector('[data-act="down"]').addEventListener('click', function () {
							moveBlockByStep(block.id, 1);
						});
						blockEl.querySelector('[data-act="duplicate"]').addEventListener('click', function () {
							duplicateBlock(block.id);
						});
						blockEl.querySelector('[data-act="delete"]').addEventListener('click', function () {
							removeBlock(block.id);
						});

						colEl.appendChild(blockEl);
						colEl.appendChild(createDropZone(columnPath, blockIndex + 1));
					});

					var addWrap = document.createElement('div');
					addWrap.className = 'enews-builder-v2-add-hint';
					var moduleSelect = document.createElement('select');
					Object.keys(modules).forEach(function (type) {
						var option = document.createElement('option');
						option.value = type;
						option.textContent = modules[type].label || type;
						moduleSelect.appendChild(option);
					});
					moduleSelect.value = 'text';
					var addHintButton = document.createElement('button');
					addHintButton.type = 'button';
					addHintButton.className = 'button button-small';
					addHintButton.textContent = '+ Block hinzufügen';
					addHintButton.addEventListener('click', function () {
						addBlock(moduleSelect.value || 'text', columnPath);
					});
					addWrap.appendChild(moduleSelect);
					addWrap.appendChild(addHintButton);
					colEl.appendChild(addWrap);

					colsWrap.appendChild(colEl);
				});

				rowEl.appendChild(colsWrap);
				sectionCard.appendChild(rowEl);
			});

			canvasEl.appendChild(sectionCard);
		});

		var addSectionBtn = document.createElement('button');
		addSectionBtn.type = 'button';
		addSectionBtn.className = 'button button-secondary enews-builder-v2-add-section';
		addSectionBtn.textContent = '+ Section';
		addSectionBtn.addEventListener('click', addSection);
		canvasEl.appendChild(addSectionBtn);
	}

	function getRowPresetValue(columns) {
		if (!columns || !columns.length) {
			return '1';
		}
		if (columns.length === 1) {
			return '1';
		}
		if (columns.length === 2) {
			var a = Math.round(columns[0].width);
			var b = Math.round(columns[1].width);
			if (Math.abs(a - 50) < 3 && Math.abs(b - 50) < 3) {
				return '2';
			}
			if (Math.abs(a - 30) < 3 && Math.abs(b - 70) < 3) {
				return '37';
			}
			if (Math.abs(a - 70) < 3 && Math.abs(b - 30) < 3) {
				return '73';
			}
		}
		if (columns.length === 3) {
			return '3';
		}
		return '1';
	}

	function humanQueryMode(value) {
		if (value === 'latest') return 'Neueste';
		if (value === 'trigger') return 'Trigger';
		return 'Manuell';
	}

	function humanLayout(value) {
		if (value === 'single') return 'Einzeln';
		if (value === 'list') return 'Liste';
		if (value === 'links') return 'Linkliste';
		if (value === 'slider') return 'Slider';
		return 'Grid';
	}

	function buildSummary(block) {
		if (!block || !block.settings) return '';
		if (block.type === 'hero') return block.settings.title || 'Hero ohne Titel';
		if (block.type === 'products') return 'Produkte: ' + humanQueryMode(block.settings.query_mode) + ', Layout: ' + humanLayout(block.settings.layout);
		if (block.type === 'posts') return 'Beiträge: ' + humanQueryMode(block.settings.query_mode) + ', Layout: ' + humanLayout(block.settings.layout);
		if (block.type === 'footer') return block.settings.company ? ('Footer für ' + block.settings.company) : 'Footer';
		if (block.type === 'social') return block.settings.title || 'Social-Links';
		if (block.type === 'heading' && block.settings.text) return block.settings.text;
		if (block.type === 'text' && block.settings.text) return stripTags(block.settings.text).slice(0, 90);
		if (block.type === 'button' && block.settings.label) return block.settings.label + (block.settings.url ? ' -> ' + block.settings.url : '');
		if (block.type === 'image' && block.settings.url) return block.settings.url;
		if (block.settings.title) return String(block.settings.title);
		return Object.keys(block.settings).filter(function (key) {
			return !hiddenSettingKeys[key] && key !== 'query_mode' && key !== 'layout';
		}).slice(0, 2).map(function (key) {
			return key + ': ' + String(block.settings[key]);
		}).join(' | ');
	}

	function stripTags(html) {
		return String(html || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
	}

	function renderPresets() {
		if (!presetsEl) return;
		presetsEl.innerHTML = '';
		var keys = Object.keys(presets);
		if (!keys.length) {
			var empty = document.createElement('div');
			empty.className = 'enews-builder-v2-empty-state';
			empty.textContent = 'Noch keine Presets vorhanden.';
			presetsEl.appendChild(empty);
			return;
		}

		var groups = [
			{ key: 'user', title: 'Eigene Presets', filter: function (preset) { return !!preset.is_user_preset; } },
			{ key: 'default', title: 'Standard-Presets', filter: function (preset) { return !preset.is_user_preset; } }
		];

		groups.forEach(function (group) {
			var matching = keys.filter(function (key) {
				return group.filter(presets[key] || {});
			});
			if (!matching.length) return;

			var groupEl = document.createElement('div');
			groupEl.className = 'enews-builder-v2-preset-group';
			groupEl.innerHTML = '<h4 class="enews-builder-v2-preset-group-title">' + escapeHtml(group.title) + '</h4>';
			var gridEl = document.createElement('div');
			gridEl.className = 'enews-builder-v2-preset-grid';

			matching.forEach(function (key) {
				var preset = presets[key] || {};
				var card = document.createElement('div');
				card.className = 'enews-builder-v2-preset-card' + (selectedPresetId === key ? ' is-selected' : '');
				card.innerHTML = '' +
					'<div class="enews-builder-v2-preset-card-head">' +
						'<span class="enews-builder-v2-preset-badge">' + escapeHtml(group.key === 'user' ? 'User' : 'Default') + '</span>' +
						'<div>' +
							'<p class="enews-builder-v2-preset-card-title">' + escapeHtml(preset.label || key) + '</p>' +
							'<p class="enews-builder-v2-preset-card-copy">' + escapeHtml(preset.description || 'Vordefinierter Startpunkt für den Builder.') + '</p>' +
						'</div>' +
					'</div>';

				var actions = document.createElement('div');
				actions.className = 'enews-builder-v2-preset-card-actions';

				var applyButton = document.createElement('button');
				applyButton.type = 'button';
				applyButton.className = 'button button-secondary';
				applyButton.textContent = t('applyPreset', 'Preset anwenden');
				applyButton.addEventListener('click', function () {
					applyPresetState(key);
				});
				actions.appendChild(applyButton);

				if (preset.is_user_preset) {
					var selectButton = document.createElement('button');
					selectButton.type = 'button';
					selectButton.className = 'button';
					selectButton.textContent = selectedPresetId === key ? 'Ausgewählt' : 'Zum Löschen markieren';
					selectButton.addEventListener('click', function () {
						selectedPresetId = key;
						if (deletePresetButtonEl) {
							deletePresetButtonEl.disabled = false;
						}
						renderPresets();
					});
					actions.appendChild(selectButton);
				}

				card.appendChild(actions);
				gridEl.appendChild(card);
			});

			groupEl.appendChild(gridEl);
			presetsEl.appendChild(groupEl);
		});

		if (deletePresetButtonEl) {
			deletePresetButtonEl.disabled = !(selectedPresetId && presets[selectedPresetId] && presets[selectedPresetId].is_user_preset);
		}
	}

	function renderSettings() {
		settingsEl.innerHTML = '';
		if (selectionMetaEl) {
			selectionMetaEl.textContent = 'Wähle einen Block im Canvas aus, um ihn zu bearbeiten.';
		}

		var globalWrap = document.createElement('div');
		globalWrap.className = 'enews-builder-v2-settings-box';
		globalWrap.innerHTML = '<h3>' + escapeHtml(t('globalSettings', 'Mail-Rahmen & Branding')) + '</h3><p class="enews-builder-v2-settings-box-copy">Globale Breite, Farben und Typografie für die komplette Mail.</p>';
		appendField(globalWrap, 'subject', 'Betreff', state.global.subject, function (value) {
			state.global.subject = value;
			if (subjectInputEl && subjectInputEl.value !== value) subjectInputEl.value = value;
			afterStateChange();
		});
		appendNumberField(globalWrap, 'content_width', t('contentWidth', 'Inhaltsbreite'), state.global.content_width, 420, 760, function (value) {
			updateGlobalSetting('content_width', value);
		});
		appendToggleField(globalWrap, 'full_width', t('fullWidth', 'Fullwidth-Inhalt'), state.global.full_width === '1', function (checked) {
			updateGlobalSetting('full_width', checked ? '1' : '0');
		});
		appendColorField(globalWrap, 'background_color', t('backgroundColor', 'Hintergrundfarbe außen'), state.global.background_color, function (value) {
			updateGlobalSetting('background_color', value);
		});
		appendColorField(globalWrap, 'content_background', t('contentBackground', 'Hintergrundfarbe innen'), state.global.content_background, function (value) {
			updateGlobalSetting('content_background', value);
		});
		appendColorField(globalWrap, 'text_color', t('textColor', 'Standard-Textfarbe'), state.global.text_color, function (value) {
			updateGlobalSetting('text_color', value);
		});
		appendField(globalWrap, 'font_family', t('fontFamily', 'Schriftfamilie'), state.global.font_family, function (value) {
			updateGlobalSetting('font_family', value);
		});
		appendNumberField(globalWrap, 'section_gap', t('sectionGap', 'Abstand zwischen Modulen'), state.global.section_gap, 0, 60, function (value) {
			updateGlobalSetting('section_gap', value);
		});
		settingsEl.appendChild(globalWrap);

		var block = getSelectedBlock();
		if (!block) {
			var hint = document.createElement('div');
			hint.className = 'enews-builder-v2-empty-state';
			hint.textContent = 'Noch kein Block aktiv. Klicke im Canvas auf ein Modul, damit hier dessen Einstellungen erscheinen.';
			settingsEl.appendChild(hint);
			return;
		}

		var path = getBlockPathById(block.id);
		if (selectionMetaEl && path) {
			selectionMetaEl.textContent = (modules[block.type] ? modules[block.type].label : block.type) + ' aktiv in Section ' + (path.section + 1) + ', Row ' + (path.row + 1) + ', Spalte ' + (path.column + 1) + '.';
		}

		var blockWrap = document.createElement('div');
		blockWrap.className = 'enews-builder-v2-settings-box';
		blockWrap.innerHTML = '<h3>' + escapeHtml((modules[block.type] ? modules[block.type].label : block.type) + ' - Einstellungen') + '</h3><p class="enews-builder-v2-settings-box-copy">Direkte Konfiguration des aktuell selektierten Blocks.</p>';
		Object.keys(block.settings || {}).forEach(function (key) {
			if (key.indexOf('__') === 0) return;
			renderSmartField(blockWrap, key, block.settings[key], function (next) {
				updateSelectedBlockSetting(key, next);
			});
		});
		settingsEl.appendChild(blockWrap);
	}

	function renderSmartField(parent, key, value, onChange) {
		if (hiddenSettingKeys[key]) return;
		var block = getSelectedBlock();
		if (block && modules[block.type] && Array.isArray(modules[block.type].fields)) {
			var matchingField = modules[block.type].fields.filter(function (field) {
				return field && field.key === key;
			})[0];
			if (matchingField) {
				renderFieldFromDefinition(parent, matchingField, value, onChange);
				return;
			}
		}
		var label = fieldLabelMap[key] || key.replace(/_/g, ' ');
		if (fieldSelectMap[key]) {
			appendSelectField(parent, key, label, String(value == null ? '' : value), fieldSelectMap[key], onChange);
			return;
		}
		if (typeof value === 'number') {
			appendNumberField(parent, key, label, value, 0, 2000, onChange);
			return;
		}
		if (value === '0' || value === '1') {
			appendToggleField(parent, key, label, value === '1', function (checked) {
				onChange(checked ? '1' : '0');
			});
			return;
		}
		if (/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/.test(String(value))) {
			appendColorField(parent, key, label, value, onChange);
			return;
		}
		if (/html|text|address|legal|branding|contact/.test(key)) {
			appendTextAreaField(parent, key, label, String(value || ''), onChange);
			return;
		}
		appendField(parent, key, label, String(value == null ? '' : value), onChange);
	}

	function renderFieldFromDefinition(parent, field, value, onChange) {
		var type = field.type || 'text';
		var label = field.label || field.key;
		if (type === 'select' && Array.isArray(field.options)) {
			appendSelectField(parent, field.key, label, String(value == null ? '' : value), field.options, onChange);
			return;
		}
		if (type === 'textarea') {
			appendTextAreaField(parent, field.key, label, String(value || ''), onChange);
			return;
		}
		if (type === 'number') {
			appendNumberField(parent, field.key, label, Number(value || 0), Number(field.min || 0), Number(field.max || 2000), onChange);
			return;
		}
		if (type === 'toggle') {
			appendToggleField(parent, field.key, label, String(value) === '1' || value === true, function (checked) {
				onChange(checked ? '1' : '0');
			});
			return;
		}
		if (type === 'color') {
			appendColorField(parent, field.key, label, value, onChange);
			return;
		}
		appendField(parent, field.key, label, String(value == null ? '' : value), onChange);
	}

	function appendSelectField(parent, key, label, value, options, onChange) {
		var field = document.createElement('div');
		field.className = 'enews-builder-v2-field';
		field.innerHTML = '<label for="enews-field-' + escapeHtml(key) + '">' + escapeHtml(label) + '</label>';
		var select = document.createElement('select');
		select.id = 'enews-field-' + key;
		options.forEach(function (optionData) {
			var option = document.createElement('option');
			option.value = optionData.value;
			option.textContent = optionData.label;
			if (String(optionData.value) === String(value)) option.selected = true;
			select.appendChild(option);
		});
		select.addEventListener('change', function () {
			onChange(select.value);
		});
		field.appendChild(select);
		parent.appendChild(field);
	}

	function appendField(parent, key, label, value, onChange) {
		var field = document.createElement('div');
		field.className = 'enews-builder-v2-field';
		field.innerHTML = '<label for="enews-field-' + escapeHtml(key) + '">' + escapeHtml(label) + '</label>';
		var input = document.createElement('input');
		input.type = 'text';
		input.id = 'enews-field-' + key;
		input.value = value || '';
		input.addEventListener('input', function () {
			onChange(input.value);
		});
		field.appendChild(input);
		parent.appendChild(field);
	}

	function appendTextAreaField(parent, key, label, value, onChange) {
		var field = document.createElement('div');
		field.className = 'enews-builder-v2-field';
		field.innerHTML = '<label for="enews-field-' + escapeHtml(key) + '">' + escapeHtml(label) + '</label>';
		var input = document.createElement('textarea');
		input.id = 'enews-field-' + key;
		input.rows = 4;
		input.value = value || '';
		input.addEventListener('input', function () {
			onChange(input.value);
		});
		field.appendChild(input);
		parent.appendChild(field);
	}

	function appendNumberField(parent, key, label, value, min, max, onChange) {
		var field = document.createElement('div');
		field.className = 'enews-builder-v2-field';
		field.innerHTML = '<label for="enews-field-' + escapeHtml(key) + '">' + escapeHtml(label) + '</label>';
		var input = document.createElement('input');
		input.type = 'number';
		input.min = String(min);
		input.max = String(max);
		input.id = 'enews-field-' + key;
		input.value = String(value);
		input.addEventListener('input', function () {
			var parsed = parseInt(input.value, 10);
			if (!isFinite(parsed)) return;
			onChange(Math.max(min, Math.min(max, parsed)));
		});
		field.appendChild(input);
		parent.appendChild(field);
	}

	function appendColorField(parent, key, label, value, onChange) {
		var field = document.createElement('div');
		field.className = 'enews-builder-v2-field';
		field.innerHTML = '<label for="enews-field-' + escapeHtml(key) + '">' + escapeHtml(label) + '</label>';
		var input = document.createElement('input');
		input.type = 'color';
		input.id = 'enews-field-' + key;
		input.value = /^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/.test(String(value)) ? value : '#000000';
		input.addEventListener('input', function () {
			onChange(input.value);
		});
		field.appendChild(input);
		parent.appendChild(field);
	}

	function appendToggleField(parent, key, label, checked, onChange) {
		var field = document.createElement('div');
		field.className = 'enews-builder-v2-field enews-builder-v2-toggle';
		var input = document.createElement('input');
		input.type = 'checkbox';
		input.id = 'enews-field-' + key;
		input.checked = !!checked;
		input.addEventListener('change', function () {
			onChange(input.checked);
		});
		var text = document.createElement('label');
		text.setAttribute('for', input.id);
		text.textContent = label;
		field.appendChild(input);
		field.appendChild(text);
		parent.appendChild(field);
	}

	function setPreviewStatus(message, isError, ensureVisible) {
		if (!previewEl) return;
		if (!previewStatusEl || !previewEl.contains(previewStatusEl)) {
			previewStatusEl = document.createElement('p');
			previewStatusEl.className = 'description enews-builder-v2-preview-status';
			previewEl.prepend(previewStatusEl);
		}
		previewStatusEl.className = 'description enews-builder-v2-preview-status' + (isError ? ' enews-builder-v2-error' : '');
		previewStatusEl.textContent = message || '';
		previewStatusEl.style.display = ensureVisible && message ? 'block' : 'none';
	}

	function schedulePreview() {
		window.clearTimeout(previewTimer);
		previewTimer = window.setTimeout(loadPreview, 350);
	}

	function loadPreview() {
		if (!previewEl) return;
		var hasBlocks = false;
		state.sections.forEach(function (section) {
			(section.rows || []).forEach(function (row) {
				(row.columns || []).forEach(function (col) {
					if ((col.blocks || []).length) hasBlocks = true;
				});
			});
		});

		if (!hasBlocks) {
			setPreviewStatus(t('emptyPreview', 'Die Vorschau erscheint, sobald Inhalte vorhanden sind.'), false, true);
			if (previewFrame && previewEl.contains(previewFrame)) {
				previewFrame.remove();
				previewFrame = null;
			}
			return;
		}

		previewRequestId += 1;
		var requestId = previewRequestId;
		setPreviewStatus(t('previewLoading', 'Vorschau wird aktualisiert ...'), false, true);
		var payload = new URLSearchParams();
		payload.append('action', 'enews_builder_v2_preview');
		payload.append('nonce', config.previewNonce || '');
		payload.append('newsletter_id', String(config.newsletterId || 0));
		payload.append('state', JSON.stringify(state));
		var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
		var timeoutId = window.setTimeout(function () {
			if (controller) controller.abort();
		}, 15000);

		fetch(config.ajaxUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: payload.toString(),
			credentials: 'same-origin',
			signal: controller ? controller.signal : undefined
		})
			.then(function (response) { return response.text(); })
			.then(function (text) {
				var json = null;
				try {
					json = JSON.parse(text);
				} catch (err) {
					throw new Error('non-json: ' + String(text || '').slice(0, 120));
				}
				if (requestId !== previewRequestId) return;
				if (!json || !json.success || !json.data || !json.data.html) {
					throw new Error((json && json.data && json.data.message) ? json.data.message : 'preview-error');
				}
				renderPreviewHtml(json.data.html);
				setPreviewStatus('', false, false);
			})
			.catch(function (err) {
				if (requestId !== previewRequestId) return;
				var msg = t('previewError', 'Vorschau konnte nicht geladen werden.');
				if (err && err.message) msg += ' (' + err.message + ')';
				setPreviewStatus(msg, true, true);
			})
			.finally(function () {
				window.clearTimeout(timeoutId);
			});
	}

	function renderPreviewHtml(html) {
		if (!previewFrame || !previewEl.contains(previewFrame)) {
			previewFrame = document.createElement('iframe');
			previewFrame.className = 'enews-builder-v2-preview-frame';
			previewFrame.setAttribute('title', 'Newsletter-Vorschau');
			previewEl.appendChild(previewFrame);
		}
		previewFrame.srcdoc = html;
		applyPreviewMode();
	}

	function applyPreviewMode() {
		if (!previewEl) return;
		previewEl.classList.toggle('enews-builder-v2-preview-mobile', previewMode === 'mobile');
		previewEl.classList.toggle('enews-builder-v2-preview-desktop', previewMode !== 'mobile');
		if (viewDesktopButtonEl) viewDesktopButtonEl.classList.toggle('is-active', previewMode === 'desktop');
		if (viewMobileButtonEl) viewMobileButtonEl.classList.toggle('is-active', previewMode === 'mobile');
	}

	function saveCurrentPreset() {
		var label = window.prompt(t('presetNamePrompt', 'Name für das Preset'), state.global.subject || 'Mein Preset');
		if (!label) {
			return;
		}
		var payload = new URLSearchParams();
		payload.append('action', 'enews_builder_v2_save_preset');
		payload.append('nonce', config.presetsNonce || '');
		payload.append('label', label);
		payload.append('state', JSON.stringify(state));
		fetch(config.ajaxUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: payload.toString(),
			credentials: 'same-origin'
		})
			.then(function (response) { return response.json(); })
			.then(function (json) {
				if (!json || !json.success || !json.data) {
					throw new Error((json && json.data && json.data.message) || t('presetSaveError', 'Preset konnte nicht gespeichert werden.'));
				}
				presets = json.data.presets || presets;
				selectedPresetId = json.data.presetId || '';
				renderPresets();
				window.alert(t('presetSaved', 'Preset gespeichert.'));
			})
			.catch(function (err) {
				window.alert((err && err.message) || t('presetSaveError', 'Preset konnte nicht gespeichert werden.'));
			});
	}

	function deleteCurrentPreset() {
		if (!selectedPresetId || !presets[selectedPresetId] || !presets[selectedPresetId].is_user_preset) {
			return;
		}
		var payload = new URLSearchParams();
		payload.append('action', 'enews_builder_v2_delete_preset');
		payload.append('nonce', config.presetsNonce || '');
		payload.append('presetId', selectedPresetId);
		fetch(config.ajaxUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: payload.toString(),
			credentials: 'same-origin'
		})
			.then(function (response) { return response.json(); })
			.then(function (json) {
				if (!json || !json.success || !json.data) {
					throw new Error((json && json.data && json.data.message) || t('presetDeleteError', 'Preset konnte nicht geloescht werden.'));
				}
				presets = json.data.presets || presets;
				selectedPresetId = '';
				renderPresets();
				window.alert(t('presetDeleted', 'Preset geloescht.'));
			})
			.catch(function (err) {
				window.alert((err && err.message) || t('presetDeleteError', 'Preset konnte nicht geloescht werden.'));
			});
	}

	function sendTestMail() {
		if (!sendTestButtonEl || !sendTestEmailEl || !sendTestStatusEl) return;
		var email = (sendTestEmailEl.value || '').trim();
		if (!/.+@.+\..+/.test(email)) {
			sendTestStatusEl.textContent = t('testMailNeedEmail', 'Bitte gib eine gueltige Test-E-Mail-Adresse ein.');
			return;
		}

		sendTestStatusEl.textContent = t('testMailSending', 'Test-Mail wird gesendet ...');
		sendTestButtonEl.disabled = true;
		var payload = new URLSearchParams();
		payload.append('action', 'send_email_preview');
		payload.append('newsletter_id', String(config.newsletterId || 0));
		payload.append('preview_email', email);
		payload.append('nonce', config.sendPreviewNonce || '');
		payload.append('builder_state_json', JSON.stringify(state));

		fetch(config.ajaxUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: payload.toString(),
			credentials: 'same-origin'
		})
			.then(function (response) { return response.text(); })
			.then(function (text) {
				sendTestStatusEl.textContent = text || t('testMailError', 'Test-Mail konnte nicht gesendet werden.');
			})
			.catch(function () {
				sendTestStatusEl.textContent = t('testMailError', 'Test-Mail konnte nicht gesendet werden.');
			})
			.finally(function () {
				sendTestButtonEl.disabled = false;
			});
	}

	function renderAll() {
		renderCanvas();
		renderSettings();
	}

	function initAccordions() {
		document.querySelectorAll('[data-accordion-toggle]').forEach(function (toggle) {
			toggle.addEventListener('click', function () {
				var item = toggle.closest('[data-accordion-item]');
				if (!item) return;
				var panel = item.querySelector('[data-accordion-panel]');
				var isOpen = item.classList.contains('is-open');
				item.classList.toggle('is-open', !isOpen);
				toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
				if (panel) panel.hidden = isOpen;
			});
		});
	}

	function bindEvents() {
		if (moduleSearchEl) {
			moduleSearchEl.addEventListener('input', renderPalette);
		}
		if (subjectInputEl) {
			subjectInputEl.value = state.global.subject || subjectInputEl.value || '';
			subjectInputEl.addEventListener('input', function () {
				syncSubjectIntoState();
				saveStateToInput();
				schedulePreview();
			});
		}
		if (sendTestButtonEl) sendTestButtonEl.addEventListener('click', sendTestMail);
		if (savePresetButtonEl) savePresetButtonEl.addEventListener('click', saveCurrentPreset);
		if (deletePresetButtonEl) {
			deletePresetButtonEl.disabled = true;
			deletePresetButtonEl.addEventListener('click', deleteCurrentPreset);
		}
		if (undoButtonEl) undoButtonEl.addEventListener('click', function () { restoreHistory(-1); });
		if (redoButtonEl) redoButtonEl.addEventListener('click', function () { restoreHistory(1); });
		if (viewDesktopButtonEl) viewDesktopButtonEl.addEventListener('click', function () { previewMode = 'desktop'; applyPreviewMode(); });
		if (viewMobileButtonEl) viewMobileButtonEl.addEventListener('click', function () { previewMode = 'mobile'; applyPreviewMode(); });
		if (formEl) {
			formEl.addEventListener('submit', function () {
				syncSubjectIntoState();
				saveStateToInput();
			});
		}
		document.addEventListener('drop', function () {
			activeDrag = null;
		});
	}

	renderPresets();
	renderPalette();
	bindEvents();
	saveStateToInput();
	renderAll();
	applyPreviewMode();
	updateHistoryButtons();
	schedulePreview();
})();
