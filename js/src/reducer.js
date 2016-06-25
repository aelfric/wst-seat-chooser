var actions = require('./actions.js');

module.exports = function (state, action) {
    console.info(state);
    var newState = Object.assign({}, state);
    switch (action.type) {
        case actions.SELECT:
            if (newState.numSelected < newState.numSeats) {
                newState.selected[action.payload.seatNumber] = true;
                newState.numSelected += 1;
            }
            break;
        case actions.DESELECT:
            newState.selected[action.payload.seatNumber] = false;
            newState.numSelected -= 1;
            break;
        case actions.SUBMIT:
            if(action.payload.selectedSeats.length === newState.numSeats) {
                document.getElementById('seatsChosen').value = action.payload.selectedSeats.sort();
                action.payload.modal.close();
                jQuery('.cart').submit();
            } 
            break;
        case actions.INIT:
            newState = Object.assign({}, action.payload);
            break;
    }
    return newState;
}
