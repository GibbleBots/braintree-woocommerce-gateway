
<html>
  <head>
  </head>
  <body>
   <div style="text-align:center;vertical-align:middle;">
    <h1>Braintree Payment Gateway</h1>
    
      <form action="transaction.php" method="POST" id="braintree-payment-form">
      <p>
        <label>Card Number</label>
        <input type="text" size="20" autocomplete="off" data-encrypted-name="number" name="number"/>
      </p>
      <p>
        <label>CVV</label>
        <input type="text" size="4" autocomplete="off" data-encrypted-name="cvv"  name="cvv"/>
      </p>
      <p>
        <label>Expiration (MM/YYYY)</label>
        <input type="text" size="2" data-encrypted-name="month"  name="month"  /> /<input type="text" size="4" data-encrypted-name="year"  name="year">
      </p>
      <input type="hidden" name="customerReference" value="<?php echo $_POST['customerReference'];?>">
      <input type="hidden" name="order_id" value="<?php echo $_POST['INVNUM'];?>">
      <input type="hidden" name="amount" value="<?php echo $_POST['amount'];?>">
      <input class="submit-button" type="submit" />
    </form>
    <script src="https://js.braintreegateway.com/v1/braintree.js"></script>
    <script>
      var braintree = Braintree.create("YourClientSideEncryptionKey");
      braintree.onSubmitEncryptForm("braintree-payment-form");
    </script>
    </div>
  </body>
</html>
