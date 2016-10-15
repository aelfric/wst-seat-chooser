var actions = require('./actions.js');

module.exports = {

    init : function (numSeats, seatsSelected, inputId, initialState) {
        var selectedSeatsObj = {};
        seatsSelected.forEach(function (seatNumber) {
            selectedSeatsObj[seatNumber] = true;
        });
        var maxRowSize = initialState["seatingChart"].reduce(function(maxLen, nextArr){
            return Math.max(maxLen, nextArr.length);
        }, 0);
        return {
            type : actions.INIT,
            payload : Object.assign(initialState, {
                numSeats : numSeats,
                numSelected : seatsSelected.length,
                gridSize: maxRowSize,
                selected : selectedSeatsObj,
                inputId: inputId
            })
        };

    },

    submit : function (selectedObj, inputId, modal) {
        var arr = [];
        for (var i in selectedObj) {
            if (selectedObj[i] === true) {
                arr.push(i);
            }
        }
        return {
            type : actions.SUBMIT,
            payload : {
                selectedSeats : arr,
                inputId : inputId,
                modal: modal
            }
        };
    },

    select : function (seatNumber) {
        return {
            type : actions.SELECT,
            payload : {
                seatNumber : seatNumber
            }
        };
    },

    deselect : function (seatNumber) {
        return {
            type : actions.DESELECT,
            payload : {
                seatNumber : seatNumber
            }
        };
    }

};

