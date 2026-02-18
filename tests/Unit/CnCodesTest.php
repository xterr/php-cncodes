<?php

namespace Xterr\CnCodes\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Xterr\CnCodes\CnCode;
use Xterr\CnCodes\CnCodesFactory;
use Xterr\CnCodes\CnVersion;

class CnCodesTest extends TestCase
{
    public function testIterator(): void
    {
        $factory = new CnCodesFactory();
        $codes = $factory->getCodes();

        foreach ($codes as $cnCode) {
            static::assertInstanceOf(
                CnCode::class,
                $cnCode
            );
        }

        static::assertIsArray($codes->toArray());
        static::assertGreaterThan(0, count($codes));
    }

    public function testGetByCodeAndVersion(): void
    {
        $factory = new CnCodesFactory();
        $cnCode = $factory->getCodes()->getByCodeAndVersion('01012100', CnVersion::VERSION_2026);

        static::assertInstanceOf(CnCode::class, $cnCode);

        static::assertEquals('I', $cnCode->getSection());
        static::assertEquals('01', $cnCode->getChapter());
        static::assertEquals('0101', $cnCode->getHeading());
        static::assertEquals('01012100', $cnCode->getCode());
        static::assertEquals('0101 21 00', $cnCode->getRawCode());
        static::assertEquals(CnVersion::VERSION_2026, $cnCode->getVersion());
        static::assertEquals('Pure-bred breeding animals', $cnCode->getName());
        static::assertEquals('PST', $cnCode->getSupplementaryUnit());
    }

    public function testGetByNormalizedCodeAndVersion(): void
    {
        $factory = new CnCodesFactory();
        $cnCode = $factory->getCodes()->getByRawCodeAndVersion('0101 21 00', CnVersion::VERSION_2026);

        static::assertInstanceOf(CnCode::class, $cnCode);

        static::assertEquals('I', $cnCode->getSection());
        static::assertEquals('01', $cnCode->getChapter());
        static::assertEquals('0101', $cnCode->getHeading());
        static::assertEquals('01012100', $cnCode->getCode());
        static::assertEquals('0101 21 00', $cnCode->getRawCode());
        static::assertEquals(CnVersion::VERSION_2026, $cnCode->getVersion());
        static::assertEquals('Pure-bred breeding animals', $cnCode->getName());
    }

    public function testGetAllByVersion(): void
    {
        $factory = new CnCodesFactory();

        static::assertCount(
            9791,
            $factory->getCodes()->getAllByVersion(CnVersion::VERSION_2026)
        );
    }

    public function testCount(): void
    {
        $factory = new CnCodesFactory();
        static::assertEquals(39087, $factory->getCodes()->count());
    }
}
