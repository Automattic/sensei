<?php

class Sensei_Class_Wc_Test extends WP_UnitTestCase {
  public function testHasCustomerBoughtProduct() {
    //
    $invalid_product_id = -123;
    $whatever_user_id = null;

    $this->assertFalse( Sensei_WC::has_customer_bought_product( $whatever_user_id, $invalid_product_id ), 'Returns false when product does not exist' );
  }
}
