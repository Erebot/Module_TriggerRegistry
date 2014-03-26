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

namespace Erebot\Module;

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
 * The main methods are Erebot::Module::TriggerRegistry::registerTriggers()
 * which is used to register new triggers (eg. at the beginning of a game)
 * and Erebot::Module::TriggerRegistry::freeTriggers() which is used to free
 * triggers when they are not used anymore (eg. at the end of the game).
 */
class TriggerRegistry extends \Erebot\Module\Base implements \Erebot\Interfaces\HelpEnabled
{
    /// Array of arrays where the actual triggers are kept.
    protected $triggers;

    /**
     * Used to (un)register a trigger globally
     * rather than for a single IRC channel.
    */
    const MATCH_ANY = '*';

    /**
     * This method is called whenever the module is (re)loaded.
     *
     * \param int $flags
     *      A bitwise OR of the Erebot::Module::Base::RELOAD_*
     *      constants. Your method should take proper actions
     *      depending on the value of those flags.
     *
     * \note
     *      See the documentation on individual RELOAD_*
     *      constants for a list of possible values.
     */
    public function reload($flags)
    {
        if ($flags & self::RELOAD_MEMBERS) {
            $this->triggers = array(self::MATCH_ANY => array());
        }
    }

    /**
     * Provides help about this module.
     *
     * \param Erebot::Interfaces::Event::Base::TextMessage $event
     *      Some help request.
     *
     * \param Erebot::Interfaces::TextWrapper $words
     *      Parameters passed with the request. This is the same
     *      as this module's name when help is requested on the
     *      module itself (in opposition with help on a specific
     *      command provided by the module).
     */
    public function getHelp(
        \Erebot\Interfaces\Event\Base\TextMessage $event,
        \Erebot\Interfaces\TextWrapper $words
    ) {
        if ($event instanceof \Erebot\Interfaces\Event\Base\PrivateMessage) {
            $target = $event->getSource();
            $chan   = null;
        } else {
            $target = $chan = $event->getChan();
        }

        $fmt        = $this->getFormatter($chan);
        $moduleName = strtolower(get_class());

        if (count($words) == 1 && $words[0] == $moduleName) {
            $msg = $fmt->_(
                "This module does not provide any command, but ".
                "provides a registry that other modules may use ".
                "to register triggers (commands)."
            );
            $this->sendMessage($target, $msg);
            return true;
        }
        return false;
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
     *      \b true if the given value is contained
     *      in the passed array, \b false otherwise.
     */
    protected function containsRecursive(&$array, &$value)
    {
        if (!is_array($array)) {
            return false;
        }

        foreach ($array as $sub) {
            if (is_string($sub) && !strcasecmp($sub, $value)) {
                return true;
            }

            if (is_array($sub) && $this->containsRecursive($sub, $value)) {
                return true;
            }
        }
        return false;
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
     *      or the constant Erebot::Module::TriggerRegistry::MATCH_ANY
     *      to register them globally (for all channels).
     *
     * \retval mixed
     *      Either a string which acts as a key to later
     *      unregister the triggers at a later time
     *      (see Erebot::Module::TriggerRegistry::freeTriggers),
     *      or \b null if the triggers could not be registered
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
        if (!is_array($triggers)) {
            $triggers = array($triggers);
        }

        $fmt        = $this->getFormatter(false);
        $translator = $fmt->getTranslator();
        if (!is_string($channel)) {
            throw new \Erebot\InvalidValueException($fmt->_('Invalid channel'));
        }

        $scopes = array(
            $channel => $translator->_(
                'A trigger named "<var name="trigger"/>" '.
                'has already been registered on channel <var name="chan"/>.'
            ),
            self::MATCH_ANY => $translator->_(
                'A trigger named "<var name="trigger"/>" '.
                'has already been registered globally.'
            ),
        );

        foreach ($triggers as $trigger) {
            foreach ($scopes as $scope => $error) {
                if (isset($this->triggers[$scope])) {
                    if ($this->containsRecursive($this->triggers[$scope], $trigger)) {
                        $this->logger and $this->logger->info(
                            $fmt->render(
                                $error,
                                array(
                                    'trigger' => $trigger,
                                    'chan' => $channel,
                                )
                            )
                        );
                        return null;
                    }
                }
            }
        }

        $this->triggers[$channel][] = $triggers;
        end($this->triggers[$channel]);
        return $channel.' '.key($this->triggers[$channel]);
    }

    /**
     * Unregisters a series of triggers from the registry,
     * based on the unique key associated with them.
     *
     * \param string $token
     *      A key such as the ones returned by
     *      Erebot::Module::TriggerRegistry::registerTriggers()
     *      that is associated with the triggers to unregister.
     *
     * \note
     *      When several triggers are registered at the same time
     *      using Erebot::Module::TriggerRegistry::registerTriggers,
     *      they are treated as a single group.
     *      Calling Erebot::Module::TriggerRegistry::freeTriggers
     *      with the key associated with that group unregisters
     *      all of those triggers from the registry.
     */
    public function freeTriggers($token)
    {
        $fmt = $this->getFormatter(false);

        if (!is_string($token) || strpos($token, ' ') === false) {
            throw new \Erebot\InvalidValueException($fmt->_('Invalid token'));
        }

        list($chan, $pos) = explode(' ', $token);

        if (!isset($this->triggers[$chan][$pos])) {
            throw new \Erebot\NotFoundException($fmt->_('No such triggers'));
        }

        unset($this->triggers[$chan][$pos]);
    }

    /**
     * Returns a list of all triggers
     * registered for a given channel.
     *
     * \param string $channel
     *      Either the name of a specific IRC channel
     *      to return only those triggers that were
     *      specifically registered for use in that channel,
     *      or the constant Erebot::Module::TriggerRegistry::MATCH_ANY
     *      to return triggers that were registered globally.
     *
     * \retval array
     *      A list of all triggers registered
     *      for the given IRC channel.
     */
    public function getChanTriggers($channel)
    {
        if (!isset($this->triggers[$channel])) {
            $fmt = $this->getFormatter(false);
            throw new \Erebot\NotFoundException(
                $fmt->_(
                    'No triggers found for channel "<var name="chan"/>"',
                    array('chan' => $channel)
                )
            );
        }

        return $this->triggers[$channel];
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
        $fmt = $this->getFormatter(false);

        if (!is_string($token) || strpos($token, ' ') === false) {
            throw new \Erebot\InvalidValueException($fmt->_('Invalid token'));
        }

        list($chan, $pos) = explode(' ', $token);

        if (!isset($this->triggers[$chan][$pos])) {
            throw new \Erebot\NotFoundException($fmt->_('No such triggers'));
        }

        return $this->triggers[$chan][$pos];
    }
}
