<?php

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;
use org\bovigo\vfs\visitor\vfsStreamVisitor;

class CacheLiteTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!@class_exists('org\\bovigo\\vfs\\vfsStreamWrapper', true)) {
            $this->markTestSkipped('vfsStreamWrapper not found');
            return;
        }
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('rootDir'));
    }

    public function testCacheDirIsCreated()
    {
        //valid cache dir
        $cache = new Reo_Classic_CacheLite(vfsStream::url('rootDir/cache'), array('hashedDirectoryUmask' => $umask = 0644));
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('cache'));
        //verify option set
        $this->assertEquals($umask, $cache->hashedDirectoryUmask);
        //verify permissions
        $this->assertEquals($umask, vfsStreamWrapper::getRoot()->getChild('cache')->getPermissions());
        //invalid cache dir
        $this->setExpectedException('InvalidArgumentException');
        $cache = new Reo_Classic_CacheLite(vfsStream::url('rootBeer/cache'));
    }

    public function testSaveGetEntry()
    {
        $cache = $this->getCache();
        $this->assertNull($cache->get('cheese'));
        $cache->save('cheese', 'cheddar');
        //var_dump(file_get_contents(vfsStream::url('rootDir/cache/cheese')));
        $this->assertEquals('cheddar', $cache->get('cheese'));
    }

    public function testSaveGetGroupEntry()
    {
        $cache = $this->getCache();
        $this->assertNull($cache->get('cheese', 'pizza'));
        $cache->save('cheese', 'colby');
        $cache->save('cheese', 'mozzarella', 'pizza');
        $cache->save('cheese', 'provolone', 'sub');
        $this->assertEquals('colby', $cache->get('cheese'));
        $this->assertEquals('mozzarella', $cache->get('cheese', 'pizza'));
        $this->assertEquals('provolone', $cache->get('cheese', 'sub'));
    }

    public function testRemove()
    {
        $cache = $this->getCache();
        //key doesn't exists
        $this->assertTrue($cache->remove($key = 'cheese'));
        //debug mode
        $this->assertFalse($cache->setDebug()->remove($key));
        //set value
        $cache->setDebug(false)->save($key, $value = 'swiss');
        $this->assertEquals($value, $cache->get($key));
        $cache->remove($key);
        $this->assertNull($cache->get($key));
    }

    public function testRemoveGroup()
    {
        $cache = $this->getCache();
        //key doesn't exists
        $this->assertTrue($cache->remove($key = 'cheese', $group = 'burger'));
        $cache->save($key, 'pepperjack');
        $cache->save($key, $value = 'gouda', $group);
        $this->assertEquals($value, $cache->get($key, $group));
        $cache->remove($key);
        $this->assertNull($cache->get($key));
        $this->assertNotNull($cache->get($key, $group));
        $cache->save($key, 'colbyjack');
        $cache->remove($key, $group);
        $this->assertNull($cache->get($key, $group));
        $this->assertNotNull($cache->get($key));
    }

    public function testPurge()
    {
        $cache = $this->getCache();
        //set value
        $keys = $vals = array();
        $cache->save($keys[] = 'cheese', $vals[] = 'swiss', $group = 'burger');
        $cache->save($keys[] = 'bread', $vals[] = 'toasted', $group);
        $cache->save($keys[] = 'heinz', $vals[] = 57, $group);
        $cache->save('sides', 'french fried potaters');
        for ($ct = count($keys), $xx = 0; $xx < $ct; $xx++) {
            $this->assertEquals($vals[$xx], $cache->get($keys[$xx], $group));
        }
        $this->assertNull($cache->get('sides', $group));

        //purge by group
        $cache->clean($group);
        for ($xx = 0; $xx < $ct; $xx++) {
            $this->assertNull($cache->get($keys[$xx], $group));
        }
        $this->assertEquals('french fried potaters', $cache->get('sides'));

        $cache->save('burger', 'cheese', $group = 'paradise');
        $this->assertEquals('cheese', $cache->get('burger', 'paradise'));
        //saved in group dirs
        $this->assertTrue(vfsStreamWrapper::getRoot()->getChild('cache')->hasChildren());
        //purge it all
        $cache->clean();
        $this->assertNull($cache->get('sides'));
        $this->assertNull($cache->get('burger', 'paradise'));
        //orphaned dirs are removed
        $this->assertFalse(vfsStreamWrapper::getRoot()->getChild('cache')->hasChildren());
    }

    public function testPurgeApacheMode()
    {
        $cache = $this->getCache(true);
        //set value
        $keys = $vals = array();
        $cache->save($keys[] = 'cheese/holes', $vals[] = 'swiss', $group = 'burger');
        $cache->save($keys[] = 'bread/garlic', $vals[] = 'toasted', $group);
        $cache->save($keys[] = 'heinz/sauce', $vals[] = 57, $group);
        $cache->save($keyNoGroup = 'sides/french', 'fried potaters');
        for ($ct = count($keys), $xx = 0; $xx < $ct; $xx++) {
            $this->assertEquals($vals[$xx], $cache->get($keys[$xx], $group));
        }
        $this->assertNull($cache->get($keyNoGroup, $group));

        //purge by group
        $cache->clean($group);
        for ($xx = 0; $xx < $ct; $xx++) {
            $this->assertNull($cache->get($keys[$xx], $group));
        }
        //non grouped key still exists
        $this->assertEquals('fried potaters', $cache->get($keyNoGroup));

        $cache->save('burger', 'cheese', $group = 'paradise');
        $this->assertEquals('cheese', $cache->get('burger', 'paradise'));
        //saved in group dirs
        $this->assertTrue(vfsStreamWrapper::getRoot()->getChild('cache')->hasChildren());
        //purge it all
        $cache->clean();
        $this->assertNull($cache->get($keyNoGroup));
        $this->assertNull($cache->get('burger', 'paradise'));
        //orphaned dirs are removed
        $this->assertFalse(vfsStreamWrapper::getRoot()->getChild('cache')->hasChildren());
    }

    public function testRemoveEntry()
    {
        $cache = $this->getCache();
        $cache->save('nacho', 'chipolte');
        $this->assertEquals('chipolte', $cache->get('nacho'));
        $cache->remove('nacho');
        $this->assertNull($cache->get('nacho'));

        //with group
        $cache->save('burger', 'cheese', $group = 'paradise');
        $cache->save('nacho', 'guac', $group = 'paradise');
        $this->assertEquals('cheese', $cache->get('burger', $group));
        $this->assertNull($cache->get('paradise'));

        //no group, should not be removed
        $cache->remove('burger');
        $this->assertEquals('cheese', $cache->get('burger', $group));
        //group, should be removed
        $cache->remove('burger', 'paradise');
        $this->assertNull($cache->get('burger', $group));
        //others in group should remain
        $this->assertEquals('guac', $cache->get('nacho', $group));
    }

    public function testRemoveEntryApacheMode()
    {
        $cache = $this->getCache(true);
        $cache->save('nacho/cheese', 'chipolte');
        $cache->save('nacho/chips', 'guac');
        $this->assertNull($cache->get('nacho'));
        $this->assertEquals('chipolte', $cache->get('nacho/cheese'));
        $cache->remove('nacho/cheese');
        $this->assertNull($cache->get('nacho/cheese'));
        $this->assertEquals('guac', $cache->get('nacho/chips'));
        $cache->remove('nacho/chips');
        $this->assertNull($cache->get('nacho/chips'));

        //orphaned dir
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($dir = 'cache/nacho'));
        $cache->clean('nacho');
        $this->assertFalse(vfsStreamWrapper::getRoot()->hasChild($dir));

        //with group
        $cache->save('burger', 'cheese', $group = 'paradise');
        $cache->save('nacho', 'guac', $group = 'paradise');
        $this->assertEquals('cheese', $cache->get('burger', $group));

        //no group, should not be removed
        $cache->remove('burger');
        $this->assertEquals('cheese', $cache->get('burger', $group));

        //group, should be removed
        $cache->remove('burger', 'paradise');
        $this->assertNull($cache->get('burger', $group));

        //others in group should remain
        $this->assertEquals('guac', $cache->get('nacho', $group));
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($dir = 'cache/paradise'));

        //clean the group
        $cache->clean('paradise');
        $this->assertNull($cache->get('nacho', $group));
        $this->assertFalse(vfsStreamWrapper::getRoot()->hasChild($dir));
    }

    public function testApacheMode()
    {
        $cache = new Reo_Classic_CacheLite(vfsStream::url('rootDir/cache'), array('fileNameHashMode' => 'apache', 'debug' => true));
        $this->assertEquals('apache', $cache->fileNameHashMode);
        $cache->save('biscuits', 'mustard');
        $this->assertEquals('mustard', $cache->get('biscuits'));
        //key is directory
        $this->assertTrue(vfsStreamWrapper::getRoot()->getChild('cache')->hasChild('biscuits'));
        
        //with a group
        $cache->save('/meat', 'potted', 'shed');
        $this->assertEquals('potted', $cache->get('meat', 'shed'));

        //trailing dirs mean nothing
        $cache->save('/french/', 'fries', 'shed');
        $this->assertEquals('fries', $cache->get('french', 'shed'));
        $cache->clean();

        //save with proper indexes required with "pretty urls"
        $cache->save('/meat/index', 'potted', 'shed');
        $cache->save('/meat/potaters', 'french fries', 'shed');
        $this->assertEquals('potted', $cache->get('meat/index', 'shed'));
        $this->assertEquals('french fries', $cache->get('/meat/potaters', 'shed'));

        //exception when directory is already a file @note: this "turns off errors" for the rest of the function
        $this->setExpectedException('RuntimeException');
        $cache->save('/meat', 'potted', 'shed');
        $cache->clean();
    }

    public function testApacheModeSecurity()
    {
        $cache = new Reo_Classic_CacheLite(vfsStream::url('rootDir/cache'), array('fileNameHashMode' => 'apache', 'debug' => true, 'readControl' => false));
        //relative path security
        $this->loadPrivateDir();
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('private'));
        $cache->save('/french/fried/potaters', 'r good');
        $this->assertEquals('r good', $cache->get('/french/fried/potaters'));
        $this->assertNull($cache->get('/french/../../private/goodies'));
        $cache->save('/french/../../private/goodies', 'bogus data');
        $this->assertEquals($this->getPrivateData(), 'Treasure Trove!');
        //var_dump(vfsStream::inspect(new vfsStreamStructureVisitor())->getStructure());exit;
        //vsfStream::newFile('config.ini')->at(vfsStreamWrapper::getRoot());
    }

    public function testTtl()
    {
        $cache = new Reo_Classic_CacheLite(vfsStream::url('rootDir/cache'), array('fileNameHashMode' => 'apache', 'readControlType' => 'md5'));
        $time = time();
        $cache->set($key = 'nothing', $value = 'burger', $ttl = 100);
        $time += $ttl;
        $written = file_get_contents(vfsStream::url('rootDir/cache/nothing'));
        //ttl
        $setTtl = substr($written, 0, 10);
        $this->assertEquals($time, $setTtl);
        //hash
        $setHash = substr($written, 10, 32);
        $this->assertEquals(md5($value), $setHash);
        //value
        $setValue = substr($written, 42);
        $this->assertEquals($value, $setValue);
        //the actual cache getter
        $this->assertEquals($value, $cache->get($key));
        //test read control
        $bogus = $setTtl . md5('cheese') . $setValue;
        file_put_contents(vfsStream::url('rootDir/cache/nothing'), $bogus);
        $this->assertNull($cache->get($key));
        $cache->set($key = 'cheese', $value = 'burger');
        $written = file_get_contents(vfsStream::url('rootDir/cache/cheese'));
        //ttl
        $setTtl = substr($written, 0, 10);
        $this->assertEquals('0000000000', $setTtl);
        //ttl without read control
        $cache->setOption('readControl', false);
        $cache->set($key = 'nothing', $value = 'burger');
        $written = file_get_contents(vfsStream::url('rootDir/cache/nothing'));
        $this->assertEquals($value, $written);
        $cache->setLifeTime($ttl = -3600);
        $time = time() + $ttl;
        $this->assertEquals($time, $cache->refreshTime);
        $this->assertNull($cache->get($key));
        //garbage is auto cleaned up
        $this->assertFalse(@file_exists(vfsStream::url('rootDir/cache/nothing')));
        //read control none (ttl stored)
        $cache->setLifeTime(0);
        $cache->setOption('readControl', true);
        $cache->setOption('readControlType', 'none');
        $time = time() + 1800;
        $cache->set($key = 'burger', $value = 'cheese', 1800);
        $written = file_get_contents(vfsStream::url('rootDir/cache/burger'));
        $this->assertEquals($time.$value, $written);
        $this->assertEquals($value, $cache->get($key));
        //refresh time is set after get
        $this->assertEquals($time, $cache->refreshTime);
    }

    public function testHasEntry()
    {
        $cache = new Reo_Classic_CacheLite(vfsStream::url('rootDir/cache'));
        $cache->save($key = 'burger', $value = 'cheese', $group = 'paradise');
        $this->assertTrue($cache->has($key, $group));
        $cache->setLifeTime(-3600);
        //doesn't check the ttl validity
        $this->assertTrue($cache->has($key, $group));
        //remove
        $cache->remove($key, $group);
        $this->assertNull($cache->get($key, $group));
        $this->assertFalse($cache->has($key, $group));
        //does check the ttl without readControl
        $cache->setOption('readControl', false);
        $cache->save($key = 'burger', $value = 'cheese', $group = 'paradise');
        $this->assertFalse($cache->has($key, $group));
    }

    public function testFileExt()
    {
        $cache = $this->getCache();
        $this->assertFalse($cache->has('cheeseburger'));
        $this->assertNotNull($name = $cache->getFileName());
        //add extension
        $cache->setOption('fileExt', $ext = '.yum');
        //reset file name
        $this->assertFalse($cache->has('cheeseburger'));
        //the name is the same, but with the appropriate ext
        $this->assertEquals($name . $ext, $cache->getFileName());
    }

    private function getCache($apacheMode = false)
    {
        $cache =  new Reo_Classic_CacheLite(vfsStream::url('rootDir/cache'), $apacheMode ? array('fileNameHashMode' => 'apache', 'debug' => true, 'readControl' => false) : array());
        $cache->clean();
        return $cache;
    }

    private function loadPrivateDir()
    {
        vfsStream::newDirectory('private')->at($root = vfsStreamWrapper::getRoot());
        vfsStream::newFile('goodies')->at($root->getChild('private'))->setContent("Treasure Trove!");
        return;
        $file = vfsStream::url('rootDir/private/goodies');
        file_put_contents($file, 'Treasure Trove!');
    }

    private function getPrivateData()
    {
        return(file_get_contents(vfsStream::url('rootDir/private/goodies')));
    }
}
