jQuery(document).ready(function($) {
    // Check if the payment method is selected
    $('form.checkout').on('change', 'input[name="payment_method"]', function() {
        if ($(this).val() === 'urway') {
            // Show or hide elements specific to the urway payment method
            $('#urway-payment-fields').show();
        } else {
            $('#urway-payment-fields').hide();
        }
    });

    // Example of handling the payment form submission
    $('form.checkout').on('submit', function(event) {
        if ($('input[name="payment_method"]:checked').val() === 'urway') {
            event.preventDefault(); // Prevent the default form submission

            // Perform any additional validation or operations here
            var valid = true; // Replace this with your actual validation logic

            if (valid) {
                // Submit the form or perform AJAX request to process payment
                // For example, redirect to payment page
                var paymentUrl = $(this).data('payment-url'); // URL to send the payment data
                window.location.href = paymentUrl;
            } else {
                // Show an error message
                alert('Please check the payment details.');
            }
        }
    });
});
