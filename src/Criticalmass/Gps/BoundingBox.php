<?php
/**
 * Created by PhpStorm.
 * User: maltehuebner
 * Date: 02.08.16
 * Time: 23:01
 */

namespace App\Criticalmass\Gps;

/**
 * @deprecated
 */
class BoundingBox
{
    /** @var Coord $northWest */
    protected $northWest;

    /** @var Coord $southEast */
    protected $southEast;

    public function __construct(Coord $northWest, Coord $southEast)
    {
        $this->northWest = $northWest;
        $this->southEast = $southEast;
    }

    public function getNorthWest()
    {
        return $this->northWest;
    }

    public function getSouthEast()
    {
        return $this->southEast;
    }
}
