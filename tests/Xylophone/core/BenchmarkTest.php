<?php
/**
 * Xylophone
 *
 * An open source HMVC application development framework for PHP 5.3 or newer
 * Derived from CodeIgniter, Copyright (c) 2008 - 2013, EllisLab, Inc. (http://ellislab.com/)
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Open Software License version 3.0
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is
 * bundled with this package in the files license.txt / license.rst. It is
 * also available through the world wide web at this URL:
 * http://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to obtain it
 * through the world wide web, please send an email to licensing@xylophone.io
 * so we can send you a copy immediately.
 *
 * @package     Xylophone
 * @author      Xylophone Dev Team, EllisLab Dev Team
 * @copyright   Copyright (c) 2014, Xylophone Team (http://xylophone.io/)
 * @license     http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link        http://xylophone.io
 * @since       Version 1.0
 * @filesource
 */

/**
 * Benchmark Unit Test
 *
 * @package Xylophone
 */
class BenchmarkTest extends XyTestCase
{
    /**
     * Test mark()
     *
     * @return  object  Benchmark object
     */
    public function testMark()
    {
        // Instantiate Benchmark - we'll use it for all the tests
        $bench = new Xylophone\core\Benchmark();

        // Ensure marker starts empty and tagged is false
        $this->assertEmpty($bench->marker);
        $this->assertFalse($bench->tagged);

        // Set a benchmark
        $mark = 'code_start';
        $bench->mark($mark);

        // Check for single marker
        $this->assertEquals(1, count($bench->marker));
        $this->assertArrayHasKey($mark, $bench->marker);

        // Return Benchmark to be used later
        return $bench;
    }

    /**
     * Test elapsedTime()
     *
     * @depends testMark
     */
    public function testElapsedTime($bench)
    {
        // Verify no args gets the placeholder and tagged gets set
        $this->assertEquals('{elapsed_time}', $bench->elapsedTime());
        $this->assertTrue($bench->tagged);

        // Verify an undefined marker returns an empty string
        $this->assertEquals('', $bench->elapsedTime('undefined_point'));

        // Set two marks a known time apart
        $mark1 = 'test_start';
        $mark2 = 'test_end';
        $time = 42.1311;
        $decs = 4;
        $bench->marker[$mark1] = microtime(true);
        $bench->marker[$mark2] = $bench->marker[$mark1] + $time;

        // Verify the elapsed time string
        $this->assertEquals((string)$time, $bench->elapsedTime($mark1, $mark2, $decs));
    }

    /**
     * Test memoryUsage()
     *
     * @depends testMark
     */
    public function testMemoryUsage($bench)
    {
        // Clear tagged
        $bench->tagged = false;

        // Verify we get the placeholder and tagged gets set
        $this->assertEquals('{memory_usage}', $bench->memoryUsage());
        $this->assertTrue($bench->tagged);
    }
}

