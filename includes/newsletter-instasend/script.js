(function ($) {

    var StepsModule = (function () {

        var _containerEl, _stepElements, _actualStep, _maxSteps, _prevEl, _nextEl, _options;
        var _slideDuration = 200;

        function init(containerElementId, stepClass, prevButtonId, nextButtonId, options) {
            _containerEl = $(containerElementId);
            _stepElements = $(stepClass);
            _prevEl = $(prevButtonId);
            _nextEl = $(nextButtonId);
            _maxSteps = _stepElements.length;
            _actualStep = 0;
            _options = Object.assign({}, options);

            _stepElements.hide();
            _stepElements.first().show();

            if (_options.hideControllersOnFirst) {
                hideControllers();
            }

            attachEventHandler();
        }

        function attachEventHandler() {

            _prevEl.on('click', prev);
            _nextEl.on('click', next);

        }

        function next() {
            if (_actualStep < _maxSteps - 1) {
                _actualStep++;
                _stepElements.eq(_actualStep - 1).toggle("slide", {direction: 'left'}, _slideDuration, function () {
                    _stepElements.eq(_actualStep).toggle("slide", {direction: 'right'}, _slideDuration);
                });
            }

            if (isLastStep()) {
                _nextEl.hide();
                _prevEl.show();
                if (_options.onEnterLastStepCallback instanceof Function) {
                    _options.onEnterLastStepCallback();
                }
            } else {
                _nextEl.show();
                _prevEl.show();
            }
        }

        function isLastStep() {
            return _actualStep === _maxSteps - 1;
        }

        function prev() {
            if (_actualStep > 0) {
                _actualStep--;
                _stepElements.eq(_actualStep + 1).toggle("slide", {direction: 'right'}, _slideDuration, function () {
                    _stepElements.eq(_actualStep).toggle("slide", {direction: 'left'}, _slideDuration);
                });
            }

            if (_actualStep === 0) {

                if (_options.hideControllersOnFirst) {
                    hideControllers();
                } else {
                    _nextEl.show();
                    _prevEl.hide();
                }

            } else {
                _nextEl.show();
                _prevEl.show();
            }
        }

        function goTo(step) {

            var prevStep = _actualStep;
            _actualStep = step;

            _stepElements.eq(prevStep).toggle("slide", {direction: 'left'}, _slideDuration, function () {
                _stepElements.eq(_actualStep).toggle("slide", {direction: 'right'}, _slideDuration);
            });

        }

        function totalSteps() {
            return _maxSteps;
        }

        function _currentStep() {
            return _actualStep;
        }

        function hideControllers() {
            _prevEl.hide();
            _nextEl.hide();
        }

        return {
            init: init,
            next: next,
            prev: prev,
            goTo: goTo,
            totalSteps: totalSteps,
            currentStep: _currentStep,
            hideControllers: hideControllers
        }
    })();

    StepsModule.init('#tnp-instasend-metabox',
        '.tnp_metabox_section',
        '#tnp-instasend-prev',
        '#tnp-instasend-next',
        {
            hideControllersOnFirst: true,
            onEnterLastStepCallback: lastStepHandler
        });

    var containerEl = $('#tnp-instasend-metabox');
    var data = {};

    containerEl.on('click', '#tnp-instasend-make-draft', StepsModule.next);
    containerEl.on('change', '#options-instasend_content_type_dropdown', toggle_excerpt_fields);

    function toggle_excerpt_fields() {
        if ('excerpt' === $(this).val()) {
            $('#tnp-excerpt-field-container').removeClass('hidden');
            $('#tnp-full-content-field-container').addClass('hidden');
        } else {
            $('#tnp-excerpt-field-container').addClass('hidden');
            $('#tnp-full-content-field-container').removeClass('hidden');
        }
    }

    function lastStepHandler() {

        try {

            showNotice('');

            data = {
                'action': 'instasend_create_newsletter',
                'nonce': $("#tnp-instasend-metabox input[name='instasend_nonce']").val(),
                'postID': $("#tnp-instasend-metabox input[name='instasend_post_id']").val(),
                'showFeaturedImage': $("#tnp-instasend-metabox select[name='options[instasend_show_featured_image]']").val(),
                'keepPostContentImages': $("#tnp-instasend-metabox select[name='options[instasend_keep_post_content_images]']").val(),
                'postContentLength': $('#options-instasend_content_type_dropdown').val(),
                'excerptMaxWords': $('#options-instasend_excerpt_words').val(),
                'showReadMoreButton': $("#tnp-instasend-metabox select[name='options[instasend_excerpt_read_more]']").val(),
            };

            disableConfirmButton();
            checkDataFields(data);
            enableConfirmButton();

        } catch (e) {
            showNotice(e.message);
        }
    }

    function confirmButtonAjaxCall() {
        $.post(
            ajaxurl,
            data,
            createNewsletterAjaxHandler
        );
    }

    function createNewsletterAjaxHandler(response) {
        if (false === response.success) {
            showNotice(response.data);
        } else {
            StepsModule.hideControllers();
            $('#tnp-instasend-metabox .tnp_metabox_section').hide();
            $('#tnp-instasend-metabox .tnp_metabox_section_notice').hide();

            $('#tnp-instasend-metabox .inside').append(response.data);
            $("#tnp-instasend-metabox input[name='instasend_already_have_newsletter']").val('1');
        }
    }

    function showNotice(message) {
        $(".tnp_metabox_section_notice .tnp-notice").fadeOut(function () {
            $(this).text(message).fadeIn();
        });
    }

    function checkDataFields(data) {
        if (data.postContentLength === 'excerpt' && (data.excerptMaxWords === '' || parseInt(data.excerptMaxWords) === 0)) {
            throw new Error('Excerpt words length is invalid!');
        }
    }

    function enableConfirmButton() {
        var button = $('#tnp-instasend-confirm-button');
        button.removeAttr('disabled');
        button.on('click', confirmButtonAjaxCall);
    }

    function disableConfirmButton() {
        var button = $('#tnp-instasend-confirm-button');
        button.attr('disabled', 'disabled');
        button.off('click');
    }

    function disableMakeDraftButton(disable) {
        var button = $('#tnp-instasend-make-draft');
        if (disable) {
            button.attr('disabled', 'disabled');
        } else {
            button.removeAttr('disabled');
        }
    }

    function onDirtyEditorHandler() {

        //Check if newsletter is already publish
        if ($("#tnp-instasend-metabox input[name='instasend_already_have_newsletter']").val() === '1') return;

        if (editorStatus.isDirty()) {
            disableMakeDraftButton(true);
            if (StepsModule.currentStep() !== 0) {
                StepsModule.goTo(0);
                StepsModule.hideControllers();
            }
            showNotice('Please save a draft or publish the post before creating the newsletter!');

        } else {
            disableMakeDraftButton(false);
            showNotice('');
        }

    }

    var editorStatus = (function () {
        var _isDirty;
        var _onChangeListeners;

        function init() {
            _isDirty = false;
            _onChangeListeners = [];

            if (_isBlockEditor()) {
                _registerBlockEditorHandler();
            } else {
                _registerClassicEditorHandler();
            }
        }

        function _onChangeSubscribe(callback) {
            _onChangeListeners.push(callback);
        }

        function _onChangePublish() {
            _onChangeListeners.forEach(function (callback) {
                callback();
            })
        }

        function _registerClassicEditorHandler() {

            setTimeout(function () {

                if (!_isClassicEditor()) return;

                tinyMCE.editors.forEach(function (editor) {
                    editor.on('change', function () {
                        _setIsDirty(this.isDirty());
                    });
                });

            }, 10); //Devo fare così per andare in coda all'inizializzazione di tinyMCE

        }

        function _registerBlockEditorHandler() {

            wp.data.subscribe(function () {

                if (wp.data.select('core/editor').isSavingPost()) {
                    _setIsDirty(false);
                } else {
                    _setIsDirty(!!wp.data.select('core/editor').isEditedPostDirty());
                }

            });

        }

        function _isBlockEditor() {
            return wp && wp.data && wp.data.subscribe && wp.data.select('core/editor');
        }

        function _isClassicEditor() {
            return typeof tinyMCE !== 'undefined' && tinyMCE.editors.length;
        }

        function _setIsDirty(val) {
            if (val !== _isDirty) { //Check if it is changed from prev value
                _isDirty = val;
                _onChangePublish();
            }
        }

        function isDirty() {

            return _isDirty;

        }

        return {
            init: init,
            isDirty: isDirty,
            onChangeSubscribe: _onChangeSubscribe
        }

    })();

    $(document).ready(function () {
        editorStatus.init();
        editorStatus.onChangeSubscribe(onDirtyEditorHandler);
    });

})(jQuery);
