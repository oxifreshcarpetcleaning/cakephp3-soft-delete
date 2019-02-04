<?php
namespace SoftDelete\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\Datasource\EntityInterface;
use SoftDelete\Error\MissingColumnException;
use SoftDelete\ORM\Query;

trait SoftDeleteNoQueryTrait
{

    /**
     * Get the configured deletion field
     *
     * @return string
     * @throws \SoftDelete\Error\MissingFieldException
     */
    public function getSoftDeleteField()
    {
        if (isset($this->softDeleteField)) {
            $field = $this->softDeleteField;
        } else {
            $field = 'active';
        }

        if ($this->schema()->column($field) === null) {
            throw new MissingColumnException(
                __('Configured field `{0}` is missing from the table `{1}`.',
                    $field,
                    $this->alias()
                )
            );
        }

        return $field;
    }

    /**
     * Perform the delete operation.
     *
     * Will soft delete the entity provided. Will remove rows from any
     * dependent associations, and clear out join tables for BelongsToMany associations.
     *
     * @param \Cake\DataSource\EntityInterface $entity The entity to soft delete.
     * @param \ArrayObject $options The options for the delete.
     * @throws \InvalidArgumentException if there are no primary key values of the
     * passed entity
     * @return bool success
     */
    protected function _processDelete($entity, $options)
    {
        if ($entity->isNew()) {
            return false;
        }

        $primaryKey = (array)$this->primaryKey();
        if (!$entity->has($primaryKey)) {
            $msg = 'Deleting requires all primary key values.';
            throw new \InvalidArgumentException($msg);
        }

        if ($options['checkRules'] && !$this->checkRules($entity, RulesChecker::DELETE, $options)) {
            return false;
        }

        $event = $this->dispatchEvent('Model.beforeDelete', [
            'entity' => $entity,
            'options' => $options
        ]);

        if ($event->isStopped()) {
            return $event->result;
        }

        $this->_associations->cascadeDelete(
            $entity,
            ['_primary' => false] + $options->getArrayCopy()
        );

        // If it's not already marked deleted, delete it now
        $success = true;
        if($entity->{$this->getSoftDeleteField()} != 0) {
            $query = $this->query();
            $conditions = (array)$entity->extract($primaryKey);
            $statement = $query->update()
                ->set([$this->getSoftDeleteField() => 0])
                ->where($conditions)
                ->execute();

            $success = $statement->rowCount() > 0;

            $entity->{$this->getSoftDeleteField()} = 0;
            if (!$success) {
                return $success;
            }
        }

        $this->dispatchEvent('Model.afterDelete', [
            'entity' => $entity,
            'options' => $options
        ]);

        return $success;
    }

    /**
     * Soft deletes all records matching `$conditions`.
     * @return int number of affected rows.
     */
    public function deleteAll($conditions)
    {
        $query = $this->query()
            ->update()
            ->set([$this->getSoftDeleteField() => 0])
            ->where($conditions);
        $statement = $query->execute();
        $statement->closeCursor();
        return $statement->rowCount();
    }

    /**
     * Hard deletes the given $entity.
     * @return bool true in case of success, false otherwise.
     */
    public function hardDelete(EntityInterface $entity)
    {
        if (!$this->delete($entity)) {
            return false;
        }
        $primaryKey = (array)$this->primaryKey();
        $query = $this->query();
        $conditions = (array)$entity->extract($primaryKey);
        $statement = $query->delete()
            ->where($conditions)
            ->execute();

        $success = $statement->rowCount() > 0;
        if (!$success) {
            return $success;
        }

        return $success;
    }


    public function activate(EntityInterface $entity)
    {
        $primaryKey = (array)$this->primaryKey();
        $query = $this->query();
        $conditions = (array)$entity->extract($primaryKey);
        $statement = $query->update()
            ->set([$this->getSoftDeleteField() => 1])
            ->where($conditions)
            ->execute();

        $entity->{$this->getSoftDeleteField()} = 1;

        $success = $statement->rowCount() > 0;
        if (!$success) {
            return $success;
        }

        return $success;
    }

    /**
     * Return inactive entities - these do exist even if they are "not active"
     * @param array|\ArrayAccess $conditions
     *
     * @return bool
     */
    public function exists($conditions)
    {
        return (bool)count(
            $this->find('all', ['withInactive'])
                ->select(['existing' => 1])
                ->where($conditions)
                ->limit(1)
                ->hydrate(false)
                ->toArray()
        );
    }

    /**
     * @return \SoftDelete\ORM\Query
     */
    public function query()
    {
        return new Query($this->getConnection(), $this);
    }
}
