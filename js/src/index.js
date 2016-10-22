"use strict";
var h = require('virtual-dom/h');
var diff = require('virtual-dom/diff');
var patch = require('virtual-dom/patch');
var createElement = require('virtual-dom/create-element');
var reduce = require('./reducer.js');
var actionCreators = require('./actionCreators.js');
var modal = require("./modal.js");
var components = require('./components.js');

if (typeof Object.assign != 'function') {
    (function () {
        Object.assign = function (target) {
            'use strict';
            // We must check against these specific cases.
            if (target === undefined || target === null) {
                throw new TypeError('Cannot convert undefined or null to object');
            }

            var output = Object(target);
            for (var index = 1; index < arguments.length; index++) {
                var source = arguments[index];
                if (source !== undefined && source !== null) {
                    for (var nextKey in source) {
                        if (source.hasOwnProperty(nextKey)) {
                            output[nextKey] = source[nextKey];
                        }
                    }
                }
            }
            return output;
        };
    })();
}
var initialize = function (numSeats, preSelected, parentElementId, inputId,
    modal, initialState, seatWidth) {
    initialState["seatWidth"] = 43;
    var store = reduce({}, 
        actionCreators.init(
            numSeats, 
            preSelected, 
            inputId, 
            initialState));
    var tree = render(store); 
    var rootNode = createElement(tree);
    document.getElementById(parentElementId).appendChild(rootNode); 
    function dispatch(action) {
        store = reduce(store, action);
        update(store);
    }

    function update(store) {
        var newTree = render(store);
        var patches = diff(tree, newTree);
        tree = newTree;
        rootNode = patch(rootNode, patches);
        console.info(store);
    }


    function render(store) {
        return h('div', {
            className : "container"
        }, [
        components.instructions({
            numSeats: store.numSeats,
            numSelected: store.numSelected
        }),
        components.stage(),
        components.chart({
            seatingChart: store.seatingChart,
            selected: store.selected,
            unavailable: store.unavailable,
            gridSize: store.gridSize,
            boxOfficeData: store.boxOfficeData,
            seatWidth: store.seatWidth
        }, dispatch),
        components.addToCartButton({
            selected: store.selected,
            inputId: store.inputId,
            modal: modal
        }, dispatch)]);
    }

};

function getSeatData(variation_id, callback){
    jQuery.get({
        url: "/seating_chart/display/",
        data: {"variation_id":variation_id},
        success: callback
    });
}

jQuery(document).ready(function () {
    // A user cannot update their order quantity from the the shopping
    // cart, because this would require them to re-select seats
    jQuery('.wst_seat_choice_item .quantity input').attr("disabled", true); 
    var variation_id = jQuery('.single_variation_wrap .variation_id').val();
    jQuery( ".single_variation_wrap" ).on( "show_variation", function ( event, variation ) {
        variation_id = variation.variation_id;
    } );
    jQuery('.single_add_to_cart_button').off('click');
    jQuery('.single_add_to_cart_button').click(function(event) {
        if( jQuery(this).hasClass('disabled')){
            return;
        }
        modal.open({
            content : "<div id='chooser' style='width: 960px; height: 500px;'></div>"
        });
        var seatsChosenValue = "";
        var seatsChosen = [];
        var intializeModal = function(result){
            seatsChosenValue = document.getElementById('seatsChosen').value;
            if (seatsChosenValue.length > 0) {	
                seatsChosen = seatsChosenValue.split(',');
            }
            initialize(
                parseInt(jQuery('input[name=quantity]').val(), 10),
                seatsChosen, 
                'chooser', 
                'seatsChosen', 
                modal, 
                result);
        }
        getSeatData(
            variation_id,
            intializeModal
        );
        event.preventDefault();
    });

    var BOX_OFFICE_CHART = 'box-office-chart';
    if(jQuery('#box-office-chart').length){
        jQuery('#btn-print').click(function(){
            var restorepage = jQuery('body').html();
            var restorehead = jQuery('head').html();
            
            var printcontent = jQuery('#'+BOX_OFFICE_CHART).html();

            jQuery('head').html(jQuery('#wst_seat_chooser_style-css')[0].outerHTML);
            jQuery('body').html(printcontent);

            window.print();
            jQuery('body').html(restorepage);
            jQuery('head').html(restorehead);
        });
        jQuery('#wst-show-select').change(function(){
            getSeatData(
                jQuery(this).val(),
                function(result){ 
                    initialize(
                        0,
                        [], 
                        BOX_OFFICE_CHART,
                        null, 
                        null, 
                        result);
                }
            );
        });
    }
});
