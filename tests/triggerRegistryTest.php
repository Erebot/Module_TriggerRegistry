<?php
/*
    This file is part of Erebot.

    Erebot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Erebot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Erebot.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined('__DIR__')) {
  class __FILE_CLASS__ {
    function  __toString() {
      $X = debug_backtrace();
      return dirname($X[1]['file']);
    }
  }
  define('__DIR__', new __FILE_CLASS__);
} 

include_once(__DIR__.'/../../../../autoloader.php');
include_once(__DIR__.'/testenv/bootstrap.php');

class   TriggerRegistryTest
extends ErebotModuleTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->_module = new Erebot_Module_TriggerRegistry(
            $this->_connection,
            NULL
        );
        $this->_module->reload(Erebot_Module_Base::RELOAD_MEMBERS);
    }

    public function tearDown()
    {
        parent::tearDown();
        unset($this->_module);
    }

    /**
     * @expectedException   Erebot_InvalidValueException
     */
    public function testRegisterWithInvalidValueForChannel()
    {
        $this->_module->registerTriggers('test', NULL);
    }

    /**
     * @expectedException   Erebot_InvalidValueException
     */
    public function testUnregisterWithInvalidValueForChannel()
    {
        $this->_module->freeTriggers(NULL);
    }

    /**
     * @expectedException   Erebot_NotFoundException
     */
    public function testUnregisterInexistentTrigger()
    {
        $this->_module->freeTriggers('inexistent trigger');
    }

    public function testRegisterGeneralTrigger()
    {
        $chan = '*'; // Any chan.
        $token1 = $this->_module->registerTriggers(array('foo', 'bar'), $chan);
        $this->assertNotSame(NULL, $token1);

        $token2 = $this->_module->registerTriggers('foo', $chan);
        $this->assertSame(NULL, $token2);

        $this->assertContains('foo', $this->_module->getTriggers($token1));
        $this->assertContains('bar', $this->_module->getTriggers($token1));
        $this->assertEquals(
            array(array('foo', 'bar')),
            $this->_module->getChanTriggers($chan)
        );
        $this->_module->freeTriggers($token1);

        $token1 = $this->_module->registerTriggers(array('foo', 'bar'), $chan);
        $this->assertNotSame(NULL, $token1);
        $this->_module->freeTriggers($token1);
    }

    /**
     * @expectedException   Erebot_NotFoundException
     */
    public function testInexistentChanTriggers()
    {
        $this->_module->getChanTriggers('#does_not_exist');
    }

    public function testExistingChanTriggers()
    {
        $chan = '#test'; // Specific chan.
        $token1 = $this->_module->registerTriggers(array('foo', 'bar'), $chan);
        $this->assertNotSame(NULL, $token1);

        $token2 = $this->_module->registerTriggers('foo', $chan);
        $this->assertSame(NULL, $token2);

        $this->assertContains('foo', $this->_module->getTriggers($token1));
        $this->assertContains('bar', $this->_module->getTriggers($token1));
        $this->assertEquals(
            array(array('foo', 'bar')),
            $this->_module->getChanTriggers($chan)
        );
        $this->_module->freeTriggers($token1);

        $token1 = $this->_module->registerTriggers(array('foo', 'bar'), $chan);
        $this->assertNotSame(NULL, $token1);
        $this->_module->freeTriggers($token1);
    }

    /**
     * @expectedException   Erebot_NotFoundException
     */
    public function testInvalidToken()
    {
        $chan = '#test';
        $token1 = $this->_module->registerTriggers(array('foo', 'bar'), $chan);
        $this->assertNotSame(NULL, $token1);
        $this->assertContains('foo', $this->_module->getTriggers($chan.' BOGUS'));
    }

    /**
     * @expectedException   Erebot_InvalidValueException
     */
    public function testInvalidToken2()
    {
        $this->_module->getTriggers(NULL);
    }
}

