# Integration Messaging CQRS

Does work for services and aggregates.

## Example
    /**    
     * @AggregateAnnotation()
     */
    class Order
    {
        /**
         * @param DoStuffCommand $command
         * @CommandHandlerAnnotation()
         */
        public static function register(RegisterNewOrder $command) : void
        {
            // do something
        }
        
        public functon cancel(CancelOrder $command) : void
        {
            // do something
        }
    }
    
    class CancelOrder
    {
        /**
         * @var string
         * @AggregateIdAnnotation()
         */
        private $orderId;
        /**
         * @var int
         * @AggregateExpectedVersionAnnotation()
         */
        private $version;
    }

#### How to send messages 
Send Message with payload as query or command to `messaging.cqrs.message` having header `messaging.cqrs.message.type` as `query`|`command`.


 