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

include_once(__DIR__.'/testenv/coreStub.php');
include_once(__DIR__.'/testenv/serverConfigStub.php');
include_once(__DIR__.'/testenv/connectionStub.php');
include_once(__DIR__.'/testenv/i18nStub.php');

include_once(__DIR__.'/../TriggerRegistry.php');

class   TriggerRegistryTest
extends PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        $this->module = new ErebotModule_TriggerRegistry();
        $this->module->reload(
            $this->module->RELOAD_ALL |
            $this->module->RELOAD_INIT
        );
    }

    public function __destruct()
    {
        unset($this->module);
    }

    /**
     * @expectedException   EErebotInvalidValue
     */
    public function testRegisterWithInvalidValueForChannel()
    {
        $this->module->registerTriggers('test', NULL);
    }

    /**
     * @expectedException   EErebotInvalidValue
     */
    public function testUnregisterWithInvalidValueForChannel()
    {
        $this->module->freeTriggers(NULL);
    }

    /**
     * @expectedException   EErebotNotFound
     */
    public function testUnregisterInexistentTrigger()
    {
        $this->module->freeTriggers('inexistent trigger');
    }

    public function testRegisterGeneralTrigger()
    {
        $any = '*';
        $token1 = $this->module->registerTriggers('test', $any);
        $this->assertNotSame(NULL, $token1);

        $token2 = $this->module->registerTriggers('test', $any);
        $this->assertSame(NULL, $token2);

        $this->assertContains('test', $this->module->getTriggers($token1));
        $this->module->freeTriggers($token1);
    }
}

?>
