<?php namespace App\Lib;

class DBConnect {
	private static $db;

	public static function getDB() {
		if(!self::$db)
			self::$db = self::createDB();
		
		return self::$db;
	}

	public static function createDB() {
		$conn = new \PDO('mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_NAME'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
		$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		return $conn;
	}

	public static function createInsertStmt(string $tableName, array $columns) {
		$placeholders = array_map(function($col) { return ':' . $col; }, $columns);

		return sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $tableName,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
	}
}