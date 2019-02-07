<style>
  .v3d-order-form th,td {
    padding: 2px;
  }
  .v3d-order-form th {
    width: 170px;
    text-align: left;
  }
  .v3d-asterix {
    color: red;
  }

  .v3d-order-form textarea {
    min-width: 200px;
    min-height: 100px;
    resize: none;
  }

  .v3d-order-form .form-field input {
    width: 100%;
  }

  .v3d-order-form .button {
    display: block;
    margin: auto;
  }
</style>

<script>
</script>

<div class="v3d-order-form">
  <form method="post" id="updateorderform">
    <input type="hidden" name="v3d_action" value="submit" />
    <input type="hidden" name="v3d_title" value="<?php echo $title ?>" />
    <input type="hidden" name="v3d_content" value="<?php echo $content ?>" />
    <input type="hidden" name="v3d_screenshot" value="<?php echo $screenshot ?>" />

    <table class="form-table">
      <tbody>
        <tr class="form-field">
          <th scope="row">
            <label for="textarea">Content</label>
          </th>
          <td>
            <textarea name="textarea" readonly><?php echo esc_html($content) ?></textarea>
          </td>
        </tr>
        <tr class="form-field form-required">
          <th scope="row">
            <label for="v3d_price">Total Price</label>
          </th>
          <td>
            <input type="text" name="v3d_price" id="v3d_price" value="<?php echo esc_html($price) ?>" required="true" readonly>
          </td>
        </tr>
        <tr class="form-field form-required">
          <th scope="row">
            <label for="v3d_user_name">Your Name <span class="v3d-asterix">*</span></label>
          </th>
          <td>
            <input type="text" name="v3d_user_name" id="v3d_user_name" value="" required="true" >
          </td>
        </tr>
        <tr class="form-field form-required">
          <th scope="row">
            <label for="v3d_user_email">Your E-Mail <span class="v3d-asterix">*</span></label>
          </th>
          <td>
            <input type="email" name="v3d_user_email" id="v3d_user_email" value="" required="true" >
          </td>
        </tr>
        <tr class="form-field form-required">
          <th scope="row">
            <label for="v3d_user_phone">Your Phone <span class="v3d-asterix">*</span></label>
          </th>
          <td>
            <input type="tel" name="v3d_user_phone" id="v3d_user_phone" value="" required="true" >
          </td>
        </tr>
        <tr class="form-field">
          <th scope="row">
            <label for="v3d_user_comment">Comments</label>
          </th>
          <td>
            <input type="text" name="v3d_user_comment" id="v3d_user_comment" value="">
          </td>
        </tr>
        <tr class="form-field" style="display: <?php echo empty($screenshot) ? 'none' : 'table-row' ?>;">
          <th scope="row">
            <label for="v3d_screenshot">Screenshot</label>
          </th>
          <td>
            <img src="<?php echo $screenshot ?>" id="v3d_screenshot" style="min-width:200px">
          </td>
        </tr>
      </tbody>
    </table>
    <p class="submit"><input type="submit" class="button button-primary"></p>
  </form>
</div>
