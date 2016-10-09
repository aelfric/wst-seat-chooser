"use strict";
var h = require('virtual-dom/h');
var diff = require('virtual-dom/diff');
var patch = require('virtual-dom/patch');
var createElement = require('virtual-dom/create-element');
var reduce = require('./reducer.js');
var actionCreators = require('./actionCreators.js');
var modal = require("./modal.js");
var components = require('./components.js');

var initialize = function (numSeats, seatingChart, preSelected,
    unavailableSeats, boxOfficeData, parentElementId, inputId, modal) {
    var store = reduce({}, actionCreators.init(numSeats, seatingChart,
        preSelected, unavailableSeats, boxOfficeData, inputId));
    var tree = render(store); // We need an initial tree
    var rootNode = createElement(tree);
    document.getElementById(parentElementId).appendChild(rootNode); // ... and it should be in the document
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
            boxOfficeData: store.boxOfficeData
        }, dispatch),
        components.addToCartButton({
            selected: store.selected,
            inputId: store.inputId,
            modal: modal
        }, dispatch)]);
    }

};

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
                result["seating_chart"], 
                seatsChosen, 
                result["reserved_seats"], 
                result["boxOfficeData"],
                'chooser', 
                'seatsChosen', 
                modal);
        }
        jQuery.get({
            url: "/seating_chart/5445/",
            data: {"variation_id":variation_id},
            success: intializeModal
        });
        event.preventDefault();
    });
});

// TODO
//
// 1. tie into woocommerce - this screen should come up after add-to-cart
// 2. check for conflicts before submitting and if one is found, notify the user
// 3. provide a report for the box office with names per seat.
