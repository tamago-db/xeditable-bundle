(function ($) {
    "use strict";

    $.fn.ibrowsXeditableInit = function (options) {
        return this.each(function () {
            var elem = $(this);

            var id = elem.attr('id');
            var isIbrowsXeditableForm = elem.data('type') == 'ibrows_xeditable_form';

            var params = isIbrowsXeditableForm ? function (params) {
                var values = params.value.serializeArray();
                //allways send the current path with the form
                values.push({name: 'path', value: $('#' + params.name).data('path')});
                return jQuery.param(values);
            } : null;

            var display = isIbrowsXeditableForm ? function (value, response) {
                if (typeof response == "undefined") {
                    return value;
                }

                var content = $('<div>' + response + '</div>');

                content.find('[data-xeditable-replace]').each(function () {
                    var replaceElem = $(this);
                    var targetElem = $('body').find('[data-xeditable-replace="' + replaceElem.data('xeditable-replace') + '"]');
                    var callback = replaceElem.data('xeditable-replace-callback');

                    targetElem.html(replaceElem.html());
                    if (callback) {
                        window[callback].call(null, targetElem, replaceElem, content, elem);
                    }
                });
                var newContent = content.find('#' + id);
                $(this).html(newContent.html());
                $(this).data('form', newContent.data('form'));


                var event = new CustomEvent("xeditable-ajax-success", {'detail' : response});
                elem.get(0).dispatchEvent(event);

            } : null;

            var error = isIbrowsXeditableForm ? function (response, form) {
                form.find('.editable-error-block').html(response.responseText);
            } : null;

            var settings = $.extend({
                send: isIbrowsXeditableForm ? 'always' : null,
                params: params,
                display: display,
                error: error
            }, options);

            elem.editable(settings);
        });
    };

    var ibrowsXeditableForm = function (options) {
        this.init('ibrows_xeditable_form', options, ibrowsXeditableForm.defaults);
    };

    $.fn.editableutils.inherit(ibrowsXeditableForm, $.fn.editabletypes.abstractinput);

    $.extend(ibrowsXeditableForm.prototype, {
        render: function () {
            var tpl = this.$tpl;
            var scope = $(this.options.scope);
            var htmlForm = scope.data('form');
            if (htmlForm) {
                tpl.append(htmlForm);
                tpl.find(':input:first').focus();
                scope.trigger('render.success', [htmlForm, tpl]);
                return;
            }
            $.ajax({
                url: scope.data('url'),
                async: true,
                data: {path: scope.data('path')},
                dataType: 'html',
                success: function (html, status, xhr) {
                    var contentType = xhr.getResponseHeader("content-type") || "";
                    if (contentType.indexOf('html') != -1) {
                        tpl.append(html);
                        tpl.find(':input:first').focus();
                        scope.trigger('render.success', [html, tpl]);
                    }
                }
            });
        },
        input2value: function () {
            return this.$tpl.closest('form');
        },
        activate: function () {
            this.$input.find('input:first').focus();
        }
    });

    ibrowsXeditableForm.defaults = $.extend({}, $.fn.editabletypes.abstractinput.defaults, {
        tpl: '<span></span>'
    });

    $.fn.editabletypes.ibrows_xeditable_form = ibrowsXeditableForm;

})(window.jQuery);