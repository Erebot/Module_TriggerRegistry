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

abstract class  TextWrapper
implements      \Erebot\Interfaces\TextWrapper
{
    private $_chunks;

    public function __construct($text)
    {
        $this->_chunks = explode(' ', $text);
    }

    public function __toString()
    {
        return implode(' ', $this->_chunks);
    }

    public function getTokens($start, $length = 0, $separator = " ")
    {
        if ($length !== 0)
            return implode(" ", array_slice($this->_chunks, $start, $length));
        return implode(" ", array_slice($this->_chunks, $start));
    }

    public function offsetGet($offset)
    {
        return $this->_chunks[$offset];
    }

    public function count()
    {
        return count($this->_chunks);
    }
}

class   TriggerRegistryTest
extends Erebot_Testenv_Module_TestCase
{
    public function setUp()
    {
        $this->_module = new \Erebot\Module\TriggerRegistry(NULL);
        parent::setUp();

        $this->_module->reloadModule(
            $this->_connection,
            \Erebot\Module\Base::RELOAD_MEMBERS
        );
    }

    public function tearDown()
    {
        $this->_module->unloadModule();
        parent::tearDown();
    }

    /**
     * @expectedException   \Erebot\InvalidValueException
     */
    public function testRegisterWithInvalidValueForChannel()
    {
        $this->_module->registerTriggers('test', NULL);
    }

    /**
     * @expectedException   \Erebot\InvalidValueException
     */
    public function testUnregisterWithInvalidValueForChannel()
    {
        $this->_module->freeTriggers(NULL);
    }

    /**
     * @expectedException   \Erebot\NotFoundException
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
     * @expectedException   \Erebot\NotFoundException
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
     * @expectedException   \Erebot\NotFoundException
     */
    public function testInvalidToken()
    {
        $chan = '#test';
        $token1 = $this->_module->registerTriggers(array('foo', 'bar'), $chan);
        $this->assertNotSame(NULL, $token1);
        $this->assertContains('foo', $this->_module->getTriggers($chan.' BOGUS'));
    }

    /**
     * @expectedException   \Erebot\InvalidValueException
     */
    public function testInvalidToken2()
    {
        $this->_module->getTriggers(NULL);
    }

    public function testHelp()
    {
        $wordsClass = $this->getMockForAbstractClass(
            'TextWrapper',
            array(),
            '',
            FALSE,
            FALSE
        );
        $words = new $wordsClass('Erebot\\Module\\TriggerRegistry');

        $event = $this->getMock(
            '\\Erebot\\Interfaces\\Event\\ChanText',
            array(), array(), '', FALSE, FALSE
        );
        $event
            ->expects($this->any())
            ->method('getChan')
            ->will($this->returnValue('#test'));

        $this->assertTrue($this->_module->getHelp($event, $words));
    }
}

