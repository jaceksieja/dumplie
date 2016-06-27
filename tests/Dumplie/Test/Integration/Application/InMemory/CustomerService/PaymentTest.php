<?php

namespace Dumplie\Test\Integration\Application\InMemory\CustomerService;

use Dumplie\Application\Command\CustomerService\CreatePayment;
use Dumplie\Application\Command\CustomerService\CreatePaymentHandler;
use Dumplie\Application\Command\CustomerService\PayPayment;
use Dumplie\Application\Command\CustomerService\PayPaymentHandler;
use Dumplie\Application\Command\CustomerService\RejectPayment;
use Dumplie\Application\Command\CustomerService\RejectPaymentHandler;
use Dumplie\Domain\CustomerService\Order;
use Dumplie\Domain\CustomerService\Payment;
use Dumplie\Domain\CustomerService\Payments;
use Dumplie\Infrastructure\InMemory\CustomerService\InMemoryOrders;
use Dumplie\Infrastructure\InMemory\CustomerService\InMemoryPayments;

class PaymentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Payment
     */
    private $payment;

    /**
     * @var Payments
     */
    private $payments;

    function setUp()
    {
        $this->payment = new Payment(new Order());
        $this->payments = new InMemoryPayments([$this->payment]);
    }

    function test_create_payment()
    {
        $order = new Order();
        $carts = new InMemoryOrders([$order]);

        $command = new CreatePayment($order->id());
        $handler = new CreatePaymentHandler($carts, $this->payments);

        $handler->handle($command);
    }

    function test_pay_payment()
    {
        $command = new PayPayment($this->payment->id());
        $handler = new PayPaymentHandler($this->payments);

        $handler->handle($command);
    }

    function test_reject_payment()
    {
        $payCommand = new RejectPayment($this->payment->id());
        $payHandler = new RejectPaymentHandler($this->payments);
        $payHandler->handle($payCommand);
    }
}
