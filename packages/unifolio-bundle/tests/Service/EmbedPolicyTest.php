<?php

namespace UnifolioBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use UnifolioBundle\Service\Media\EmbedPolicy;

class EmbedPolicyTest extends TestCase
{
    public function testHotesAutorises(): void
    {
        $this->assertTrue(EmbedPolicy::isAutorise('https://www.youtube.com/watch?v=abc'));
        $this->assertTrue(EmbedPolicy::isAutorise('https://youtu.be/abc'));
        $this->assertTrue(EmbedPolicy::isAutorise('https://www.figma.com/file/x'));
    }

    public function testHotesRefuses(): void
    {
        $this->assertFalse(EmbedPolicy::isAutorise('http://www.youtube.com/watch?v=abc'), 'HTTPS obligatoire');
        $this->assertFalse(EmbedPolicy::isAutorise('https://youtube.com.pirate.example/x'));
        $this->assertFalse(EmbedPolicy::isAutorise('https://evilyoutube.com/x'));
        $this->assertFalse(EmbedPolicy::isAutorise('javascript:alert(1)'));
    }
}
