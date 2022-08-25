<?php
$downloads = v3d_get_order_downloads($order_id);
?>

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

  </style>
</head>

<body>
  <div class="v3d-order-email">
    <?php if (get_option('v3d_merchant_logo')): ?>
    <img src="<?= esc_attr(v3d_inline_image(wp_get_attachment_image_src(get_option('v3d_merchant_logo'), 'thumbnail', false)[0])); ?>" style="width: <?= esc_attr(get_option('v3d_merchant_logo_width')); ?>">
    <?php endif; ?>

    <p><?php
      if ($to == $order['user_email']) {
          echo esc_html(v3d_format_order(get_option("v3d_order_email_{$notify_type}_content_user"), $order, $order_id));
      } else {
          echo esc_html(v3d_format_order(get_option("v3d_order_email_{$notify_type}_content"), $order, $order_id));
      }
    ?></p>

    <?php if ($notify_type !== 'quote' && $notify_type !== 'invoice'): ?>
      <table class="v3d-user-info-table">
        <tbody>
          <tr>
            <th scope="row">
              Order No
            </th>
            <td>
              <?php echo esc_html($order_id); ?>
            </td>
          </tr>
          <tr>
            <th scope="row">
              Status
            </th>
            <td>
              <?php echo esc_html(ucwords($order['status'])); ?>
            </td>
          </tr>
          <tr>
            <th scope="row">
              Date
            </th>
            <td>
              <?php echo date('j M Y'); ?>
            </td>
          </tr>
          <tr>
            <th scope="row">
              Email
            </th>
            <td>
              <?php echo esc_html($order['user_email']); ?>
            </td>
          </tr>
          <tr>
            <th scope="row">
              Phone
            </th>
            <td>
              <?php echo esc_html($order['user_phone']) ?>
            </td>
          </tr>
          <?php if (!empty($order['user_comment'])): ?>
            <tr>
              <th scope="row">
                Comments
              </th>
              <td>
                <?php echo esc_html($order['user_comment']) ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>

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
              <th scope="row" colspan=3  class="has-text-align-right">Discount</th>
              <td class="has-text-align-center"><?= esc_html('-'.$order['discount'].'%'); ?></td>
            </tr>
          <?php endif; ?>

          <?php if (!empty($order['tax'])): ?>
            <tr>
              <th scope="row" colspan=3  class="has-text-align-right">Tax</th>
              <td class="has-text-align-center"><?= esc_html($order['tax'].'%'); ?></td>
            </tr>
          <?php endif; ?>

          <tr>
            <th scope="row" colspan=3  class="has-text-align-right">Total</th>
            <td class="has-text-align-center"><?= esc_html(v3d_price(calc_total_price($order, true))); ?></td>
          </tr>

        </tbody>
      </table>

      <?php if ($order['status'] == 'completed' && !empty($downloads)): ?>
        <h3>Downloads</h3>
        <table class="v3d-order-downloads-table">
          <thead>
            <tr>
              <th class="has-text-align-center">Product</th>
              <th class="has-text-align-center">File</th>
            </tr>
          </thead>
          <tbody>
          <?php
            foreach ($downloads as $h => $d) {
              ?>
                <tr>
                  <td><?= basename($d['title']); ?></td>
                  <td><a href="<?= get_site_url().'?v3d_download_file='.$h.'&order='.$order_id; ?>"><?= basename($d['link']); ?></a></td>
                </tr>
              <?php
            }
            ?>
        </table>
      <?php endif; ?>

    <?php endif; ?>
  </div>
</body>
</html>
