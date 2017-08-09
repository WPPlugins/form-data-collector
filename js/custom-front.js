/* ========================================================================
* Prixal: FDC  handler
* ======================================================================== */
window.fdc = window.fdc || {};

jQuery(function($) {

    var settings = typeof _fdcVars === 'undefined' ? {} : _fdcVars;

    var _isObject = function(variable) {
        return ( typeof variable === 'function' || typeof variable === 'object' ) && !!variable;
    };

    fdc.ajax = {
        settings: settings.ajax || {},

        /**
         * fdc.ajax.post([formID | jQuery object], [options])
         *
         * Sends a POST request to WordPress.
         *
         * @since 1.2.0
         *
         * @param  {string|object}  Selector or jQuery object
         * @param  {object}         The options passed to jQuery.ajax.
         * @return {$.promise}      A jQuery promise that represents the request, decorated with an abort() method.
         */
        post: function(form, options) {
            var promise, deferred;
            var $form = ( form instanceof $ ) ? form : $(form);

            if( ! $form.length ) {
                throw new Error('Target Form not found.');
                return;
            }

            options = $.extend({
                url: settings.ajax.url,
                type: 'POST',
                url: fdc.ajax.settings.url,
                context: this,
                data: {
                    action: 'fdc_action',
                    cmd: 'save',
                    check: settings.ajax.nonce,
                    fdcUtility: true,
                    data: $form.serialize()
                }
            }, options);

            deferred = $.Deferred(function(deferred) {
                if ( options.success ) {
                    deferred.done( options.success );
                }
                if ( options.error ) {
                    deferred.fail( options.error );
                }

                delete options.success;
                delete options.error;
                delete options.form;

                deferred.jqXHR = $.ajax(options).done(function(response) {
                    if( _isObject(response) ) {
                        deferred[ response.success ? 'resolveWith' : 'rejectWith' ]( this, [response.data] );
                    } else {
                        deferred.rejectWith( this, [response] );
                    }
                }).fail( function() {
                    deferred.rejectWith( this, arguments );
                });
            });

            promise = deferred.promise();
            promise.abort = function() {
                deferred.jqXHR.abort();
                return this;
            };

            return promise;
        }
    };

});
