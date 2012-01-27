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

/**
 * \brief
 *      A module that acts as a registry of "triggers".
 *
 * Here, a trigger simply means a command that the bot
 * recognizes and reacts to (eg. "!lag"). This module
 * is meant to prevent conflicts between modules by
 * ensuring no two modules try to use the same triggers
 * at the same time.
 *
 * The main methods are Erebot_Module_TriggerRegistry::registerTriggers()
 * which is used to register new triggers (eg. at the beginning of a game)
 * and Erebot_Module_TriggerRegistry::freeTriggers() which is used to free
 * triggers when they are not used anymore (eg. at the end of the game).
 */
class   Erebot_Module_TriggerRegistry
extends Erebot_Module_Base
{
    /// Array of arrays where the actual triggers are kept.
    protected $_triggers;

    /**
     * Used to (un)register a trigger globally
     * rather than for a single IRC channel.
    */
    const MATCH_ANY = '*';

    /**
     * This method is called whenever the module is (re)loaded.
     *
     * \param int $flags
     *      A bitwise OR of the Erebot_Module_Base::RELOAD_*
     *      constants. Your method should take proper actions
     *      depending on the value of those flags.
     *
     * \note
     *      See the documentation on individual RELOAD_*
     *      constants for a list of possible values.
     */
    public function _reload($flags)
    {
        if ($flags & self::RELOAD_MEMBERS)
            $this->_triggers = array(self::MATCH_ANY => array());
    }

    /**
     * Checks whether some array contains the given
     * data, in a recursive fashion.
     *
     * \param array $array
     *      Array to test.
     *
     * \param mixed $value
     *      Look for this specific value.
     *
     * \retval bool
     *      TRUE if the given value is contained
     *      in the passed array, FALSE otherwise.
     */
    protected function _containsRecursive(&$array, &$value)
    {
        if (!is_array($array))
            return FALSE;

        foreach ($array as $sub) {
            if (is_string($sub) && !strcasecmp($sub, $value))
                return TRUE;

            if (is_array($sub) && $this->_containsRecursive($sub, $value))
                return TRUE;
        }
        return FALSE;
    }

    /**
     * Registers a series of triggers, either globally
     * or for a specific channel.
     *
     * \param mixed $triggers
     *      Either a single string or an array of strings
     *      with the names of the triggers to register.
     *
     * \param string $channel
     *      Either the name of a specific IRC channel
     *      the triggers should be registered for (eg. #Erebot),
     *      or the constant Erebot_Module_TriggerRegistry::MATCH_ANY
     *      to register them globally (for all channels).
     *
     * \retval mixed
     *      Either a string which acts as a key to later
     *      unregister the triggers at a later time
     *      (see Erebot_Module_TriggerRegistry::freeTriggers),
     *      or NULL if the triggers could not be registered
     *      (eg. because they conflict with other already
     *      registered triggers).
     *
     * \warning
     *      Triggers should only contain alphanumeric characters
     *      (characters from the range a-z or 0-9), should not
     *      contain spaces or prefixes (eg. "!"), and are
     *      case-insensitive.
     */
    public function registerTriggers($triggers, $channel)
    {
        if (!is_array($triggers))
            $triggers = array($triggers);

        $fmt = $this->getFormatter(FALSE);
        if (!is_string($channel)) {
            throw new Erebot_InvalidValueException($fmt->_('Invalid channel'));
        }

        foreach ($triggers as $trigger) {
            if ($channel != self::MATCH_ANY &&
                isset($this->_triggers[$channel])) {
                if ($this->_containsRecursive(
                    $this->_triggers[$channel],
                    $trigger
                )) {
                    $this->_logger and $this->_logger->info(
                        $fmt->_(
                            'A trigger named "<var name="trigger"/>" '.
                            'already exists on <var name="chan"/>',
                            array(
                                'trigger' => $trigger,
                                'chan' => $channel,
                            )
                        )
                    );
                    return NULL;
                }
            }

            if ($this->_containsRecursive(
                $this->_triggers[self::MATCH_ANY],
                $trigger
            )) {
                $this->_logger and $this->_logger->info(
                    'A trigger named "<var name="trigger"/>" '.
                    'already exists globally.',
                    array('trigger' => $trigger)
                );
                return NULL;
            }
        }

        $this->_triggers[$channel][] = $triggers;
        end($this->_triggers[$channel]);
        return $channel.' '.key($this->_triggers[$channel]);
    }

    /**
     * Unregisters a series of triggers from the registry,
     * based on the unique key associated with them.
     *
     * \param string $token
     *      A key such as the ones returned by
     *      Erebot_Module_TriggerRegistry::registerTriggers()
     *      that is associated with the triggers to unregister.
     *
     * \note
     *      When several triggers are registered at the same time
     *      using Erebot_Module_TriggerRegistry::registerTriggers,
     *      they are treated as a single group.
     *      Calling Erebot_Module_TriggerRegistry::freeTriggers
     *      with the key associated with that group unregisters
     *      all of those triggers from the registry.
     */
    public function freeTriggers($token)
    {
        $fmt = $this->getFormatter(FALSE);

        if (!is_string($token) || strpos($token, ' ') === FALSE)
            throw new Erebot_InvalidValueException($fmt->_('Invalid token'));

        list($chan, $pos) = explode(' ', $token);

        if (!isset($this->_triggers[$chan][$pos]))
            throw new Erebot_NotFoundException($fmt->_('No such triggers'));

        unset($this->_triggers[$chan][$pos]);
    }

    /**
     * Returns a list of all triggers
     * registered for a given channel.
     *
     * \param string $channel
     *      Either the name of a specific IRC channel
     *      to return only those triggers that were
     *      specifically registered for use in that channel,
     *      or the constant Erebot_Module_TriggerRegistry::MATCH_ANY
     *      to return triggers that were registered globally.
     *
     * \retval array
     *      A list of all triggers registered
     *      for the given IRC channel.
     */
    public function getChanTriggers($channel)
    {
        if (!isset($this->_triggers[$channel])) {
            $fmt = $this->getFormatter(FALSE);
            throw new Erebot_NotFoundException(
                $fmt->_(
                    'No triggers found for channel "<var name="chan"/>"',
                    array('chan' => $channel)
                )
            );
        }

        return $this->_triggers[$channel];
    }

    /**
     * Returns a list of all triggers
     * associated with the given key.
     *
     * \param string $token
     *      Some key associated with a series
     *      of triggers.
     *
     * \retval array
     *      A list of all triggers associated
     *      with the given key.
     */
    public function getTriggers($token)
    {
        $fmt = $this->getFormatter(FALSE);

        if (!is_string($token) || strpos($token, ' ') === FALSE)
            throw new Erebot_InvalidValueException($fmt->_('Invalid token'));

        list($chan, $pos) = explode(' ', $token);

        if (!isset($this->_triggers[$chan][$pos])) {
            throw new Erebot_NotFoundException($fmt->_('No such triggers'));
        }

        return $this->_triggers[$chan][$pos];
    }
}

