(function () {
	'use strict';

	if (!window.enewsBuilderV2 || !document.getElementById('enews-builder-v2-app')) {
		return;
	}

	var config = window.enewsBuilderV2;
	var modules = config.modules || {};
	var presets = config.presets || {};
	var state = normalizeState(config.state || {});
	var selectedModuleId = null;
	var collapsedModuleMap = {};
	var dragSourceId = null;
	var dragGridHint = null;
	var dragPointerOffsetY = 0;
	var transparentDragImage = null;
	var previewTimer = null;
	var previewRequestId = 0;
	var resizeState = null;
	var itemCache = {};
	var itemSearchTimers = {};
	var reflowAnimationCleanupTimer = null;
	var moduleRowSpanCache = {};
	var moduleIdCounter = 0;
	var paletteEl = document.getElementById('enews-builder-v2-palette');
	var canvasEl = document.getElementById('enews-builder-v2-canvas');
	var previewEl = document.getElementById('enews-builder-v2-preview');
	var settingsEl = document.getElementById('enews-builder-v2-settings-panel');
	var formEl = document.getElementById('enews-builder-v2-form');
	var stateInputEl = document.getElementById('builder_state_json');
	var presetsEl = document.getElementById('enews-builder-v2-presets');
	var sendTestButtonEl = document.getElementById('enews-builder-v2-send-test');
	var sendTestEmailEl = document.getElementById('enews-builder-v2-preview-email');
	var sendTestStatusEl = document.getElementById('enews-builder-v2-send-test-status');
	var subjectInputEl = document.getElementById('enews-builder-v2-subject');

	function normalizeState(rawState) {
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
			modules: []
		};
		var next = Object.assign({}, defaults, rawState || {});
		next.global = Object.assign({}, defaults.global, (rawState && rawState.global) || {});
		next.modules = Array.isArray(rawState && rawState.modules) ? rawState.modules.map(normalizeModule) : [];
		next.modules = ensureUniqueModuleIds(next.modules);
		return next;
	}

	function generateModuleId() {
		moduleIdCounter += 1;
		return 'mod_' + Date.now() + '_' + moduleIdCounter + '_' + Math.random().toString(16).slice(2, 8);
	}

	function ensureUniqueModuleIds(list) {
		var seen = {};
		return (Array.isArray(list) ? list : []).map(function (module) {
			var nextModule = module || {};
			var id = String(nextModule.id || '');
			if (!id || seen[id]) {
				id = generateModuleId();
			}
			seen[id] = true;
			nextModule.id = id;
			return nextModule;
		});
	}

	function normalizeModule(module) {
		var nextModule = module || {};
		nextModule.settings = nextModule.settings || {};
		if (nextModule.settings.lock_full_width == null) {
			nextModule.settings.lock_full_width = isDefaultFullWidthType(nextModule.type) ? '1' : '0';
		}
		nextModule.settings.grid_span = getModuleSpan(nextModule);
		nextModule.settings.grid_col = getModuleCol(nextModule);
		nextModule.settings.grid_row = getModuleRow(nextModule);
		nextModule.settings.grid_rows = getModuleBaseRows(nextModule);
		nextModule.settings.canvas_min_height = getModuleMinHeight(nextModule);
		return nextModule;
	}

	function isDefaultFullWidthType(type) {
		return type === 'hero' || type === 'footer';
	}

	function deepClone(value) {
		return JSON.parse(JSON.stringify(value));
	}

	function saveStateToInput() {
		stateInputEl.value = JSON.stringify(state);
	}

	function createModule(type) {
		var source = modules[type];
		if (!source) {
			return null;
		}
		return normalizeModule({
			id: generateModuleId(),
			type: type,
			settings: deepClone(source.defaults || {})
		});
	}

	function getModuleLabel(type) {
		return modules[type] ? modules[type].label : type;
	}

	function renderPresets() {
		if (!presetsEl) {
			return;
		}

		presetsEl.innerHTML = '';
		var keys = Object.keys(presets);
		if (!keys.length) {
			return;
		}

		var row = document.createElement('div');
		row.className = 'enews-builder-v2-presets-row';

		var select = document.createElement('select');
		select.id = 'enews-builder-v2-preset-select';
		var placeholder = document.createElement('option');
		placeholder.value = '';
		placeholder.textContent = config.l10n.selectPreset;
		select.appendChild(placeholder);

		keys.forEach(function (key) {
			var preset = presets[key];
			var option = document.createElement('option');
			option.value = key;
			option.textContent = preset.label || key;
			select.appendChild(option);
		});

		var applyButton = document.createElement('button');
		applyButton.type = 'button';
		applyButton.className = 'button button-secondary';
		applyButton.textContent = config.l10n.applyPreset;
		applyButton.addEventListener('click', function () {
			var selected = select.value;
			if (!selected || !presets[selected] || !presets[selected].state) {
				return;
			}
			if (!window.confirm(config.l10n.applyPresetConfirm)) {
				return;
			}

			state = normalizeState(deepClone(presets[selected].state));
			moduleRowSpanCache = {};
			selectedModuleId = null;
			renderAll();
		});

		row.appendChild(select);
		row.appendChild(applyButton);
		presetsEl.appendChild(row);

		var detail = document.createElement('p');
		detail.className = 'description enews-builder-v2-preset-detail';
		select.addEventListener('change', function () {
			var selected = select.value;
			detail.textContent = selected && presets[selected] ? (presets[selected].description || '') : '';
		});
		presetsEl.appendChild(detail);
	}

	function renderPalette() {
		paletteEl.innerHTML = '';
		Object.keys(modules).forEach(function (type) {
			var button = document.createElement('button');
			button.type = 'button';
			button.className = 'enews-builder-v2-palette-item';
			button.draggable = true;
			button.dataset.moduleType = type;
			button.innerHTML = '<span class="enews-builder-v2-palette-icon">' + resolveModuleSymbol(type, modules[type].icon) + '</span><span class="enews-builder-v2-palette-label">' + escapeHtml(modules[type].label) + '</span>';
			button.addEventListener('click', function () {
				var block = createModule(type);
				if (!block) {
					return;
				}
				state.modules.push(block);
				selectedModuleId = block.id;
				renderAll();
			});
			button.addEventListener('dragstart', function (event) {
				event.dataTransfer.setData('text/enews-module-type', type);
				event.dataTransfer.effectAllowed = 'copy';
			});
			paletteEl.appendChild(button);
		});
	}

	function renderCanvas() {
		var previousItemRects = captureCanvasItemRects();
		canvasEl.innerHTML = '';
		canvasEl.classList.toggle('is-empty', state.modules.length === 0);
		canvasEl.style.minHeight = '';
		ensureGridPositions();
		var activeModuleMap = {};
		state.modules.forEach(function (module) {
			activeModuleMap[module.id] = true;
		});
		Object.keys(moduleRowSpanCache).forEach(function (moduleId) {
			if (!activeModuleMap[moduleId]) {
				delete moduleRowSpanCache[moduleId];
			}
		});
		if (!state.modules.length) {
			var empty = document.createElement('div');
			empty.className = 'enews-builder-v2-empty';
			empty.textContent = config.l10n.emptyCanvas;
			canvasEl.appendChild(empty);
		}

		state.modules.forEach(function (module, index) {
			normalizeModule(module);
			var span = getModuleSpan(module);
			var rowSpan = getModuleGridRows(module);
			var isLocked = isModuleWidthLocked(module);
			var isCollapsed = !!collapsedModuleMap[module.id];
			var item = document.createElement('div');
			item.className = 'enews-builder-v2-canvas-item' + (selectedModuleId === module.id ? ' is-selected' : '');
			item.draggable = false;
			item.dataset.moduleId = module.id;
			item.dataset.moduleIndex = String(index);
			item.style.gridColumn = getModuleCol(module) + ' / span ' + span;
			item.style.gridRow = getModuleRow(module) + ' / span ' + rowSpan;
			item.style.minHeight = '';
			item.innerHTML = '' +
				'<div class="enews-builder-v2-canvas-head">' +
				'<span class="enews-builder-v2-module-chip">' + escapeHtml(getModuleLabel(module.type)) + '</span>' +
				'<span class="enews-builder-v2-size-chip">' + escapeHtml(span + '/12' + (isLocked ? ' ' + config.l10n.lockedSuffix : '')) + '</span>' +
				'<div class="enews-builder-v2-actions">' +
				'<button type="button" data-action="drag-handle" class="enews-builder-v2-icon-button enews-builder-v2-drag-handle-button" title="' + escapeHtml(config.l10n.dragHandle || 'Ziehen') + '" aria-label="' + escapeHtml(config.l10n.dragHandle || 'Ziehen') + '"><span aria-hidden="true"></span></button>' +
				'<button type="button" data-action="toggle-inline" class="enews-builder-v2-icon-button" title="' + escapeHtml(isCollapsed ? config.l10n.expandSettings : config.l10n.collapseSettings) + '">' + (isCollapsed ? '&#9656;' : '&#9662;') + '</button>' +
				'<button type="button" data-action="duplicate">' + escapeHtml(config.l10n.duplicate) + '</button>' +
				'<button type="button" data-action="delete" class="enews-builder-v2-icon-button enews-builder-v2-delete-button" title="' + escapeHtml(config.l10n.remove) + '">&#128465;</button>' +
				'</div>' +
				'</div>' +
				'<div class="enews-builder-v2-canvas-body">' +
				'<strong>' + escapeHtml(getModuleLabel(module.type)) + '</strong>' +
				'<p>' + escapeHtml(buildSummary(module)) + '</p>' +
				'</div>' +
				'<button type="button" class="enews-builder-v2-resize-handle" data-action="resize" aria-label="Resize"' + (isLocked ? ' disabled' : '') + '></button>';

			if (selectedModuleId === module.id) {
				item.appendChild(createQuickLayoutButtons(module, index));
			}

			if (selectedModuleId === module.id && !isCollapsed) {
				item.appendChild(createInlineEditor(module));
			}

			item.addEventListener('click', function (event) {
				if (event.target && event.target.closest && event.target.closest('[data-action]')) {
					return;
				}
				if (event.target && event.target.closest && event.target.closest('.enews-builder-v2-inline-editor')) {
					return;
				}
				selectedModuleId = module.id;
				collapsedModuleMap[module.id] = false;
				renderCanvas();
				renderSettings();
			});
			var dragHandle = item.querySelector('[data-action="drag-handle"]');
			dragHandle.draggable = true;

			dragHandle.addEventListener('dragstart', function (event) {
				dragSourceId = module.id;
				dragGridHint = null;
				var itemRect = item.getBoundingClientRect();
				dragPointerOffsetY = Math.max(0, event.clientY - itemRect.top);
				event.dataTransfer.setData('text/enews-module-id', module.id);
				event.dataTransfer.effectAllowed = 'move';
				event.dataTransfer.setDragImage(getTransparentDragImage(), 0, 0);
			});
			dragHandle.addEventListener('dragend', function () {
				dragSourceId = null;
				dragGridHint = null;
				dragPointerOffsetY = 0;
				clearDropMarkers();
				canvasEl.classList.remove('is-drop-target');
				renderDragHint();
			});
			item.addEventListener('drop', function (event) {
				event.preventDefault();
				event.stopPropagation();
				handleCanvasDrop(event);
				dragSourceId = null;
				clearDropMarkers();
				renderDragHint();
			});

			item.querySelector('[data-action="toggle-inline"]').addEventListener('click', function (event) {
				event.preventDefault();
				event.stopPropagation();
				if (selectedModuleId !== module.id) {
					selectedModuleId = module.id;
					collapsedModuleMap[module.id] = false;
				} else {
					collapsedModuleMap[module.id] = !collapsedModuleMap[module.id];
				}
				renderCanvas();
				renderSettings();
			});
			item.querySelector('[data-action="duplicate"]').addEventListener('click', function () {
				duplicateModule(module.id);
			});
			item.querySelector('[data-action="delete"]').addEventListener('click', function () {
				deleteModule(module.id);
			});

			var resizeHandle = item.querySelector('[data-action="resize"]');
			if (resizeHandle && !resizeHandle.disabled) {
				resizeHandle.addEventListener('mousedown', function (event) {
					startResize(event, module.id);
				});
			}

			canvasEl.appendChild(item);
		});

		syncVisualRowSpans();
		if (ensureGridPositions(true)) {
			renderCanvas();
			return;
		}
		applyDynamicCanvasMinHeight();
		markGridConflicts();

		renderDragHint();
		animateCanvasReflow(previousItemRects);
	}

	function captureCanvasItemRects() {
		var rects = {};
		canvasEl.querySelectorAll('.enews-builder-v2-canvas-item').forEach(function (item) {
			var moduleId = item.dataset.moduleId;
			if (!moduleId) {
				return;
			}
			var rect = item.getBoundingClientRect();
			rects[moduleId] = {
				left: rect.left,
				top: rect.top
			};
		});
		return rects;
	}

	function animateCanvasReflow(previousItemRects) {
		if (!previousItemRects || dragSourceId || resizeState) {
			return;
		}

		var hasAnimation = false;
		canvasEl.querySelectorAll('.enews-builder-v2-canvas-item').forEach(function (item) {
			var moduleId = item.dataset.moduleId;
			var previous = moduleId ? previousItemRects[moduleId] : null;
			if (!previous) {
				return;
			}

			var now = item.getBoundingClientRect();
			var deltaX = previous.left - now.left;
			var deltaY = previous.top - now.top;
			if (Math.abs(deltaX) < 1 && Math.abs(deltaY) < 1) {
				return;
			}

			hasAnimation = true;
			item.classList.remove('is-reflow-animating');
			item.style.transition = 'none';
			item.style.transform = 'translate(' + deltaX + 'px,' + deltaY + 'px)';
			item.getBoundingClientRect();
			item.classList.add('is-reflow-animating');
			item.style.transition = '';
			item.style.transform = '';
		});

		if (!hasAnimation) {
			return;
		}

		window.clearTimeout(reflowAnimationCleanupTimer);
		reflowAnimationCleanupTimer = window.setTimeout(function () {
			canvasEl.querySelectorAll('.enews-builder-v2-canvas-item.is-reflow-animating').forEach(function (item) {
				item.classList.remove('is-reflow-animating');
			});
		}, 260);
	}

	function applyDynamicCanvasMinHeight() {
		var rowStep = getCanvasRowStep();
		if (rowStep <= 0) {
			return;
		}

		var maxRowEnd = 1;
		state.modules.forEach(function (module) {
			var rowStart = getModuleRow(module);
			var rowSpan = getModuleGridRows(module);
			maxRowEnd = Math.max(maxRowEnd, rowStart + rowSpan - 1);
		});

		// Keep a few free rows below the last module to make further drag/drop easier.
		var reserveRows = state.modules.length ? 6 : 12;
		var targetRows = maxRowEnd + reserveRows;
		var minPixels = Math.max(320, targetRows * rowStep);
		canvasEl.style.minHeight = Math.ceil(minPixels) + 'px';
	}

	function markGridConflicts() {
		var items = Array.prototype.slice.call(canvasEl.querySelectorAll('.enews-builder-v2-canvas-item'));
		items.forEach(function (item) {
			item.classList.remove('is-grid-conflict');
		});
		for (var i = 0; i < items.length; i += 1) {
			var a = items[i].getBoundingClientRect();
			for (var j = i + 1; j < items.length; j += 1) {
				var b = items[j].getBoundingClientRect();
				if (
					a.left < b.right &&
					a.right > b.left &&
					a.top < b.bottom &&
					a.bottom > b.top
				) {
					items[i].classList.add('is-grid-conflict');
					items[j].classList.add('is-grid-conflict');
				}
			}
		}
	}

	function syncVisualRowSpans() {
		var grid = getCanvasGridMetrics();
		var rowStep = grid.step;
		if (rowStep <= 0) {
			return;
		}
		canvasEl.querySelectorAll('.enews-builder-v2-canvas-item').forEach(function (item) {
			var module = findModule(item.dataset.moduleId);
			if (!module) {
				return;
			}
			var baseRows = getModuleBaseRows(module);
			var editor = item.querySelector('.enews-builder-v2-inline-editor');
			// Ignore expanded inline settings so persisted layout does not get inflated by editor chrome.
			var measuredHeight = Math.max(rowStep, item.scrollHeight - getElementOuterHeight(editor));
			// Grid item height is n*rowHeight + (n-1)*rowGap, so compensate the final missing gap.
			var measuredRows = Math.max(1, Math.ceil((measuredHeight + grid.rowGap) / rowStep));
			var nextRows = Math.max(baseRows, measuredRows);
			moduleRowSpanCache[module.id] = nextRows;
			item.style.gridRow = getModuleRow(module) + ' / span ' + nextRows;
		});
	}

	function getElementOuterHeight(element) {
		if (!element) {
			return 0;
		}
		var rect = element.getBoundingClientRect();
		var styles = window.getComputedStyle(element);
		var marginTop = parseFloat(styles.marginTop) || 0;
		var marginBottom = parseFloat(styles.marginBottom) || 0;
		return rect.height + marginTop + marginBottom;
	}

	function startResize(event, moduleId) {
		event.preventDefault();
		event.stopPropagation();
		var module = findModule(moduleId);
		if (!module || isModuleWidthLocked(module)) {
			return;
		}

		var rect = canvasEl.getBoundingClientRect();
		resizeState = {
			moduleId: moduleId,
			startX: event.clientX,
			startSpan: getModuleSpan(module),
			previewSpan: getModuleSpan(module),
			columnWidth: Math.max(20, rect.width / 12)
		};

		document.body.classList.add('enews-builder-v2-resizing');
		document.addEventListener('mousemove', handleResizeMove);
		document.addEventListener('mouseup', stopResize);
	}

	function handleResizeMove(event) {
		if (!resizeState) {
			return;
		}

		var module = findModule(resizeState.moduleId);
		if (!module) {
			stopResize();
			return;
		}

		var delta = event.clientX - resizeState.startX;
		var step = Math.round(delta / resizeState.columnWidth);
		var nextSpan = Math.max(3, Math.min(12, resizeState.startSpan + step));
		if (nextSpan === resizeState.previewSpan) {
			return;
		}
		resizeState.previewSpan = nextSpan;
		updateResizePreview(module.id, nextSpan);
	}

	function updateResizePreview(moduleId, span) {
		var targetEl = null;
		var module = findModule(moduleId);
		canvasEl.querySelectorAll('.enews-builder-v2-canvas-item').forEach(function (item) {
			if (item.dataset.moduleId === moduleId) {
				targetEl = item;
			}
		});
		if (!targetEl || !module) {
			return;
		}
		targetEl.classList.add('is-resize-preview');
		targetEl.style.gridColumn = getModuleCol(module) + ' / span ' + span;
	}

	function stopResize() {
		if (!resizeState) {
			return;
		}

		var module = findModule(resizeState.moduleId);
		if (module && !isModuleWidthLocked(module)) {
			module.settings.grid_span = resizeState.previewSpan;
			renderCanvas();
			schedulePreview();
			saveStateToInput();
		}

		resizeState = null;
		document.body.classList.remove('enews-builder-v2-resizing');
		document.removeEventListener('mousemove', handleResizeMove);
		document.removeEventListener('mouseup', stopResize);
	}

	function ensureGridPositions(compactRows) {
		compactRows = !!compactRows;
		var occupied = {};
		var changed = false;

		function key(col, row) {
			return row + ':' + col;
		}

		function isSlotFree(col, row, span, rowSpan) {
			if (col < 1 || row < 1 || (col + span - 1) > 12) {
				return false;
			}
			for (var r = row; r < (row + rowSpan); r += 1) {
				for (var c = col; c < (col + span); c += 1) {
					if (occupied[key(c, r)]) {
						return false;
					}
				}
			}
			return true;
		}

		function markSlot(col, row, span, rowSpan) {
			for (var r = row; r < (row + rowSpan); r += 1) {
				for (var c = col; c < (col + span); c += 1) {
					occupied[key(c, r)] = true;
				}
			}
		}

		state.modules.forEach(function (module) {
			normalizeModule(module);
			var span = getModuleSpan(module);
			var rowSpan = getModuleGridRows(module);
			var savedCol = parseInt(module.settings.grid_col, 10) || 0;
			var savedRow = parseInt(module.settings.grid_row, 10) || 0;

			if (!compactRows && isSlotFree(savedCol, savedRow, span, rowSpan)) {
				if (module.settings.grid_col !== savedCol || module.settings.grid_row !== savedRow) {
					changed = true;
				}
				module.settings.grid_col = savedCol;
				module.settings.grid_row = savedRow;
				markSlot(savedCol, savedRow, span, rowSpan);
				return;
			}

			var found = false;

			if (compactRows) {
				var preferredCol = savedCol;
				if (!preferredCol || preferredCol < 1 || preferredCol > (13 - span)) {
					preferredCol = 1;
				}
				for (var preferredRow = 1; preferredRow <= 999 && !found; preferredRow += 1) {
					if (!isSlotFree(preferredCol, preferredRow, span, rowSpan)) {
						continue;
					}
					if (module.settings.grid_col !== preferredCol || module.settings.grid_row !== preferredRow) {
						changed = true;
					}
					module.settings.grid_col = preferredCol;
					module.settings.grid_row = preferredRow;
					markSlot(preferredCol, preferredRow, span, rowSpan);
					found = true;
				}
			}

			for (var row = 1; row <= 999 && !found; row += 1) {
				for (var col = 1; col <= (13 - span); col += 1) {
					if (!isSlotFree(col, row, span, rowSpan)) {
						continue;
					}
					if (module.settings.grid_col !== col || module.settings.grid_row !== row) {
						changed = true;
					}
					module.settings.grid_col = col;
					module.settings.grid_row = row;
					markSlot(col, row, span, rowSpan);
					found = true;
					break;
				}
			}
		});

		return changed;
	}

	function getModuleCol(module) {
		var settings = module && module.settings ? module.settings : {};
		var value = parseInt(settings.grid_col, 10);
		if (!value || value < 1) {
			return 1;
		}
		return Math.min(Math.max(1, 13 - getModuleSpan(module)), value);
	}

	function getModuleRow(module) {
		var settings = module && module.settings ? module.settings : {};
		var value = parseInt(settings.grid_row, 10);
		if (!value || value < 1) {
			return 1;
		}
		return Math.min(999, value);
	}

	function getModuleSpan(module) {
		var settings = module && module.settings ? module.settings : {};
		if (isModuleWidthLocked(module)) {
			return 12;
		}
		var value = parseInt(settings.grid_span, 10);
		if (!value || value < 3) {
			return 12;
		}
		return Math.min(12, value);
	}

	function getModuleGridRows(module) {
		if (!module) {
			return 1;
		}
		var baseRows = getModuleBaseRows(module);
		var rows = baseRows;
		if (module.id && moduleRowSpanCache[module.id]) {
			rows = Math.max(rows, moduleRowSpanCache[module.id]);
		}
		return rows;
	}

	function getModuleSavedRows(module) {
		var settings = module && module.settings ? module.settings : {};
		var value = parseInt(settings.grid_rows, 10);
		if (!value || value < 1) {
			return 1;
		}
		return Math.min(999, value);
	}

	function getModuleBaseRows(module) {
		var minHeight = getModuleMinHeight(module);
		if (!minHeight || minHeight < 1) {
			return 1;
		}
		return Math.max(1, Math.ceil(minHeight / getCanvasRowStep()));
	}

	function getCanvasRowStep() {
		var grid = getCanvasGridMetrics();
		return grid.step;
	}

	function getCanvasGridMetrics() {
		var styles = window.getComputedStyle(canvasEl);
		var rowHeight = parseFloat(styles.gridAutoRows) || 24;
		var rowGap = parseFloat(styles.rowGap || styles.gap) || 0;
		var step = Math.max(1, rowHeight + rowGap);
		return {
			rowHeight: rowHeight,
			rowGap: rowGap,
			step: step
		};
	}

	function isModuleWidthLocked(module) {
		var settings = module && module.settings ? module.settings : {};
		if (settings.lock_full_width == null) {
			return isDefaultFullWidthType(module && module.type);
		}
		return String(settings.lock_full_width) === '1';
	}

	function createQuickLayoutButtons(module, index) {
		var wrap = document.createElement('div');
		wrap.className = 'enews-builder-v2-quick-layout';

		var label = document.createElement('span');
		label.className = 'enews-builder-v2-quick-layout-label';
		label.textContent = config.l10n.quickLayouts;
		wrap.appendChild(label);

		var hasNext = index < state.modules.length - 1;
		var nextModule = hasNext ? state.modules[index + 1] : null;
		var shouldDisable = !hasNext || isModuleWidthLocked(module) || (nextModule && isModuleWidthLocked(nextModule));

		[
			{ text: '3/9', left: 3, right: 9 },
			{ text: '4/8', left: 4, right: 8 },
			{ text: '6/6', left: 6, right: 6 }
		].forEach(function (preset) {
			var button = document.createElement('button');
			button.type = 'button';
			button.className = 'enews-builder-v2-quick-layout-button';
			button.textContent = preset.text;
			button.disabled = shouldDisable;
			button.addEventListener('click', function () {
				if (!hasNext) {
					window.alert(config.l10n.quickLayoutNeedsNext);
					return;
				}
				applyQuickLayoutPreset(module.id, preset.left, preset.right);
			});
			wrap.appendChild(button);
		});

		if (shouldDisable) {
			var hint = document.createElement('span');
			hint.className = 'enews-builder-v2-quick-layout-hint';
			hint.textContent = !hasNext ? config.l10n.quickLayoutNeedsNext : config.l10n.quickLayoutLocked;
			wrap.appendChild(hint);
		}

		return wrap;
	}

	function createShortcodeMigrationActions(module) {
		if (module.type !== 'html') {
			return null;
		}

		var html = String(module.settings && module.settings.html ? module.settings.html : '');
		var migration = parseLegacyShortcode(html);
		if (!migration) {
			return null;
		}

		var wrap = document.createElement('div');
		wrap.className = 'enews-builder-v2-shortcode-migrate';
		var text = document.createElement('span');
		text.textContent = config.l10n.shortcodeDetected;
		wrap.appendChild(text);
		var button = document.createElement('button');
		button.type = 'button';
		button.className = 'button button-secondary button-small';
		button.textContent = config.l10n.convertShortcode;
		button.addEventListener('click', function () {
			convertHtmlModuleToTyped(module, migration);
		});
		wrap.appendChild(button);
		return wrap;
	}

	function parseLegacyShortcode(html) {
		var candidates = [
			{ tag: 'enews_products', type: 'products' },
			{ tag: 'enews_posts', type: 'posts' },
			{ tag: 'enews_post_links', type: 'posts', forcedLayout: 'links' },
			{ tag: 'enews_post', type: 'posts', forcedLayout: 'single' }
		];

		for (var i = 0; i < candidates.length; i += 1) {
			var candidate = candidates[i];
			var regex = new RegExp('\\\\[' + candidate.tag + '\\s+([^\\\\]]*)\\\\]', 'i');
			var match = html.match(regex);
			if (!match) {
				continue;
			}
			return {
				type: candidate.type,
				forcedLayout: candidate.forcedLayout || '',
				attrs: parseShortcodeAttrs(match[1] || '')
			};
		}

		return null;
	}

	function parseShortcodeAttrs(raw) {
		var attrs = {};
		var re = /(\w+)="([^"]*)"/g;
		var match;
		while ((match = re.exec(raw))) {
			attrs[match[1]] = match[2];
		}
		return attrs;
	}

	function convertHtmlModuleToTyped(module, migration) {
		var defaults = modules[migration.type] && modules[migration.type].defaults ? deepClone(modules[migration.type].defaults) : {};
		var nextSettings = Object.assign({}, defaults);
		Object.keys(migration.attrs || {}).forEach(function (key) {
			nextSettings[key] = migration.attrs[key];
		});
		if (migration.forcedLayout) {
			nextSettings.layout = migration.forcedLayout;
		}
		if (migration.type === 'posts' && migration.attrs.id && !migration.attrs.ids) {
			nextSettings.ids = migration.attrs.id;
		}

		nextSettings.grid_span = module.settings.grid_span || 12;
		nextSettings.canvas_min_height = module.settings.canvas_min_height || 0;
		nextSettings.lock_full_width = module.settings.lock_full_width || (isDefaultFullWidthType(migration.type) ? '1' : '0');

		module.type = migration.type;
		module.settings = nextSettings;
		normalizeModule(module);
		renderCanvas();
		renderSettings();
		schedulePreview();
		saveStateToInput();
	}

	function applyQuickLayoutPreset(moduleId, leftSpan, rightSpan) {
		var index = state.modules.findIndex(function (item) {
			return item.id === moduleId;
		});
		if (index === -1 || index >= state.modules.length - 1) {
			return;
		}

		var left = state.modules[index];
		var right = state.modules[index + 1];
		if (!left || !right || isModuleWidthLocked(left) || isModuleWidthLocked(right)) {
			window.alert(config.l10n.quickLayoutLocked);
			return;
		}

		left.settings.grid_span = leftSpan;
		right.settings.grid_span = rightSpan;
		renderCanvas();
		schedulePreview();
		saveStateToInput();
	}

	function getModuleMinHeight(module) {
		var settings = module && module.settings ? module.settings : {};
		var value = parseInt(settings.canvas_min_height, 10);
		if (!value || value < 0) {
			return 0;
		}
		return Math.min(480, value);
	}

	function buildSummary(module) {
		var settings = module.settings || {};
		switch (module.type) {
			case 'heading':
				return settings.text || '';
			case 'text':
				return stripHtml(settings.text || '').slice(0, 90);
			case 'button':
				return (settings.label || '') + ' -> ' + (settings.url || '');
			case 'image':
				return settings.url || config.l10n.imageEmpty;
			case 'hero':
				return settings.title || '';
			case 'columns_2':
				return config.l10n.leftColumn + ' / ' + config.l10n.rightColumn;
			case 'cta_box':
				return settings.title || '';
			case 'divider':
				return config.l10n.dividerSummary;
			case 'spacer':
				return (settings.height || 0) + 'px';
			case 'social':
				return [settings.facebook, settings.instagram, settings.linkedin, settings.x, settings.youtube].filter(Boolean).length + ' Links';
			case 'footer':
				return settings.company || '';
			case 'html':
				return stripHtml(settings.html || '').slice(0, 90);
			case 'products':
			case 'posts':
				return (settings.ids || config.l10n.noIds) + ' / ' + (settings.layout || '');
			default:
				return '';
		}
	}

	function renderSettings() {
		settingsEl.innerHTML = '';
		renderGlobalSettings();
	}

	function createInlineEditor(module) {
		var editor = document.createElement('div');
		editor.className = 'enews-builder-v2-inline-editor';
		var title = document.createElement('div');
		title.className = 'enews-builder-v2-inline-editor-title';
		title.textContent = config.l10n.moduleSettings;
		editor.appendChild(title);

		var migration = createShortcodeMigrationActions(module);
		if (migration) {
			editor.appendChild(migration);
		}

		getSchema(module.type).forEach(function (field) {
			editor.appendChild(createField(field, module));
		});

		setTimeout(function () {
			bindFieldEvents(module, editor);
		}, 0);

		return editor;
	}

	function renderGlobalSettings() {
		[
			{ key: 'full_width', label: config.l10n.fullWidth, type: 'toggle' },
			{ key: 'content_width', label: config.l10n.contentWidth, type: 'number' },
			{ key: 'background_color', label: config.l10n.backgroundColor, type: 'color' },
			{ key: 'content_background', label: config.l10n.contentBackground, type: 'color' },
			{ key: 'text_color', label: config.l10n.textColor, type: 'color' },
			{ key: 'font_family', label: config.l10n.fontFamily, type: 'font_picker' },
			{ key: 'heading_font_size', label: config.l10n.headingFontSize, type: 'number', min: 12, max: 72, step: 1 },
			{ key: 'heading_color', label: config.l10n.headingColor, type: 'color' },
			{ key: 'heading_decoration', label: config.l10n.headingDecoration, type: 'select', options: getTextDecorationOptions() },
			{ key: 'paragraph_font_size', label: config.l10n.paragraphFontSize, type: 'number', min: 10, max: 36, step: 1 },
			{ key: 'paragraph_color', label: config.l10n.paragraphColor, type: 'color' },
			{ key: 'paragraph_decoration', label: config.l10n.paragraphDecoration, type: 'select', options: getTextDecorationOptions() },
			{ key: 'quote_font_size', label: config.l10n.quoteFontSize, type: 'number', min: 10, max: 42, step: 1 },
			{ key: 'quote_color', label: config.l10n.quoteColor, type: 'color' },
			{ key: 'quote_decoration', label: config.l10n.quoteDecoration, type: 'select', options: getTextDecorationOptions() },
			{ key: 'section_gap', label: config.l10n.sectionGap, type: 'number' },
			{ key: 'branding_html', label: config.l10n.brandingHtml, type: 'textarea', rows: 4 },
			{ key: 'contact_info', label: config.l10n.contactInfo, type: 'textarea', rows: 4 },
			{ key: 'view_browser_html', label: config.l10n.viewBrowserHtml, type: 'textarea', rows: 3 }
		].forEach(function (field) {
			settingsEl.appendChild(createGlobalField(field));
		});

		settingsEl.querySelectorAll('[data-global-key]').forEach(function (input) {
			input.addEventListener('input', function () {
				var key = input.dataset.globalKey;
				state.global[key] = readFieldValue(input);
				schedulePreview();
				saveStateToInput();
			});
			input.addEventListener('change', function () {
				var key = input.dataset.globalKey;
				state.global[key] = readFieldValue(input);
				schedulePreview();
				saveStateToInput();
			});
		});
	}

	function syncSubjectInput() {
		if (!subjectInputEl) {
			return;
		}

		var nextValue = String((state.global && state.global.subject) || '');
		if (subjectInputEl.value !== nextValue) {
			subjectInputEl.value = nextValue;
		}
	}

	function createGlobalField(field) {
		var wrapper = document.createElement('label');
		wrapper.className = 'enews-builder-v2-field' + (field.type === 'toggle' ? ' is-toggle' : '');
		wrapper.innerHTML = '<span>' + escapeHtml(field.label) + '</span>';
		var value = state.global[field.key] == null ? '' : state.global[field.key];
		var input;
		if (field.type === 'textarea') {
			input = document.createElement('textarea');
			input.rows = field.rows || 4;
			input.value = value;
		} else if (field.type === 'select') {
			input = document.createElement('select');
			var hasSelectedOption = false;
			Object.keys(field.options || {}).forEach(function (optionValue) {
				var option = document.createElement('option');
				option.value = optionValue;
				option.textContent = field.options[optionValue];
				if (String(optionValue) === String(value)) {
					option.selected = true;
					hasSelectedOption = true;
				}
				input.appendChild(option);
			});
			if (!hasSelectedOption && String(value).trim() !== '') {
				var customOption = document.createElement('option');
				customOption.value = String(value);
				customOption.textContent = String(value);
				customOption.selected = true;
				input.appendChild(customOption);
			}
		} else if (field.type === 'font_picker') {
			wrapper.appendChild(createFontPickerControl());
			return wrapper;
		} else if (field.type === 'color') {
			wrapper.appendChild(createColorPairControl(value, field.key, 'global'));
			return wrapper;
		} else if (field.type === 'toggle') {
			input = document.createElement('input');
			input.type = 'checkbox';
			input.checked = String(value) === '1';
			input.value = '1';
		} else {
			input = document.createElement('input');
			input.type = field.type;
			input.value = value;
		}
		input.dataset.globalKey = field.key;
		wrapper.appendChild(input);
		return wrapper;
	}

	function normalizeHexColor(value) {
		var raw = String(value || '').trim();
		if (!raw) {
			return null;
		}
		if (raw.charAt(0) !== '#') {
			raw = '#' + raw;
		}
		if (/^#[0-9a-fA-F]{3}$/.test(raw)) {
			return (
				'#' +
				raw.charAt(1) + raw.charAt(1) +
				raw.charAt(2) + raw.charAt(2) +
				raw.charAt(3) + raw.charAt(3)
			).toLowerCase();
		}
		if (/^#[0-9a-fA-F]{6}$/.test(raw)) {
			return raw.toLowerCase();
		}
		return null;
	}

	function createColorPairControl(value, key, scope) {
		var wrap = document.createElement('div');
		wrap.className = 'enews-builder-v2-color-pair';

		var normalized = normalizeHexColor(value) || '#000000';
		var inputId = 'enews_color_' + scope + '_' + key + '_' + Math.random().toString(16).slice(2, 8);

		var colorInput = document.createElement('input');
		colorInput.type = 'color';
		colorInput.value = normalized;
		colorInput.id = inputId;
		colorInput.dataset[scope + 'Key'] = key;
		wrap.appendChild(colorInput);

		var hexInput = document.createElement('input');
		hexInput.type = 'text';
		hexInput.value = normalized;
		hexInput.dataset[scope + 'Key'] = key;
		hexInput.dataset.colorHex = '1';
		hexInput.dataset.colorSource = inputId;
		hexInput.maxLength = 7;
		hexInput.autocapitalize = 'off';
		hexInput.autocomplete = 'off';
		hexInput.spellcheck = false;
		wrap.appendChild(hexInput);

		colorInput.addEventListener('input', function () {
			hexInput.value = colorInput.value.toLowerCase();
		});

		hexInput.addEventListener('input', function () {
			var next = normalizeHexColor(hexInput.value);
			if (next) {
				colorInput.value = next;
			}
		});

		hexInput.addEventListener('blur', function () {
			var next = normalizeHexColor(hexInput.value);
			hexInput.value = next || colorInput.value;
		});

		return wrap;
	}

	function getSchema(type) {
		var schema;
		switch (type) {
			case 'heading':
				schema = [
					{ key: 'text', label: config.l10n.text, type: 'text' },
					{ key: 'level', label: config.l10n.level, type: 'select', options: { h1: 'H1', h2: 'H2', h3: 'H3' } },
					{ key: 'align', label: config.l10n.align, type: 'select', options: getAlignOptions() },
					{ key: 'color', label: config.l10n.color, type: 'color' },
					{ key: 'font_size', label: config.l10n.fontSize, type: 'number' }
				];
				break;
			case 'text':
				schema = [
					{ key: 'text', label: config.l10n.text, type: 'textarea' },
					{ key: 'align', label: config.l10n.align, type: 'select', options: getAlignOptions() },
					{ key: 'color', label: config.l10n.color, type: 'color' },
					{ key: 'font_size', label: config.l10n.fontSize, type: 'number' }
				];
				break;
			case 'button':
				schema = [
					{ key: 'label', label: config.l10n.label, type: 'text' },
					{ key: 'url', label: config.l10n.url, type: 'url' },
					{ key: 'align', label: config.l10n.align, type: 'select', options: getAlignOptions() },
					{ key: 'background', label: config.l10n.backgroundColor, type: 'color' },
					{ key: 'color', label: config.l10n.textColor, type: 'color' },
					{ key: 'radius', label: config.l10n.radius, type: 'number' }
				];
				break;
			case 'image':
				schema = [
					{ key: 'url', label: config.l10n.image, type: 'media', idKey: 'media_id', altKey: 'alt' },
					{ key: 'alt', label: config.l10n.altText, type: 'text' },
					{ key: 'link', label: config.l10n.linkUrl, type: 'url' },
					{ key: 'align', label: config.l10n.align, type: 'select', options: getAlignOptions() },
					{ key: 'width', label: config.l10n.width, type: 'number' }
				];
				break;
			case 'hero':
				schema = [
					{ key: 'image_url', label: config.l10n.image, type: 'media', altKey: 'image_alt' },
					{ key: 'image_alt', label: config.l10n.altText, type: 'text' },
					{ key: 'eyebrow', label: config.l10n.eyebrow, type: 'text' },
					{ key: 'title', label: config.l10n.title, type: 'text' },
					{ key: 'text', label: config.l10n.text, type: 'textarea' },
					{ key: 'button_label', label: config.l10n.buttonText, type: 'text' },
					{ key: 'button_url', label: config.l10n.url, type: 'url' },
					{ key: 'align', label: config.l10n.align, type: 'select', options: getAlignOptions() },
					{ key: 'background', label: config.l10n.backgroundColor, type: 'color' },
					{ key: 'text_color', label: config.l10n.textColor, type: 'color' },
					{ key: 'button_background', label: config.l10n.buttonBackground, type: 'color' },
					{ key: 'button_color', label: config.l10n.buttonColor, type: 'color' }
				];
				break;
			case 'columns_2':
				schema = [
					{ key: 'left_html', label: config.l10n.leftColumn, type: 'textarea' },
					{ key: 'right_html', label: config.l10n.rightColumn, type: 'textarea' },
					{ key: 'left_background', label: config.l10n.leftBackground, type: 'color' },
					{ key: 'right_background', label: config.l10n.rightBackground, type: 'color' },
					{ key: 'gap', label: config.l10n.columnGap, type: 'number' }
				];
				break;
			case 'cta_box':
				schema = [
					{ key: 'title', label: config.l10n.title, type: 'text' },
					{ key: 'text', label: config.l10n.text, type: 'textarea' },
					{ key: 'button_label', label: config.l10n.buttonText, type: 'text' },
					{ key: 'button_url', label: config.l10n.url, type: 'url' },
					{ key: 'align', label: config.l10n.align, type: 'select', options: getAlignOptions() },
					{ key: 'background', label: config.l10n.backgroundColor, type: 'color' },
					{ key: 'text_color', label: config.l10n.textColor, type: 'color' },
					{ key: 'button_background', label: config.l10n.buttonBackground, type: 'color' },
					{ key: 'button_color', label: config.l10n.buttonColor, type: 'color' }
				];
				break;
			case 'divider':
				schema = [
					{ key: 'color', label: config.l10n.color, type: 'color' },
					{ key: 'thickness', label: config.l10n.thickness, type: 'number' }
				];
				break;
			case 'spacer':
				schema = [
					{ key: 'height', label: config.l10n.height, type: 'number' }
				];
				break;
			case 'social':
				schema = [
					{ key: 'title', label: config.l10n.title, type: 'text' },
					{ key: 'align', label: config.l10n.align, type: 'select', options: getAlignOptions() },
					{ key: 'facebook', label: config.l10n.facebook, type: 'url' },
					{ key: 'instagram', label: config.l10n.instagram, type: 'url' },
					{ key: 'linkedin', label: config.l10n.linkedin, type: 'url' },
					{ key: 'x', label: config.l10n.xLabel, type: 'url' },
					{ key: 'youtube', label: config.l10n.youtube, type: 'url' }
				];
				break;
			case 'footer':
				schema = [
					{ key: 'company', label: config.l10n.company, type: 'text' },
					{ key: 'address', label: config.l10n.address, type: 'textarea' },
					{ key: 'legal_text', label: config.l10n.legalText, type: 'textarea' },
					{ key: 'manage_url', label: config.l10n.manageUrl, type: 'url' },
					{ key: 'view_url', label: config.l10n.viewUrl, type: 'url' },
					{ key: 'unsubscribe_url', label: config.l10n.unsubscribeUrl, type: 'url' },
					{ key: 'align', label: config.l10n.align, type: 'select', options: getAlignOptions() },
					{ key: 'background', label: config.l10n.backgroundColor, type: 'color' },
					{ key: 'text_color', label: config.l10n.textColor, type: 'color' },
					{ key: 'link_color', label: config.l10n.linkColor, type: 'color' }
				];
				break;
			case 'html':
				schema = [
					{ key: 'html', label: config.l10n.html, type: 'textarea' }
				];
				break;
			case 'products':
				schema = [
					{ key: 'query_mode', label: config.l10n.queryMode, type: 'select', options: { manual: config.l10n.queryManual, latest: config.l10n.queryLatest, trigger: config.l10n.queryTrigger } },
					{ key: 'query_limit', label: config.l10n.queryLimit, type: 'number', min: 1, max: 24, step: 1 },
					{ key: 'ids', label: config.l10n.ids, type: 'item_picker', itemType: 'products' },
					{ key: 'layout', label: config.l10n.layout, type: 'select', options: { single: config.l10n.single, list: config.l10n.list, grid: config.l10n.grid } },
					{ key: 'show_image', label: config.l10n.showImage, type: 'select', options: getBooleanOptions() },
					{ key: 'show_price', label: config.l10n.showPrice, type: 'select', options: getBooleanOptions() },
					{ key: 'show_old_price', label: config.l10n.showOldPrice, type: 'select', options: getBooleanOptions() },
					{ key: 'show_button', label: config.l10n.showButton, type: 'select', options: getBooleanOptions() },
					{ key: 'show_badge', label: config.l10n.showBadge, type: 'select', options: getBooleanOptions() },
					{ key: 'track', label: config.l10n.track, type: 'select', options: getBooleanOptions() },
					{ key: 'badge_text', label: config.l10n.badgeText, type: 'text' },
					{ key: 'button_text', label: config.l10n.buttonText, type: 'text' }
				];
				break;
			case 'posts':
				schema = [
					{ key: 'query_mode', label: config.l10n.queryMode, type: 'select', options: { manual: config.l10n.queryManual, latest: config.l10n.queryLatest, trigger: config.l10n.queryTrigger } },
					{ key: 'query_scope', label: config.l10n.queryScope, type: 'select', options: { all: config.l10n.queryScopeAll, week: config.l10n.queryScopeWeek, month: config.l10n.queryScopeMonth } },
					{ key: 'query_limit', label: config.l10n.queryLimit, type: 'number', min: 1, max: 24, step: 1 },
					{ key: 'ids', label: config.l10n.ids, type: 'item_picker', itemType: 'posts' },
					{ key: 'layout', label: config.l10n.layout, type: 'select', options: { single: config.l10n.single, links: config.l10n.links, grid: config.l10n.grid, slider: config.l10n.slider } },
					{ key: 'show_image', label: config.l10n.showImage, type: 'select', options: getBooleanOptions() },
					{ key: 'show_excerpt', label: config.l10n.showExcerpt, type: 'select', options: getBooleanOptions() },
					{ key: 'excerpt_words', label: config.l10n.excerptWords, type: 'number' },
					{ key: 'show_button', label: config.l10n.showButton, type: 'select', options: getBooleanOptions() },
					{ key: 'track', label: config.l10n.track, type: 'select', options: getBooleanOptions() },
					{ key: 'button_text', label: config.l10n.buttonText, type: 'text' }
				];
				break;
			default:
				schema = [];
				break;
		}

		return schema.concat([
			{ key: 'lock_full_width', label: config.l10n.lockFullWidth, type: 'select', options: getBooleanOptions() },
			{ key: 'grid_span', label: config.l10n.moduleWidthColumns, type: 'range', min: 3, max: 12, step: 1 },
			{ key: 'canvas_min_height', label: config.l10n.moduleMinHeight, type: 'number', min: 0, max: 480, step: 10 }
		]);
	}

	function getAlignOptions() {
		return { left: config.l10n.left, center: config.l10n.center, right: config.l10n.right };
	}

	function getBooleanOptions() {
		return { '1': config.l10n.yes, '0': config.l10n.no };
	}

	function getTextDecorationOptions() {
		return {
			none: config.l10n.decorationNone,
			underline: config.l10n.decorationUnderline,
			'line-through': config.l10n.decorationLineThrough,
			overline: config.l10n.decorationOverline
		};
	}

	function getFontFamilyOptions() {
		var options = {
			'Arial, sans-serif': 'Arial',
			'Arial, Helvetica, sans-serif': 'Arial',
			'Helvetica, Arial, sans-serif': 'Helvetica',
			'Verdana, Geneva, sans-serif': 'Verdana',
			'Tahoma, Geneva, sans-serif': 'Tahoma',
			'Trebuchet MS, Helvetica, sans-serif': 'Trebuchet MS',
			'Times New Roman, serif': 'Times New Roman',
			'Georgia, Times New Roman, serif': 'Georgia',
			'Times New Roman, Times, serif': 'Times New Roman',
			'Courier New, Courier, monospace': 'Courier New',
			'Segoe UI, Tahoma, Geneva, sans-serif': 'Segoe UI',
			'Roboto, Arial, sans-serif': 'Roboto',
			'Open Sans, Arial, sans-serif': 'Open Sans',
			'Lato, Arial, sans-serif': 'Lato',
			'Montserrat, Arial, sans-serif': 'Montserrat',
			'Poppins, Arial, sans-serif': 'Poppins',
			'Nunito, Arial, sans-serif': 'Nunito'
		};

		var catalog = Array.isArray(config.googleFontCatalog) ? config.googleFontCatalog : [];
		catalog.forEach(function (fontName) {
			var safe = String(fontName || '').trim();
			if (!safe) {
				return;
			}
			var key = '"' + safe + '", Arial, sans-serif';
			if (!options[key]) {
				options[key] = safe;
			}
		});

		return options;
	}

	function normalizeNeedle(value) {
		return String(value || '').trim().toLowerCase();
	}

	function findBestMatch(options, query) {
		var needle = normalizeNeedle(query);
		if (!needle) {
			return options.length ? options[0] : null;
		}

		for (var i = 0; i < options.length; i += 1) {
			if (normalizeNeedle(options[i].label) === needle || normalizeNeedle(options[i].familyName) === needle) {
				return options[i];
			}
		}

		for (var j = 0; j < options.length; j += 1) {
			if (normalizeNeedle(options[j].label).indexOf(needle) === 0 || normalizeNeedle(options[j].familyName).indexOf(needle) === 0) {
				return options[j];
			}
		}

		for (var k = 0; k < options.length; k += 1) {
			if (normalizeNeedle(options[k].label).indexOf(needle) !== -1 || normalizeNeedle(options[k].familyName).indexOf(needle) !== -1) {
				return options[k];
			}
		}

		return null;
	}

	function createFontPickerControl() {
		var group = document.createElement('div');
		group.className = 'enews-builder-v2-font-picker';
		var endpoint = String(config.fontsCssEndpoint || '').trim();

		var label = document.createElement('small');
		label.className = 'enews-builder-v2-font-picker-label';
		label.textContent = config.l10n.fontSearchTitle;
		group.appendChild(label);

		var cssInput = document.createElement('input');
		cssInput.type = 'hidden';
		cssInput.dataset.globalKey = 'font_css_url';
		cssInput.value = state.global.font_css_url || '';
		group.appendChild(cssInput);

		var fontInput = document.createElement('input');
		fontInput.type = 'hidden';
		fontInput.dataset.globalKey = 'font_family';
		fontInput.value = state.global.font_family || 'Arial, sans-serif';
		group.appendChild(fontInput);

		var loadRow = document.createElement('div');
		loadRow.className = 'enews-builder-v2-font-picker-load-row';

		var searchInput = document.createElement('input');
		searchInput.type = 'search';
		searchInput.placeholder = config.l10n.fontSearchPlaceholder;
		loadRow.appendChild(searchInput);

		var searchButton = document.createElement('button');
		searchButton.type = 'button';
		searchButton.className = 'button button-secondary';
		searchButton.textContent = config.l10n.searchButton;
		loadRow.appendChild(searchButton);

		group.appendChild(loadRow);

		var status = document.createElement('div');
		status.className = 'enews-builder-v2-font-picker-status';
		status.textContent = endpoint ? '' : config.l10n.fontServerMissing;
		group.appendChild(status);

		var suggestions = document.createElement('div');
		suggestions.className = 'enews-builder-v2-font-suggestions';
		group.appendChild(suggestions);

		var allOptions = [];
		var lastVisibleOptions = [];

		function pushOption(label, value, source, familyName) {
			if (!label || !value) {
				return;
			}
			var normalizedLabel = normalizeNeedle(label);
			var normalizedFamily = normalizeNeedle(familyName || label);
			var existingIndex = -1;
			for (var optionIndex = 0; optionIndex < allOptions.length; optionIndex += 1) {
				var existingItem = allOptions[optionIndex];
				if (normalizeNeedle(existingItem.label) === normalizedLabel || normalizeNeedle(existingItem.familyName || existingItem.label) === normalizedFamily) {
					existingIndex = optionIndex;
					break;
				}
			}
			var nextItem = {
				label: String(label),
				value: String(value),
				source: source || 'websafe',
				familyName: familyName || ''
			};
			if (existingIndex === -1) {
				allOptions.push(nextItem);
				return;
			}
			var existing = allOptions[existingIndex];
			var nextIsHosted = nextItem.source !== 'websafe';
			var existingIsHosted = existing.source !== 'websafe';
			if (nextIsHosted && !existingIsHosted) {
				allOptions[existingIndex] = nextItem;
			}
		}

		Object.keys(getFontFamilyOptions()).forEach(function (value) {
			var labelText = getFontFamilyOptions()[value];
			var optionSource = String(value).charAt(0) === '"' ? 'hosted-catalog' : 'websafe';
			var familyName = optionSource === 'websafe' ? '' : getPrimaryFontName(value);
			pushOption(labelText, value, optionSource, familyName);
		});

		function getPrimaryFontName(fontStack) {
			return String(fontStack || '')
				.split(',')[0]
				.replace(/^\s*['\"]?|['\"]?\s*$/g, '')
				.trim();
		}

		function buildHostedStack(fontName) {
			var safe = String(fontName || '').trim();
			if (!safe) {
				return '';
			}
			return '"' + safe + '", Arial, sans-serif';
		}

		function buildHostedCssUrl(fontName) {
			if (!endpoint) {
				return '';
			}
			return endpoint + (endpoint.indexOf('?') === -1 ? '?' : '&') + 'family=' + encodeURIComponent(fontName).replace(/%20/g, '+');
		}

		function getSelectedMeta() {
			var selectedValue = String(fontInput.value || '');
			for (var i = 0; i < allOptions.length; i += 1) {
				if (String(allOptions[i].value) === selectedValue) {
					return allOptions[i];
				}
			}
			return null;
		}

		function syncCssInputWithSelection(triggerEvent) {
			var selected = getSelectedMeta();
			if (!selected || selected.source === 'websafe') {
				cssInput.value = '';
			} else {
				cssInput.value = buildHostedCssUrl(selected.familyName || getPrimaryFontName(selected.value));
			}
			if (triggerEvent) {
				cssInput.dispatchEvent(new Event('change', { bubbles: true }));
			}
		}

		function applyOption(item, saveNow) {
			if (!item) {
				return;
			}
			fontInput.value = item.value;
			state.global.font_family = item.value;
			syncCssInputWithSelection(saveNow);
			if (saveNow) {
				fontInput.dispatchEvent(new Event('change', { bubbles: true }));
			}
		}

		function renderOptions(filter) {
			var needle = String(filter || '').trim().toLowerCase();
			var currentValue = String(fontInput.value || state.global.font_family || '');
			suggestions.innerHTML = '';

			var visible = allOptions.filter(function (item) {
				if (!needle) {
					return true;
				}
				return normalizeNeedle(item.label).indexOf(needle) !== -1 || normalizeNeedle(item.familyName).indexOf(needle) !== -1;
			});

			if (!visible.length && !needle) {
				visible = allOptions.slice();
			}
			lastVisibleOptions = visible.slice();

			if (!visible.length) {
				var empty = document.createElement('div');
				empty.className = 'enews-builder-v2-font-suggestion is-empty';
				empty.textContent = config.l10n.noSearchResults;
				suggestions.appendChild(empty);
				return;
			}

			visible.slice(0, 10).forEach(function (item) {
				var button = document.createElement('button');
				button.type = 'button';
				button.className = 'enews-builder-v2-font-suggestion';
				button.style.fontFamily = item.value;
				if (String(item.value) === currentValue) {
					button.classList.add('is-active');
				}
				button.textContent = item.label;
				button.addEventListener('click', function () {
					pickFontOption(item);
				});
				suggestions.appendChild(button);
			});

		}

		function fetchHostedFont(familyName) {
			var requested = String(familyName || '').trim();
			if (!requested) {
				return Promise.reject(new Error('missing-family'));
			}
			if (!endpoint) {
				return Promise.reject(new Error('missing-endpoint'));
			}
			var url = buildHostedCssUrl(requested);
			return window.fetch(url, {
				method: 'GET',
				credentials: 'omit'
			}).then(function (response) {
				if (!response.ok) {
					throw new Error('http');
				}
				return response.text();
			}).then(function (text) {
				var hasFace = String(text || '').toLowerCase().indexOf('@font-face') !== -1;
				if (!hasFace) {
					throw new Error('not-found');
				}
				return requested;
			});
		}

		function pickFontOption(item) {
			if (!item) {
				status.textContent = config.l10n.fontSearchNeedQuery;
				return;
			}

			applyOption(item, true);
			searchInput.value = item.label;
			status.textContent = config.l10n.fontSelectedOne.replace('%s', item.label);
			renderOptions(searchInput.value);

			if (item.source !== 'websafe') {
				fetchHostedFont(item.familyName || getPrimaryFontName(item.value)).then(function () {
					status.textContent = config.l10n.fontLoadedOne.replace('%s', item.label);
				}).catch(function () {
					status.textContent = config.l10n.fontNotFound;
				});
			}
		}

		searchInput.addEventListener('input', function () {
			renderOptions(searchInput.value);
			if (String(searchInput.value || '').trim() === '') {
				status.textContent = '';
			}
		});

		searchInput.addEventListener('keydown', function (event) {
			if (event.key === 'Enter') {
				event.preventDefault();
				var rawQuery = String(searchInput.value || '').trim();
				var bestMatch = findBestMatch(lastVisibleOptions, rawQuery);
				pickFontOption(bestMatch);
			}
		});

		searchButton.addEventListener('click', function () {
			var rawQuery = String(searchInput.value || '').trim();
			var bestMatch = findBestMatch(lastVisibleOptions, rawQuery);
			if (!bestMatch) {
				status.textContent = config.l10n.fontNotFound;
				return;
			}
			pickFontOption(bestMatch);
		});

		var initialFamily = getPrimaryFontName(state.global.font_family || '');
		if (state.global.font_css_url && initialFamily) {
			pushOption(initialFamily, buildHostedStack(initialFamily), 'hosted', initialFamily);
		}
		var initialMatch = findBestMatch(allOptions, state.global.font_family || '');
		if (initialMatch) {
			fontInput.value = initialMatch.value;
			searchInput.value = initialMatch.label;
		}

		renderOptions(searchInput.value || '');
		syncCssInputWithSelection(false);
		return group;
	}

	function createField(field, module) {
		var wrapper = document.createElement('label');
		wrapper.className = 'enews-builder-v2-field';
		wrapper.innerHTML = '<span>' + escapeHtml(field.label) + '</span>';
		var value = module.settings[field.key] == null ? '' : module.settings[field.key];
		var input;

		if (field.type === 'textarea') {
			input = document.createElement('textarea');
			input.rows = 5;
			input.value = value;
		} else if (field.type === 'select') {
			input = document.createElement('select');
			Object.keys(field.options || {}).forEach(function (optionValue) {
				var option = document.createElement('option');
				option.value = optionValue;
				option.textContent = field.options[optionValue];
				if (String(optionValue) === String(value)) {
					option.selected = true;
				}
				input.appendChild(option);
			});
		} else if (field.type === 'media') {
			wrapper.appendChild(createMediaControl(field, module));
			return wrapper;
		} else if (field.type === 'item_picker') {
			wrapper.appendChild(createItemPickerControl(field, module));
			return wrapper;
		} else if (field.type === 'color') {
			wrapper.appendChild(createColorPairControl(value, field.key, 'module'));
			return wrapper;
		} else {
			input = document.createElement('input');
			input.type = field.type;
			input.value = value;
			if (field.min != null) {
				input.min = String(field.min);
			}
			if (field.max != null) {
				input.max = String(field.max);
			}
			if (field.step != null) {
				input.step = String(field.step);
			}
		}

		input.dataset.moduleKey = field.key;
		if (field.key === 'grid_span' && isModuleWidthLocked(module)) {
			input.disabled = true;
		}
		wrapper.appendChild(input);
		return wrapper;
	}

	function createMediaControl(field, module) {
		var group = document.createElement('div');
		group.className = 'enews-builder-v2-media-control';

		var input = document.createElement('input');
		input.type = 'url';
		input.value = module.settings[field.key] || '';
		input.dataset.moduleKey = field.key;
		group.appendChild(input);

		var selectButton = document.createElement('button');
		selectButton.type = 'button';
		selectButton.className = 'button button-secondary';
		selectButton.textContent = module.settings[field.key] ? config.l10n.changeImage : config.l10n.selectImage;
		selectButton.addEventListener('click', function () {
			openMediaLibrary(module, field, input, selectButton);
		});
		group.appendChild(selectButton);

		var clearButton = document.createElement('button');
		clearButton.type = 'button';
		clearButton.className = 'button button-link-delete';
		clearButton.textContent = config.l10n.clearImage;
		clearButton.addEventListener('click', function () {
			input.value = '';
			module.settings[field.key] = '';
			if (field.idKey) {
				module.settings[field.idKey] = 0;
			}
			renderCanvas();
			schedulePreview();
			saveStateToInput();
			selectButton.textContent = config.l10n.selectImage;
		});
		group.appendChild(clearButton);

		return group;
	}

	function createItemPickerControl(field, module) {
		var group = document.createElement('div');
		group.className = 'enews-builder-v2-item-picker';
		group.dataset.itemType = field.itemType;

		var hidden = document.createElement('input');
		hidden.type = 'hidden';
		hidden.value = sanitizeIds(module.settings[field.key] || '');
		hidden.dataset.moduleKey = field.key;
		group.appendChild(hidden);

		var searchRow = document.createElement('div');
		searchRow.className = 'enews-builder-v2-item-picker-search';

		var searchInput = document.createElement('input');
		searchInput.type = 'search';
		searchInput.placeholder = field.itemType === 'products' ? config.l10n.searchPlaceholderProducts : config.l10n.searchPlaceholderPosts;
		searchRow.appendChild(searchInput);

		var searchButton = document.createElement('button');
		searchButton.type = 'button';
		searchButton.className = 'button button-secondary';
		searchButton.textContent = config.l10n.searchButton;
		searchRow.appendChild(searchButton);

		group.appendChild(searchRow);

		var selectedLabel = document.createElement('div');
		selectedLabel.className = 'enews-builder-v2-item-picker-label';
		selectedLabel.textContent = config.l10n.selectedItems;
		group.appendChild(selectedLabel);

		var selectedList = document.createElement('div');
		selectedList.className = 'enews-builder-v2-item-picker-selected';
		group.appendChild(selectedList);

		var resultsLabel = document.createElement('div');
		resultsLabel.className = 'enews-builder-v2-item-picker-label';
		resultsLabel.textContent = config.l10n.searchResults;
		group.appendChild(resultsLabel);

		var resultBox = document.createElement('div');
		resultBox.className = 'enews-builder-v2-item-picker-results';
		group.appendChild(resultBox);

		function updateSelected(newIds, shouldPreview) {
			var ids = newIds.map(function (id) { return String(parseInt(id, 10)); }).filter(function (id) { return id && id !== 'NaN'; });
			hidden.value = ids.join(',');
			module.settings[field.key] = hidden.value;
			renderSelected(ids);
			renderCanvas();
			saveStateToInput();
			if (shouldPreview) {
				schedulePreview();
			}
		}

		function renderSelected(ids) {
			selectedList.innerHTML = '';
			if (!ids.length) {
				var empty = document.createElement('div');
				empty.className = 'enews-builder-v2-item-picker-empty';
				empty.textContent = config.l10n.noIds;
				selectedList.appendChild(empty);
				return;
			}

			ids.forEach(function (id) {
				var chip = document.createElement('button');
				chip.type = 'button';
				chip.className = 'enews-builder-v2-item-chip';
				var item = itemCache[id];
				chip.innerHTML = '<span>' + escapeHtml(item ? item.title : ('#' + id)) + ' (' + escapeHtml(id) + ')</span><strong aria-hidden="true">×</strong>';
				chip.title = config.l10n.removeItem;
				chip.addEventListener('click', function () {
					updateSelected(ids.filter(function (currentId) { return currentId !== id; }), true);
				});
				selectedList.appendChild(chip);
			});
		}

		function renderResults(items) {
			resultBox.innerHTML = '';
			if (!items.length) {
				var empty = document.createElement('div');
				empty.className = 'enews-builder-v2-item-picker-empty';
				empty.textContent = config.l10n.noSearchResults;
				resultBox.appendChild(empty);
				return;
			}

			var selectedIds = hidden.value ? hidden.value.split(',').filter(Boolean) : [];
			items.forEach(function (item) {
				itemCache[String(item.id)] = item;
				var row = document.createElement('div');
				row.className = 'enews-builder-v2-item-result';
				var meta = item.type ? ' [' + item.type + ']' : '';
				row.innerHTML = '<div><strong>' + escapeHtml(item.title || ('#' + item.id)) + '</strong><div class="enews-builder-v2-item-result-meta">ID ' + escapeHtml(String(item.id)) + meta + '</div></div>';
				var addButton = document.createElement('button');
				addButton.type = 'button';
				addButton.className = 'button button-small';
				addButton.textContent = selectedIds.indexOf(String(item.id)) !== -1 ? config.l10n.removeItem : config.l10n.addItem;
				addButton.addEventListener('click', function () {
					var updated = hidden.value ? hidden.value.split(',').filter(Boolean) : [];
					var idString = String(item.id);
					if (updated.indexOf(idString) !== -1) {
						updated = updated.filter(function (currentId) { return currentId !== idString; });
					} else {
						updated.push(idString);
					}
					updateSelected(updated, true);
					renderResults(items);
				});
				row.appendChild(addButton);
				resultBox.appendChild(row);
			});
		}

		function searchItems(query, includeIds) {
			var body = new URLSearchParams();
			body.set('action', 'enews_builder_v2_search_items');
			body.set('nonce', config.searchNonce || '');
			body.set('itemType', field.itemType);
			body.set('query', query || '');
			if (includeIds && includeIds.length) {
				body.set('includeIds', includeIds.join(','));
			}

			return window.fetch(config.ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString(),
				credentials: 'same-origin'
			}).then(function (response) {
				return response.json();
			}).then(function (payload) {
				if (!payload || !payload.success || !payload.data || !Array.isArray(payload.data.items)) {
					throw new Error('search');
				}
				return payload.data.items;
			});
		}

		function runSearch() {
			var query = (searchInput.value || '').trim();
			if (query.length < 2) {
				resultBox.innerHTML = '<div class="enews-builder-v2-item-picker-empty">' + escapeHtml(config.l10n.searchMinChars) + '</div>';
				return;
			}
			resultBox.innerHTML = '<div class="enews-builder-v2-item-picker-empty">' + escapeHtml(config.l10n.previewLoading) + '</div>';
			searchItems(query, []).then(function (items) {
				renderResults(items);
			}).catch(function () {
				resultBox.innerHTML = '<div class="enews-builder-v2-item-picker-empty is-error">' + escapeHtml(config.l10n.searchError) + '</div>';
			});
		}

		searchButton.addEventListener('click', runSearch);
		searchInput.addEventListener('input', function () {
			clearTimeout(itemSearchTimers[field.itemType]);
			itemSearchTimers[field.itemType] = setTimeout(runSearch, 260);
		});

		var initialIds = hidden.value ? hidden.value.split(',').filter(Boolean) : [];
		renderSelected(initialIds);
		if (initialIds.length) {
			searchItems('', initialIds).then(function (items) {
				items.forEach(function (item) {
					itemCache[String(item.id)] = item;
				});
				renderSelected(initialIds);
			}).catch(function () {
				// Ignore hydration errors; IDs are still preserved.
			});
		}

		return group;
	}

	function sanitizeIds(value) {
		return String(value || '')
			.split(',')
			.map(function (part) { return String(parseInt(part, 10)); })
			.filter(function (part) { return part && part !== 'NaN'; })
			.filter(function (part, index, array) { return array.indexOf(part) === index; })
			.join(',');
	}

	function openMediaLibrary(module, field, input, selectButton) {
		if (!window.wp || !wp.media) {
			window.alert(config.l10n.previewError);
			return;
		}

		var frame = wp.media({
			title: config.l10n.mediaLibrary,
			button: { text: config.l10n.selectImage },
			multiple: false,
			library: { type: 'image' }
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			input.value = attachment.url || '';
			module.settings[field.key] = attachment.url || '';
			if (field.idKey) {
				module.settings[field.idKey] = attachment.id || 0;
			}
			if (field.altKey && attachment.alt && !module.settings[field.altKey]) {
				module.settings[field.altKey] = attachment.alt;
			}
			if (module.type === 'image' && attachment.width) {
				module.settings.width = Math.min(attachment.width, 600);
			}
			selectButton.textContent = config.l10n.changeImage;
			renderCanvas();
			renderSettings();
			schedulePreview();
			saveStateToInput();
		});

		frame.open();
	}

	function bindFieldEvents(module, scopeEl) {
		(scopeEl || settingsEl).querySelectorAll('[data-module-key]').forEach(function (input) {
			var liveEventName = input.tagName === 'SELECT' ? 'change' : 'input';
			input.addEventListener(liveEventName, function () {
				var key = input.dataset.moduleKey;
				module.settings[key] = readFieldValue(input);
				if (key === 'grid_span' || key === 'canvas_min_height' || key === 'lock_full_width') {
					renderCanvas();
					if (selectedModuleId === module.id) {
						renderSettings();
					}
				}
				schedulePreview();
				saveStateToInput();
			});
			if (liveEventName !== 'change') {
				input.addEventListener('change', function () {
					module.settings[input.dataset.moduleKey] = readFieldValue(input);
					renderCanvas();
					schedulePreview();
					saveStateToInput();
				});
			}
		});
	}

	function readFieldValue(input) {
		if (input.dataset && input.dataset.colorHex === '1') {
			var normalized = normalizeHexColor(input.value);
			if (normalized) {
				return normalized;
			}
			if (input.dataset.colorSource) {
				var source = document.getElementById(input.dataset.colorSource);
				if (source && source.value) {
					return source.value;
				}
			}
			return '#000000';
		}
		if (input.type === 'checkbox') {
			return input.checked ? '1' : '0';
		}
		if (input.type === 'number' || input.type === 'range') {
			return Number(input.value || 0);
		}
		return input.value;
	}

	function schedulePreview() {
		clearTimeout(previewTimer);
		previewTimer = setTimeout(function () {
			renderServerPreview();
		}, 220);
	}

	function setTestMailStatus(message, isError) {
		if (!sendTestStatusEl) {
			return;
		}
		sendTestStatusEl.textContent = String(message || '');
		sendTestStatusEl.style.color = isError ? '#b91c1c' : '#166534';
	}

	function sendLiveTestMail() {
		if (!sendTestButtonEl || !sendTestEmailEl) {
			return;
		}

		var previewEmail = String(sendTestEmailEl.value || '').trim();
		if (!previewEmail) {
			setTestMailStatus(config.l10n.testMailNeedEmail, true);
			sendTestEmailEl.focus();
			return;
		}

		saveStateToInput();
		sendTestButtonEl.disabled = true;
		setTestMailStatus(config.l10n.testMailSending, false);

		var body = new URLSearchParams();
		body.set('action', 'send_email_preview');
		body.set('newsletter_id', String(config.newsletterId || 0));
		body.set('preview_email', previewEmail);
		body.set('builder_state_json', JSON.stringify(state));
		body.set('nonce', String(config.sendPreviewNonce || ''));

		window.fetch(config.ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
			},
			body: body.toString(),
			credentials: 'same-origin'
		}).then(function (response) {
			return response.text();
		}).then(function (text) {
			var message = String(text || '').trim();
			if (!message) {
				throw new Error('empty');
			}
			var isError = /nicht|fehler|ungueltig|keine\s+gueltige/i.test(message);
			setTestMailStatus(message, isError);
		}).catch(function () {
			setTestMailStatus(config.l10n.testMailError, true);
		}).finally(function () {
			sendTestButtonEl.disabled = false;
		});
	}

	function renderServerPreview() {
		previewRequestId += 1;
		var requestId = previewRequestId;
		previewEl.innerHTML = '<div class="enews-builder-v2-preview-state">' + escapeHtml(config.l10n.previewLoading) + '</div>';
		saveStateToInput();

		var body = new URLSearchParams();
		body.set('action', 'enews_builder_v2_preview');
		body.set('nonce', config.previewNonce);
		body.set('newsletter_id', String(config.newsletterId || 0));
		body.set('state', JSON.stringify(state));

		window.fetch(config.ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
			},
			body: body.toString(),
			credentials: 'same-origin'
		}).then(function (response) {
			return response.json();
		}).then(function (payload) {
			if (requestId !== previewRequestId) {
				return;
			}
			if (!payload || !payload.success || !payload.data || !payload.data.html) {
				throw new Error('preview');
			}
			previewEl.innerHTML = '';
			var shell = document.createElement('div');
			shell.className = 'enews-builder-v2-preview-shell';
			var frame = document.createElement('iframe');
			frame.className = 'enews-builder-v2-preview-frame';
			frame.setAttribute('title', 'Newsletter Vorschau');
			frame.setAttribute('loading', 'lazy');
			frame.srcdoc = String(payload.data.html);
			shell.appendChild(frame);
			previewEl.appendChild(shell);
		}).catch(function () {
			if (requestId !== previewRequestId) {
				return;
			}
			previewEl.innerHTML = '<div class="enews-builder-v2-preview-state is-error">' + escapeHtml(config.l10n.previewError) + '</div>';
		});
	}

	function updateDragHintFromPointer(event, sourceId) {
		var source = sourceId ? findModule(sourceId) : null;
		var span = source ? getModuleSpan(source) : 12;
		var rowSpan = source ? getModuleGridRows(source) : 1;
		var rect = canvasEl.getBoundingClientRect();
		if (rect.width <= 0) {
			return;
		}
		var colWidth = rect.width / 12;
		var rawCol = Math.floor((event.clientX - rect.left) / colWidth) + 1;
		var anchorY = event.clientY - (source ? dragPointerOffsetY : 0);
		var rowStep = getCanvasRowStep();
		var rawRow = Math.floor((anchorY - rect.top) / rowStep) + 1;
		var col = Math.max(1, Math.min(13 - span, rawCol));
		var row = Math.max(1, rawRow);
		dragGridHint = {
			col: col,
			row: row,
			span: span,
			rowSpan: rowSpan,
			fit: canPlaceModuleAt(sourceId || '', col, row, span, rowSpan)
		};
		renderDragHint();
	}

	function getTransparentDragImage() {
		if (transparentDragImage) {
			return transparentDragImage;
		}
		var img = new Image();
		img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"></svg>');
		transparentDragImage = img;
		return transparentDragImage;
	}

	function canPlaceModuleAt(sourceId, col, row, span, rowSpan) {
		var minCol = col;
		var maxCol = col + span - 1;
		var minRow = row;
		var maxRow = row + rowSpan - 1;

		for (var i = 0; i < state.modules.length; i += 1) {
			var module = state.modules[i];
			if (module.id === sourceId) {
				continue;
			}
			var otherRow = getModuleRow(module);
			var otherRowSpan = getModuleGridRows(module);
			var otherMinRow = otherRow;
			var otherMaxRow = otherRow + otherRowSpan - 1;
			if (maxRow < otherMinRow || minRow > otherMaxRow) {
				continue;
			}
			var otherCol = getModuleCol(module);
			var otherSpan = getModuleSpan(module);
			var otherMinCol = otherCol;
			var otherMaxCol = otherCol + otherSpan - 1;
			if (!(maxCol < otherMinCol || minCol > otherMaxCol)) {
				return false;
			}
		}

		return true;
	}

	function renderDragHint() {
		canvasEl.querySelectorAll('.enews-builder-v2-drop-hint').forEach(function (hintEl) {
			hintEl.parentNode.removeChild(hintEl);
		});
		if (!dragGridHint) {
			return;
		}
		var hint = document.createElement('div');
		hint.className = 'enews-builder-v2-drop-hint ' + (dragGridHint.fit ? 'is-ok' : 'is-conflict');
		hint.style.gridColumn = dragGridHint.col + ' / span ' + dragGridHint.span;
		hint.style.gridRow = dragGridHint.row + ' / span ' + (dragGridHint.rowSpan || 1);
		canvasEl.appendChild(hint);
	}

	function clearDropMarkers() {
		dragGridHint = null;
	}

	function handleCanvasDrop(event) {
		var newType = event.dataTransfer.getData('text/enews-module-type');
		if (newType) {
			var newModule = createModule(newType);
			if (!newModule) {
				return;
			}
			if (dragGridHint && dragGridHint.fit) {
				newModule.settings.grid_col = dragGridHint.col;
				newModule.settings.grid_row = dragGridHint.row;
			}
			state.modules.push(newModule);
			selectedModuleId = newModule.id;
			collapsedModuleMap[newModule.id] = false;
			renderAll();
			return;
		}

		var sourceId = event.dataTransfer.getData('text/enews-module-id') || dragSourceId;
		if (!sourceId) {
			return;
		}
		var source = findModule(sourceId);
		if (!source) {
			return;
		}

		if (dragGridHint && dragGridHint.fit) {
			source.settings.grid_col = dragGridHint.col;
			source.settings.grid_row = dragGridHint.row;
			renderAll();
			return;
		}

		state.modules = state.modules.filter(function (item) {
			return item.id !== sourceId;
		});
		state.modules.push(source);
		renderAll();
	}

	function insertModuleNear(module, targetId, position) {
		var targetIndex = state.modules.findIndex(function (item) {
			return item.id === targetId;
		});
		if (targetIndex === -1) {
			state.modules.push(module);
			return;
		}
		var insertAt = position === 'after' ? targetIndex + 1 : targetIndex;
		state.modules.splice(insertAt, 0, module);
	}

	function reorderModules(sourceId, targetId, position) {
		var sourceIndex = state.modules.findIndex(function (item) {
			return item.id === sourceId;
		});
		var targetIndex = state.modules.findIndex(function (item) {
			return item.id === targetId;
		});
		if (sourceIndex === -1 || targetIndex === -1) {
			return;
		}
		var moved = state.modules.splice(sourceIndex, 1)[0];
		var newTargetIndex = state.modules.findIndex(function (item) {
			return item.id === targetId;
		});
		var insertAt = position === 'after' ? newTargetIndex + 1 : newTargetIndex;
		state.modules.splice(insertAt, 0, moved);
	}

	function duplicateModule(moduleId) {
		var module = findModule(moduleId);
		if (!module) {
			return;
		}
		var clone = deepClone(module);
		clone.id = 'mod_' + Date.now() + '_' + Math.random().toString(16).slice(2, 8);
		var index = state.modules.findIndex(function (item) {
			return item.id === moduleId;
		});
		state.modules.splice(index + 1, 0, clone);
		selectedModuleId = clone.id;
		collapsedModuleMap[clone.id] = false;
		renderAll();
	}

	function deleteModule(moduleId) {
		state.modules = state.modules.filter(function (item) {
			return item.id !== moduleId;
		});
		if (selectedModuleId === moduleId) {
			selectedModuleId = null;
		}
		renderAll();
	}

	function findModule(moduleId) {
		return state.modules.find(function (item) {
			return item.id === moduleId;
		}) || null;
	}

	function escapeHtml(value) {
		return String(value || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function stripHtml(value) {
		return String(value || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
	}

	function resolveModuleSymbol(type, icon) {
		var raw = String(icon || '').trim();
		if (raw.indexOf('&') === 0 && raw.indexOf(';') > 0) {
			return raw;
		}

		var symbols = {
			heading: '&lt;h&gt;',
			text: '&para;',
			button: '&#9632;',
			image: '&#128247;',
			hero: '&#9733;',
			columns_2: '&#9638;',
			cta_box: '&#10071;',
			divider: '&#9473;',
			spacer: '&#8597;',
			social: '&#9829;',
			footer: '&#169;',
			html: '&lt;/&gt;',
			products: '&#128722;',
			posts: '&#128240;'
		};

		if (symbols[type]) {
			return symbols[type];
		}

		return '&bull;';
	}

	function initSidebarAccordions() {
		var toggles = document.querySelectorAll('[data-accordion-toggle]');
		toggles.forEach(function (button) {
			button.addEventListener('click', function () {
				var expanded = button.getAttribute('aria-expanded') === 'true';
				var panelId = button.getAttribute('aria-controls');
				var panel = panelId ? document.getElementById(panelId) : null;
				var item = button.closest('[data-accordion-item]');

				if (!expanded) {
					toggles.forEach(function (otherButton) {
						if (otherButton === button) {
							return;
						}
						var otherPanelId = otherButton.getAttribute('aria-controls');
						var otherPanel = otherPanelId ? document.getElementById(otherPanelId) : null;
						var otherItem = otherButton.closest('[data-accordion-item]');
						otherButton.setAttribute('aria-expanded', 'false');
						if (otherPanel) {
							otherPanel.hidden = true;
						}
						if (otherItem) {
							otherItem.classList.remove('is-open');
						}
					});
				}

				button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
				if (panel) {
					panel.hidden = expanded;
				}
				if (item) {
					item.classList.toggle('is-open', !expanded);
				}
			});
		});
	}

	function renderAll() {
		renderCanvas();
		renderSettings();
		syncSubjectInput();
		saveStateToInput();
		schedulePreview();
	}

	canvasEl.addEventListener('dragover', function (event) {
		event.preventDefault();
		canvasEl.classList.add('is-drop-target');
		var sourceId = event.dataTransfer.getData('text/enews-module-id') || dragSourceId || '';
		updateDragHintFromPointer(event, sourceId);
	});

	canvasEl.addEventListener('dragleave', function () {
		canvasEl.classList.remove('is-drop-target');
		if (dragSourceId) {
			return;
		}
		clearDropMarkers();
		renderDragHint();
	});

	canvasEl.addEventListener('drop', function (event) {
		event.preventDefault();
		canvasEl.classList.remove('is-drop-target');
		handleCanvasDrop(event);
		dragSourceId = null;
		clearDropMarkers();
		renderDragHint();
	});

	formEl.addEventListener('submit', function () {
		saveStateToInput();
	});

	if (sendTestButtonEl) {
		sendTestButtonEl.addEventListener('click', function () {
			sendLiveTestMail();
		});
	}

	if (subjectInputEl) {
		subjectInputEl.addEventListener('input', function () {
			state.global.subject = String(subjectInputEl.value || '').trim();
			saveStateToInput();
		});
		subjectInputEl.addEventListener('change', function () {
			state.global.subject = String(subjectInputEl.value || '').trim();
			saveStateToInput();
		});
		syncSubjectInput();
	}

	initSidebarAccordions();
	renderPresets();
	renderPalette();
	renderCanvas();
	renderSettings();
	syncSubjectInput();
	saveStateToInput();
	renderServerPreview();
})();
