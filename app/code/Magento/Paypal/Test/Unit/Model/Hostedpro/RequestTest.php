<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Paypal\Test\Unit\Model\Hostedpro;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $helper;

    /**
     * @var \Magento\Paypal\Model\Hostedpro\Request
     */
    protected $_model;

    protected $localeResolverMock;

    /**
     * @var \Magento\Tax\Helper\Data
     */
    protected $taxData;

    protected function setUp()
    {
        $this->helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->localeResolverMock = $this->getMockBuilder('Magento\Framework\Locale\Resolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->taxData = $this->helper->getObject('Magento\Tax\Helper\Data');

        $this->_model = $this->helper->getObject(
            'Magento\Paypal\Model\Hostedpro\Request',
            [
                'localeResolver' => $this->localeResolverMock,
                'taxData' => $this->taxData
            ]
        );
    }

    /**
     * @dataProvider addressesDataProvider
     */
    public function testSetOrderAddresses($billing, $shipping, $billingState, $state)
    {
        $payment = $this->getMock('Magento\Sales\Model\Order\Payment', ['__wakeup'], [], '', false);
        $order = $this->getMock(
            'Magento\Sales\Model\Order',
            ['getPayment', '__wakeup', 'getBillingAddress', 'getShippingAddress'],
            [],
            '',
            false
        );
        $order->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($payment));
        $order->expects($this->any())
            ->method('getBillingAddress')
            ->will($this->returnValue($billing));
        $order->expects($this->any())
            ->method('getShippingAddress')
            ->will($this->returnValue($shipping));
        $this->_model->setOrder($order);
        $this->assertEquals($billingState, $this->_model->getData('billing_state'));
        $this->assertEquals($state, $this->_model->getData('state'));
    }

    /**
     * @return array
     */
    public function addressesDataProvider()
    {
        $billing = new \Magento\Framework\DataObject([
            'firstname' => 'Firstname',
            'lastname' => 'Lastname',
            'city' => 'City',
            'region_code' => 'CA',
            'postcode' => '12346',
            'country' => 'United States',
            'Street' => '1 Ln Ave',
        ]);
        $shipping = new \Magento\Framework\DataObject([
            'firstname' => 'ShipFirstname',
            'lastname' => 'ShipLastname',
            'city' => 'ShipCity',
            'region' => 'olala',
            'postcode' => '12346',
            'country' => 'United States',
            'Street' => '1 Ln Ave',
        ]);
        $billing2 = new \Magento\Framework\DataObject([
            'firstname' => 'Firstname',
            'lastname' => 'Lastname',
            'city' => 'City',
            'region_code' => 'muuuu',
            'postcode' => '12346',
            'country' => 'United States',
            'Street' => '1 Ln Ave',
        ]);
        $shipping2 = new \Magento\Framework\DataObject([
            'firstname' => 'ShipFirstname',
            'lastname' => 'ShipLastname',
            'city' => 'ShipCity',
            'postcode' => '12346',
            'country' => 'United States',
            'Street' => '1 Ln Ave',
        ]);
        return [
            [$billing, $shipping, 'CA', 'olala'],
            [$billing2, $shipping2, 'muuuu', 'ShipCity']
        ];
    }

    public function testSetPaymentMethod()
    {
        $expectedData = [
            'paymentaction' => 'authorization',
            'notify_url' => 'https://test.com/notifyurl',
            'cancel_return' => 'https://test.com/cancelurl',
            'return' => 'https://test.com/returnurl',
            'lc' => 'US',
            'template' => 'mobile-iframe',
            'showBillingAddress' => 'false',
            'showShippingAddress' => 'true',
            'showBillingEmail' => 'false',
            'showBillingPhone' => 'false',
            'showCustomerName' => 'false',
            'showCardInfo' => 'true',
            'showHostedThankyouPage' => 'false'
        ];
        $paymentMethodMock = $this->getMockBuilder('Magento\Paypal\Model\Hostedpro')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $paymentMethodMock->expects($this->once())
            ->method('getConfigData')->with('payment_action')->willReturn('Authorization');
        $paymentMethodMock->expects($this->once())->method('getNotifyUrl')->willReturn('https://test.com/notifyurl');
        $paymentMethodMock->expects($this->once())->method('getCancelUrl')->willReturn('https://test.com/cancelurl');
        $paymentMethodMock->expects($this->once())->method('getReturnUrl')->willReturn('https://test.com/returnurl');
        $this->localeResolverMock->expects($this->once())->method('getLocale')->willReturn('en_US');
        $this->assertEquals($this->_model, $this->_model->setPaymentMethod($paymentMethodMock));
        $this->assertEquals('US', $this->_model->getData('lc'));
        $this->assertEquals($expectedData, $this->_model->getData());
    }

    /**
     * @covers \Magento\Paypal\Model\Hostedpro\Request::setOrder
     */
    public function testSetOrder()
    {
        $expectation = [
            'invoice' => '#000001',
            'address_override' => 'true',
            'currency_code' => 'USD',
            'buyer_email' => 'buyer@email.com',
        ];

        $order = $this->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();

        $order->expects(static::once())
            ->method('getIncrementId')
            ->willReturn($expectation['invoice']);

        $order->expects(static::once())
            ->method('getBaseCurrencyCode')
            ->willReturn($expectation['currency_code']);

        $order->expects(static::once())
            ->method('getCustomerEmail')
            ->willReturn($expectation['buyer_email']);

        $this->_model->setOrder($order);
        static::assertEquals($expectation, $this->_model->getData());
    }

    /**
     * @covers \Magento\Paypal\Model\Hostedpro\Request::setAmount
     */
    public function testSetAmountWithoutTax()
    {
        $expectation = [
            'subtotal' => 12.04,
            'tax' => 2.03,
            'shipping' => 5.05
        ];
        $amount = array_sum($expectation);

        static::assertFalse($this->taxData->priceIncludesTax());

        $payment = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $order = $this->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();

        $payment->expects(static::once())
            ->method('getBaseAmountAuthorized')
            ->willReturn($amount);

        $order->expects(static::once())
            ->method('getPayment')
            ->willReturn($payment);

        $order->expects(static::atLeastOnce())
            ->method('getBaseTaxAmount')
            ->willReturn($expectation['tax']);

        $order->expects(static::atLeastOnce())
            ->method('getBaseShippingAmount')
            ->willReturn($expectation['shipping']);

        $this->_model->setAmount($order);

        static::assertEquals($expectation, $this->_model->getData());
    }

    public function testSetAmountWithIncludedTax()
    {
        /** @var \Magento\Tax\Model\Config  $config */
        $config = $this->helper->getObject('Magento\Tax\Model\Config');
        $config->setPriceIncludesTax(true);

        $this->taxData = $this->helper->getObject(
            'Magento\Tax\Helper\Data',
            [
                'taxConfig' => $config
            ]
        );

        $this->_model = $this->helper->getObject(
            'Magento\Paypal\Model\Hostedpro\Request',
            [
                'localeResolver' => $this->localeResolverMock,
                'taxData' => $this->taxData
            ]
        );

        static::assertTrue($this->taxData->getConfig()->priceIncludesTax());

        $amount = 19.65;

        $expectation = [
            'amount' => $amount,
            'subtotal' => $amount
        ];

        $payment = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $order = $this->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();

        $payment->expects(static::once())
            ->method('getBaseAmountAuthorized')
            ->willReturn($amount);

        $order->expects(static::once())
            ->method('getPayment')
            ->willReturn($payment);

        $this->_model->setAmount($order);

        static::assertEquals($expectation, $this->_model->getData());
    }
}
