var h = require('virtual-dom/h');
var actionCreators = require('./actionCreators.js');

module.exports = {
    stage: function(){
        return h('div', {className: 'stage'}, "STAGE");
    },
    chart: function(props, dispatch){
        var self = this;
        return props.seatingChart.map(function(row){
            return self.seatRow({
                row: row,
                selected: props.selected,
                unavailable: props.unavailable
            }, dispatch);
        })
    },
    seatRow: function (props, dispatch) {
        var self = this;
        var rowWidth = props.seatWidth * props.seatsPerRow;
        return h('ul', {
            className : 'seat-row',
            style: {width: '680px'}
        }, props.row.map(function (seatNumber) {
            if(seatNumber !== '|' && seatNumber !== '-') {
                return self.seat({
                    seatNumber : seatNumber,
                    isSelected : props.selected[seatNumber] === true,
                    isReserved : props.unavailable[seatNumber] === true
                }, dispatch);
            } else {
                if(seatNumber === '|') {
                return self.aisle();
                } else if (seatNumber === '-') {
                    return h('hr');
                }
            }
        })
        )
    },
    seat: function(props, dispatch) {
        var innerClassName = "seat";
        var action = actionCreators.select(props.seatNumber);

        if (props.isReserved) {
            innerClassName = "seat unavailable";
            action = null;
        } else if (props.isSelected) {
            innerClassName = "seat selected";
            action = actionCreators.deselect(props.seatNumber);
        }
        return h('li', {
            className : innerClassName,
            onclick : dispatch.bind(this, action)
        });
    },
    aisle: function(){
        return h('li', {className: 'aisle'});
    },
    instructions: function(props){
        return h('p', { className : "instructions"}, 
                [
                "Please select ",
                h('span', {className : "selections-remaining"}, props.numSeats - props.numSelected),
                " more seats."
                ]);
    },
    addToCartButton: function(props, dispatch){
        return h('a', {
            className : "btn-add-to-cart",
            href : "#",
            onclick : dispatch.bind(this, actionCreators.submit(props.selected, props.inputId, props.modal))
        }, "Add to Cart")
    }
}
