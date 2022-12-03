<div class="wrap">
  <h1 class="wp-heading-inline"><?php echo $product_id > -1 ? 'Update Product' : 'Create Product' ?></h1>
  <form method="post" id="updateproductform">

    <input type="hidden" name="page" value="<?php echo sanitize_text_field($_REQUEST['page']) ?>" />
    <input type="hidden" name="action" value="<?php echo $product_id > -1 ? 'edit' : 'create' ?>" />
    <input type="hidden" name="product" value="<?php echo $product_id ?>" />

    <table class="form-table">
      <tbody>
        <tr class="form-field form-required">
          <th scope="row">
            <label for="title">Title <span class="description">(required)</span></label>
          </th>
          <td>
            <input type="text" name="title" id="title" value="<?php echo esc_html($title) ?>" required="true" autocapitalize="none" autocorrect="off" maxlength="200">
          </td>
        </tr>
        <tr class="form-field form-required">
          <th scope="row">
            <label for="sku">SKU <span class="description">(required)</span></label>
          </th>
          <td>
            <input type="text" name="sku" id="sku" value="<?php echo esc_html($sku) ?>" required="true">
          </td>
        </tr>
        <tr class="form-field form-required">
          <th scope="row">
            <label for="price">Price <span class="description">(required)</span></label>
          </th>
          <td>
            <input type="number" name="price" id="price" value="<?php echo esc_html($price) ?>" required="true">
          </td>
        </tr>
        <tr class="form-field">
          <th scope="row">
            <label for="download_link">Download link</label>
          </th>
          <td>
            <input type="text" name="download_link" id="download_link" value="<?php echo esc_html($download_link) ?>">
          </td>
        </tr>
      </tbody>
      </tbody>
    </table>
    <p class="submit"><input type="submit" value="<?php echo $product_id > -1 ? 'Update' : 'Create' ?>" class="button button-primary"></p>
  </form>
</div>

