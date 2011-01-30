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

class   Erebot_Module_TriggerRegistry
extends Erebot_Module_Base
{
    protected $_triggers;

    const MATCH_ANY = '*';

    public function _reload($flags)
    {
        if ($flags & self::RELOAD_MEMBERS)
            $this->_triggers = array(self::MATCH_ANY => array());
    }

    protected function _unload()
    {
    }

    protected function containsRecursive(&$array, &$value)
    {
        if (!is_array($array))
            return FALSE;

        if (in_array($value, $array))
            return TRUE;

        foreach ($array as &$sub)
            if ($this->containsRecursive($sub, $value))
                return TRUE;
        return FALSE;
    }

    public function registerTriggers($triggers, $channel)
    {
        if (!is_array($triggers))
            $triggers = array($triggers);

        if (!is_string($channel)) {
            $translator = $this->getTranslator(FALSE);
            throw new Erebot_InvalidValueException(
                $translator->gettext('Invalid channel')
            );
        }

        /// @TODO: clean this up
        $logging    =&  Plop::getInstance();
        $logger     =   $logging->getLogger(__FILE__);

        foreach ($triggers as &$trigger) {
            if ($channel != self::MATCH_ANY && isset($this->_triggers[$channel]))
                if ($this->containsRecursive($this->_triggers[$channel], $trigger)) {
                    $logger->info('The trigger named "'.$trigger.'" already exists on '.$channel);
                    return NULL;
                }

            if ($this->containsRecursive($this->_triggers[self::MATCH_ANY], $trigger)) {
                $logger->info('The trigger named "'.$trigger.'" already exists globally.');
                return NULL;
            }
        }
        unset($trigger);

        $this->_triggers[$channel][] = $triggers;
        end($this->_triggers[$channel]);
        return $channel.' '.key($this->_triggers[$channel]);
    }

    public function freeTriggers($token)
    {
        $translator = $this->getTranslator(FALSE);

        if (!is_string($token) || strpos($token, ' ') === FALSE)
            throw new Erebot_InvalidValueException(
                $translator->gettext('Invalid token')
            );

        list($chan, $pos) = explode(' ', $token);

        if (!isset($this->_triggers[$chan][$pos]))
            throw new Erebot_NotFoundException(
                $translator->gettext('No such triggers')
            );

        unset($this->_triggers[$chan][$pos]);
    }

    public function getChanTriggers($chan)
    {
        if (!isset($this->_triggers[$chan])) {
            $translator = $this->getTranslator(FALSE);
            throw new Erebot_NotFoundException(
                sprintf(
                    $translator->gettext(
                        'No triggers found for channel "%s"'
                    ),
                    $chan
                )
            );
        }

        return $this->_triggers[$chan];
    }

    public function getTriggers($token)
    {
        $translator = $this->getTranslator(FALSE);

        if (!is_string($token) || strpos($token, ' ') === FALSE)
            throw new Erebot_InvalidValueException(
                $translator->gettext('Invalid token')
            );

        list($chan, $pos) = explode(' ', $token);

        if (!isset($this->_triggers[$chan][$pos])) {
            throw new Erebot_NotFoundException(
                $translator->gettext('No such triggers')
            );
        }

        return $this->_triggers[$chan][$pos];
    }
}

