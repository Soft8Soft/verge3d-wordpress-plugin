<?php
defined('ABSPATH') || exit;
?>

<script>
const v3d_payment_ajax_url='<?= admin_url('admin-ajax.php'); ?>';

const v3d_send_payment_done = (status) => {
    const form_data = new FormData();
    form_data.append('action', 'v3d_payment_done');
    form_data.append('_ajax_nonce', "<?= wp_create_nonce('v3d-payment'); ?>");
    form_data.append('order_id', '<?= intval($order_id); ?>');
    form_data.append('payment_status', status);

    const req = new XMLHttpRequest();
    req.open('POST', v3d_payment_ajax_url);
    req.send(form_data);
    req.addEventListener('load', function() {
        document.getElementById('payment-intro').innerHTML = req.response;
        document.getElementById('paypal-button-container').style.display = 'none';
    });
}
</script>

<div id="payment-intro">Please proceed with the preferred payment option:</div>

<div id="paypal-button-container"></div>

<!-- Sample PayPal credentials (client-id) are included -->
<script src="https://www.paypal.com/sdk/js?client-id=<?= $paypal_id ?>&currency=<?= get_option('v3d_currency'); ?>&intent=capture&enable-funding=venmo" data_source="integrationbuilder"></script>
<script>
  const paypalButtonsComponent = paypal.Buttons({
      // optional styling for buttons
      // https://developer.paypal.com/docs/checkout/standard/customize/buttons-style-guide/
      style: {
          color: 'gold',
          shape: 'rect',
          layout: 'vertical'
      },

      // set up the transaction
      createOrder: (data, actions) => {
          // pass in any options from the v2 orders create call:
          // https://developer.paypal.com/api/orders/v2/#orders-create-request-body
          const createOrderPayload = {
              purchase_units: [
                  {
                      amount: {
                          value: '<?= esc_html($price) ?>'
                      },
                  }
              ]
          };

          return actions.order.create(createOrderPayload);
      },

      // finalize the transaction
      onApprove: (data, actions) => {
          const captureOrderHandler = (details) => {
              const payerName = details.payer.name.given_name;
              v3d_send_payment_done('success');
          };

          return actions.order.capture().then(captureOrderHandler);
      },

      // handle unrecoverable errors
      onError: (err) => {
          v3d_send_payment_done('failed');
      }
  });

  paypalButtonsComponent
      .render("#paypal-button-container")
      .catch((err) => {
          console.error('PayPal Buttons failed to render');
      });
</script>
