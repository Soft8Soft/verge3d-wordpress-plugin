<html>

<head>
  <meta charset="UTF-8">
  <style>
    <?php v3d_inline_custom_styles(); ?>

    body {
        font-family: Arial, Helvetica, sans-serif;
        color: #333447;
    }
    h2 {
        font-size: large;
        margin: 5px 0px;
    }

    @media print {
        @page {
            margin: 0;
            size: A4;
        }
        body { margin: 1.6cm; }
    }
  </style>
</head>

<body>
  <div class="v3d-order-pdf">
    <?php if (get_option('v3d_merchant_logo')): ?>
      <img src="<?= esc_attr(v3d_inline_image(wp_get_attachment_image_src(get_option('v3d_merchant_logo'), 'thumbnail', false)[0])); ?>" style="width: <?= esc_attr(get_option('v3d_merchant_logo_width')); ?>">
    <?php endif; ?>

    <h1><?= esc_html(get_option('v3d_merchant_name')); ?></h1>
    <h1><?= (esc_html($pdftype) == 'quote' ? 'Quote' : 'Invoice') . ' #' . esc_html($order_id); ?></h1>

    <div>
      <table class="v3d-user-info-table">
        <tbody>
          <?php if (!empty(get_option('v3d_merchant_address1')) || !empty(get_option('v3d_merchant_address2'))): ?>
            <tr>
              <td>
                <?php echo esc_html(get_option('v3d_merchant_address1')); ?>
                <?php echo esc_html(get_option('v3d_merchant_address2')); ?>
              </td>
            </tr>
          <?php endif; ?>
          <?php if (!empty(get_option('v3d_merchant_city')) || !empty(get_option('v3d_merchant_state')) || !empty(get_option('v3d_merchant_postcode'))): ?>
            <tr>
              <td>
                <?php echo esc_html(get_option('v3d_merchant_city')); ?>
                <?php echo esc_html(get_option('v3d_merchant_state')); ?>
                <?php echo esc_html(get_option('v3d_merchant_postcode')); ?>
              </td>
            </tr>
          <?php endif; ?>
          <?php if (!empty(get_option('v3d_merchant_country'))): ?>
            <tr>
              <td>
                <?php echo esc_html(get_option('v3d_merchant_country')); ?>
              </td>
            </tr>
          <?php endif; ?>
          <?php if (!empty(get_option('v3d_merchant_phone'))): ?>
            <tr>
              <td>
                Phone: <?php echo esc_html(get_option('v3d_merchant_phone')); ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div>
      <table class="v3d-user-info-table">
        <thead>
          <tr>
            <th>Bill to</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <?php echo esc_html($order['user_name']); ?>
            </td>
          </tr>
          <?php if (!empty($order['user_address1']) || !empty($order['user_address2'])): ?>
            <tr>
              <td>
                <?php echo esc_html($order['user_address1']); ?>
                <?php echo esc_html($order['user_address2']); ?>
              </td>
            </tr>
          <?php endif; ?>
          <?php if (!empty($order['user_city']) || !empty($order['user_state']) || !empty($order['user_postcode'])): ?>
            <tr>
              <td>
                <?php echo esc_html($order['user_city']); ?>
                <?php echo esc_html($order['user_state']); ?>
                <?php echo esc_html($order['user_postcode']); ?>
              </td>
            </tr>
          <?php endif; ?>
          <?php if (!empty($order['user_country'])): ?>
            <tr>
              <td>
                <?php echo esc_html($order['user_country']); ?>
              </td>
            </tr>
          <?php endif; ?>
          <tr>
            <td>
              <?php echo esc_html($order['user_email']); ?>
            </td>
          </tr>
          <tr>
            <td>
              <?php echo esc_html($order['user_phone']) ?>
            </td>
          </tr>
        </tbody>
      </table>

      <?php if (!empty($order['shipping_address1']) || !empty($order['shipping_address2'])): ?>
        <table class="v3d-user-info-table">
          <thead>
            <tr>
              <th>Ship to</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <?php echo esc_html($order['shipping_address1']); ?>
                <?php echo esc_html($order['shipping_address2']); ?>
              </td>
            </tr>
            <?php if (!empty($order['shipping_city']) || !empty($order['shipping_state']) || !empty($order['shipping_postcode'])): ?>
              <tr>
                <td>
                  <?php echo esc_html($order['shipping_city']); ?>
                  <?php echo esc_html($order['shipping_state']); ?>
                  <?php echo esc_html($order['shipping_postcode']); ?>
                </td>
              </tr>
            <?php endif; ?>
            <?php if (!empty($order['shipping_country'])): ?>
              <tr>
                <td>
                  <?php echo esc_html($order['shipping_country']); ?>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="has-text-align-right">
      <table class="v3d-user-info-table">
        <tbody>
          <tr>
            <th scope="row">
              Date:
            </th>
            <td>
              <?php echo date('j M Y'); ?>
            </td>
          </tr>
          <?php if ($pdftype == 'quote'): ?>
            <tr>
              <th scope="row">
                Valid until:
              </th>
              <td>
                <?php echo date('j M Y', strtotime('+'.intval(get_option('v3d_quote_valid')).' days')); ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <table class="v3d-order-items-table">
      <thead>
        <tr>
          <th class="has-text-align-center">Item</th>
          <th class="has-text-align-center">Price</th>
          <th class="has-text-align-center">Quantity</th>
          <th class="has-text-align-center">Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($order['items'] as $item): ?>
          <tr>
            <td class="item-title"><?= esc_html($item['title']); ?></td>
            <td class="has-text-align-center"><?= esc_html(v3d_price($item['price'])); ?></td>
            <td class="has-text-align-center"><?= esc_html($item['quantity']); ?></td>
            <td class="has-text-align-center"><?= esc_html(v3d_price($item['quantity'] * $item['price'])); ?></td>
          </tr>
        <?php endforeach; ?>

        <tr class="v3d-bold-border-total">
          <th scope="row" colspan=3 class="has-text-align-right">Subtotal</th>
          <td class="has-text-align-center"><?= esc_html(v3d_price(calc_subtotal_price($order['items'], true))); ?></td>
        </tr>

        <?php if (!empty($order['discount'])): ?>
          <tr>
            <th scope="row" colspan=3  class="has-text-align-right">Discount (<?= esc_html($order['discount'].'%'); ?>)</th>
            <td class="has-text-align-center"><?= esc_html('-'.v3d_price(calc_discount($order, true))); ?></td>
          </tr>
        <?php endif; ?>

        <?php if (!empty($order['tax'])): ?>
          <tr>
            <th scope="row" colspan=3  class="has-text-align-right">Tax (<?= esc_html($order['tax'].'%'); ?>)</th>
            <td class="has-text-align-center"><?= esc_html(v3d_price(calc_tax($order, true))); ?></td>
          </tr>
        <?php endif; ?>

        <tr>
          <th scope="row" colspan=3  class="has-text-align-right">Total</th>
          <td class="has-text-align-center"><?= esc_html(v3d_price(calc_total_price($order, true))); ?></td>
        </tr>

      </tbody>
    </table>

    <?php if (!empty(get_option("v3d_{$pdftype}_notes"))): ?>
      <div class="v3d-additonal-info"><strong>Additional notes:</strong> <?= esc_html(get_option("v3d_{$pdftype}_notes")) ?></div>
    <?php endif; ?>

  </div>
</body>
</html>
