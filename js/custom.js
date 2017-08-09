/* ========================================================================
* Prixal: FDC list handler
* ======================================================================== */

jQuery(function($) {

    'use strict';

    var FDC = {
        $target: null,
        itemID: 0,
        action: null,
        init: function() {
            $(document).on('click', '[data-action]', FDC.toAction);
            $('#pxFDCModal').on('show.bs.modal', FDC.toModal);
        },
        toAction: function() {
            FDC.$target = $(this);
            FDC.action = FDC.$target.data('action');
            FDC.itemID = FDC.$target.data('id');

            switch( FDC.action )
            {
                case 'view'     : FDC.toView();    break;
                case 'delete'   : FDC.toDelete();  break;
            }
        },
        toView: function() {
            $('#pxFDCModal').addClass('loading').modal();
        },
        toDelete: function() {
            if( window.confirm("Do you really want to permanently delete this entry?") ) {
                $.post(ajaxurl, { action: 'fdc_action', id: FDC.itemID, cmd: FDC.action }, function(data) {
                    FDC.$target.closest('tr').fadeOut();
                });
            }
        },
        toModal: function() {
            var $modal = $(this);
            $modal.addClass('loading');
            $modal.find('.modal-body').html($modal.data('loading'));

            $.post(ajaxurl, { action: 'fdc_action', id: FDC.itemID, cmd: FDC.action }, function(data) {
                $modal.removeClass('loading');
                $modal.find('.modal-title').text('Entry details (#' + FDC.itemID + ')');
                $modal.find('.modal-body').html(data);
                $modal.removeClass('loading');
            });

        }
    };

    FDC.init();

});

/* ========================================================================
* Prixal: FDC list handler
* ======================================================================== */
jQuery(function($) {
    FDC = {
        init: function() {
            $(document).on('click', '[data-toggle="tab"]', this.toggle);
        },
        toggle: function(e) {
            e.preventDefault();
            var $self = $(this);
            var $target = $($self.data('target'));

            $self.addClass('nav-tab-active').siblings().removeClass('nav-tab-active');
            $target.addClass('active').siblings().removeClass('active');
        }
    };

    FDC.init();
});
