var actions = require('./actions.js');

module.exports = {

  init : function (numSeats, seatsSelected = [], unavailableSeats = [], inputId) {
    var unavailableObj = {};
    unavailableSeats.forEach(function (seatNumber) {
      unavailableObj[seatNumber] = true;
    })
	
	  var selectedSeatsObj = {};
	  seatsSelected.forEach(function (seatNumber) {
		  selectedSeatsObj[seatNumber] = true;
	  })
    return {
      type : actions.INIT,
      payload : {
        numSeats : numSeats,
        numSelected : seatsSelected.length,
        gridSize: 21,
        seatingChart : [
['A17',  'A16',  'A15',  'A14',  'A13',  'A12',  'A11',  'A10',  'A9',   'A8',   'A7',   'A6',   '|',    'A5',   'A4',   'A3',   'A2',   'A1'],
['|',    'B17',  'B16',  'B15',  'B14',  'B13',  'B12',  'B11',  'B10',  'B9',   'B8',   'B7',   '|',    'B6',   'B5',   'B4',   'B3',   'B2',   'B1'],
['-'],
['|',    '|',    'C17',  'C16',  'C15',  'C14',  'C13',  'C12',  'C11',  'C10',  'C9',   'C8',   '|',    'C7',   'C6',   'C5',   'C4',   'C3',   'C2',   'C1'],
['|',    '|',    '|',    'D16',  'D15',  'D14',  'D13',  'D12',  'D11',  'D10',  'D9',   'D8',   '|',    'D7',   'D6',   'D5',   'D4',   'D3',   'D2',   'D1'],
['-'],
['|',    '|',    '|',    '|',    'E15',  'E14',  'E13',  'E12',  'E11',  'E10',  'E9',   'E8',   '|',    'E7',   'E6',   'E5',   'E4',   'E3',   'E2',   'E1'],
['|',    '|',    '|',    '|',    '|',    '|',    '|',    '|',    '|',    'F10',  'F9',   'F8',   '|',    'F7',   'F6',   'F5',   'F4',   'F3',   'F2',   'F1'],
['-'],
['|',    '|',    '|',    '|',    '|',    '|',    '|',    '|',    '|',    '|',    '|',    '|',    '|',    'G4',   'G3',   'G2',   'G1']
        ],
        selected : selectedSeatsObj,
        unavailable : unavailableObj,
		inputId: inputId
      }
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
    }
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

}
