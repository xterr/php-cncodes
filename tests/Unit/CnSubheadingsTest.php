<?php

namespace Xterr\CnCodes\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Xterr\CnCodes\CnCodesFactory;
use Xterr\CnCodes\CnSubheading;
use Xterr\CnCodes\CnVersion;

class CnSubheadingsTest extends TestCase
{
    public function testIterator(): void
    {
        $factory     = new CnCodesFactory();
        $subheadings = $factory->getSubheadings();

        foreach ($subheadings as $cnSubheading) {
            static::assertInstanceOf(
                CnSubheading::class,
                $cnSubheading
            );
        }

        static::assertIsArray($subheadings->toArray());
        static::assertGreaterThan(0, count($subheadings));
    }

    public function testGetByCodeAndVersion(): void
    {
        $factory      = new CnCodesFactory();
        $cnSubheading = $factory->getSubheadings()->getByCodeAndVersion('010221', CnVersion::VERSION_2026);

        static::assertInstanceOf(CnSubheading::class, $cnSubheading);

        static::assertEquals('010221', $cnSubheading->getCode());
        static::assertEquals('0102 21', $cnSubheading->getRawCode());
        static::assertEquals(CnVersion::VERSION_2026, $cnSubheading->getVersion());
        static::assertEquals('Pure-bred breeding animals', $cnSubheading->getName());
        static::assertEquals('0102', $cnSubheading->getHeading());
        static::assertEquals('01', $cnSubheading->getChapter());
        static::assertEquals('I', $cnSubheading->getSection());
    }

    public function testGetAllByVersion(): void
    {
        $factory = new CnCodesFactory();

        static::assertCount(
            1835,
            $factory->getSubheadings()->getAllByVersion(CnVersion::VERSION_2026)
        );
    }

    public function testCount(): void
    {
        $factory = new CnCodesFactory();
        static::assertEquals(7314, $factory->getSubheadings()->count());
    }
}
