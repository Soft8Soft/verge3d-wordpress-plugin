<?php
defined('ABSPATH') || exit;

if (empty($items)) {
    echo '<p>Order form is empty. Please check your Puzzles logic.</p>';
    return;
}
?>

<form method="post" id="updateorderform" class="v3d-order-form">
  <input type="hidden" name="v3d_action" value="submit" />
  <input type="hidden" name="v3d_items" value='<?= json_encode(empty($items) ? array() : $items, JSON_UNESCAPED_UNICODE); ?>' />
  <?php if (!empty($attachments)): ?>
    <?php foreach($attachments as $att): ?>
      <input type="hidden" name="v3d_attachments[]" value="<?= $att ?>" />
    <?php endforeach; ?>
  <?php endif; ?>

  <table>
    <thead>
      <tr>
        <th class="has-text-align-center">Item</th>
        <th class="has-text-align-center">Price</th>
        <th class="has-text-align-center">Quantity</th>
        <th class="has-text-align-center">Amount</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($items as $item): ?>
        <tr>
          <td class="has-text-align-left"><?= esc_html($item['title']); ?></td>
          <td class="has-text-align-center"><?= esc_html(v3d_price($item['price'])); ?></td>
          <td class="has-text-align-center"><?= esc_html($item['quantity']); ?></td>
          <td class="has-text-align-center"><?= esc_html(v3d_price($item['quantity'] * $item['price'])); ?></td>
        </tr>
      <?php endforeach; ?>

      <tr class="v3d-bold-border-total">
        <td scope="row" colspan=3 class="has-text-align-right"><strong>Total</strong></td>
        <td class="has-text-align-center"><?= esc_html(v3d_price(calc_subtotal_price($items, true))); ?></td>
      </tr>

      <?php if (!empty($attachments)): ?>
        <tr>
          <th scope="row">
            <label for="v3d_screenshot">Attachments</label>
          </th>
          <td colspan=3 class="has-text-align-left">
            <?php foreach($attachments as $att): ?>
              <a href="<?= esc_url($att); ?>" target="_blank"><img src="<?= esc_url(v3d_attachment_icon($att)); ?>" id="v3d_attachments"></a>
            <?php endforeach; ?>
          </td>
        </tr>
      <?php endif; ?>

    </tbody>
  </table>

  <table>
    <tbody>
      <tr>
        <th scope="row">
          <label for="v3d_user_name">Your Name <span class="v3d-asterix">*</span></label>
        </th>
        <td>
          <input type="text" name="v3d_user_name" id="v3d_user_name" value="" required="true">
        </td>
      </tr>

      <tr>
        <th scope="row">
          <label for="v3d_user_email">Your E-Mail <span class="v3d-asterix">*</span></label>
        </th>
        <td>
          <input type="email" name="v3d_user_email" id="v3d_user_email" value="" required="true" >
        </td>
      </tr>
      <tr>
        <th scope="row">
          <label for="v3d_user_phone">Your Phone <span class="v3d-asterix">*</span></label>
        </th>
        <td>
          <input type="tel" name="v3d_user_phone" id="v3d_user_phone" value="" required="true" >
        </td>
      </tr>
      <tr>
        <th scope="row">
          <label for="v3d_user_comment">Comments</label>
        </th>
        <td>
          <input type="text" name="v3d_user_comment" id="v3d_user_comment" value="">
        </td>
      </tr>

      <?php if (get_option('v3d_require_billing_address')): ?>
        <tr>
          <th colspan=2>Billing address</h2>
        </tr>
        <tr>
          <th scope="row">
            <label for="v3d_user_address1">Address 1 <span class="v3d-asterix">*</span></label>
          </th>
          <td>
            <input type="text" name="v3d_user_address1" id="v3d_user_address1" value="" required="true">
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="v3d_user_address2">Address 2</label>
          </th>
          <td>
            <input type="text" name="v3d_user_address2" id="v3d_user_address2" value="">
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="v3d_user_city">City</label>
          </th>
          <td>
            <input type="text" name="v3d_user_city" id="v3d_user_city" value="">
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="v3d_user_state">State / County</label>
          </th>
          <td>
            <input type="text" name="v3d_user_state" id="v3d_user_state" value="">
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="v3d_user_country">Country <span class="v3d-asterix">*</span></label>
          </th>
          <td>
            <input type="text" name="v3d_user_country" id="v3d_user_country" value="" required="true">
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="v3d_user_postcode">Postcode <span class="v3d-asterix">*</span></label>
          </th>
          <td>
            <input type="text" name="v3d_user_postcode" id="v3d_user_postcode" value="" required="true">
          </td>
        </tr>
      <?php endif; ?>


      <?php if (get_option('v3d_require_shipping_address')): ?>
        <tr>
          <th colspan=2>Shipping address</h2>
        </tr>
        <tr>
          <th scope="row">
            <label for="v3d_shipping_address1">Address 1 <span class="v3d-asterix">*</span></label>
          </th>
          <td>
            <input type="text" name="v3d_shipping_address1" id="v3d_shipping_address1" value="" required="true">
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="v3d_shipping_address2">Address 2</label>
          </th>
          <td>
            <input type="text" name="v3d_shipping_address2" id="v3d_shipping_address2" value="">
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="v3d_shipping_city">City</label>
          </th>
          <td>
            <input type="text" name="v3d_shipping_city" id="v3d_shipping_city" value="">
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="v3d_shipping_state">State / County</label>
          </th>
          <td>
            <input type="text" name="v3d_shipping_state" id="v3d_shipping_state" value="">
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="v3d_shipping_country">Country <span class="v3d-asterix">*</span></label>
          </th>
          <td>
            <input type="text" name="v3d_shipping_country" id="v3d_shipping_country" value="" required="true">
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="v3d_shipping_postcode">Postcode <span class="v3d-asterix">*</span></label>
          </th>
          <td>
            <input type="text" name="v3d_shipping_postcode" id="v3d_shipping_postcode" value="" required="true">
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="has-text-align-center">
    <input type="submit" class="button button-primary">
  </div>
</form>
