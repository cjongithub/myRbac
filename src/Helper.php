<?php

namespace Laratrust;

use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

class Helper
{
    /**
     * Gets the it from an array, object or integer.
     *
     * @param  mixed  $object
     * @param  string  $type
     * @return int
     */
    public static function getIdFor($object, $type)
    {
        if (is_null($object)) {
            return null;
        } elseif (is_object($object)) {
            return $object->getKey();
        } elseif (is_array($object)) {
            return $object['id'];
        } elseif (is_numeric($object)) {
            return $object;
        } elseif (is_string($object)) {
            return call_user_func_array([
                Config::get("laratrust.models.{$type}"), 'where'
            ], ['name', $object])->firstOrFail()->getKey();
        }

        throw new InvalidArgumentException(
            'getIdFor function only accepts an integer, a Model object or an array with an "id" key'
        );
    }

    /**
     * Returns the team's foreign key.
     *
     * @return string
     */
    public static function teamForeignKey()
    {
        return Config::get('laratrust.foreign_keys.team');
    }

    /**
     * Fetch the team model from the name.
     *
     * @param  mixed  $team
     * @return mixed
     */
    public static function fetchTeam($team = null)
    {
        if (is_null($team) || !Config::get('laratrust.use_teams')) {
            return null;
        }

        $team = call_user_func_array(
                    [Config::get('laratrust.models.team'), 'where'],
                    ['name', $team]
                )->first();
        return is_null($team) ? $team : $team->getKey();
    }

    /**
     * Assing the real values to the team and requireAllOrOptions parameters.
     *
     * @param  mixed  $team
     * @param  mixed  $requireAllOrOptions
     * @return array
     */
    public static function assignRealValuesTo($team, $requireAllOrOptions, $method)
    {
        return [
            ($method($team) ? null : $team),
            ($method($team) ? $team : $requireAllOrOptions),
        ];
    }

    /**
     * Checks if the string passed contains a pipe '|' and explodes the string to an array.
     * @param  string|array  $value
     * @return string|array
     */
    public static function standardize($value)
    {
        if (is_array($value) || strpos($value, '|') === false) {
            return $value;
        }

        return explode('|', $value);
    }

    /**
     * Check if a role or permission is attach to the user in a same team.
     *
     * @param  mixed  $rolePermission
     * @param  \Illuminate\Database\Eloquent\Model  $team
     * @return boolean
     */
    public static function isInSameTeam($rolePermission, $team)
    {
        if (
            !Config::get('laratrust.use_teams')
            || (!Config::get('laratrust.teams_strict_check') && is_null($team))
        ) {
            return true;
        }

        $teamForeignKey = static::teamForeignKey();
        return $rolePermission->pivot->$teamForeignKey == $team;
    }

    /**
     * Checks if the option exists inside the array,
     * otherwise, it sets the first option inside the default values array.
     *
     * @param  string  $option
     * @param  array  $array
     * @param  array  $possibleValues
     * @return array
     */
    public static function checkOrSet($option, $array, $possibleValues)
    {
        if (!isset($array[$option])) {
            $array[$option] = $possibleValues[0];

            return $array;
        }

        $ignoredOptions = ['team', 'foreignKeyName'];

        if (!in_array($option, $ignoredOptions) && !in_array($array[$option], $possibleValues, true)) {
            throw new InvalidArgumentException();
        }

        return $array;
    }
}
