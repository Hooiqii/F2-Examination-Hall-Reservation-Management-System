function showBookingForm() {
    // Check if the guideline agreement checkbox is checked
    if (document.getElementById("guidelineAgree").checked) {
        // Hide the guideline section
        document.getElementById("guidelineWrapper").style.display = "none";

        // Show the booking form section
        document.getElementById("bookingForm").style.display = "block";

        // Enable the start date field
        document.getElementById("startDate").removeAttribute("disabled");

        // Ensure that the "One Day" option is checked initially
        document.getElementById("oneDay").checked = true;

        // Set the end date to the same date as start date for one day booking
        document.getElementById("endDate").max = document.getElementById("startDate").value;
        document.getElementById("endDate").min = document.getElementById("startDate").value;
        // Call the function to set the initial state of date and time fields
        setInitialDateTimeState();

        // Add event listener to the booking duration radio buttons
        document.querySelectorAll('input[name="bookingDuration"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                if (this.value === 'multipleDays') {
                    // Disable past dates for both start date and end date fields
                    disableTodayForMultipleDays();
                    // Reset end date option to "Please select"
                    document.getElementById("endDate").value = "";
                    // Enable the end date for user to select
                    document.getElementById("endDate").disabled = false;
                    document.getElementById("endDate").max = "8000-12-31";
                    // Call the function to set the initial state of date and time fields
                    setInitialDateTimeState();
                    // Call setEndDate function to update the end date field's state
                    setEndDate();
                    // Call limitStartEndTime to check the time range
                    limitStartEndTime(); 
                } else {
                    // Enable the end date field for one day booking
                    document.getElementById("endDate").max = document.getElementById("startDate").value;
                    document.getElementById("endDate").min = document.getElementById("startDate").value;
                    // Call the function to set the initial state of date and time fields
                    setInitialDateTimeState();
                }
                // Call limitStartEndTime whenever the booking duration changes
                limitStartEndTime();
            });
        });

        // Ensure that the function is called when the page loads initially
        if (document.querySelector('input[name="bookingDuration"]:checked').value === 'multipleDays') {
            disableTodayForMultipleDays();
            // Call setEndDate function to update the end date field's state
            setEndDate();
        }
    } else {
        // Show an alert or any message if the checkbox is not checked
        alert("Please agree to the guidelines before proceeding.");
    }
}

// Function to disable today's date and past dates in the start and end date fields for multiple days booking
function disableTodayForMultipleDays() {
    // Check if the current booking duration is 'multipleDays'
    if (document.querySelector('input[name="bookingDuration"]:checked').value === 'multipleDays') {
        // Get today's date
        var today = new Date();
        var yyyy = today.getFullYear();
        var mm = today.getMonth() + 1; // January is 0!
        var dd = today.getDate();
        if (mm < 10) {
            mm = '0' + mm;
        }
        if (dd < 10) {
            dd = '0' + dd;
        }
        var currentDate = yyyy + '-' + mm + '-' + dd;

        // Set the minimum date for both start date and end date fields to tomorrow
        var tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        var yyyy_tomorrow = tomorrow.getFullYear();
        var mm_tomorrow = tomorrow.getMonth() + 1; // January is 0!
        var dd_tomorrow = tomorrow.getDate();
        if (mm_tomorrow < 10) {
            mm_tomorrow = '0' + mm_tomorrow;
        }
        if (dd_tomorrow < 10) {
            dd_tomorrow = '0' + dd_tomorrow;
        }
        var tomorrowDate = yyyy_tomorrow + '-' + mm_tomorrow + '-' + dd_tomorrow;

        // Set the minimum date for both start date and end date fields to tomorrow
        document.getElementById("startDate").setAttribute("min", tomorrowDate);
        document.getElementById("endDate").setAttribute("min", tomorrowDate);

        // Reset the start date and end date selection if it's today
        if (document.getElementById("startDate").value === currentDate) {
            document.getElementById("startDate").value = "";
        }
        if (document.getElementById("endDate").value === currentDate) {
            document.getElementById("endDate").value = "";
        }
    }
}

// Function to show the guideline section
function showGuideline() {
    // Hide the booking form section
    document.getElementById("bookingForm").style.display = "none";

    // Show the guideline section
    document.getElementById("guidelineWrapper").style.display = "block";
}

// Function to confirm reset of the form
function confirmReset() {
    // Use the confirm dialog to ask the user if they want to reset the form
    const isConfirmed = confirm("Are you sure you want to reset the form?");

    // If the user confirms, reset the form
    if (isConfirmed) {
        document.getElementById('resetButton').form.reset();
    }
}

// Function to set initial state for date and time fields
function setInitialDateTimeState() {
    // Call functions to set initial state for date and time fields
    disablePastDates();
    limitStartEndTime();
}

// Function to update the end date field's state and validate the relationship between start date and end date
function setEndDate() {
    // Get the value of the start date
    const startDateValue = document.getElementById("startDate").value;

    // Check if the endDate element exists
    const endDateElement = document.getElementById("endDate");
    if (endDateElement) {
        // Get the value of the end date if the element exists
        const endDateValue = endDateElement.value;
        const bookingDuration = document.querySelector('input[name="bookingDuration"]:checked').value;

        // Reset end date only if it's not empty when switching to the "multipleDays" option
        if (bookingDuration === 'multipleDays' && endDateValue) {
            endDateElement.value = "";
        }

        // Check if the "One Day" option is selected
        if (bookingDuration === 'oneDay') {
            // Set the end date same as start date for one day booking
            endDateElement.max = startDateValue;
            endDateElement.min = startDateValue;
            endDateElement.disabled = false;
        } else {
            endDateElement.value = endDateValue;
            // For multiple days booking
            if (endDateValue && endDateValue <= startDateValue) {
                // If end date is earlier than or equal to start date, display alert and reset end date
                alert("Error! End date cannot be earlier than or equal to start date.");
                endDateElement.value = "";
            }
        }
    }
}

document.addEventListener("DOMContentLoaded", function() {
    // Add event listener to end date input field to validate selected end date
    var endDateElement = document.getElementById("endDate");
    if (endDateElement) {
        // If the element with ID "endDate" exists, add a change event listener to it
        endDateElement.addEventListener('change', function() {
            // Call setEndDate function to validate the end date
            setEndDate(); 
        });
    }

    // Add event listener to start date input field to validate selected start date
    var startDateElement = document.getElementById("startDate");
    if (startDateElement) {
        // If the element with ID "startDate" exists, add a change event listener to it
        startDateElement.addEventListener('change', function() {
            // Call setEndDate function to validate the end date against the start date
            setEndDate(); 
        });
    }
});

// Function to disable past dates in the start date field and end date field for one day option
function disablePastDates() {
    // Check if the current booking duration is 'oneDay' and if the start date element exists
    var bookingDuration = document.querySelector('input[name="bookingDuration"]:checked');
    if (bookingDuration && bookingDuration.value === 'oneDay') {
        var startDateElement = document.getElementById("startDate");
        if (startDateElement) {
            // Get today's date
            var today = new Date();
            var yyyy = today.getFullYear();
            var mm = today.getMonth() + 1; // January is 0!
            var dd = today.getDate();
            if (mm < 10) {
                mm = '0' + mm;
            }
            if (dd < 10) {
                dd = '0' + dd;
            }
            var currentDate = yyyy + '-' + mm + '-' + dd;

            // Disable past and today's dates for both start date and end date for one day booking
            startDateElement.setAttribute("min", currentDate);
            var endDateElement = document.getElementById("endDate");
            if (endDateElement) {
                endDateElement.setAttribute("min", currentDate);
            }
        }
    }
}

// Call setEndDate and disablePastDates function on page load to set the initial value and disable the end date field
window.onload = function() {
    disablePastDates();
    // Check if the endDate element exists before calling setEndDate
    if (document.getElementById("endDate")) {
        setEndDate();
    }
}

// Function to limit start and end time selection and display alert for invalid time range
function limitStartEndTime() {
    var startDateValue = document.getElementById("startDate").value;
    var endDateValue = document.getElementById("endDate").value;
    var startTimeSelect = document.getElementById("startTime");
    var endTimeSelect = document.getElementById("endTime");
    var startTimeValue = startTimeSelect.value;
    var endTimeValue = endTimeSelect.value;
    var currentDate = new Date().toISOString().split('T')[0];

    // Disable past times for today's date
    Array.from(startTimeSelect.options).forEach(function(option) {
        var optionTime = new Date(currentDate + "T" + option.value);
        option.disabled = startDateValue === currentDate && optionTime <= new Date();
    });

    Array.from(endTimeSelect.options).forEach(function(option) {
        var optionTime = new Date(currentDate + "T" + option.value);
        option.disabled = endDateValue === currentDate && optionTime <= new Date();
    });

    // Check if both start and end times are selected
    if (startTimeValue && endTimeValue) {
        var startTime = new Date("2000-01-01T" + startTimeValue);
        var endTime = new Date("2000-01-01T" + endTimeValue);
        
        // Validate end time against start time
        if (endTime <= startTime) {
            // Check if it's for "multiple days" and start date is different from end date
            var bookingDuration = document.querySelector('input[name="bookingDuration"]:checked').value;
            if (bookingDuration === 'multipleDays' && endDateValue !== startDateValue) {
                // Display alert for invalid time range
                alert("Error! Your End Time cannot be earlier than or equal to Start Time.");
                // Reset end time selection
                endTimeSelect.value = "";
            } else {
                // Display alert for invalid time range
                alert("Error! Your End Time cannot be earlier than or equal to Start Time.");
                // Reset end time selection
                endTimeSelect.value = "";
            }
        }
    }
}

// Call limitStartEndTime function on page load to limit start and end time options
window.onload = function() {
    limitStartEndTime();
};

document.addEventListener("DOMContentLoaded", function() {
    // Add event listeners to the necessary elements
    var endDateElement = document.getElementById("endDate");
    var startDateElement = document.getElementById("startDate");
    var startTimeElement = document.getElementById("startTime");
    var endTimeElement = document.getElementById("endTime");
    var bookingDurationRadios = document.querySelectorAll('input[name="bookingDuration"]');

    // Function to call limitStartEndTime
    function handleTimeChange() {
        limitStartEndTime();
    }

    // Add event listeners for date and time changes
    if (endDateElement) {
        endDateElement.addEventListener('change', handleTimeChange);
    }
    if (startDateElement) {
        startDateElement.addEventListener('change', handleTimeChange);
    }
    if (startTimeElement) {
        startTimeElement.addEventListener('change', handleTimeChange);
    }
    if (endTimeElement) {
        endTimeElement.addEventListener('change', handleTimeChange);
    }

    // Add event listener for booking duration changes
    bookingDurationRadios.forEach(function(radio) {
        radio.addEventListener('change', handleTimeChange);
    });

    // Call limitStartEndTime when the page loads initially
    limitStartEndTime();


     // Initially disable the time fields
     document.getElementById("startTime").disabled = true;
     document.getElementById("endTime").disabled = true;
 
     // Function to check if both date fields are filled
     function checkDatesFilled() {
         var startDate = document.getElementById("startDate").value;
         var endDate = document.getElementById("endDate").value;
 
         if (startDate !== "" && endDate !== "") {
             document.getElementById("startTime").disabled = false;
             document.getElementById("endTime").disabled = false;
         } else {
             document.getElementById("startTime").disabled = true;
             document.getElementById("endTime").disabled = true;
         }
     }
 
     // Add event listeners to the date fields
     document.getElementById("startDate").addEventListener('change', checkDatesFilled);
     document.getElementById("endDate").addEventListener('change', checkDatesFilled);
     
});

document.addEventListener("DOMContentLoaded", function() {
    // Function to handle changes in booking duration or start date
    function handleBookingDurationOrStartDateChange() {
        var bookingDuration = document.querySelector('input[name="bookingDuration"]:checked').value;
        var startDate = document.getElementById("startDate").value;
        var endDateElement = document.getElementById("endDate");

        if (bookingDuration === 'oneDay' && startDate !== "") {
            endDateElement.value = startDate;
            endDateElement.setAttribute('readonly', true); // Make end date field readonly
            endDateElement.classList.add('disabled'); // Add disabled class for styling
        } else {
            endDateElement.removeAttribute('readonly'); // Make end date field editable if not one-day booking
            endDateElement.classList.remove('disabled'); // Remove disabled class
        }
        limitStartEndTime();
        checkDatesFilled();
    }

    // Function to check if both date fields are filled and enable/disable time fields accordingly
    function checkDatesFilled() {
        var startDate = document.getElementById("startDate").value;
        var endDate = document.getElementById("endDate").value;

        var timeFieldsDisabled = startDate === "" || endDate === "";
        document.getElementById("startTime").disabled = timeFieldsDisabled;
        document.getElementById("endTime").disabled = timeFieldsDisabled;
    }

    // Function to limit start and end time (to be defined)
    function limitStartEndTime() {
        // Define the logic to limit start and end time based on your requirements
    }

    // Add event listeners to booking duration radio buttons
    document.querySelectorAll('input[name="bookingDuration"]').forEach(function(radio) {
        radio.addEventListener('change', handleBookingDurationOrStartDateChange);
    });

    // Add event listener to start date input field
    document.getElementById("startDate").addEventListener('change', handleBookingDurationOrStartDateChange);

    // Add event listener to end date input field
    document.getElementById("endDate").addEventListener('change', checkDatesFilled);

    // Initial setup on page load
    handleBookingDurationOrStartDateChange();
    checkDatesFilled();
});



// AJAX code for displaying the remaining capacity of the selected floor, date, start time, and end time
$(document).ready(function() {
    $('#floorSelect, #startDate, #endDate, #startTime, #endTime').on('change', function() {
        var floorSelection = $('#floorSelect').val();
        var startDate = $('#startDate').val();
        var endDate = $('#endDate').val();
        var startTime = $('#startTime').val();
        var endTime = $('#endTime').val();
        // Check if any of the values are not selected
        if (floorSelection === "" || startDate === "" || endDate === "" || startTime === "" || endTime === "") {
            // Set the remaining capacity to "N/A" and exit the function
            $('.remaining-capacity').text('Remaining Capacity for Selected Time Slot: N/A');
            return; // Exit the function early
        }

        // Retrieve the remaining capacity from local storage

        // If all values are selected, proceed with the AJAX request
        $.ajax({
            url: 'getRemainingCapacity.php',
            type: 'POST',
            data: {
                floorSelection: floorSelection,
                startDate: startDate,
                endDate: endDate,
                startTime: startTime,
                endTime: endTime
            },
            success: function(response) {
                $('.remaining-capacity').text('Remaining Capacity for Selected Time Slot: ' + response);
            },
            error: function() {
                alert('Error fetching remaining capacity!');
            }
        });
    });
});
