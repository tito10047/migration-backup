<?php

namespace Tito10047\MigrationBackup\Tests\Unit\Resolver;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Tito10047\MigrationBackup\Dto\ConnectionParams;
use Tito10047\MigrationBackup\Resolver\ConnectionResolver;

class ConnectionResolverTest extends TestCase {
	public function testResolveSuccessful(): void {
		$registry   = $this->createMock(ManagerRegistry::class);
		$connection = $this->createMock(Connection::class);

		$params = [
			'host'     => 'localhost',
			'port'     => '3306',
			'dbname'   => 'test_db',
			'user'     => 'test_user',
			'password' => 'test_pass',
			'driver'   => 'pdo_mysql',
		];

		$registry->expects($this->once())
			->method('getConnection')
			->with('default')
			->willReturn($connection);

		$connection->expects($this->once())
			->method('getParams')
			->willReturn($params);

		$resolver = new ConnectionResolver($registry);
		$result   = $resolver->resolve('default');

		$this->assertInstanceOf(ConnectionParams::class, $result);
		$this->assertEquals('localhost', $result->host);
		$this->assertEquals('3306', $result->port);
		$this->assertEquals('test_db', $result->database);
		$this->assertEquals('test_user', $result->user);
		$this->assertEquals('test_pass', $result->password);
		$this->assertEquals('pdo_mysql', $result->driver);
	}
}
