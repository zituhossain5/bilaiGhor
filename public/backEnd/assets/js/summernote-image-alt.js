/*
 * Alt text for images inside Summernote content (SEO + accessibility).
 *
 * Load AFTER summernote-lite.min.js and the vendored summernote-image-attributes plugin,
 * and BEFORE $('.summernote').summernote(...). It only sets global defaults, so every
 * editor on the page picks it up without changing its own options:
 *
 *  - adds the plugin's "Image Attributes" (alt / title / size) button to the image popover;
 *  - opens that dialog as soon as an image is inserted (uploaded or by URL), so alt text is
 *    entered at the point of insertion;
 *  - the server rejects content whose images have no alt (App\Rules\RichTextImagesHaveAlt).
 */
(function ($) {
    if (!$ || !$.summernote || !$.summernote.plugins || !$.summernote.plugins.imageAttributes) {
        return;
    }

    var defaults = $.summernote.options;

    // Upload lives in the editor's own picture dialog; the attributes dialog is only for alt/title/size.
    defaults.imageAttributes = $.extend({}, defaults.imageAttributes, {
        icon: '<i class="note-icon-pencil"></i>',
        removeEmpty: true,
        disableUpload: true
    });
    defaults.lang = defaults.lang || 'en-US';
    if ($.summernote.lang['en-US'] && $.summernote.lang['en-US'].imageAttributes) {
        $.summernote.lang['en-US'].imageAttributes.tooltip = 'Image alt text & attributes';
        $.summernote.lang['en-US'].imageAttributes.alt = 'Alt Text *';
    }

    var hasButton = (defaults.popover.image || []).some(function (group) {
        return (group[1] || []).indexOf('imageAttributes') !== -1;
    });
    if (!hasButton) {
        defaults.popover.image = (defaults.popover.image || []).concat([['custom', ['imageAttributes']]]);
    }

    // Open the alt dialog for a just-inserted image.
    function askForAlt(note, img) {
        var context = $(note).data('summernote');
        if (!context || !img) return;
        context.layoutInfo.editable.data('target', img);
        context.invoke('imageAttributes.show');
        // The plugin focuses its first field (Source); the reason it opened is the alt text.
        setTimeout(function () {
            $('.note-modal .note-imageAttributes-alt').filter(':visible').first().trigger('focus');
        }, 50);
    }

    function insertThenAsk(note, src, filename) {
        var context = $(note).data('summernote');
        if (!context) return;
        var $editable = context.layoutInfo.editable;
        $(note).summernote('insertImage', src, function ($img) {
            // Same sizing/naming Summernote applies by default when no callback is given.
            if (filename) $img.attr('data-filename', filename);
            $img.css('width', Math.min($editable.width(), $img.width()));
            setTimeout(function () { askForAlt(note, $img[0]); }, 0);
        });
    }

    defaults.callbacks = $.extend({}, defaults.callbacks, {
        // Same as Summernote's default (embed as data URL, honour maximumImageFileSize), then ask for alt.
        onImageUpload: function (files) {
            var note = this;
            var context = $(note).data('summernote');
            var options = context ? context.options : defaults;
            $.each(files, function (i, file) {
                if (options.maximumImageFileSize && options.maximumImageFileSize < file.size) {
                    context && context.triggerEvent('image.upload.error', options.langInfo.image.maximumFileSizeError);
                    return;
                }
                var reader = new FileReader();
                reader.onload = function (e) { insertThenAsk(note, e.target.result, file.name); };
                reader.onerror = function () { context && context.triggerEvent('image.upload.error'); };
                reader.readAsDataURL(file);
            });
        },
        onImageLinkInsert: function (url) {
            insertThenAsk(this, url, null);
        }
    });

    // The plugin's dialog is Bootstrap 3/4 tab markup; under Bootstrap 5 its first pane
    // ("fade in active") is invisible and the tab links do nothing. Only the Image pane
    // (source, title, alt, size) is needed, so show it and hide the tab bar.
    $('<style id="summernote-image-alt-style">' +
        '.note-modal .note-nav-tabs { display: none; }' +
        '.note-modal [id^="note-imageAttributes-"].tab-pane { display: none; }' +
        '.note-modal [id^="note-imageAttributes-"]:not([id*="-attributes-"]):not([id*="-link-"]):not([id*="-upload-"]).tab-pane { display: block; opacity: 1; }' +
        '.note-modal .note-group-imageAttributes-alt .note-input { border-color: #727cf5; }' +
      '</style>').appendTo(document.head);
})(window.jQuery);
