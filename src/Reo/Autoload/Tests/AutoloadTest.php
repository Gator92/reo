<?php

use Reo\Autoload\Autoloader;

class AutoloaderTest extends PHPUnit_Framework_TestCase
{
    public function testRegistry()
    {
        $autoloader = $this->getAutoloader();
        $this->assertFalse($autoloader->getOption('register_spl'));
        $autoloader->register('Framework\Component\FabStuff', false);
        $this->assertEquals(null, $autoloader->getRegistryEntry('Framework\Component'));
        $this->assertEquals(false, $autoloader->getRegistryEntry('Framework\Component\FabStuff'));
        $this->assertEquals(true, $autoloader->checkRegistry('Framework\Component\FabStuff'));
        $this->assertEquals(false, $autoloader->checkRegistry('Framework\Component'));

        //root namespace set, still overrides deeper class
        $autoloader->register('Framework', $dir = 'dev/null');
        $this->assertEquals($dir, $autoloader->getRegistryEntry('Framework'));
        $this->assertEquals(true, $autoloader->checkRegistry('Framework\Component\FabStuff'));
        $this->assertEquals(false, $autoloader->checkRegistry('Framework'));

        $autoloader = $this->getAutoloader();
        $autoloader->register('Framework\Component', false);
        $this->assertEquals(true, $autoloader->checkRegistry('Framework\Component\Fabstuff'));
        $this->assertEquals(true, $autoloader->checkRegistry('Framework\Component\Fabstuff\Blixer'));
        $this->assertEquals(false, $autoloader->checkRegistry('Framework'));
        $this->assertEquals(true, $autoloader->checkRegistry('Framework\Component'));

        $autoloader = $this->getAutoloader();
        $autoloader->register('Framework', false);
        $this->assertEquals(true, $autoloader->checkRegistry('Framework\Component\Fabstuff'));
        $this->assertEquals(true, $autoloader->checkRegistry('Framework\Component\Fabstuff\Blixer'));
        $this->assertEquals(true, $autoloader->checkRegistry('Framework'));
        $this->assertEquals(true, $autoloader->checkRegistry('Framework\Component'));
    }

    public function testRegistryAutoload()
    {
        $autoloader = $this->getAutoloader();
        $autoloader->register($namespace = 'Framework', $dir = __DIR__ . DIRECTORY_SEPARATOR . 'lib');
        $this->assertEquals(true, @file_exists($dir));
        $this->assertEquals($dir, $autoloader->getRegistryEntry($namespace));

        $this->assertEquals(false, $autoloader->checkRegistry($namespace . '\Blixer'));
        $this->assertEquals(true, $autoloader->checkRegistry($class = $namespace . '\Component\Blixer'));
        $this->assertTrue(class_exists($class, false), 'The loaded class does not exists.');

        //pear style classes
        $this->assertEquals(false, $autoloader->checkRegistry($namespace . '_Bloxer'));
        $this->assertEquals(true, $autoloader->checkRegistry($class = $namespace . '_Component_Bloxer'));
        $this->assertTrue(class_exists($class, false), 'The loaded class does not exists.');
    }

    public function testLibPathAutoload()
    {
        $autoloader = $this->getAutoloader();
        $this->assertEquals(true, $autoloader->loadClass('Framework\Component\Bluxer'));
        $this->assertTrue(class_exists('Framework\Component\Bluxer', false), 'The loaded class does not exists.');
        $bluxer = new Framework\Component\Bluxer();
        $this->assertEquals('hello', $bluxer->talk());
    }

    private function getAutoloader()
    {
        return new Autoloader(array('lib' => __DIR__ . DIRECTORY_SEPARATOR.'lib'), array('register_spl' => false));
    }
}
