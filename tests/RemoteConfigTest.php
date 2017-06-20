<?php

use Flaviozantut\RemoteConfig\RemoteConfig;

class FormBuilderTest extends PHPUnit_Framework_TestCase
{
    public function testRequestValue()
    {
        $remoteConfig = new RemoteConfig('http://localhost:8000/', 'xxxxxx');

        $this->assertEquals('foo', $remoteConfig->get()['test']);
    }
}
