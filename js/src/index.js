"use strict"
var h = require('virtual-dom/h');
var diff = require('virtual-dom/diff');
var patch = require('virtual-dom/patch');
var createElement = require('virtual-dom/create-element');
var actions = require('./actions.js');
var reduce = require('./reducer.js');
var actionCreators = require('./actionCreators.js');
var modal = require("./modal.js");
var $ = require("jquery");
var components = require('./components.js');

var initialize = function (numSeats, preSelected, unavailableSeats, parentElementId, inputId, modal) {
    var store = reduce({}, actionCreators.init(numSeats, preSelected, unavailableSeats, inputId));
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
            unavailable: store.unavailable
        }, dispatch),
        components.addToCartButton({
            selected: store.selected,
            inputId: store.inputId,
            modal: modal
        }, dispatch)]);
    }

}

jQuery(document).ready(function () {
    jQuery('.single_add_to_cart_button').off('click');
    jQuery('.single_add_to_cart_button').click(function(event) {
        console.log('OPEN MODAL!!!');
        modal.open({
            content : "<div id='chooser' style='width: 960px; height: 500px;'></div>"
        });
        var seatsChosenValue = document.getElementById('seatsChosen').value
            var seatsChosen = [];
        if (seatsChosenValue.length > 0) {	
            seatsChosen = seatsChosenValue.split(',');
        }
        initialize(7, seatsChosen, ["C4", "C5", "C6", "C7"], 'chooser', 'seatsChosen', modal);
        event.preventDefault();
    })
})

// TODO
//
// 1. tie into woocommerce - this screen should come up after add-to-cart
// 2. check for conflicts before submitting and if one is found, notify the user
// 3. provide a report for the box office with names per seat.
