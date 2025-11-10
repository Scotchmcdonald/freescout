<?php

namespace Doctrine\DBAL\Driver;

use const PHP_VERSION_ID;

use PDOStatement;

use function func_get_args;

if (PHP_VERSION_ID >= 80000) {
    /**
     * @internal
     */
    trait PDOQueryImplementation
    {
        /**
         * @return PDOStatement
         *                      : PDOStatement|false
         */
        #[\ReturnTypeWillChange]
        public function query(?string $query = null, ?int $fetchMode = null, mixed ...$fetchModeArgs)
        {
            return $this->doQuery($query, $fetchMode, ...$fetchModeArgs);
        }
    }
} else {
    /**
     * @internal
     */
    trait PDOQueryImplementation
    {
        /**
         * @return PDOStatement
         */
        public function query()
        {
            return $this->doQuery(...func_get_args());
        }
    }
}
