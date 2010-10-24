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

set_include_path(__DIR__.'/Core'.PATH_SEPARATOR.get_include_path());
include_once(__DIR__.'/testenv/bootstrap.php');
include_once(__DIR__.'/../TriggerRegistry.php');

class   TriggerRegistryTest
extends ErebotModuleTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->_module = new ErebotModule_TriggerRegistry($this->_connection, NULL);
    }

    public function tearDown()
    {
        parent::tearDown();
        unset($this->_module);
    }

    /**
     * @expectedException   EErebotInvalidValue
     */
    public function testRegisterWithInvalidValueForChannel()
    {
        $this->_module->registerTriggers('test', NULL);
    }

    /**
     * @expectedException   EErebotInvalidValue
     */
    public function testUnregisterWithInvalidValueForChannel()
    {
        $this->_module->freeTriggers(NULL);
    }

    /**
     * @expectedException   EErebotNotFound
     */
    public function testUnregisterInexistentTrigger()
    {
        $this->_module->freeTriggers('inexistent trigger');
    }

    public function testRegisterGeneralTrigger()
    {
        $any = '*';
        $token1 = $this->_module->registerTriggers('test', $any);
        $this->assertNotSame(NULL, $token1);

        $token2 = $this->_module->registerTriggers('test', $any);
        $this->assertSame(NULL, $token2);

        $this->assertContains('test', $this->_module->getTriggers($token1));
        $this->_module->freeTriggers($token1);
    }
}

