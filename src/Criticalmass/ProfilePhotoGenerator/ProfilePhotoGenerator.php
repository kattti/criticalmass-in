<?php declare(strict_types=1);

namespace App\Criticalmass\ProfilePhotoGenerator;

use App\Entity\User;
use Imagine\Image\AbstractFont;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Palette\PaletteInterface;
use Imagine\Image\Point;
use Imagine\Image\Point\Center;
use Imagine\Gd\Font;
use Imagine\Gd\Imagine;
use Imagine\Image\Palette;

class ProfilePhotoGenerator implements ProfilePhotoGeneratorInterface
{
    const FONT_FILE = '/assets/fonts/Verdana/Bold.ttf';

    /** @var User $user */
    protected $user;

    /** @var string $projectDirectory */
    protected $projectDirectory;

    /** @var string $uploadDestinationUserPhoto */
    protected $uploadDestinationUserPhoto;

    /** @var PaletteInterface $palette */
    protected $palette;

    public function __construct(string $projectDirectory, string $uploadDestinationUserPhoto)
    {
        $this->projectDirectory = $projectDirectory;
        $this->uploadDestinationUserPhoto = $uploadDestinationUserPhoto;
        $this->palette = new Palette\RGB();
    }

    public function setUser(User $user): ProfilePhotoGeneratorInterface
    {
        $this->user = $user;

        return $this;
    }

    public function generate(): string
    {
        $image = $this->createImage();

        $filename = $this->generateFilePath();

        $this->writeText($image);

        $image->save($filename);

        return $filename;
    }

    protected function createImage(): ImageInterface
    {
        $imagine = new Imagine();

        $box = new Box(1024, 1024);

        return $imagine->create($box, $this->getUserBackgroundColor());
    }

    protected function writeText(ImageInterface $image): void
    {
        $text = $this->getUserInitials();
        $font = $this->getFont($image);

        $textBox = $font->box($text);
        $textCenterPosition = new Center($textBox);
        $imageCenterPosition = new Center($image->getSize());
        $centeredTextPosition = new Point(
            $imageCenterPosition->getX() - $textCenterPosition->getX(),
            $imageCenterPosition->getY() - $textCenterPosition->getY()
        );

        $image->draw()->text($text, $font, $centeredTextPosition);
    }

    protected function getUserInitials(): string
    {
        $parts = explode(' ', $this->user->getUsername());

        if (2 === count($parts)) {
            $initials = sprintf('%s%s', $parts[0][0], $parts[1][0]);
        } else {
            $initials = strtoupper(substr($this->user->getUsername(), 0, 2));
        }

        return $initials;
    }

    protected function getFont(ImageInterface $image): AbstractFont
    {
        $fontColor = $this->palette->color('fff');
        $fontSize = 256;
        $fontFilename = sprintf('%s%s', $this->projectDirectory, self::FONT_FILE);

        $font = new Font($fontFilename, $fontSize, $fontColor);

        return $font;
    }

    protected function getUserBackgroundColor(): ColorInterface
    {
        return $this->palette->color([
            $this->user->getColorRed(),
            $this->user->getColorGreen(),
            $this->user->getColorBlue()
        ]);
    }

    protected function generateFilePath(): string
    {
        $filename = sprintf('%s.png', uniqid());

        $this->user->setImageName($filename);

        return sprintf('%s/%s', $this->uploadDestinationUserPhoto, $filename);
    }
}
