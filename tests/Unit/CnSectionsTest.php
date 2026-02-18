<?php

namespace Xterr\CnCodes\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Xterr\CnCodes\CnCodesFactory;
use Xterr\CnCodes\CnSection;
use Xterr\CnCodes\CnVersion;

class CnSectionsTest extends TestCase
{
    public function testIterator(): void
    {
        $factory = new CnCodesFactory();
        $sections = $factory->getSections();

        foreach ($sections as $cnSection) {
            static::assertInstanceOf(
                CnSection::class,
                $cnSection
            );
        }

        static::assertIsArray($sections->toArray());
        static::assertGreaterThan(0, count($sections));
    }

    public function testGetByCodeAndVersion(): void
    {
        $factory = new CnCodesFactory();
        $cnSection = $factory->getSections()->getByCodeAndVersion('I', CnVersion::VERSION_2026);

        static::assertInstanceOf(CnSection::class, $cnSection);

        static::assertEquals('I', $cnSection->getCode());
        static::assertEquals(CnVersion::VERSION_2026, $cnSection->getVersion());
        static::assertEquals('LIVE ANIMALS; ANIMAL PRODUCTS', $cnSection->getName());
    }

    public function testGetAllByVersion(): void
    {
        $factory = new CnCodesFactory();

        static::assertCount(
            21,
            $factory->getSections()->getAllByVersion(CnVersion::VERSION_2026)
        );
    }

    public function testCount(): void
    {
        $factory = new CnCodesFactory();
        static::assertEquals(84, $factory->getSections()->count());
    }
}
