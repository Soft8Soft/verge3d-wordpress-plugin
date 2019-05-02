<html>

<head>
  <meta charset="UTF-8">
  <style>
    body {
      font-family: Arial, Helvetica, sans-serif;
      color: #333447;
    }
    h2 {
      font-size: large;
      margin: 5px 0px;
    }

    .form-table th {
      padding: 10px;
      text-align: left;
    }
    .form-table td {
      padding: 10px;
    }
    @media print {
      @page { margin: 0; }
      body { margin: 1.6cm; }
    }
  </style>
</head>

<body>
  <div class="wrap">
    <h2>Order details</h2>

    <table class="form-table">
      <tbody>
        <tr class="form-field">
          <th scope="row">
            Order ID
          </th>
          <td>
            <?php echo $order_id ?>
          </td>
        </tr>
        <tr class="form-field">
          <th scope="row">
            Date
          </th>
          <td>
            <?php echo date('j M Y') ?>
          </td>
        </tr>
        <tr class="form-field">
          <th scope="row">
            Content
          </th>
          <td>
            <?php echo esc_html($order['content']) ?>
          </td>
        </tr>
        <tr class="form-field">
          <th scope="row">
            Total Price
          </th>
          <td>
            <?php echo esc_html($order['price']) ?>
          </td>
        </tr>
        <tr class="form-field">
          <th scope="row">
            Email
          </th>
          <td>
            <?php echo esc_html($order['user_email']) ?>
          </td>
        </tr>
        <tr class="form-field">
          <th scope="row">
            Phone
          </th>
          <td>
            <?php echo esc_html($order['user_phone']) ?>
          </td>
        </tr>
        <tr class="form-field">
          <th scope="row">
            Comments
          </th>
          <td>
            <?php echo esc_html($order['user_comment']) ?>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</body>
</html>
