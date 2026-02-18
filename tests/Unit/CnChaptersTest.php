<?php

namespace Xterr\CnCodes\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Xterr\CnCodes\CnChapter;
use Xterr\CnCodes\CnCodesFactory;
use Xterr\CnCodes\CnVersion;

class CnChaptersTest extends TestCase
{
    public function testIterator(): void
    {
        $factory  = new CnCodesFactory();
        $chapters = $factory->getChapters();

        foreach ($chapters as $cnChapter) {
            static::assertInstanceOf(
                CnChapter::class,
                $cnChapter
            );
        }

        static::assertIsArray($chapters->toArray());
        static::assertGreaterThan(0, count($chapters));
    }

    public function testGetByCodeAndVersion(): void
    {
        $factory   = new CnCodesFactory();
        $cnChapter = $factory->getChapters()->getByCodeAndVersion('01', CnVersion::VERSION_2026);

        static::assertInstanceOf(CnChapter::class, $cnChapter);

        static::assertEquals('01', $cnChapter->getCode());
        static::assertEquals(CnVersion::VERSION_2026, $cnChapter->getVersion());
        static::assertEquals('LIVE ANIMALS', $cnChapter->getName());
        static::assertEquals('I', $cnChapter->getSection());
    }

    public function testGetAllByVersion(): void
    {
        $factory = new CnCodesFactory();

        static::assertCount(
            97,
            $factory->getChapters()->getAllByVersion(CnVersion::VERSION_2026)
        );
    }

    public function testCount(): void
    {
        $factory = new CnCodesFactory();
        static::assertEquals(388, $factory->getChapters()->count());
    }
}
