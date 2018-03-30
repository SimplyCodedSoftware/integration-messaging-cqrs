# Integration Messaging CQRS Module

Module proving CQRS functionality. Does work for services and aggregates.  
If you're not aware of naming used in here, please [read documentation of integration messaging](https://github.com/SimplyCodedSoftware/integration-messaging).

#### Usage

##### Service Command Handler

    /**
     * @MessageEndpointAnnotation(referenceName="serviceCommandHandlerExample")
     */
    class CommandHandlerServiceExample
    {
        /**
         * @CommandHandlerAnnotation()
         */
        public function doAction(SomeCommand $command) : void
        {
    
        }
    }
    

##### Service Query Handler

    /**
     * @MessageEndpointAnnotation()
     */
    class QueryHandlerServiceExample
    {
        /**
         * @QueryHandlerAnnotation()
         */
        public function searchFor(SomeQuery $query) : SomeResult
        {
            return new SomeResult();
        }
    }
    
    
##### Aggregate Command Handler

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


##### Aggregate Query Handler


    /**
     * @AggregateAnnotation()
     */
    class AggregateQueryHandlerExample
    {
        /**
         * @QueryHandlerAnnotation()
         */
        public function doStuff(SomeQuery $query) : SomeResult
        {
            return new SomeResult();
        }
    }

#### How to send messages

##### inject into your controller/service CommandGateway or QueryGateway

`SimplyCodedSoftware\IntegrationMessaging\Cqrs\CommandGateway`  or `SimplyCodedSoftware\IntegrationMessaging\Cqrs\QueryGateway` 
are automatically registered within your container.  
They are accessible under class names, so if your container can do 
auto-wiring then you can just simply type hint in method declaration, to get them injected. 

##### Manual sending 
Send Message with payload as query or command to `integration_messaging.cqrs.execute_message` message channel


#### Your own flow

If you need to perform some actions, before executing `command` / `query` for example deserializing json, then you can benefit 
from `Message Flow` extension.  
This allows for connecting specific name with message class name. You define default flow, that all your message will go through, 
or for specific usecases define custom flow.     
Also flow will take of of mapping message name to class name, so you know what to deserialize.  
To begin flow for message you can make use of `SimplyCodedSoftware\IntegrationMessaging\Cqrs\MessageFlowGateway`, 
what you need to pass is message name defined same as in `SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\MessageFlowAnnotation`.  
Then you need to define default channel, where messages will be send `integration_messaging.cqrs.start_default_flow`, if you will 
define custom flow for message, then you will also need to `create channel` after `message name`.  
In headers you will have access to class mapping defined under: `integration_messaging.cqrs.message_class`




 