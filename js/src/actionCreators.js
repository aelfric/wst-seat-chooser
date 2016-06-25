var actions = require('./actions.js');

module.exports = {

  init : function (numSeats, seatingChart = [], seatsSelected = [], unavailableSeats = [], inputId) {
    var unavailableObj = {};
    unavailableSeats.forEach(function (seatNumber) {
      unavailableObj[seatNumber] = true;
    })
	
	  var selectedSeatsObj = {};
	  seatsSelected.forEach(function (seatNumber) {
		  selectedSeatsObj[seatNumber] = true;
	  })
    var maxRowSize = seatingChart.reduce(function(maxLen, nextArr){
        return Math.max(maxLen, nextArr.length);
    }, 0);
    return {
      type : actions.INIT,
      payload : {
        numSeats : numSeats,
        numSelected : seatsSelected.length,
        gridSize: maxRowSize,
        seatingChart : seatingChart,
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

