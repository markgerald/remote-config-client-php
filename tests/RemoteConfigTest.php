<?php

use Linx\RemoteConfigClient\RemoteConfig;

class FormBuilderTest extends PHPUnit_Framework_TestCase
{
    public function testRequestValue()
    {
        $remoteConfig = new RemoteConfig([
            'host' => 'http://127.0.0.1:8000',
            'username' => 'client@email',
            'password' => '123456',
            'application' => 'application',
            'environment' =>  'development',
        ]);

        $remoteConfig->getClientConfig('client');


        $this->assertEquals('foo', $remoteConfig->get()['test']);
    }
}
