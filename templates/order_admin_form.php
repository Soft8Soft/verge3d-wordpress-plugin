<div class="wrap">
  <h1 class="wp-heading-inline"><?php echo $order_id > -1 ? 'Update Order' : 'Create Order' ?></h1>
  <form method="post" id="updateorderform">

    <input type="hidden" name="page" value="<?php echo sanitize_text_field($_REQUEST['page']) ?>" />
    <input type="hidden" name="action" value="<?php echo $order_id > -1 ? 'edit' : 'create' ?>" />
    <input type="hidden" name="order" value="<?php echo $order_id ?>" />

    <table class="form-table">
      <tbody>
        <tr class="form-field form-required">
          <th scope="row">
            <label for="title">Title <span class="description">(required)</span></label>
          </th>
          <td>
            <input type="text" name="title" id="title" value="<?php echo empty($order['title']) ? '' : esc_html($order['title']) ?>" required="true" autocapitalize="none" autocorrect="off" maxlength="200">
          </td>
        </tr>
        <tr class="form-field form-required">
          <th scope="row">
            <label for="content">Content <span class="description">(required)</span></label>
          </th>
          <td>
            <input type="text" name="content" id="content" value="<?php echo empty($order['content']) ? '' : esc_html($order['content']) ?>" required="true" autocapitalize="none" autocorrect="off" maxlength="200">
          </td>
        </tr>
        <tr class="form-field form-required">
          <th scope="row">
            <label for="price">Total Price <span class="description">(required)</span></label>
          </th>
          <td>
            <input type="text" name="price" id="price" value="<?php echo empty($order['price']) ? '' : esc_html($order['price']) ?>" required="true" >
          </td>
        </tr>
        <tr class="form-field form-required">
          <th scope="row">
            <label for="user_name">Customer Name <span class="description">(required)</span></label>
          </th>
          <td>
            <input type="text" name="user_name" id="user_name" value="<?php echo empty($order['user_name']) ? '' : esc_html($order['user_name']) ?>" required="true" >
          </td>
        </tr>
        <tr class="form-field form-required">
          <th scope="row">
            <label for="user_email">Customer E-Mail <span class="description">(required)</span></label>
          </th>
          <td>
            <input type="email" name="user_email" id="user_email" value="<?php echo empty($order['user_email']) ? '' : esc_html($order['user_email']) ?>" required="true" >
          </td>
        </tr>
        <tr class="form-field form-required">
          <th scope="row">
            <label for="user_phone">Customer Phone <span class="description">(required)</span></label>
          </th>
          <td>
            <input type="tel" name="user_phone" id="user_phone" value="<?php echo empty($order['user_phone']) ? '' : esc_html($order['user_phone']) ?>" required="true" >
          </td>
        </tr>
        <tr class="form-field">
          <th scope="row">
            <label for="user_comment">Comments</label>
          </th>
          <td>
            <input type="tel" name="user_comment" id="user_comment" value="<?php echo empty($order['user_comment']) ? '' : esc_html($order['user_comment']) ?>">
          </td>
        </tr>
        <tr class="form-field">
          <th scope="row">
            <label for="screenshot">Screenshot</label>
          </th>
          <td>
            <img src="<?php echo empty($order['screenshot']) ? '' : esc_url($order['screenshot']) ?>" id="screenshot" class="v3d-admin-screenshot">
          </td>
        </tr>
      </tbody>
    </table>
    <p class="submit"><input type="submit" class="button button-primary"></p>
  </form>
</div>
