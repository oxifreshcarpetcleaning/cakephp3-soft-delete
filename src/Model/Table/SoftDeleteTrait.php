<?php

namespace SoftDelete\Model\Table;

use SoftDelete\ORM\Query;

trait SoftDeleteTrait
{

    use SoftDeleteNoQueryTrait;

    /**
     * @return \SoftDelete\ORM\Query
     */
    public function query()
    {
        return new Query($this->getConnection(), $this);
    }
}
