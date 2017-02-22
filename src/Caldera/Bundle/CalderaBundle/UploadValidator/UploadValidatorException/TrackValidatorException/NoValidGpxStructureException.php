<?php

namespace Caldera\Bundle\CalderaBundle\UploadValidator\UploadValidatorException\TrackValidatorException;

class NoValidGpxStructureException extends TrackValidatorException
{
    protected $message = 'Die Gpx-Struktur deiner hochgeladenen Datei ist offenbar defekt.';
}