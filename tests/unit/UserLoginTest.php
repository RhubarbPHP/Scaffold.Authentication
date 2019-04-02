<?php

namespace Rhubarb\Scaffolds\Authentication\Tests;

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Scaffolds\Authentication\UserLog;

class UserLoginTest extends RhubarbTestCase
{
    public function testGetUserIpAddressReturnsEmptyStringWhenNoIPAddress() {
        $_SERVER = [];
        self::assertEquals('', UserLog::getUserIpAddress());
    }

    public function testGetUserIpAddressHandlesIPAdsressFromAmazonELB() {
        $_SERVER = ['HTTP_X_FORWARDED_FOR' => '123.123.123.123'];
        self::assertEquals('123.123.123.123', UserLog::getUserIpAddress());
    }

    public function testGetUserIpAddressHandlesIPAdsressFromRemoteAddr() {
        $_SERVER = ['REMOTE_ADDR' => '123.123.123.123'];
        self::assertEquals('123.123.123.123', UserLog::getUserIpAddress());
    }

    public function testGetUserIpAddressPrefersAmazonELBAddressOverRemoteAddr() {
        $_SERVER = ['REMOTE_ADDR' => '111.111.111.111', 'HTTP_X_FORWARDED_FOR' => '222.222.222.222'];
        self::assertEquals('222.222.222.222', UserLog::getUserIpAddress());
    }

    public function testBeforeSaveStoresIPAddress() {
        $_SERVER = ['REMOTE_ADDR' => '123.123.123.123'];

        $userLog = new UserLog();
        $userLog->UserID = uniqid();
        $userLog->EnteredUsername = uniqid();
        $userLog->LogType = UserLog::USER_LOG_LOGIN_SUCCESSFUL;
        $userLog->save();

        self::assertEquals($userLog->IPAddress, '123.123.123.123');
    }

    public function testBeforeSaveStoresDateCreatedNow() {
        $userLog = new UserLog();
        $userLog->UserID = uniqid();
        $userLog->EnteredUsername = uniqid();
        $userLog->LogType = UserLog::USER_LOG_LOGIN_SUCCESSFUL;
        $userLog->save();

        self::assertInstanceOf(RhubarbDateTime::class, $userLog->DateCreated);
        self::assertGreaterThan(new \DateTime('now -1 second'), $userLog->DateCreated);
    }
}