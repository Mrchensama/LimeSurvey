/**
 * Neccessary methods for the confirmation modal
 */
import _ from 'lodash';

const ConfirmationModal = function(e){
    //////PREGENERATED VARIABLES
    //Define the scope
    const _this = this;
    //Set everything to null on default
    const optionsDefault = {
        onclick     : null,
        href        : null,
        message     : null,
        keepopen    : null,    
        postDatas   : null,    
        gridid      : null,
        "ajax-url"  : null,
    };

    //////METHODS
    //Parse available options from specific item.data settings, if not available load relatedTarget settings
    const _parseOptions = (e) => {
        return _.mergeWith(optionsDefault, $(_this).data(), (objValue, srcValue, key ) => {
            return srcValue || $(e.relatedTarget).data(key) || null;
        }); 
    },
    //Generate a simple link on the ok button
    _basicLink = () => {
        $(_this).find('.btn-ok').attr('href', options.href);
    },
    //Evaluate a function on ok button click
    _onClickFunction = () => {
        const onclick_fn = eval(options.onclick);
        if (typeof onclick_fn == 'function') {
            $(_this).find('.btn-ok').off('click');

            $(_this).find('.btn-ok').on('click', function(ev) {
                if(!options.keepopen ) { $('#confirmation-modal').modal('hide'); }
                onclick_fn();
            });
            return
        }
        console.error("Confirmation modal: onclick is not a function. Wrap data-onclick content in (function() { ... }).");
        return;
    },
    //Set up an ajax call and regenerate a gridView on ok button click
    _ajaxHandler = () => {
        $(_this).find('.btn-ok').on('click', function(ev) {
            $.ajax({
                type: "POST",
                url: options['ajax-url'],
                data: options.postDatas,

                success : function(html, statut)
                {
                    $.fn.yiiGridView.update(options.gridid);                   // Update the surveys list
                    $('#confirmation-modal').modal('hide');
                },
                error :  function(html, statut){
                    $('#confirmation-modal .modal-body-text').append(html.responseText);
                }

            });
        });
    },
    _setTarget = () => {
        //Set up normal href
        if (!!options.href) {
            _basicLink();
            return;
        }
        //Set up a complete function
        if (!!options.onclick) {
            _onClickFunction();
            return;
        }
        //Set up an ajax post
        if (!!options['ajax-url']) {
            _ajaxHandler();
            return;
        }
        console.error("Confirmation modal: Found neither data-href or data-onclick, nor ajax data.");
    };

    //////RUN BINDINGS
    //Current options object
    const options = _parseOptions(e);
    //Set the message if available
    $(this).find('.modal-body-text').html(options.message);
    //Run setTarget to determine loading target
    _setTarget();
};

const loadMethods = ()=>{
    $('#confirmation-modal').on('show.bs.modal', function(e) {
        ConfirmationModal.call(this,e);
    });
};

export default loadMethods;