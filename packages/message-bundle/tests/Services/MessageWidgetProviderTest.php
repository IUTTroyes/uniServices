<?php

namespace MessageBundle\Tests\Services;

use App\Domain\Dashboard\WidgetDefinition;
use MessageBundle\Services\Dashboard\Provider\MessageWidgetProvider;
use PHPUnit\Framework\TestCase;

class MessageWidgetProviderTest extends TestCase
{
    public function testProviderConfiguration(): void
    {
        $provider = new MessageWidgetProvider();

        $this->assertSame('message', $provider->getBundleCode());
        $this->assertSame('Messagerie', $provider->getBundleLabel());

        $widgets = $provider->getWidgets();
        $this->assertCount(1, $widgets);

        $widget = $widgets[0];
        $this->assertInstanceOf(WidgetDefinition::class, $widget);
        $this->assertSame('message.recent', $widget->getCode());
        $this->assertSame('message', $widget->getBundle());
        $this->assertSame('MessageWidget', $widget->toArray()['component'] ?? 'MessageWidget');
    }
}

