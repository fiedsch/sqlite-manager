<?php

namespace Fiedsch\SqliteManager\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Assert;
use Fiedsch\SqliteManager\ColumnConfguration;

/**
 * Class ColumnConfigurationTest
 *
 * @package Fiedsch\SqliteManager\Tests
 */
class ColumnConfigurationTest extends TestCase
{

    public function testConstructor()
    {
        $columns = [
            'type' => 'TEXT',
        ];
        $cc = new ColumnConfguration($columns);
        Assert::assertFalse($cc->hasErrors());
    }

    public function testGetConfigurationAugmentation()
    {
        $config = [];
        $cc = new ColumnConfguration($config);

        $expected = [
            'type'      => 'TEXT',
            'mandatory' => false,
            'unique'    => false,
            'default'   => null,
        ];
        Assert::assertEquals($expected, $cc->getConfiguration());
    }

    public function testGetConfigurationCaseMapping()
    {
        $config = ['tYpE' => 'TeXt'];
        $cc = new ColumnConfguration($config);

        $expected = [
            'type'      => 'TEXT',
            'mandatory' => false,
            'unique'    => false,
            'default'   => null,
        ];
        Assert::assertEquals($expected, $cc->getConfiguration());
    }

    public function testTypeSetting()
    {
        $config = ['type' => 'foo']; // there is no type 'foo'
        $cc = new ColumnConfguration($config);
        Assert::assertTrue($cc->hasErrors());
        Assert::assertNotEmpty($cc->getErrors());

        $config = ['type' => 'text']; // 'text' should have been mapped to 'TEXT' which is valid
        $cc = new ColumnConfguration($config);
        Assert::assertFalse($cc->hasErrors());
        Assert::assertEmpty($cc->getErrors());
    }

    /**
     * requiring a unique column and specifying a (non null) default
     * at the same time does not make sense
     */
    public function testCheckConfigUniqueAndDefault()
    {
        $config = [
            'unique'  => true, // this combination
            'default' => 42,   // is not allowed
        ];
        $cc = new ColumnConfguration($config);
        Assert::assertTrue($cc->hasErrors());
        Assert::assertNotEmpty($cc->getErrors());

        $config = [
            'unique'  => true, // this combination
            'default' => null, // is allowed!
        ];
        $cc = new ColumnConfguration($config);
        Assert::assertFalse($cc->hasErrors());
        Assert::assertEmpty($cc->getErrors());
    }

    public function testUniqueIsBoolean()
    {
        $config = [
            'unique' => 'not a boolean',
        ];
        $cc = new ColumnConfguration($config);
        Assert::assertTrue($cc->hasErrors());
        Assert::assertNotEmpty($cc->getErrors());

        $config = [
            'unique' => 'TrUe' // ColumnConfiguration will convert 'true' to true (case is not significant!)
        ];
        $cc = new ColumnConfguration($config);
        Assert::assertFalse($cc->hasErrors());
        Assert::assertEmpty($cc->getErrors());

        $config = [
            'unique' => true // this is what we really expect.
        ];
        $cc = new ColumnConfguration($config);
        Assert::assertFalse($cc->hasErrors());
        Assert::assertEmpty($cc->getErrors());
    }

}
