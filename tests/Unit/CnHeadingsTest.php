<?php

namespace Xterr\CnCodes\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Xterr\CnCodes\CnCodesFactory;
use Xterr\CnCodes\CnHeading;
use Xterr\CnCodes\CnVersion;

class CnHeadingsTest extends TestCase
{
    public function testIterator(): void
    {
        $factory = new CnCodesFactory();
        $headings = $factory->getHeadings();

        foreach ($headings as $cnHeading) {
            static::assertInstanceOf(
                CnHeading::class,
                $cnHeading
            );
        }

        static::assertIsArray($headings->toArray());
        static::assertGreaterThan(0, count($headings));
    }

    public function testGetByCodeAndVersion(): void
    {
        $factory = new CnCodesFactory();
        $cnHeading = $factory->getHeadings()->getByCodeAndVersion('0101', CnVersion::VERSION_2026);

        static::assertInstanceOf(CnHeading::class, $cnHeading);

        static::assertEquals('0101', $cnHeading->getCode());
        static::assertEquals('0101', $cnHeading->getRawCode());
        static::assertEquals(CnVersion::VERSION_2026, $cnHeading->getVersion());
        static::assertEquals(
            'Live horses, asses, mules and hinnies',
            $cnHeading->getName()
        );
        static::assertEquals('01', $cnHeading->getChapter());
    }

    public function testGetAllByVersion(): void
    {
        $factory = new CnCodesFactory();

        static::assertCount(
            957,
            $factory->getHeadings()->getAllByVersion(CnVersion::VERSION_2026)
        );
    }

    public function testCount(): void
    {
        $factory = new CnCodesFactory();
        static::assertEquals(7656, $factory->getHeadings()->count());
    }
}
