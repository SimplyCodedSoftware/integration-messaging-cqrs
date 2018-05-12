<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\EnricherBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\SetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class EnrichInterceptor
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EnrichCallInterceptor implements CallInterceptor
{
    /**
     * @var string
     */
    private $requestChannelName;
    /**
     * @var string|null
     */
    private $requestPayloadExpression;
    /**
     * @var SetterBuilder[]
     */
    private $setterBuilders;
    /**
     * @var string[]
     */
    private $requestHeaders = [];

    /**
     * EnricherBuilder constructor.
     *
     * @param SetterBuilder[] $setters
     */
    private function __construct(array $setters)
    {
        Assert::allInstanceOfType($setters, SetterBuilder::class);

        $this->setterBuilders   = $setters;
    }

    /**
     * @param Setter[] $setterBuilders
     *
     * @return self
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function create(array $setterBuilders): self
    {
        return new self($setterBuilders);
    }

    /**
     * @param string $requestPayloadExpression
     *
     * @return EnrichCallInterceptor
     */
    public function withRequestPayloadExpression(string $requestPayloadExpression) : self
    {
        $this->requestPayloadExpression = $requestPayloadExpression;

        return $this;
    }

    /**
     * @param string $requestChannelName
     *
     * @return EnrichCallInterceptor
     */
    public function withRequestChannelName(string $requestChannelName) : self
    {
        $this->requestChannelName = $requestChannelName;

        return $this;
    }

    /**
     * @param array $requestHeaders
     *
     * @return EnrichCallInterceptor
     */
    public function withRequestHeaders(array $requestHeaders) : self
    {
        $this->requestHeaders = $requestHeaders;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandlerBuilderWithOutputChannel
    {
        $enricherBuilder = EnricherBuilder::create("", $this->setterBuilders)
            ->withRequestMessageChannel($this->requestChannelName);

        if ($this->requestPayloadExpression) {
            $enricherBuilder->withRequestPayloadExpression($this->requestPayloadExpression);
        }

        foreach ($this->requestHeaders as $header => $value) {
            $enricherBuilder->withRequestHeader($header, $value);
        }

        return $enricherBuilder;
    }
}